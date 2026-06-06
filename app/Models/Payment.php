<?php

namespace App\Models;

use App\Enums\PaymentStatus;
use App\Exceptions\InvalidStateTransitionException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    protected $fillable = [
        'order_id',
        'provider',
        'provider_transaction_id',
        'provider_session_id',
        'provider_response',
        'status',
        'attempts',
        'expires_at',
    ];

    protected $casts = [
        'provider_response' => 'json',
        'expires_at' => 'datetime',
        'status' => PaymentStatus::class,
    ];

    // Provider constants
    const PROVIDER_STRIPE = 'stripe';
    const PROVIDER_PAYPAL = 'paypal';
    const PROVIDER_RAZORPAY = 'razorpay';

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeByProvider($query, $provider)
    {
        return $query->where('provider', $provider);
    }

    public function transitionStatus(PaymentStatus $newStatus): static
    {
        $currentStatus = $this->status;

        if (!$currentStatus instanceof PaymentStatus) {
            throw new \RuntimeException('Current status is not a valid PaymentStatus enum.');
        }

        if ($currentStatus === $newStatus) {
            return $this;
        }

        if (!$currentStatus->canTransitionTo($newStatus)) {
            throw new InvalidStateTransitionException(
                $currentStatus->value,
                $newStatus->value,
                class_basename(static::class)
            );
        }

        $this->status = $newStatus;
        $this->save();

        return $this;
    }

    public function isPaid(): bool
    {
        return $this->status === PaymentStatus::Paid;
    }

    public function isPending(): bool
    {
        return $this->status === PaymentStatus::Pending;
    }

    public function isFailed(): bool
    {
        return $this->status === PaymentStatus::Failed;
    }
}
