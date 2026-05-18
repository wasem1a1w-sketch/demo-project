<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage; // Add this import
use Illuminate\Support\Number; // Add this import for currency formatting

class NewOrderNotification extends Notification
{
    use Queueable;

    public function __construct(
        public Order $order
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'broadcast', 'mail']; // Modified
    }

    public function toMail(object $notifiable): MailMessage // New method
    {
        $orderNumber = $this->order->order_number;
        $total = Number::currency($this->order->total, 'USD'); // Assuming USD, adjust as needed

        return (new MailMessage)
                    ->subject('Order Confirmation - #' . $orderNumber)
                    ->greeting('Hello ' . $this->order->shipping_name . '!')
                    ->line('Thank you for your order! Your order number is: #' . $orderNumber)
                    ->line('Total: ' . $total)
                    ->action('View Order', route('order.show', $orderNumber)) // Assuming a route exists
                    ->line('We will notify you once your order has been shipped.');
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
