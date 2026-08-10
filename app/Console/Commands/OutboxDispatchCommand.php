<?php

namespace App\Console\Commands;

use App\Kafka\Outbox;
use Illuminate\Console\Command;

class OutboxDispatchCommand extends Command
{
    protected $signature = 'outbox:dispatch
        {--limit=500 : Max messages to deliver per run}';

    protected $description = 'Send pending outbox messages to Kafka';

    public function handle(Outbox $outbox): int
    {
        $processed = $outbox->dispatch((int) $this->option('limit'));

        $this->info("Dispatched {$processed} outbox message(s).");

        return self::SUCCESS;
    }
}
