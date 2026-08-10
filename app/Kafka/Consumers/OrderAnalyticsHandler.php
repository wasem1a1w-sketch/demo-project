<?php

namespace App\Kafka\Consumers;

use App\Kafka\EventEnvelope;
use App\Models\UserActivityLog;
use Junges\Kafka\Contracts\ConsumerMessage;
use Junges\Kafka\Contracts\MessageConsumer;

/**
 * Consumer group `order-analytics` on topic `order-events`.
 * Writes analytics/audit rows (activity log) for order lifecycle events.
 */
class OrderAnalyticsHandler
{
    public function __invoke(ConsumerMessage $message, MessageConsumer $consumer): void
    {
        $envelope = EventEnvelope::fromArray($message->getBody() ?? []);
        $data = $envelope->data;

        match ($envelope->eventType) {
            'order.placed' => UserActivityLog::persist([
                'user_id' => $data['user_id'] ?? null,
                'type' => 'order_placed',
                'description' => "Order placed: {$data['order_number']}",
                'created_at' => $envelope->occurredAt,
            ]),
            'order.status_changed' => UserActivityLog::persist([
                'user_id' => $data['actor_user_id'] ?? null,
                'type' => 'order_status_changed',
                'description' => "Order #{$data['order_number']} status changed: {$data['old_status']} -> {$data['new_status']}",
                'created_at' => $envelope->occurredAt,
            ]),
            default => null,
        };

        $consumer->commit($message);
    }
}
