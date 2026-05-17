<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Payment;
use App\Notifications\OrderConfirmation;
use App\Services\PaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PaymentController extends Controller
{
    public function __construct(
        protected PaymentService $paymentService
    ) {}

    public function createCheckoutSession(Request $request)
    {
        $user = Auth::user();

        $request->validate([
            'order_id' => 'required|integer|exists:orders,id',
        ]);

        $order = Order::with('items.product')->findOrFail($request->order_id);

        if ($order->user_id !== $user->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        if (!$order->shipping_address) {
            return response()->json([
                'error' => 'Validation failed',
                'errors' => ['shipping_address' => ['Shipping address is required']],
            ], 422);
        }

        $existingPaidPayment = Payment::where('order_id', $order->id)
            ->where('status', Payment::STATUS_PAID)
            ->exists();

        if ($existingPaidPayment || $order->payment_status === 'paid') {
            return response()->json(['error' => 'Order already paid'], 400);
        }

        $existingExpiredPayment = Payment::where('order_id', $order->id)
            ->where('status', Payment::STATUS_EXPIRED)
            ->exists();

        if ($existingExpiredPayment || $order->payment_status === 'expired') {
            return response()->json(['error' => 'Order payment expired'], 400);
        }

        $itemsTotal = $order->items->sum(fn ($item) => $item->price * $item->quantity);
        if (abs($itemsTotal - (float) $order->subtotal) > 0.01) {
            return response()->json([
                'error' => 'Validation failed',
                'errors' => ['order_total' => ['Order total does not match items total']],
            ], 422);
        }

        foreach ($order->items as $item) {
            if ($item->product && $item->product->stock < $item->quantity) {
                return response()->json([
                    'error' => 'Validation failed',
                    'errors' => ['stock' => ["Insufficient stock for {$item->product->name}"]],
                ], 422);
            }
        }

        $existingPending = Payment::where('order_id', $order->id)
            ->where('status', Payment::STATUS_PENDING)
            ->first();

        if ($existingPending) {
            $existingPending->update(['status' => Payment::STATUS_EXPIRED]);
        }

        $provider = $order->payment_method ?: 'stripe';

        $payment = Payment::create([
            'order_id' => $order->id,
            'provider' => $provider,
            'status' => Payment::STATUS_PENDING,
            'attempts' => 0,
        ]);

        $order->update(['payment_status' => 'pending']);

        try {
            $sessionData = $provider === 'paypal'
                ? $this->paymentService->createPayPalOrder($order)
                : $this->paymentService->createStripeSession($order);
        } catch (\RuntimeException $e) {
            $payment->update(['status' => Payment::STATUS_FAILED]);
            $order->update(['payment_status' => 'failed']);
            return response()->json(['error' => $e->getMessage()], 502);
        }

        $payment->update([
            'provider_session_id' => $sessionData['session_id'],
            'provider_response' => $sessionData,
        ]);

        return response()->json($sessionData);
    }

    public function handleWebhook(Request $request)
    {
        if (!$this->paymentService->verifyWebhookSignature($request)) {
            return response()->json(['error' => 'Invalid signature'], 400);
        }

        $event = $request->json('type');
        $eventData = $request->json('data.object');

        if ($event === 'checkout.session.completed') {
            $this->handleCheckoutComplete($eventData['id'] ?? '');
        } elseif ($event === 'payment_intent.payment_failed') {
            $this->handlePaymentFailed($eventData['id'] ?? '');
        }

        return response()->json(['success' => true]);
    }

    protected function handleCheckoutComplete($sessionId)
    {
        $payment = Payment::with('order.items.product')
            ->where('provider_session_id', $sessionId)
            ->first();

        if (!$payment || $payment->isPaid()) {
            return;
        }

        $payment->update(['status' => Payment::STATUS_PAID]);

        $order = $payment->order;
        $order->update(['payment_status' => 'paid']);

        foreach ($order->items as $item) {
            $item->product?->decrement('stock', $item->quantity);
        }

        if ($order->user) {
            $order->user->notify(new OrderConfirmation($order));
        }
    }

    protected function handlePaymentFailed($paymentIntentId)
    {
        $payment = Payment::with('order.items.product')
            ->where(function ($q) use ($paymentIntentId) {
                $q->where('provider_transaction_id', $paymentIntentId)
                  ->orWhere('provider_session_id', $paymentIntentId);
            })
            ->first();

        if ($payment && !$payment->isFailed()) {
            $payment->update(['status' => Payment::STATUS_FAILED]);
            $payment->order->update(['payment_status' => 'failed']);

            foreach ($payment->order->items as $item) {
                $item->product?->increment('stock', $item->quantity);
            }
        }
    }

    public function retryPayment(Request $request, Order $order)
    {
        $user = Auth::user();

        if ($order->user_id !== $user->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $payment = $order->payments()->orderByDesc('created_at')->first();

        if (!$payment) {
            return response()->json(['error' => 'No payment found'], 404);
        }

        if ($payment->attempts >= 3) {
            return response()->json([
                'error' => 'Maximum retry attempts reached',
            ], 400);
        }

        $payment->increment('attempts');
        $payment->update(['status' => Payment::STATUS_PENDING]);
        $order->update(['payment_status' => 'pending']);

        try {
            $sessionData = $payment->provider === 'paypal'
                ? $this->paymentService->createPayPalOrder($order)
                : $this->paymentService->createStripeSession($order);
        } catch (\RuntimeException $e) {
            $payment->update(['status' => Payment::STATUS_FAILED]);
            $order->update(['payment_status' => 'failed']);
            return response()->json(['error' => $e->getMessage()], 502);
        }

        $payment->update([
            'provider_session_id' => $sessionData['session_id'],
            'provider_response' => $sessionData,
        ]);

        return response()->json($sessionData);
    }

    public function confirmSuccess(Request $request)
    {
        $user = Auth::user();
        $sessionId = $request->query('session_id');

        $payment = Payment::with('order.items.product')
            ->where('provider_session_id', $sessionId)
            ->whereHas('order', function ($q) use ($user) {
                $q->where('user_id', $user->id);
            })
            ->first();

        if (!$payment) {
            return response()->json(['error' => 'Payment not found'], 404);
        }

        if ($payment->isPaid()) {
            return response()->json([
                'order' => $payment->order->load('items'),
                'payment' => $payment,
                'message' => 'Payment already confirmed',
            ]);
        }

        $sessionData = $this->paymentService->retrieveStripeSession($sessionId);

        if (!$sessionData || ($sessionData['payment_status'] ?? 'unpaid') !== 'paid') {
            return response()->json(['error' => 'Payment not completed'], 400);
        }

        $payment->update([
            'status' => Payment::STATUS_PAID,
            'provider_response' => $sessionData,
        ]);
        $payment->order->update(['payment_status' => 'paid']);

        foreach ($payment->order->items as $item) {
            if ($item->product && $item->product->stock >= $item->quantity) {
                $item->product->decrement('stock', $item->quantity);
            }
        }

        if ($payment->order->user) {
            $payment->order->user->notify(new OrderConfirmation($payment->order));
        }

        return response()->json([
            'order' => $payment->order->load('items'),
            'payment' => $payment,
            'message' => 'Payment successful',
        ]);
    }
}
