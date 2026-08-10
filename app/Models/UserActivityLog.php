<?php

namespace App\Models;

use App\Kafka\KafkaTopics;
use App\Kafka\Outbox;
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

    /**
     * Publish an activity log event via the transactional outbox instead of
     * writing to the DB synchronously. The `activity-log-writer` consumer
     * persists the row after `outbox:dispatch` delivers the message.
     */
    public static function record(?int $userId, string $type, string $description, array $data = [], ?\DateTimeInterface $createdAt = null): void
    {
        $payload = [
            'user_id' => $userId,
            'type' => $type,
            'description' => $description,
            'data' => $data,
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
            'created_at' => ($createdAt ?? now())->toIso8601String(),
        ];

        app(Outbox::class)->record(
            KafkaTopics::ACTIVITY_LOGS,
            'activity_log.recorded',
            $payload,
            $userId !== null ? (string) $userId : null,
        );
    }

    /**
     * Persist an activity log row (used by the Kafka consumer).
     */
    public static function persist(array $payload): self
    {
        return static::create([
            'user_id' => $payload['user_id'] ?? null,
            'type' => $payload['type'] ?? 'unknown',
            'description' => $payload['description'] ?? '',
            'data' => empty($payload['data']) ? null : $payload['data'],
            'ip_address' => $payload['ip_address'] ?? null,
            'user_agent' => $payload['user_agent'] ?? null,
            'created_at' => isset($payload['created_at']) ? new \DateTime($payload['created_at']) : now(),
        ]);
    }
}
