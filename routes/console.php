<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('pulse:check')->everyMinute();
Schedule::command('exceptions:prune --days=30')->weekly();
// Watchdog: boot/keep the Kafka consumer + dispatcher workers alive from code
// (no supervisor/systemd needed). Outbox delivery runs via kafka:dispatch-loop,
// which runs under an rdkafka-capable PHP.
Schedule::command('kafka:workers watch')->everyMinute()->withoutOverlapping();
