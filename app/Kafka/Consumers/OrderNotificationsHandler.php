<?php

namespace App\Kafka\Consumers;

use App\Enums\OrderStatus;
use App\Events\ClientNotificationBroadcast;
use App\Kafka\EventEnvelope;
use App\Models\AdminNotification;
use App\Models\Order;
use App\Notifications\OrderStatusChanged;
use Junges\Kafka\Contracts\ConsumerMessage;
use Junges\Kafka\Contracts\MessageConsumer;

/**
 * Consumer group `order-notifications` on topic `order-events`.
 * Sends admin + customer notifications for order lifecycle events.
 */
class OrderNotificationsHandler
{
    public function __invoke(ConsumerMessage $message, MessageConsumer $consumer): void
    {
        $envelope = EventEnvelope::fromArray($message->getBody() ?? []);
        $data = $envelope->data;

        match ($envelope->eventType) {
            'order.placed' => $this->handlePlaced($data),
            'order.status_changed' => $this->handleStatusChanged($data),
            default => null,
        };

        $consumer->commit($message);
    }

    private function handlePlaced(array $data): void
    {
        $order = Order::with('user')->find($data['order_id'] ?? null);

        if (!$order) {
            return;
        }

        AdminNotification::notify('new_order', [
            'order_id' => $order->id,
            'order_number' => $order->order_number,
            'total' => $order->total,
            'customer_name' => $order->shipping_name,
            'message' => "New order #{$order->order_number} for \${$order->total}",
        ]);

        $this->notifyCustomer($order, 'new', 'pending', "Order #{$order->order_number} is now pending");

        foreach ($data['low_stock'] ?? [] as $product) {
            AdminNotification::notify('low_stock', [
                'product_id' => $product['product_id'],
                'product_name' => $product['product_name'],
                'stock' => $product['stock'],
                'message' => "Low stock: {$product['product_name']} ({$product['stock']} left)",
            ]);
        }
    }

    private function handleStatusChanged(array $data): void
    {
        $order = Order::with('user')->find($data['order_id'] ?? null);

        if (!$order) {
            return;
        }

        $newStatus = $data['new_status'] ?? 'pending';
        $label = OrderStatus::tryFrom($newStatus)?->label() ?? $newStatus;

        AdminNotification::notify('order_status_changed', [
            'order_id' => $order->id,
            'order_number' => $order->order_number,
            'old_status' => $data['old_status'] ?? null,
            'new_status' => $newStatus,
            'message' => "Order #{$order->order_number} is now {$label}",
        ]);

        $this->notifyCustomer($order, $data['old_status'] ?? 'unknown', $newStatus, "Order #{$order->order_number} is now {$label}");
    }

    private function notifyCustomer(Order $order, string $oldStatus, string $newStatus, string $message): void
    {
        if (!$order->user) {
            return;
        }

        $order->user->notify(new OrderStatusChanged($order, $oldStatus, $newStatus));

        broadcast(new ClientNotificationBroadcast('order_status_changed', [
            'order_number' => $order->order_number,
            'order_id' => $order->id,
            'old_status' => $oldStatus,
            'new_status' => $newStatus,
            'message' => $message,
        ], $order->user->id));
    }
}
