<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
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

        $order->update($validated);

        return back();
    }
}
