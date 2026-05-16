<?php

namespace App\Http\Controllers\Api;

use App\Events\ClientNotificationBroadcast;
use App\Http\Controllers\Controller;
use App\Models\Address;
use App\Models\AdminNotification;
use App\Models\Coupon;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Setting;
use App\Models\UserActivityLog;
use App\Notifications\OrderStatusChanged;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;

class OrderController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'company' => ['nullable', 'string', 'max:255'],
            'address1' => ['required', 'string', 'max:500'],
            'address2' => ['nullable', 'string', 'max:500'],
            'city' => ['required', 'string', 'max:255'],
            'state' => ['required', 'string', 'max:255'],
            'postal_code' => ['required', 'string', 'max:20'],
            'country' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:30'],
            'payment_method' => ['required', 'in:stripe,paypal'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'exists:products,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:99'],
            'subtotal' => ['required', 'numeric', 'min:0'],
            'tax' => ['required', 'numeric', 'min:0'],
            'shipping' => ['required', 'numeric', 'min:0'],
            'discount' => ['required', 'numeric', 'min:0'],
            'total' => ['required', 'numeric', 'min:0'],
            'coupon_id' => ['nullable', 'exists:coupons,id'],
        ]);

        $sessionId = Session::get('cart_session');

        $items = collect($validated['items']);

        if ($items->isEmpty()) {
            return response()->json(['message' => 'Cart is empty'], 422);
        }

        $user = $request->user();
        $lowStockProducts = [];

        $shippingRate = (float) (Setting::get('shipping_rate') ?? 15);
        $freeShippingThreshold = (float) (Setting::get('free_shipping_threshold') ?? 100);
        $taxRate = (float) (Setting::get('tax_rate') ?? 10);

        $order = DB::transaction(function () use ($validated, $sessionId, $items, $user, &$lowStockProducts, $shippingRate, $freeShippingThreshold, $taxRate) {
            $orderNumber = Order::generateOrderNumber();

            $recalculatedSubtotal = 0;
            $orderItems = [];

            foreach ($items as $item) {
                $product = Product::find($item['product_id']);
                if (! $product) continue;

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
                if ($product->stock <= 5 && !isset($lowStockProducts[$product->id])) {
                    $lowStockProducts[$product->id] = $product;
                }
            }

            $discount = (float) ($validated['discount'] ?? 0);
            $tax = max(0, ($recalculatedSubtotal - $discount) * $taxRate / 100);
            $shipping = ($recalculatedSubtotal - $discount) >= $freeShippingThreshold ? 0 : $shippingRate;
            $total = $recalculatedSubtotal - $discount + $tax + $shipping;

            $order = Order::create([
                'order_number' => $orderNumber,
                'user_id' => $user?->id,
                'status' => Order::STATUS_PENDING,
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
                'payment_status' => Order::PAYMENT_PENDING,
            ]);

            foreach ($orderItems as $orderItem) {
                OrderItem::create(array_merge($orderItem, ['order_id' => $order->id]));
            }

            if (!empty($validated['coupon_id'])) {
                Coupon::where('id', $validated['coupon_id'])->increment('used_count');
            }

            DB::table('cart_items')->where('session_id', $sessionId)->delete();
            Session::forget(['cart_coupon_id', 'cart_session']);

            return $order;
        });

        if ($request->user()) {
            $request->user()->addresses()->updateOrCreate(
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

        foreach ($lowStockProducts as $lowStockProduct) {
            AdminNotification::notify('low_stock', [
                'product_id' => $lowStockProduct->id,
                'product_name' => $lowStockProduct->name,
                'stock' => $lowStockProduct->stock,
                'message' => "Low stock: {$lowStockProduct->name} ({$lowStockProduct->stock} left)",
            ]);
        }

        return response()->json([
            'order_number' => $order->order_number,
            'message' => 'Order placed successfully',
        ]);
    }

    public function show($orderNumber)
    {
        $order = Order::with('items', 'coupon')
            ->where('order_number', $orderNumber)
            ->firstOrFail();

        return response()->json($order);
    }
}
