<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Request;

class UserActivityLog extends Model
{
    const UPDATED_AT = null;

    protected $fillable = [
        'user_id',
        'type',
        'description',
        'data',
        'ip_address',
        'user_agent',
        'created_at',
    ];

    protected $casts = [
        'data' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function record(?int $userId, string $type, string $description, array $data = [], ?\DateTimeInterface $createdAt = null): self
    {
        return static::create([
            'user_id' => $userId,
            'type' => $type,
            'description' => $description,
            'data' => empty($data) ? null : $data,
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
            'created_at' => $createdAt ?? now(),
        ]);
    }
}
