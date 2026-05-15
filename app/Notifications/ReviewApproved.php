<?php

namespace App\Notifications;

use App\Models\ProductReview;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ReviewApproved extends Notification
{
    use Queueable;

    public function __construct(
        public ProductReview $review,
        public string $status,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'review_status_changed',
            'message' => $this->status === 'approved'
                ? 'Your review has been approved!'
                : 'Your review has been rejected.',
            'product_id' => $this->review->product_id,
            'product_name' => $this->review->product->name,
            'review_id' => $this->review->id,
            'status' => $this->status,
        ];
    }
}
