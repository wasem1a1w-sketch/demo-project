<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class OrderConfirmation extends Notification
{
    use Queueable;

    public function __construct(
        public Order $order
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $orderNumber = $this->order->order_number;

        return (new MailMessage)
            ->subject('Payment Confirmed - Order #' . $orderNumber)
            ->greeting('Hello ' . ($this->order->shipping_name ?: 'Valued Customer') . '!')
            ->line('Your payment has been confirmed for order #' . $orderNumber)
            ->line('Total Paid: $' . number_format($this->order->total, 2))
            ->line('We will notify you once your order has been shipped.');
    }
}
