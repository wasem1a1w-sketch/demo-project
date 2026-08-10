<?php

namespace App\Http\Controllers\Admin;

use App\Enums\OrderStatus;
use App\Exceptions\InvalidStateTransitionException;
use App\Http\Controllers\Controller;
use App\Http\Requests\OrderStatusRequest;
use App\Models\Order;
use App\Models\UserActivityLog;
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
                $order->transitionStatus($newStatus, auth()->id());
            } catch (InvalidStateTransitionException $e) {
                return back()->with('error', $e->getMessage());
            }
        }

        if ($originalPaymentStatus !== $newPaymentStatus->value) {
            $order->update(['payment_status' => $newPaymentStatus]);

            UserActivityLog::record(auth()->id(), 'order_payment_changed', "Order #{$order->order_number} payment changed: {$originalPaymentStatus} -> {$newPaymentStatus->value}");
        }

        return back()->with('success', 'Order updated');
    }
}
