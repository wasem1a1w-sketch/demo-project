# Kafka Event Streaming — How It Works

This document explains how the Kafka event streaming layer works in this project: every
file involved, what it does, and how an event flows from a business action to its side
effects (notifications, activity rows, payment processing).

---

## 1. Big picture

The app moved three synchronous side-effects onto Kafka:

1. **Activity logging** (`user_activity_logs` rows)
2. **Order lifecycle notifications + analytics** (emails, admin alerts, broadcasts, audit rows)
3. **Payment webhook processing** (marking orders paid/failed)

Instead of the request thread doing the side-effect work (or publishing straight to
Kafka), the request writes an **outbox row in the same DB transaction** as the business
change. A scheduled dispatcher ships those rows to Kafka, and long-running **consumer
workers** perform the side-effects.

```
 WRITE SIDE (request/CLI)                       READ SIDE (long-running workers)
 ─────────────────────────                      ─────────────────────────────────

 Business action (placeOrder, status change,
 activity log, webhook)
      │
      ▼
 ┌──────────────────┐   same DB txn   ┌───────────────────────┐
 │ MySQL business   │ ◄──────────────►│ outbox_messages table │  status = pending
 │ change (order,   │                 └───────────┬───────────┘
 │ payment, etc.)   │                             │  outbox:dispatch (every minute)
 └──────────────────┘                             ▼
                                        KafkaEventPublisher (rdkafka)
                                             │  withKafkaKey = partition key
                                             ▼
                              ┌──────────────────────────────┐
                              │ Kafka topics                 │
                              │ user-activity-logs           │
                              │ order-events                 │
                              │ payment-events               │
                              │ payment-events-retry         │
                              │ payment-events-dlq           │
                              └──────────────┬───────────────┘
                                             │  separate consumer groups
                     ┌───────────────────────┼───────────────────────┐
                     ▼                       ▼                       ▼
              ActivityLogHandler     OrderNotifications     OrderAnalytics
                                     Handler                Handler
                     │                       │                       │
                     ▼                       ▼                       ▼
             user_activity_logs     emails, admin      user_activity_logs
             (persist)              alerts, broadcasts (audit rows)
                                             ┌───────────────────────┘
                                             ▼
                                     PaymentEventProcessor (payments worker)
                                         idempotency + retry → DLQ
```

