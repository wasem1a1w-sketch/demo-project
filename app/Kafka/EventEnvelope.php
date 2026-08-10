<?php

namespace App\Kafka;

use Illuminate\Support\Str;

/**
 * A JSON envelope that wraps every event published to Kafka.
 *
 * Produced body shape:
 *   {
 *     "event_type":   "order.placed",
 *     "event_id":     "uuid",          // unique id, used for idempotency
 *     "occurred_at":  "2026-08-10T12:00:00+00:00",
 *     "key":          "42",            // partition key (order/user/payment id)
 *     "data":         { ... }          // payload
 *   }
 */
class EventEnvelope
{
    public function __construct(
        public readonly string $eventType,
        public readonly array $data,
        public readonly string $key,
        public readonly string $eventId,
        public readonly string $occurredAt,
    ) {}

    public static function make(string $eventType, array $data, ?string $key = null, ?string $eventId = null): self
    {
        return new self(
            eventType: $eventType,
            data: $data,
            key: $key ?? (string) Str::uuid(),
            eventId: $eventId ?? (string) Str::uuid(),
            occurredAt: now()->toIso8601String(),
        );
    }

    public function toArray(): array
    {
        return [
            'event_type' => $this->eventType,
            'event_id' => $this->eventId,
            'occurred_at' => $this->occurredAt,
            'key' => $this->key,
            'data' => $this->data,
        ];
    }

    public static function fromArray(array $body): self
    {
        return new self(
            eventType: $body['event_type'] ?? 'unknown',
            data: $body['data'] ?? [],
            key: $body['key'] ?? '',
            eventId: $body['event_id'] ?? (string) Str::uuid(),
            occurredAt: $body['occurred_at'] ?? now()->toIso8601String(),
        );
    }
}
