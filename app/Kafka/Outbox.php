<?php

namespace App\Kafka;

use App\Models\OutboxMessage;
use Throwable;

/**
 * Transactional outbox: producers write events to the `outbox_messages` table
 * inside the same DB transaction as the business change, instead of publishing
 * to Kafka directly. The `outbox:dispatch` command ships pending rows to Kafka.
 *
 * This closes the "ghost event" window — if Kafka is down when the transaction
 * commits, the message is still durably stored and delivered on a later dispatch.
 */
class Outbox
{
    private const MAX_ATTEMPTS = 5;

    private const MAX_BACKOFF_MINUTES = 30;

    public function __construct(
        private readonly KafkaEventPublisher $publisher,
    ) {}

    /**
     * Record an event into the outbox (call inside the business transaction).
     */
    public function record(string $topic, string $eventType, array $data, ?string $key = null): void
    {
        $envelope = EventEnvelope::make($eventType, $data, $key);

        OutboxMessage::create([
            'event_id' => $envelope->eventId,
            'topic' => $topic,
            'payload' => $envelope->toArray(),
            'status' => OutboxMessage::STATUS_PENDING,
            'attempts' => 0,
        ]);
    }

    /**
     * Deliver due outbox messages to Kafka.
     *
     * Returns the number of messages attempted. Each row is claim-guarded
     * (compare-and-set on status) so concurrent dispatchers never double-send.
     * A message is retried with exponential backoff up to MAX_ATTEMPTS, after
     * which it is left permanently `failed` for manual review.
     */
    public function dispatch(int $limit = 500): int
    {
        $batch = OutboxMessage::query()
            ->where('status', OutboxMessage::STATUS_PENDING)
            ->where(fn ($q) => $q->whereNull('available_at')->orWhere('available_at', '<=', now()))
            ->orderBy('id')
            ->limit($limit)
            ->get();

        $processed = 0;

        foreach ($batch as $message) {
            $claimed = OutboxMessage::where('id', $message->id)
                ->where('status', OutboxMessage::STATUS_PENDING)
                ->update(['status' => 'processing']);

            if ($claimed === 0) {
                continue;
            }

            $processed++;

            try {
                $envelope = EventEnvelope::fromArray($message->payload);

                $this->publisher->publish($message->topic, $envelope->eventType, $envelope->data, $envelope->key);

                OutboxMessage::where('id', $message->id)->update([
                    'status' => OutboxMessage::STATUS_SENT,
                    'sent_at' => now(),
                ]);
            } catch (Throwable $e) {
                $attempts = $message->attempts + 1;

                if ($attempts >= self::MAX_ATTEMPTS) {
                    OutboxMessage::where('id', $message->id)->update([
                        'status' => OutboxMessage::STATUS_FAILED,
                        'attempts' => $attempts,
                    ]);
                } else {
                    OutboxMessage::where('id', $message->id)->update([
                        'status' => OutboxMessage::STATUS_PENDING,
                        'attempts' => $attempts,
                        'available_at' => now()->addMinutes($this->backoff($attempts)),
                    ]);
                }
            }
        }

        return $processed;
    }

    private function backoff(int $attempts): int
    {
        return min(self::MAX_BACKOFF_MINUTES, 2 ** ($attempts - 1));
    }
}
