<?php

namespace App\Http\Controllers\Admin;

use App\Enums\OrderStatus;
use App\Events\ClientNotificationBroadcast;
use App\Exceptions\InvalidStateTransitionException;
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

        $originalStatus = $order->status?->value;
        $originalPaymentStatus = $order->payment_status?->value;

        $newStatus = OrderStatus::from($validated['status']);
        $newPaymentStatus = \App\Enums\PaymentStatus::from($validated['payment_status']);

        if ($originalStatus !== $newStatus->value) {
            try {
                $order->transitionStatus($newStatus);
            } catch (InvalidStateTransitionException $e) {
                return back()->with('error', $e->getMessage());
            }

            UserActivityLog::record(auth()->id(), 'order_status_changed', "Order #{$order->order_number} status changed: {$originalStatus} -> {$newStatus->value}");
            AdminNotification::notify('order_status_changed', [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'old_status' => $originalStatus,
                'new_status' => $newStatus->value,
                'message' => "Order #{$order->order_number} is now {$newStatus->label()}",
            ]);

            if ($order->user) {
                $order->user->notify(new OrderStatusChanged($order, $originalStatus, $newStatus->value));
                broadcast(new ClientNotificationBroadcast('order_status_changed', [
                    'order_number' => $order->order_number,
                    'order_id' => $order->id,
                    'old_status' => $originalStatus,
                    'new_status' => $newStatus->value,
                    'message' => "Order #{$order->order_number} is now {$newStatus->label()}",
                ], $order->user->id));
            }
        }

        if ($originalPaymentStatus !== $newPaymentStatus->value) {
            $order->update(['payment_status' => $newPaymentStatus]);

            UserActivityLog::record(auth()->id(), 'order_payment_changed', "Order #{$order->order_number} payment changed: {$originalPaymentStatus} -> {$newPaymentStatus->value}");
        }

        return back()->with('success', 'Order updated');
    }
}
