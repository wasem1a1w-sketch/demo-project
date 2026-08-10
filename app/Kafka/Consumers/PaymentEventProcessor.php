<?php

namespace App\Kafka\Consumers;

use App\Kafka\EventEnvelope;
use App\Kafka\KafkaEventPublisher;
use App\Kafka\KafkaTopics;
use App\Models\ProcessedPaymentEvent;
use App\Services\PaymentService;
use Illuminate\Database\QueryException;
use Junges\Kafka\Contracts\ConsumerMessage;
use Junges\Kafka\Contracts\MessageConsumer;
use Throwable;

/**
 * Consumer group `payment-processor` on topics `payment-events` + `payment-events-retry`.
 *
 * Processes payment events with:
 *  - idempotency (dedupe by envelope event_id)
 *  - retry on transient failure via the `-retry` topic
 *  - dead letter queue (`-dlq`) after the max attempt count is reached
 */
class PaymentEventProcessor
{
    private const MAX_ATTEMPTS = 3;

    public function __construct(
        private readonly PaymentService $paymentService,
        private readonly KafkaEventPublisher $publisher,
    ) {}

    public function __invoke(ConsumerMessage $message, MessageConsumer $consumer): void
    {
        $envelope = EventEnvelope::fromArray($message->getBody() ?? []);
        $data = $envelope->data;
        $attempts = (int) ($data['attempts'] ?? 0);

        if (!$this->claimEvent($envelope->eventId, $envelope->eventType)) {
            $consumer->commit($message);

            return;
        }

        try {
            $this->process($envelope->eventType, $data);
        } catch (Throwable $e) {
            $this->releaseClaim($envelope->eventId);

            if ($attempts < self::MAX_ATTEMPTS - 1) {
                $data['attempts'] = $attempts + 1;
                $data['error'] = $e->getMessage();

                $this->publisher->publish(
                    KafkaTopics::PAYMENT_EVENTS_RETRY,
                    $envelope->eventType,
                    $data,
                    $envelope->key,
                );
            } else {
                $this->publisher->publish(
                    KafkaTopics::PAYMENT_EVENTS_DLQ,
                    $envelope->eventType,
                    [...$data, 'error' => $e->getMessage(), 'event_id' => $envelope->eventId],
                    $envelope->key,
                );
            }
        } finally {
            $consumer->commit($message);
        }
    }

    private function process(string $eventType, array $data): void
    {
        match ($eventType) {
            'payment.confirmed' => $this->paymentService->handleCheckoutComplete($data['session_id'] ?? ''),
            'payment.failed' => $this->paymentService->handlePaymentFailed($data['provider_reference'] ?? ''),
            default => null,
        };
    }

    /**
     * Atomically claim an event. The unique index on `event_id` guarantees a
     * single consumer applies a given event, even across redeliveries.
     */
    private function claimEvent(string $eventId, string $eventType): bool
    {
        try {
            ProcessedPaymentEvent::create([
                'event_id' => $eventId,
                'event_type' => $eventType,
            ]);

            return true;
        } catch (QueryException $e) {
            if (in_array((string) $e->getCode(), ['23000', '1062', '19', '23505', '2601', '1555'], true)
                || str_contains(strtolower($e->getMessage()), 'unique')) {
                return false;
            }

            throw $e;
        }
    }

    private function releaseClaim(string $eventId): void
    {
        ProcessedPaymentEvent::where('event_id', $eventId)->delete();
    }
}
