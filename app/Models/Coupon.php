<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Coupon extends Model
{
    use HasFactory;
    protected $fillable = [
        'code',
        'type',
        'value',
        'min_order_amount',
        'max_discount_amount',
        'usage_limit',
        'used_count',
        'user_usage_limit',
        'valid_from',
        'valid_until',
        'is_active',
    ];

    protected $casts = [
        'value' => 'decimal:2',
        'min_order_amount' => 'decimal:2',
        'max_discount_amount' => 'decimal:2',
        'valid_from' => 'datetime',
        'valid_until' => 'datetime',
        'is_active' => 'boolean',
    ];

    const TYPE_PERCENTAGE = 'percentage';

    const TYPE_FIXED = 'fixed';

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function isValid(): bool
    {
        if (! $this->is_active) {
            return false;
        }

        $now = now();
        if ($this->valid_from && $now->lt($this->valid_from)) {
            return false;
        }

        if ($this->valid_until && $now->gt($this->valid_until)) {
            return false;
        }

        if ($this->usage_limit && $this->used_count >= $this->usage_limit) {
            return false;
        }

        return true;
    }

    public function calculateDiscount($orderAmount): float
    {
        if (! $this->isValid()) {
            return 0;
        }

        if ($this->min_order_amount && $orderAmount < $this->min_order_amount) {
            return 0;
        }

        $discount = $this->type === self::TYPE_PERCENTAGE
            ? $orderAmount * ($this->value / 100)
            : $this->value;

        if ($this->max_discount_amount) {
            $discount = min($discount, $this->max_discount_amount);
        }

        return min($discount, $orderAmount);
    }
}
