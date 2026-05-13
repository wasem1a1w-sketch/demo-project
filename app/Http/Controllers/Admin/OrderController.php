<?php

namespace App\Http\Controllers\Admin;

use App\Events\ClientNotificationBroadcast;
use App\Http\Controllers\Controller;
use App\Models\AdminNotification;
use App\Models\Order;
use App\Notifications\OrderStatusChanged;
use Illuminate\Http\Request;
use Inertia\Inertia;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $orders = Order::with('user')
            ->when($request->status, fn ($q, $v) => $q->where('status', $v))
            ->orderByDesc('id')
            ->paginate(20);

        return Inertia::render('Admin/Orders/Index', ['orders' => $orders]);
    }

    public function show($id)
    {
        $order = Order::with('items', 'coupon', 'user')->findOrFail($id);

        return Inertia::render('Admin/Orders/Show', ['order' => $order]);
    }

    public function update(Request $request, $id)
    {
        $order = Order::findOrFail($id);

        $validated = $request->validate([
            'status' => 'required|in:pending,processing,shipped,delivered,cancelled',
            'payment_status' => 'required|in:pending,paid,failed,refunded',
        ]);

        $originalStatus = $order->status;

        $order->update($validated);

        if ($originalStatus !== $validated['status']) {
            AdminNotification::notify('order_status_changed', [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'old_status' => $originalStatus,
                'new_status' => $validated['status'],
                'message' => "Order #{$order->order_number} is now {$validated['status']}",
            ]);

            if ($order->user) {
                $order->user->notify(new OrderStatusChanged($order, $originalStatus, $validated['status']));
                broadcast(new ClientNotificationBroadcast('order_status_changed', [
                    'order_number' => $order->order_number,
                    'order_id' => $order->id,
                    'old_status' => $originalStatus,
                    'new_status' => $validated['status'],
                    'message' => "Order #{$order->order_number} is now {$validated['status']}",
                ], $order->user->id));
            }
        }

        if ($validated['status'] === 'cancelled' && $originalStatus !== 'cancelled') {
            $order->load('items.product');
            foreach ($order->items as $item) {
                if ($item->product) {
                    $item->product->increment('stock', $item->quantity);
                }
            }
        }

        return back();
    }
}
