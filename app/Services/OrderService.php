<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Events\ClientNotificationBroadcast;
use App\Models\Address;
use App\Models\AdminNotification;
use App\Models\Coupon;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Setting;
use App\Models\UserActivityLog;
use App\Notifications\OrderStatusChanged;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;

class OrderService
{
    private array $lowStockProducts = [];

    public function placeOrder(array $validated, ?string $cartSession, $user): Order
    {
        $items = collect($validated['items']);

        if ($items->isEmpty()) {
            throw new \InvalidArgumentException('Cart is empty');
        }

        $products = Product::whereIn('id', $items->pluck('product_id'))->get()->keyBy('id');

        $shippingRate = (float) (Setting::get('shipping_rate') ?? 15);
        $freeShippingThreshold = (float) (Setting::get('free_shipping_threshold') ?? 100);
        $taxRate = (float) (Setting::get('tax_rate') ?? 10);

        $this->lowStockProducts = [];

        $order = DB::transaction(function () use ($validated, $cartSession, $items, $user, $products, $shippingRate, $freeShippingThreshold, $taxRate) {
            $orderNumber = Order::generateOrderNumber();

            $recalculatedSubtotal = 0;
            $orderItems = [];

            foreach ($items as $item) {
                $product = $products->get($item['product_id']);
                if (!$product) continue;

                $lineTotal = (float) $product->price * (int) $item['quantity'];
                $recalculatedSubtotal += $lineTotal;

                $orderItems[] = [
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'product_sku' => $product->sku,
                    'price' => $product->price,
                    'quantity' => $item['quantity'],
                    'subtotal' => $lineTotal,
                ];

                $product->decrement('stock', $item['quantity']);
                if ($product->stock <= 5 && !isset($this->lowStockProducts[$product->id])) {
                    $this->lowStockProducts[$product->id] = $product;
                }
            }

            $discount = (float) ($validated['discount'] ?? 0);
            $tax = max(0, ($recalculatedSubtotal - $discount) * $taxRate / 100);
            $shipping = ($recalculatedSubtotal - $discount) >= $freeShippingThreshold ? 0 : $shippingRate;
            $total = $recalculatedSubtotal - $discount + $tax + $shipping;

            $order = Order::create([
                'order_number' => $orderNumber,
                'user_id' => $user?->id,
                'status' => OrderStatus::Pending,
                'subtotal' => $recalculatedSubtotal,
                'tax' => $tax,
                'shipping' => $shipping,
                'discount' => $discount,
                'total' => $total,
                'coupon_id' => $validated['coupon_id'] ?? null,
                'shipping_name' => "{$validated['first_name']} {$validated['last_name']}",
                'shipping_address' => $validated['address1'].($validated['address2'] ? ", {$validated['address2']}" : ''),
                'shipping_city' => $validated['city'],
                'shipping_state' => $validated['state'],
                'shipping_postal_code' => $validated['postal_code'],
                'shipping_country' => $validated['country'],
                'shipping_phone' => $validated['phone'],
                'payment_method' => $validated['payment_method'],
                'payment_status' => PaymentStatus::Pending,
            ]);

            foreach ($orderItems as $orderItem) {
                OrderItem::create(array_merge($orderItem, ['order_id' => $order->id]));
            }

            if (!empty($validated['coupon_id'])) {
                Coupon::where('id', $validated['coupon_id'])->increment('used_count');
            }

            if ($cartSession) {
                DB::table('cart_items')->where('session_id', $cartSession)->delete();
                Session::forget(['cart_coupon_id', 'cart_session']);
            }

            return $order;
        });

        $this->sendOrderNotifications($order, $validated, $user);

        return $order;
    }

    private function sendOrderNotifications(Order $order, array $validated, $user): void
    {
        AdminNotification::notify('new_order', [
            'order_id' => $order->id,
            'order_number' => $order->order_number,
            'total' => $order->total,
            'customer_name' => $order->shipping_name,
            'message' => "New order #{$order->order_number} for \${$order->total}",
        ]);

        if ($user) {
            $user->notify(new OrderStatusChanged($order, 'new', 'pending'));
            broadcast(new ClientNotificationBroadcast('order_status_changed', [
                'order_number' => $order->order_number,
                'order_id' => $order->id,
                'old_status' => 'new',
                'new_status' => 'pending',
                'message' => "Order #{$order->order_number} is now pending",
            ], $user->id));
        }

        UserActivityLog::record($user?->id, 'order_placed', "Order placed: {$order->order_number}");

        foreach ($this->lowStockProducts as $lowStockProduct) {
            AdminNotification::notify('low_stock', [
                'product_id' => $lowStockProduct->id,
                'product_name' => $lowStockProduct->name,
                'stock' => $lowStockProduct->stock,
                'message' => "Low stock: {$lowStockProduct->name} ({$lowStockProduct->stock} left)",
            ]);
        }
    }

    public function saveShippingAddress(array $validated, $user): void
    {
        if (!$user) return;

        $user->addresses()->updateOrCreate(
            [
                'type' => Address::TYPE_SHIPPING,
                'first_name' => $validated['first_name'],
                'last_name' => $validated['last_name'],
                'address1' => $validated['address1'],
            ],
            [
                'company' => $validated['company'] ?? null,
                'address2' => $validated['address2'] ?? null,
                'city' => $validated['city'],
                'state' => $validated['state'],
                'postal_code' => $validated['postal_code'],
                'country' => $validated['country'],
                'phone' => $validated['phone'],
                'is_default' => false,
            ]
        );
    }
}
