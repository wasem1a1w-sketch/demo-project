<?php

namespace App\Kafka;

use Junges\Kafka\Contracts\MessageProducer;
use Junges\Kafka\Facades\Kafka;

class KafkaEventPublisher
{
    /**
     * Publish an event envelope to a topic (synchronous producer).
     *
     * The message key determines the partition, which guarantees ordering
     * for messages that share the same key (e.g. per-order events).
     */
    public function publish(string $topic, string $eventType, array $data, ?string $key = null): void
    {
        $envelope = EventEnvelope::make($eventType, $data, $key);

        $producer = Kafka::publish()
            ->onTopic($topic)
            ->withKafkaKey($envelope->key)
            ->withBody($envelope->toArray());

        $producer->send();
    }

    /** Direct access to a producer for advanced use (headers, retries). */
    public function producer(string $topic): MessageProducer
    {
        return Kafka::publish()->onTopic($topic);
    }
}
