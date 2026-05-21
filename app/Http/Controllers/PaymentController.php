<?php

namespace App\Http\Controllers;

use App\Http\Requests\PaymentRequest;
use App\Models\Order;
use App\Models\Payment;
use App\Services\PaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PaymentController extends Controller
{
    public function __construct(
        protected PaymentService $paymentService
    ) {}

    public function createCheckoutSession(PaymentRequest $request)
    {
        $user = Auth::user();
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

        if ($this->paymentService->isOrderAlreadyPaid($order)) {
            return response()->json(['error' => 'Order already paid'], 400);
        }

        if ($this->paymentService->isOrderExpired($order)) {
            return response()->json(['error' => 'Order payment expired'], 400);
        }

        $itemsTotal = $order->items->sum(fn ($item) => $item->price * $item->quantity);
        if (abs($itemsTotal - (float) $order->subtotal) > 0.01) {
            return response()->json([
                'error' => 'Validation failed',
                'errors' => ['order_total' => ['Order total does not match items total']],
            ], 422);
        }

        $stockError = $this->paymentService->validateItemStock($order);
        if ($stockError) {
            return response()->json([
                'error' => 'Validation failed',
                'errors' => ['stock' => [$stockError]],
            ], 422);
        }

        $provider = $order->payment_method ?: 'stripe';

        try {
            $payment = $this->paymentService->processCheckout($order, $provider);

            return response()->json($payment->provider_response);
        } catch (\RuntimeException $e) {
            return response()->json(['error' => $e->getMessage()], 502);
        }
    }

    public function handleWebhook(Request $request)
    {
        if (!$this->paymentService->verifyWebhookSignature($request)) {
            return response()->json(['error' => 'Invalid signature'], 400);
        }

        $event = $request->json('type');
        $eventData = $request->json('data.object');

        if ($event === 'checkout.session.completed') {
            $this->paymentService->handleCheckoutComplete($eventData['id'] ?? '');
        } elseif ($event === 'payment_intent.payment_failed') {
            $this->paymentService->handlePaymentFailed($eventData['id'] ?? '');
        }

        return response()->json(['success' => true]);
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
            return response()->json(['error' => 'Maximum retry attempts reached'], 400);
        }

        try {
            $sessionData = $this->paymentService->retryPayment($order, $payment, $payment->provider);

            $payment->update([
                'provider_session_id' => $sessionData['session_id'],
                'provider_response' => $sessionData,
            ]);

            return response()->json($sessionData);
        } catch (\RuntimeException $e) {
            return response()->json(['error' => $e->getMessage()], 502);
        }
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

        $this->paymentService->confirmPayment($payment, $sessionData);

        return response()->json([
            'order' => $payment->order->load('items'),
            'payment' => $payment,
            'message' => 'Payment successful',
        ]);
    }

    public function handlePayPalCapture(Request $request)
    {
        $user = Auth::user();
        $token = $request->query('token');

        if (!$token) {
            return response()->json(['error' => 'Missing PayPal token'], 400);
        }

        $payment = Payment::with('order.items.product')
            ->where('provider_session_id', $token)
            ->whereHas('order', fn($q) => $q->where('user_id', $user->id))
            ->first();

        if (!$payment) {
            return response()->json(['error' => 'Payment not found'], 404);
        }

        if ($payment->isPaid()) {
            return redirect()->route('checkout.success');
        }

        try {
            $captureData = $this->paymentService->capturePayPalOrder($request);
        } catch (\Exception $e) {
            $payment->update(['status' => Payment::STATUS_FAILED]);
            $payment->order->update(['payment_status' => 'failed']);
            return response()->json(['error' => $e->getMessage()], 502);
        }

        $this->paymentService->handlePayPalCapture($payment, $captureData);

        if (($captureData['status'] ?? '') === 'COMPLETED') {
            return redirect()->route('checkout.success');
        }

        return response()->json(['error' => 'PayPal payment not completed'], 400);
    }

    public function handlePayPalCancel(Request $request)
    {
        $this->paymentService->handlePayPalCancel($request->query('token'));

        return redirect()->route('checkout');
    }
}
