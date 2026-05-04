<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;
use Inertia\Inertia;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $orders = Order::with('items')
            ->where('user_id', $request->user()->id)
            ->orderByDesc('id')
            ->paginate(10);

        return Inertia::render('User/Orders/Index', [
            'orders' => $orders,
        ]);
    }

    public function show($orderNumber)
    {
        $order = Order::with('items', 'coupon')
            ->where('order_number', $orderNumber)
            ->firstOrFail();

        return Inertia::render('Checkout/Success', [
            'order' => $order,
        ]);
    }
}
