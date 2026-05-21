<?php

namespace App\Http\Controllers\Admin;

use App\Events\ClientNotificationBroadcast;
use App\Http\Controllers\Controller;
use App\Http\Requests\OrderStatusRequest;
use App\Models\AdminNotification;
use App\Models\Order;
use App\Models\UserActivityLog;
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

    public function update(OrderStatusRequest $request, $id)
    {
        $order = Order::findOrFail($id);
        $validated = $request->validated();

        $originalStatus = $order->status;
        $originalPaymentStatus = $order->payment_status;

        $order->update($validated);

        if ($originalStatus !== $validated['status']) {
            UserActivityLog::record(auth()->id(), 'order_status_changed', "Order #{$order->order_number} status changed: {$originalStatus} -> {$validated['status']}");
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

        if ($originalPaymentStatus !== $validated['payment_status']) {
            UserActivityLog::record(auth()->id(), 'order_payment_changed', "Order #{$order->order_number} payment changed: {$originalPaymentStatus} -> {$validated['payment_status']}");
        }

        if ($validated['status'] === 'cancelled' && $originalStatus !== 'cancelled') {
            $order->cancel();
        }

        return back();
    }
}
