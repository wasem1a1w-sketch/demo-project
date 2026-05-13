<?php

namespace App\Models;

use App\Events\AdminNotificationBroadcast;
use Illuminate\Database\Eloquent\Model;

class AdminNotification extends Model
{
    protected $fillable = ['type', 'data'];

    protected $casts = [
        'data' => 'array',
    ];

    public function readByUsers()
    {
        return $this->belongsToMany(User::class, 'admin_notification_user')
            ->withPivot('read_at')
            ->withTimestamps();
    }

    public static function notify(string $type, array $data): self
    {
        $notification = static::create(compact('type', 'data'));

        broadcast(new AdminNotificationBroadcast($notification));

        return $notification;
    }
}
