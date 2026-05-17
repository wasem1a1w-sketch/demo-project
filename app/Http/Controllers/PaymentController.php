<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PaymentController extends Controller
{
    /**
     * Create a checkout session for an order.
     * 
     * Tests: 3.1, 3.2, 3.3, 3.4, 3.5, 3.6, 3.7, 3.8
     */
    public function createCheckoutSession(Request $request)
    {
        $user = Auth::user();
        
        $request->validate([
            'order_id' => 'required|integer|exists:orders,id',
        ]);

        $order = Order::findOrFail($request->order_id);

        // Test 3.8: Verify order belongs to current user
        if ($order->user_id !== $user->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        // Test 3.3: Validate shipping address exists
        if (!$order->shipping_address) {
            return response()->json([
                'error' => 'Validation failed',
                'errors' => ['shipping_address' => ['Shipping address is required']],
            ], 422);
        }

        // Check if order already has a paid payment
        $existingPaidPayment = Payment::where('order_id', $order->id)
            ->where('status', Payment::STATUS_PAID)
            ->exists();

        // Test 3.7: Fail if already paid
        if ($existingPaidPayment || $order->payment_status === 'paid') {
            return response()->json(['error' => 'Order already paid'], 400);
        }

        // Test 3.1: Create payment with status='pending'
        $payment = Payment::create([
            'order_id' => $order->id,
            'provider' => 'stripe',
            'status' => Payment::STATUS_PENDING,
            'attempts' => 0,
        ]);

        // Update order payment status
        $order->update(['payment_status' => 'pending']);

        // Test 3.5 & 3.6: Generate Stripe session and save session ID
        // For now, using mock session ID for testing
        $sessionId = 'cs_test_' . uniqid();
        $payment->update([
            'provider_session_id' => $sessionId,
        ]);

        // Test 3.5: Return session ID and checkout URL
        return response()->json([
            'session_id' => $sessionId,
            'checkout_url' => 'https://checkout.stripe.com/pay/' . $sessionId,
        ]);
    }

    /**
     * Handle Stripe webhook events.
     * 
     * Tests: 6.1, 6.2, 6.3, 6.4, 6.5, 6.6, 6.7, 6.8
     */
    public function handleWebhook(Request $request)
    {
        // Test 6.1: Validate webhook signature
        // Placeholder for webhook verification
        
        $event = $request->json('type');
        
        if ($event === 'checkout.session.completed') {
            $sessionId = $request->json('data.object.id');
            $this->handleCheckoutComplete($sessionId);
        } elseif ($event === 'payment_intent.payment_failed') {
            $paymentIntentId = $request->json('data.object.id');
            $this->handlePaymentFailed($paymentIntentId);
        }

        return response()->json(['success' => true]);
    }

    /**
     * Handle successful checkout.
     * 
     * Tests: 6.2, 6.3, 6.4, 6.5
     */
    protected function handleCheckoutComplete($sessionId)
    {
        // Find payment by session ID
        $payment = Payment::where('provider_session_id', $sessionId)->first();
        
        if (!$payment) {
            return;
        }

        // Test 6.2: Update payment status to 'paid'
        $payment->update(['status' => Payment::STATUS_PAID]);

        // Update order payment status
        $order = $payment->order;
        $order->update(['payment_status' => 'paid']);

        // Test 6.3: Decrease product stock
        foreach ($order->items as $item) {
            $item->product->decrement('stock', $item->quantity);
        }

        // Test 6.4: Clear user's cart
        $order->user->cart_items()->delete();

        // Test 6.5: Trigger order confirmation email (placeholder)
        // Mail::queue(new OrderConfirmation($order));
    }

    /**
     * Handle payment failure.
     * 
     * Tests: 6.6, 6.7
     */
    protected function handlePaymentFailed($paymentIntentId)
    {
        // Test 6.6: Update payment status to 'failed'
        $payment = Payment::where('provider_transaction_id', $paymentIntentId)->first();
        
        if ($payment) {
            $payment->update(['status' => Payment::STATUS_FAILED]);
            $payment->order->update(['payment_status' => 'failed']);

            // Test 6.7: Release reserved stock
            foreach ($payment->order->items as $item) {
                // Stock restoration logic would go here
            }
        }
    }

    /**
     * Retry payment for an order.
     * 
     * Tests: 7.3, 7.4, 7.5
     */
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

        // Test 7.4: Increment attempts counter
        $payment->increment('attempts');

        // Test 7.5: Check if max attempts reached
        if ($payment->attempts >= 3) {
            return response()->json([
                'error' => 'Maximum retry attempts reached',
            ], 400);
        }

        // Create new session
        return $this->createCheckoutSession($request);
    }

    /**
     * Confirm successful payment and redirect user.
     * 
     * Tests: 7.1
     */
    public function confirmSuccess(Request $request)
    {
        $user = Auth::user();
        $sessionId = $request->query('session_id');

        $payment = Payment::where('provider_session_id', $sessionId)
            ->whereHas('order', function ($q) use ($user) {
                $q->where('user_id', $user->id);
            })
            ->first();

        if (!$payment) {
            return response()->json(['error' => 'Payment not found'], 404);
        }

        // Return order confirmation
        return response()->json([
            'order' => $payment->order,
            'payment' => $payment,
            'message' => 'Payment successful',
        ]);
    }
}
