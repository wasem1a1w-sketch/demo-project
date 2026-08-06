<?php

namespace App\Console\Commands;

use App\Models\AppException;
use Illuminate\Console\Command;

class PruneExceptions extends Command
{
    protected $signature = 'exceptions:prune {--days=30 : Delete exception records older than this many days}';

    protected $description = 'Delete captured exception records older than the given number of days';

    public function handle()
    {
        $days = (int) $this->option('days');

        $deleted = AppException::where('created_at', '<', now()->subDays($days))->delete();

        $this->info("Deleted {$deleted} exception record(s) older than {$days} days.");
    }
}
