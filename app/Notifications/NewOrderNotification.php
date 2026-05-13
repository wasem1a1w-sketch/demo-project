<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class NewOrderNotification extends Notification
{
    use Queueable;

    public function __construct(
        public Order $order
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'broadcast'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'type' => 'new_order',
            'order_number' => $this->order->order_number,
            'order_id' => $this->order->id,
            'total' => $this->order->total,
            'customer_name' => $this->order->shipping_name,
            'message' => "New order #{$this->order->order_number} for \${$this->order->total}",
        ];
    }

    public function toBroadcast(object $notifiable): array
    {
        return $this->toDatabase($notifiable);
    }

    public function broadcastType(): string
    {
        return 'new_order';
    }
}
