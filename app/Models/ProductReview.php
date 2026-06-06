<?php

namespace App\Models;

use App\Enums\ReviewStatus;
use App\Exceptions\InvalidStateTransitionException;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductReview extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'user_id',
        'rating',
        'title',
        'body',
        'status',
    ];

    protected $casts = [
        'status' => ReviewStatus::class,
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function transitionStatus(ReviewStatus $newStatus): static
    {
        $currentStatus = $this->status;

        if (!$currentStatus instanceof ReviewStatus) {
            throw new \RuntimeException('Current status is not a valid ReviewStatus enum.');
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
}
