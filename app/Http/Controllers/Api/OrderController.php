<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\PlaceOrderRequest;
use App\Models\Order;
use App\Services\OrderService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

class OrderController extends Controller
{
    public function __construct(
        protected OrderService $orderService
    ) {}

    public function store(PlaceOrderRequest $request)
    {
        $validated = $request->validated();
        $sessionId = Session::get('cart_session');
        $user = $request->user();

        $order = $this->orderService->placeOrder($validated, $sessionId ?: null, $user);

        $this->orderService->saveShippingAddress($validated, $user);

        return response()->json([
            'order_id' => $order->id,
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
