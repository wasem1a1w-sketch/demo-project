<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class TestNotification extends Notification
{
    use Queueable;

    public function via(object $notifiable): array
    {
        return ['broadcast'];
    }

    public function toBroadcast(object $notifiable): array
    {
        return [
            'type' => 'test',
            'message' => 'Test notification — will disappear on refresh',
        ];
    }

    public function broadcastType(): string
    {
        return 'test';
    }
}
