<?php

namespace App\Models;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Exceptions\InvalidStateTransitionException;
use App\Kafka\KafkaTopics;
use App\Kafka\Outbox;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_number',
        'user_id',
        'status',
        'subtotal',
        'tax',
        'shipping',
        'discount',
        'total',
        'coupon_id',
        'shipping_name',
        'shipping_address',
        'shipping_city',
        'shipping_state',
        'shipping_postal_code',
        'shipping_country',
        'shipping_phone',
        'payment_method',
        'payment_status',
        'payment_transaction_id',
        'notes',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'tax' => 'decimal:2',
        'shipping' => 'decimal:2',
        'discount' => 'decimal:2',
        'total' => 'decimal:2',
        'status' => OrderStatus::class,
        'payment_status' => PaymentStatus::class,
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function coupon(): BelongsTo
    {
        return $this->belongsTo(Coupon::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public static function generateOrderNumber(): string
    {
        do {
            $number = 'ORD-'.strtoupper(Str::random(8)).random_int(1000, 9999);
        } while (static::where('order_number', $number)->exists());

        return $number;
    }

    public function transitionStatus(OrderStatus $newStatus, ?int $actorUserId = null): static
    {
        $currentStatus = $this->status;

        if (! $currentStatus instanceof OrderStatus) {
            throw new \RuntimeException('Current status is not a valid OrderStatus enum.');
        }

        if ($currentStatus === $newStatus) {
            return $this;
        }

        if (! $currentStatus->canTransitionTo($newStatus)) {
            throw new InvalidStateTransitionException(
                $currentStatus->value,
                $newStatus->value,
                class_basename(static::class)
            );
        }

        DB::transaction(function () use ($newStatus, $actorUserId, $currentStatus) {
            if ($newStatus === OrderStatus::Cancelled) {
                $this->loadMissing('items.product');

                foreach ($this->items as $item) {
                    $item->product?->increment('stock', $item->quantity);
                }
            }

            $this->status = $newStatus;
            $this->save();

            app(Outbox::class)->record(
                KafkaTopics::ORDER_EVENTS,
                'order.status_changed',
                [
                    'order_id' => $this->id,
                    'order_number' => $this->order_number,
                    'old_status' => $currentStatus->value,
                    'new_status' => $newStatus->value,
                    'actor_user_id' => $actorUserId,
                ],
                (string) $this->id,
            );
        });

        return $this;
    }

    public function cancel(?int $actorUserId = null): void
    {
        $this->transitionStatus(OrderStatus::Cancelled, $actorUserId);
    }

    public function getStatusColorAttribute()
    {
        $status = $this->status;

        if ($status instanceof OrderStatus) {
            return $status->color();
        }

        return 'gray';
    }
}
