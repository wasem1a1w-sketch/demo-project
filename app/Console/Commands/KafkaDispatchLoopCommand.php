<?php

namespace App\Console\Commands;

use App\Kafka\Outbox;
use Illuminate\Console\Command;

/**
 * Long-running dispatcher that delivers pending outbox rows to Kafka.
 *
 * Run as one of the managed worker fleet (see KafkaWorkerManager) under a PHP
 * binary that can load rdkafka — this removes the dependency on the Laravel
 * scheduler's PHP for outbox delivery.
 */
class KafkaDispatchLoopCommand extends Command
{
    protected $signature = 'kafka:dispatch-loop
        {--interval=10 : Seconds between dispatch runs}';

    protected $description = 'Continuously deliver pending outbox messages to Kafka';

    public function handle(Outbox $outbox): int
    {
        $interval = max(1, (int) $this->option('interval'));

        $this->info('Dispatch loop started (interval '.$interval.'s). Press Ctrl+C to stop.');

        $previousHandler = null;
        if (function_exists('pcntl_signal')) {
            $previousHandler = pcntl_signal(SIGTERM, function (): void {
                $this->info('SIGTERM received, shutting down.');
                exit(0);
            });
        }

        while (true) {
            try {
                $processed = $outbox->dispatch();
                if ($processed > 0) {
                    $this->info('Dispatched '.$processed.' outbox message(s).');
                }
            } catch (\Throwable $e) {
                $this->error('Dispatch failed: '.$e->getMessage());
            }

            sleep($interval);
        }

        if ($previousHandler !== null && function_exists('pcntl_signal')) {
            pcntl_signal(SIGTERM, $previousHandler);
        }

        return self::SUCCESS;
    }
}