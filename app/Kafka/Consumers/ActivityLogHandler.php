<?php

namespace App\Kafka\Consumers;

use App\Kafka\EventEnvelope;
use App\Models\UserActivityLog;
use Junges\Kafka\Contracts\ConsumerMessage;
use Junges\Kafka\Contracts\MessageConsumer;

/**
 * Consumer group `activity-log-writer` on topic `user-activity-logs`.
 * Persists activity log rows that were published asynchronously.
 */
class ActivityLogHandler
{
    public function __invoke(ConsumerMessage $message, MessageConsumer $consumer): void
    {
        $envelope = EventEnvelope::fromArray($message->getBody() ?? []);

        UserActivityLog::persist($envelope->data);
    }
}
