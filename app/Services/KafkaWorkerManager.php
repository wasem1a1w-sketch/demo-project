<?php

namespace App\Services;

use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Exception\ProcessTimedOutException;
use Symfony\Component\Process\Process as SymfonyProcess;

/**
 * Manages the long-running Kafka worker fleet (consumer workers + the outbox
 * dispatcher loop) from within the repo, so no per-machine process manager
 * (supervisor/systemd) is required.
 *
 * - Resolves a PHP binary that can actually load the rdkafka extension
 *   (env override -> repo bin/php wrapper -> current binary).
 * - Spawns each worker as a detached background process (setsid + nohup),
 *   tracks it via a pidfile, and restarts anything that has died.
 *
 * Wire the watchdog into the scheduler once:
 *     Schedule::command('kafka:workers watch')->everyMinute()->withoutOverlapping();
 */
class KafkaWorkerManager
{
    private const STATE_DIR = 'kafka';

    /**
     * The worker fleet: consumers from KafkaConsumeCommand plus the outbox
     * dispatcher loop. Keys are the worker identifiers used for pidfiles/logs.
     */
    public function workers(): array
    {
        return [
            'activity-logs' => ['kafka:consume', 'activity-logs'],
            'order-notifications' => ['kafka:consume', 'order-notifications'],
            'order-analytics' => ['kafka:consume', 'order-analytics'],
            'payments' => ['kafka:consume', 'payments'],
            'dispatcher' => ['kafka:dispatch-loop'],
        ];
    }

    /**
     * Start a worker unless it is already running.
     *
     * @return bool True if the worker is running afterwards (newly or already).
     */
    public function start(string $worker): bool
    {
        if ($this->isRunning($worker)) {
            return true;
        }

        $php = $this->resolvePhp();
        $script = $this->pidfile($worker);
        $log = $this->logfile($worker);

        $args = array_merge([$php, base_path('artisan')], $this->workers()[$worker]);
        $shell = 'setsid nohup '.implode(' ', array_map('escapeshellarg', $args))
            .' >> '.escapeshellarg($log).' 2>&1 < /dev/null & echo $!';

        $process = SymfonyProcess::fromShellCommandline($shell);
        try {
            $process->setTimeout(15)->run();
        } catch (ProcessFailedException) {
            return false;
        }

        $pid = trim((string) $process->getOutput());

        if (ctype_digit($pid) && (int) $pid > 0) {
            file_put_contents($script, $pid);
        }

        return $this->isRunning($worker);
    }

    /**
     * Stop a worker, escalating to SIGKILL if it does not exit in time.
     */
    public function stop(string $worker): bool
    {
        $pid = $this->pidOf($worker);

        if ($pid === null) {
            @unlink($this->pidfile($worker));

            return true;
        }

        $this->signal($pid, SIGTERM);

        for ($i = 0; $i < 20; $i++) {
            if (! $this->processExists($pid)) {
                @unlink($this->pidfile($worker));

                return true;
            }
            usleep(250000);
        }

        $this->signal($pid, SIGKILL);
        @unlink($this->pidfile($worker));

        return ! $this->processExists($pid);
    }

    /**
     * Report whether a worker is currently alive (pidfile + process match).
     */
    public function isRunning(string $worker): bool
    {
        $pid = $this->pidOf($worker);

        if ($pid === null || ! $this->processExists($pid)) {
            return false;
        }

        // Guard against pid reuse: the process must be the artisan worker we spawned.
        $cmdline = $this->cmdlineOf($pid);

        if ($cmdline === null) {
            return true; // Cannot inspect cmdline (non-Linux) — trust the pid.
        }

        $token = implode(' ', $this->workers()[$worker]);

        return str_contains($cmdline, $token);
    }

    /**
     * Start every worker that is not running.
     *
     * @return array<string, bool> worker => running after attempt
     */
    public function watch(): array
    {
        $results = [];

        foreach (array_keys($this->workers()) as $worker) {
            $results[$worker] = $this->start($worker);
        }

        return $results;
    }

    /**
     * Find a PHP binary whose `-m` output lists rdkafka.
     */
    public function resolvePhp(): string
    {
        $candidates = [];

        $override = env('KAFKA_PHP_BIN');
        if ($override) {
            $candidates[] = $override;
        }

        $wrapper = base_path('bin/php');
        if (is_file($wrapper) && is_executable($wrapper)) {
            $candidates[] = $wrapper;
        }

        $candidates[] = PHP_BINARY;

        foreach (array_unique($candidates) as $bin) {
            if ($this->phpHasRdkafka($bin)) {
                return $bin;
            }
        }

        return PHP_BINARY;
    }

    public function phpHasRdkafka(string $bin): bool
    {
        try {
            $process = SymfonyProcess::fromShellCommandline(
                escapeshellarg($bin).' -m -d display_errors=0'
            );
            $process->setTimeout(10)->run();
        } catch (ProcessTimedOutException|ProcessFailedException) {
            return false;
        }

        return str_contains(strtolower((string) $process->getOutput()), 'rdkafka');
    }

    private function pidOf(string $worker): ?int
    {
        $file = $this->pidfile($worker);

        if (! is_file($file)) {
            return null;
        }

        $pid = (int) trim((string) file_get_contents($file));

        return $pid > 0 ? $pid : null;
    }

    private function pidfile(string $worker): string
    {
        return $this->directory().'/'.$worker.'.pid';
    }

    private function logfile(string $worker): string
    {
        return $this->directory().'/'.$worker.'.log';
    }

    private function directory(): string
    {
        $dir = storage_path('logs'.DIRECTORY_SEPARATOR.self::STATE_DIR);

        if (! is_dir($dir)) {
            mkdir($dir, 0775, true);
        }

        return $dir;
    }

    private function processExists(int $pid): bool
    {
        if (function_exists('posix_kill')) {
            return @posix_kill($pid, 0);
        }

        return is_dir('/proc/'.$pid);
    }

    private function cmdlineOf(int $pid): ?string
    {
        $cmdline = @file_get_contents('/proc/'.$pid.'/cmdline');

        if ($cmdline === false) {
            return null;
        }

        return str_replace("\0", ' ', $cmdline);
    }

    private function signal(int $pid, int $signal): void
    {
        if (function_exists('posix_kill')) {
            @posix_kill($pid, $signal);
        } else {
            @exec('kill -'.$signal.' '.$pid);
        }
    }
}