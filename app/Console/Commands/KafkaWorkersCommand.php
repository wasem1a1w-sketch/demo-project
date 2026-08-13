<?php

namespace App\Console\Commands;

use App\Services\KafkaWorkerManager;
use Illuminate\Console\Command;

class KafkaWorkersCommand extends Command
{
    protected $signature = 'kafka:workers
        {action=status : One of: status, start, stop, watch}';

    protected $description = 'Manage the Kafka consumer/dispatcher worker fleet from the repo';

    public function handle(KafkaWorkerManager $manager): int
    {
        $action = $this->argument('action');

        if (! in_array($action, ['status', 'start', 'stop', 'watch'], true)) {
            $this->error('Unknown action ['.$action.']. Available: status, start, stop, watch.');

            return self::FAILURE;
        }

        $this->{$action}($manager);

        return self::SUCCESS;
    }

    private function status(KafkaWorkerManager $manager): void
    {
        $php = $manager->resolvePhp();
        $this->info('Resolved PHP: '.$php);

        foreach (array_keys($manager->workers()) as $worker) {
            $state = $manager->isRunning($worker) ? '<info>RUNNING</info>' : '<comment>STOPPED</comment>';
            $this->line(sprintf('  %-20s %s', $worker, $state));
        }
    }

    private function start(KafkaWorkerManager $manager): void
    {
        foreach (array_keys($manager->workers()) as $worker) {
            $running = $manager->start($worker);
            $this->info(sprintf('  %-20s %s', $worker, $running ? 'running' : 'FAILED to start'));
        }
    }

    private function stop(KafkaWorkerManager $manager): void
    {
        foreach (array_keys($manager->workers()) as $worker) {
            $stopped = $manager->stop($worker);
            $this->info(sprintf('  %-20s %s', $worker, $stopped ? 'stopped' : 'still alive'));
        }
    }

    private function watch(KafkaWorkerManager $manager): void
    {
        foreach ($manager->watch() as $worker => $running) {
            $this->info(sprintf('  %-20s %s', $worker, $running ? 'ok' : 'FAILED'));
        }
    }
}