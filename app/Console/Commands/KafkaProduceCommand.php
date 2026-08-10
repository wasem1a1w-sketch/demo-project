<?php

namespace App\Console\Commands;

use App\Kafka\KafkaEventPublisher;
use App\Kafka\KafkaTopics;
use Illuminate\Console\Command;

class KafkaProduceCommand extends Command
{
    protected $signature = 'kafka:produce
        {--topic=user-activity-logs : Topic to publish to}
        {--key= : Partition key (optional)}
        {message? : Raw message body (JSON string). Defaults to a demo payload.}';

    protected $description = 'Publish a message to a Kafka topic (smoke test)';

    public function handle(KafkaEventPublisher $publisher): int
    {
        $topic = $this->option('topic');
        $body = $this->argument('message')
            ? json_decode($this->argument('message'), true)
            : ['hello' => 'world', 'source' => 'kafka:produce'];

        $publisher->publish($topic, 'smoke.test', $body, $this->option('key'));

        $this->info("Published message to [{$topic}]");

        return self::SUCCESS;
    }
}
