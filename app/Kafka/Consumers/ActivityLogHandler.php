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

        // Manual commit: the consumers are built with withManualCommit() (auto
        // commit disabled), so the offset must be committed explicitly or it
        // never advances and every worker restart redelivers duplicates.
        $consumer->commit($message);
    }
}
