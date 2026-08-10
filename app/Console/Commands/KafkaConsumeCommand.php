<?php

namespace App\Console\Commands;

use App\Kafka\Consumers\ActivityLogHandler;
use App\Kafka\Consumers\OrderAnalyticsHandler;
use App\Kafka\Consumers\OrderNotificationsHandler;
use App\Kafka\Consumers\PaymentEventProcessor;
use App\Kafka\KafkaTopics;
use Illuminate\Console\Command;
use Junges\Kafka\Facades\Kafka;

class KafkaConsumeCommand extends Command
{
    protected $signature = 'kafka:consume {worker : One of: activity-logs, order-notifications, order-analytics, payments}';

    protected $description = 'Run a Kafka consumer worker (topic subscription + handler)';

    public function handle(): int
    {
        $worker = $this->argument('worker');

        $workers = $this->workers();
        $config = $workers[$worker] ?? null;

        if ($config === null) {
            $this->error('Unknown worker ['.$worker.']. Available: '.implode(', ', array_keys($workers)));

            return self::FAILURE;
        }

        $consumer = Kafka::consumer($config['topics'], $config['group'])
            ->withHandler($config['handler'])
            ->withManualCommit();

        if (isset($config['dlq'])) {
            $consumer->withDlq($config['dlq']);
        }

        $this->info('Consuming ['.implode(', ', $config['topics']).'] as group ['.$config['group'].']');

        $consumer->build()->consume();

        return self::SUCCESS;
    }

    /**
     * Worker registry: maps a worker name to its topic subscription,
     * consumer group id and handler class.
     */
    private function workers(): array
    {
        return [
            'activity-logs' => [
                'topics' => [KafkaTopics::ACTIVITY_LOGS],
                'group' => 'activity-log-writer',
                'handler' => ActivityLogHandler::class,
            ],
            'order-notifications' => [
                'topics' => [KafkaTopics::ORDER_EVENTS],
                'group' => 'order-notifications',
                'handler' => OrderNotificationsHandler::class,
            ],
            'order-analytics' => [
                'topics' => [KafkaTopics::ORDER_EVENTS],
                'group' => 'order-analytics',
                'handler' => OrderAnalyticsHandler::class,
            ],
            'payments' => [
                'topics' => [KafkaTopics::PAYMENT_EVENTS, KafkaTopics::PAYMENT_EVENTS_RETRY],
                'group' => 'payment-processor',
                'handler' => PaymentEventProcessor::class,
            ],
        ];
    }
}
