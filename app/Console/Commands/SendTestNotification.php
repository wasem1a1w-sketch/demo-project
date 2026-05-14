<?php

namespace App\Console\Commands;

use App\Events\AdminNotificationBroadcast;
use App\Events\ClientNotificationBroadcast;
use App\Models\AdminNotification;
use App\Models\User;
use Illuminate\Console\Command;

class SendTestNotification extends Command
{
    protected $signature = 'notify:test {user} {--admin : Send as admin notification}';

    protected $description = 'Broadcast a test notification to a user (ephemeral — no DB save, gone on refresh)';

    public function handle()
    {
        $user = User::findOrFail($this->argument('user'));

        if ($this->option('admin')) {
            $notification = new AdminNotification([
                'type' => 'test',
                'data' => ['message' => 'Test admin notification — ephemeral, no DB save'],
            ]);
            $notification->id = random_int(10000, 99999);
            $notification->created_at = now();

            broadcast(new AdminNotificationBroadcast($notification));

            $this->info("Broadcast test admin notification to admin.notifications (ephemeral)");
        } else {
            broadcast(new ClientNotificationBroadcast('test', [
                'message' => 'Test notification — ephemeral, no DB save',
            ], $user->id));

            $this->info("Broadcast test notification to App.Models.User.{$user->id} (ephemeral)");
        }
    }
}
