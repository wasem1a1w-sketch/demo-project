<?php

namespace App\Events;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Support\Str;

class ClientNotificationBroadcast implements ShouldBroadcastNow
{
    public function __construct(
        public string $type,
        public array $data,
        public int $userId,
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('App.Models.User.' . $this->userId),
        ];
    }

    public function broadcastWith(): array
    {
        return [
            'id' => (string) Str::uuid(),
            'type' => $this->type,
            'data' => $this->data,
            'read_at' => null,
            'created_at' => now()->toISOString(),
        ];
    }
}
