<?php

namespace App\Kafka;

class KafkaTopics
{
    public const ACTIVITY_LOGS = 'user-activity-logs';

    public const ORDER_EVENTS = 'order-events';

    public const PAYMENT_EVENTS = 'payment-events';

    public const PAYMENT_EVENTS_RETRY = 'payment-events-retry';

    public const PAYMENT_EVENTS_DLQ = 'payment-events-dlq';

    public static function all(): array
    {
        return [
            self::ACTIVITY_LOGS,
            self::ORDER_EVENTS,
            self::PAYMENT_EVENTS,
            self::PAYMENT_EVENTS_RETRY,
            self::PAYMENT_EVENTS_DLQ,
        ];
    }
}
