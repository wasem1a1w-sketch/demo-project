<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

/**
 * Vendors the rdkafka PHP extension (and its librdkafka runtime libs, when
 * they live outside the standard loader paths) into storage/kafka/php so that
 * any PHP process — regardless of which user/HOME it runs under — can load it
 * without a per-machine system install.
 *
 * After this command has run once, `bin/php` appends the vendored directories
 * to PHP_INI_SCAN_DIR / LD_LIBRARY_PATH and the Kafka consumers/dispatcher
 * auto-started by `kafka:workers watch` work for any runtime user.
 *
 * On machines where rdkafka is installed system-wide the command is a no-op
 * (the extension is resolved from the currently loaded PHP binary).
 */
class KafkaSetupCommand extends Command
{
    protected $signature = 'kafka:setup
        {--so= : Explicit path to rdkafka.so to vendor}
        {--lib-dir= : Explicit path to a directory containing librdkafka shared libs}
        {--force : Re-vendor even if already present}';

    protected $description = 'Vendor the rdkafka extension into storage/kafka/php (run once per machine)';

    public function handle(): int
    {
        $root = storage_path('kafka/php');
        $modulesDir = $root.'/modules';
        $libDir = $root.'/lib';
        $iniPath = $root.'/rdkafka.ini';
        $soPath = $modulesDir.'/rdkafka.so';

        if (file_exists($soPath) && ! $this->option('force')) {
            $this->info('rdkafka already vendored at '.$soPath.' (use --force to replace).');

            return self::SUCCESS;
        }

        $this->info('Locating the rdkafka extension...');
        $sourceSo = $this->option('so') ?? env('KAFKA_RDKAFKA_SO') ?? $this->locateRdkafkaSo();

        if ($sourceSo === null || ! is_file($sourceSo)) {
            $this->error('Could not find rdkafka.so. Install rdkafka first (see KAFKA.md) or pass --so=/path/to/rdkafka.so.');

            return self::FAILURE;
        }

        $this->info('Found extension: '.$sourceSo);

        if (! is_dir($modulesDir)) {
            mkdir($modulesDir, 0755, true);
        }
        if (! is_dir($libDir)) {
            mkdir($libDir, 0755, true);
        }

        copy($sourceSo, $soPath);
        chmod($soPath, 0755);

        $vendoredLibs = $this->vendorLibrdkafkaLibs($sourceSo, $this->option('lib-dir'), $libDir);

        file_put_contents($iniPath, "extension={$soPath}\n");
        chmod($iniPath, 0644);

        if ($vendoredLibs === 0) {
            $this->info('librdkafka is available on the standard loader path; no runtime libs vendored.');
        } else {
            $this->info("Vendored {$vendoredLibs} librdkafka shared library file(s) into {$libDir}.");
        }

        $this->info('rdkafka vendored successfully. bin/php will now load it for any user.');
        $this->warn('Note: if you are running the web server/scheduler as another user, ensure it can read storage/kafka/php.');

        return self::SUCCESS;
    }

    /**
     * Resolve the rdkafka extension file by checking common install locations.
     * (ReflectionExtension exposes the path inconsistently across PHP 8.x.)
     */
    private function locateRdkafkaSo(): ?string
    {
        $candidates = $this->commonCandidatePaths();

        foreach ($candidates as $path) {
            if (is_file($path)) {
                return $path;
            }
        }

        // Last resort: scan the default PHP extension dir for rdkafka.so.
        $extensionDir = rtrim((string) ini_get('extension_dir'), '/');
        if ($extensionDir !== '' && is_file($extensionDir.'/rdkafka.so')) {
            return $extensionDir.'/rdkafka.so';
        }

        return null;
    }

    private function commonCandidatePaths(): array
    {
        $home = $_SERVER['HOME'] ?? getenv('HOME');
        $paths = [
            $home.'/.local/php/modules/rdkafka.so',
            '/usr/lib64/php/modules/rdkafka.so',
            '/usr/lib/php/modules/rdkafka.so',
        ];

        // Glob for versioned PHP extension dirs, e.g. /usr/lib/php/20210902/rdkafka.so
        $globs = [
            '/usr/lib/php/*/rdkafka.so',
            '/usr/lib64/php/*/rdkafka.so',
        ];
        foreach ($globs as $glob) {
            foreach (glob($glob) as $match) {
                $paths[] = $match;
            }
        }

        return array_values(array_unique(array_filter($paths)));
    }

    /**
     * Copy librdkafka shared libs into $libDir only when they resolve outside
     * the standard loader search path (e.g. a user-local build under ~/.local).
     *
     * @return int Number of files vendored
     */
    private function vendorLibrdkafkaLibs(string $soPath, ?string $explicitLibDir, string $libDir): int
    {
        $sourceDir = $explicitLibDir ?? $this->librdkafkaSourceDir($soPath);

        if ($sourceDir === null || ! is_dir($sourceDir)) {
            return 0;
        }

        $libs = glob($sourceDir.'/librdkafka.so*') ?: [];

        if ($libs === []) {
            return 0;
        }

        foreach ($libs as $lib) {
            $target = $libDir.'/'.basename($lib);
            if (! file_exists($target)) {
                copy($lib, $target);
            }
            chmod($target, 0755);
        }

        return count($libs);
    }

    /**
     * Where librdkafka runtime libs come from when they are not on the
     * standard loader path: the rdkafka.so's own dir, its parent, or ~/.local/lib.
     */
    private function librdkafkaSourceDir(string $soPath): ?string
    {
        $home = $_SERVER['HOME'] ?? getenv('HOME');

        $candidates = [
            dirname($soPath),
            dirname($soPath).'/../lib',
            $home.'/.local/lib',
        ];

        foreach (array_unique($candidates) as $dir) {
            if (is_dir($dir) && (glob($dir.'/librdkafka.so*') ?: []) !== []) {
                return $dir;
            }
        }

        return null;
    }
}