Everything on the read side runs through one of the `kafka:consume` workers. See
[§8 Workers](#8-consumer-workers--delivery-guarantees).

---

## 2. The transactional outbox (the core pattern)

**Problem it solves:** if a request saves the order to MySQL and then publishes to
Kafka, and Kafka is briefly down, you get a *ghost order* — the DB says "success", but
the email/notification consumer never fires and there is no record of the missed event.

**Solution:** never publish from the request. Instead write the event into the
`outbox_messages` table **inside the same DB transaction** as the business change.

- If the DB transaction rolls back, the outbox row rolls back too → no ghost events.
- If the DB commits but Kafka is down, the row sits safely in MySQL as `pending` →
  no lost events, and you have a full paper trail to replay.
- A dispatcher (`php artisan outbox:dispatch`, scheduled every minute) ships `pending`
  rows to Kafka.

### The `outbox_messages` table

Migration: `database/migrations/2026_08_10_000002_create_outbox_messages_table.php`

| Column | Purpose |
|---|---|
| `id` | Auto-increment, dispatcher sends in this order (preserves per-key ordering) |
| `event_id` | UUID, unique — the idempotency key of the envelope |
| `topic` | Target Kafka topic |
| `payload` | The full `EventEnvelope` JSON |
| `status` | `pending` → `sent`, or stays `pending` with backoff → `failed` after 5 attempts |
| `attempts` | Number of send attempts so far |
| `available_at` | Backoff gate — the row is not picked before this timestamp |
| `sent_at` | When it was successfully delivered |
| `created_at` / `updated_at` | Timestamps |

### Dispatch lifecycle

```
        record()                      dispatch()                          dispatch()
  ┌───────────────┐             ┌────────────────────┐             ┌─────────────────────┐
  │ pending       │  next run   │ claim (CAS)        │  publish OK │ sent (sent_at = now) │
  │ attempts = 0  │ ──────────► │ status='processing'│ ──────────► │ attempts unchanged   │
  └───────────────┘             │ publish(...)       │             └─────────────────────┘
                                └─────────┬──────────┘
                                          │ publish throws
                                          ▼
                              attempts++
                              attempts >= 5 ?  status = 'failed'  (terminal, manual review)
                                            :  status = 'pending', available_at = now + backoff
```

- **Claim guard:** the dispatcher transitions `pending → processing` with a
  compare-and-set update (`WHERE status = 'pending'`). If another worker claimed the row
  first, the affected-row count is `0` and it is skipped — **concurrent dispatchers
  never double-send**.
- **Backoff:** after a failed send, `available_at = now + min(30, 2^(attempts-1))`
  minutes (1, 2, 4, 8, 16 … capped at 30).
- **Terminal state:** after 5 failed attempts the row is left permanently `failed` for
  manual review — mirroring the payment DLQ concept.

> Transient `processing` is only ever set in-memory during a single claim; it is never a
> persistent state in the row.

---

## 3. Core components (files)

### `app/Kafka/EventEnvelope.php`

Every event has the same shape:

| Key | Meaning |
|---|---|
| `event_type` | Domain name, e.g. `order.placed` |
| `event_id` | UUID — unique per event, used for idempotency |
| `occurred_at` | ISO-8601 timestamp of when the event happened |
| `key` | Partition key (user id, order id, session id) |
| `data` | The domain payload |

```json
{
  "event_type": "order.placed",
  "event_id": "6f0b9c41-8f7a-4a0a-b8de-0e2f7c1a3b2d",
  "occurred_at": "2026-08-10T10:00:00+00:00",
  "key": "42",
  "data": { "order_id": 42, "order_number": "ORD-ABC12345-6789", "total": 109.99 }
}
```

`EventEnvelope::make()` builds one with fresh UUID + timestamp; `fromArray()` rebuilds
one from a consumed message body.

### `app/Kafka/KafkaTopics.php`

Topic-name constants (the only place topic names are defined):

| Constant | Value |
|---|---|
| `ACTIVITY_LOGS` | `user-activity-logs` |
| `ORDER_EVENTS` | `order-events` |
| `PAYMENT_EVENTS` | `payment-events` |
| `PAYMENT_EVENTS_RETRY` | `payment-events-retry` |
| `PAYMENT_EVENTS_DLQ` | `payment-events-dlq` |

### `app/Kafka/KafkaEventPublisher.php`

The only class that talks to the broker. Used by the outbox dispatcher and by the
payment consumer's retry/DLQ republishes. It sends the envelope with
`withKafkaKey($key)` so messages sharing a key land on the same partition (ordering).

### `app/Kafka/Outbox.php`

- `record($topic, $eventType, $data, $key)` → builds an envelope and inserts a
  `pending` outbox row. **Call this inside the business transaction.**
- `dispatch($limit = 500)` → ships due rows (see [§2](#2-the-transactional-outbox-the-core-pattern)),
  returns the number attempted.

---

## 4. Producers (the write side)

All producers record into the outbox — none publish to Kafka directly from the request.

### `UserActivityLog::record()` → `activity_log.recorded`

`app/Models/UserActivityLog.php`

The 49 call sites across the app stay unchanged — they still call
`UserActivityLog::record($userId, $type, $description, ...)`. It now writes an outbox
row (topic `user-activity-logs`, key = `user_id`) instead of inserting a row or
publishing.

The **consumer** (`ActivityLogHandler`) later calls `UserActivityLog::persist($payload)`,
which writes the actual `user_activity_logs` row.

> **Limitation:** most call sites are *not* inside a DB transaction, so the outbox row
> is durable/retryable but not atomic with the caller's change — the same guarantee the
> code had before Kafka.

### `OrderService::placeOrder()` → `order.placed`

`app/Services/OrderService.php`

The event is recorded **inside** the `DB::transaction` closure, right before the
transaction returns the new order. Payload includes order totals and any
`low_stock` products detected while decrementing stock. Key = `order.id`.

This closes the original "ghost order" window: if the order insert fails, the outbox row
rolls back with it; if the transaction commits but Kafka is down, `outbox:dispatch`
delivers the `order.placed` event on a later run.

### `Order::transitionStatus()` / `cancel()` → `order.status_changed`

`app/Models/Order.php`

The status save **and** the outbox record are wrapped in a single `DB::transaction`
(which also covers the stock restore when cancelling). Payload carries
`order_id`, `order_number`, `old_status`, `new_status`, and `actor_user_id` (who did it).
Key = `order.id`.

Every path that changes order status flows through here — admin panel, payment failure,
PayPal cancel — so all of them get the same event.

### `PaymentController@handleWebhook()` → `payment.confirmed` / `payment.failed`

`app/Http/Controllers/PaymentController.php`

After verifying the Stripe signature, the controller records `payment.confirmed`
(`session_id`) or `payment.failed` (`provider_reference`) into the outbox and returns
`200` immediately. The heavy lifting happens later in `PaymentEventProcessor`. Because
the record is durable, a webhook is never lost even if Kafka is down when Stripe delivers it.

---

## 5. Consumers (the read side)

### `ActivityLogHandler`

`app/Kafka/Consumers/ActivityLogHandler.php` — group `activity-log-writer` on
`user-activity-logs`. Rebuilds the envelope and calls `UserActivityLog::persist()`
to write the row. No manual commit (the request-handler model commits for it); the
others commit explicitly.

### `OrderNotificationsHandler`

`app/Kafka/Consumers/OrderNotificationsHandler.php` — group `order-notifications` on
`order-events`. For each order event it:

- **`order.placed`**: writes an `AdminNotification::notify('new_order', ...)`, emails the
  customer, and creates a `low_stock` admin notification per low-stock product.
- **`order.status_changed`**: writes an admin notification and emails/broadcasts the
  customer (`OrderStatusChanged` + `ClientNotificationBroadcast`).

It commits the message explicitly after handling it.

### `OrderAnalyticsHandler`

`app/Kafka/Consumers/OrderAnalyticsHandler.php` — group `order-analytics` on
`order-events`. Writes audit rows to `user_activity_logs`:
`order_placed` (user_id from payload) and `order_status_changed` (actor_user_id), using
the envelope's `occurred_at` as the log timestamp. Commits explicitly.

> `order-events` is consumed by **two independent consumer groups** (notifications +
> analytics). Each group tracks its own offset, so both get every message — fan-out via
> consumer groups.

### `PaymentEventProcessor`

`app/Kafka/Consumers/PaymentEventProcessor.php` — group `payment-processor` on
`payment-events` + `payment-events-retry`. This is the most safety-critical consumer:

1. **Claim / idempotency:** before processing it inserts the envelope's `event_id` into
   `processed_payment_events` (unique index). A duplicate `event_id` (redelivery) fails
   the insert and the message is committed + skipped — the same event can never be
   applied twice.
2. **Process:** dispatches to `PaymentService`:
   - `payment.confirmed` → `handleCheckoutComplete($sessionId)` → finds the payment,
     marks it `paid`, sets the order `payment_status = paid`, emails the customer.
   - `payment.failed` → `handlePaymentFailed($reference)` → marks payment + order failed
     and cancels the order (which restocks via `transitionStatus`).
3. **Retry / DLQ:** on any `Throwable` it deletes the idempotency claim (so a later
   attempt can run), increments `attempts` in the event data, and:
   - `attempts < 3` → republish to `payment-events-retry`;
   - otherwise → publish to `payment-events-dlq` for manual inspection.
4. Commits the consumed message either way (at-least-once).

---

## 6. Message flow — one full trace

Follow one order from placement to paid:

```
placeOrder()
  ├─ DB txn: insert order + items, decrement stock
  └─ Outbox::record(order-events, "order.placed", key=order.id)   [pending]

outbox:dispatch (cron)
  └─ KafkaEventPublisher → order-events partition(order.id)

OrderNotificationsHandler      OrderAnalyticsHandler
  ├─ AdminNotification new_order  ├─ user_activity_logs order_placed
  ├─ customer email
  └─ low_stock notifications

Stripe webhook: checkout.session.completed
  └─ PaymentController::handleWebhook
      └─ Outbox::record(payment-events, "payment.confirmed", key=session_id)

outbox:dispatch
  └─ KafkaEventPublisher → payment-events

PaymentEventProcessor
  ├─ claim event_id in processed_payment_events
  ├─ PaymentService::handleCheckoutComplete → payment + order = paid, email
  └─ commit message
```

---

## 7. Infrastructure

### Docker Compose

`docker-compose.yml` defines:

- **`kafka`** — `apache/kafka:3.9.0` in KRaft mode (single node, no ZooKeeper).
  Two listeners: internal `PLAINTEXT://kafka:29092` (used between containers) and
  `PLAINTEXT_HOST://localhost:9092` (used from the host). Auto-creates topics.
- **`kafka-ui`** — web UI on http://localhost:8082 to browse topics, messages, and
  consumer groups.

```bash
sudo docker compose up -d kafka kafka-ui
```

### Configuration

`config/kafka.php` is the published `mateusjunges/laravel-kafka` config. Relevant env vars:

| Variable | Description | Default |
|---|---|---|
| `KAFKA_BROKERS` | Broker address(es) | `localhost:9092` |
| `KAFKA_CONSUMER_GROUP_ID` | Default group id | `app-default` |
| `KAFKA_OFFSET_RESET` | Where new groups start reading | `earliest` |
| `KAFKA_AUTO_COMMIT` | Auto-commit offsets (we use manual) | `false` |

### The `rdkafka` extension

The `mateusjunges/laravel-kafka` package requires `ext-rdkafka`. On this machine it is
built from source into `~/.local`, so plain `php` won't see it. Use the **`bin/php`**
wrapper (sets `LD_LIBRARY_PATH` and `PHP_INI_SCAN_DIR`) for every artisan/composer/phpunit
invocation:

```bash
bin/php artisan ...        bin/php $(which composer) ...      bin/php vendor/bin/phpunit ...
```

---

## 8. Consumer workers & delivery guarantees

### Starting workers

`php artisan kafka:consume <worker>` — worker registry in
`app/Console/Commands/KafkaConsumeCommand.php`:

| Worker | Topics | Consumer group | Handler |
|---|---|---|---|
| `activity-logs` | `user-activity-logs` | `activity-log-writer` | `ActivityLogHandler` |
| `order-notifications` | `order-events` | `order-notifications` | `OrderNotificationsHandler` |
| `order-analytics` | `order-events` | `order-analytics` | `OrderAnalyticsHandler` |
| `payments` | `payment-events`, `payment-events-retry` | `payment-processor` | `PaymentEventProcessor` |

```bash
# one process per worker (production: Supervisor/process manager)
bin/php artisan kafka:consume activity-logs
bin/php artisan kafka:consume order-notifications
bin/php artisan kafka:consume order-analytics
bin/php artisan kafka:consume payments
```

`kafka:restart-consumers` (ships with the package) broadcasts a restart signal to all
consumer processes.

### Delivery guarantees

- **At-least-once:** consumers commit offsets **manually** (`withManualCommit()` +
  `$consumer->commit($message)`), only after the handler succeeds. A crash mid-message
  redelivers the message.
- **Exactly-once effect** where it matters: `PaymentEventProcessor` is idempotent via
  `processed_payment_events`. (Notification/activity consumers may re-send on
  redelivery — acceptable for this app, same as before.)
- **Ordering:** the message key routes a key's events to one partition, and the outbox
  dispatcher sends rows in `id` order — so `order.placed` always precedes
  `order.status_changed` for the same order.

---

## 9. Operations runbook

```bash
# 1. Infrastructure
sudo docker compose up -d kafka kafka-ui          # Kafka UI: http://localhost:8082

# 2. Consumers (four terminals / supervisor)
bin/php artisan kafka:consume activity-logs
bin/php artisan kafka:consume order-notifications
bin/php artisan kafka:consume order-analytics
bin/php artisan kafka:consume payments

# 3. Dispatcher (scheduled every minute automatically; run manually to catch up)
bin/php artisan outbox:dispatch
bin/php artisan outbox:dispatch --limit=1000

# 4. Smoke test: publish a demo message to a topic
bin/php artisan kafka:produce --topic=user-activity-logs
```

**Watching the outbox** (SQL) — health of the write side:

```sql
SELECT status, COUNT(*) FROM outbox_messages GROUP BY status;
SELECT * FROM outbox_messages WHERE status = 'failed';      -- needs manual review
SELECT * FROM outbox_messages WHERE status = 'pending' ORDER BY id LIMIT 20;
```

**DLQ / failed review:** `payment-events-dlq` messages and permanently-`failed` outbox
rows both indicate something that must be handled or replayed manually.

---

## 10. Failure scenarios

### Kafka is down while a customer places an order

The order + outbox row commit to MySQL together. The row sits `pending`. The web request
succeeds (the outbox write never touched Kafka). When Kafka returns, `outbox:dispatch`
delivers the event. **No ghost order, no lost notification.**

### A consumer crashes after doing the work but before committing

The message is redelivered and the handler runs again. For payments, the idempotency
claim skips the second run. For notifications, the user may get a duplicate — accepted
and documented behavior.

### Payment processing keeps failing (e.g. provider outage)

`PaymentEventProcessor` releases its claim and republishes to `payment-events-retry`
with `attempts` incremented. After 3 attempts the event lands on `payment-events-dlq`
and the idempotency claim is gone, so the event can be replayed from the DLQ once the
outage is resolved.

### The outbox send keeps failing (e.g. broker unreachable for a long time)

The row backs off exponentially and after 5 attempts becomes `failed` permanently —
visible in the outbox status query above for manual replay.

---

## 11. Testing strategy

Feature tests never touch a real broker:

- `Kafka::fake()` swaps the producer facade, so publishes are captured in memory.
- Producers write outbox rows, so tests call **`dispatchOutbox()`** first to deliver
  pending rows into the faked broker.
- The `Tests\Support\InteractsWithKafka` trait then routes captured messages through the
  **real** handlers via `drainActivityLogs()`, `drainOrderEvents()`,
  `drainPaymentEvents()` (each drain calls `dispatchOutbox()` for you).
- `TestKafkaConsumer` is a fake `MessageConsumer` so handlers can call `commit()`.

Test files in `tests/Feature/Kafka/`:

| File | Covers |
|---|---|
| `OutboxTest.php` | Atomic outbox row on placeOrder, no publish before dispatch, backoff→eventual sent, permanent-failure cap, no double-send |
| `ActivityLogKafkaTest.php` | Envelope shape, partition key, consumer persists the row |
| `OrderEventsKafkaTest.php` | `order.placed`/`order.status_changed` payloads, analytics rows, notifications + low-stock |
| `PaymentEventsKafkaTest.php` | Webhook → publish + processor marks paid, idempotency, retry→DLQ, failed webhook cancels order |

See [`kafka-migration-summary.json`](kafka-migration-summary.json) for a change-by-change
record of the migration.
