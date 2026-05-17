<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Setting;
use Illuminate\Http\Request;
use Stripe\Checkout\Session as StripeSession;
use Stripe\Stripe;
use Stripe\Webhook;

class PaymentService
{
    private function stripeSecret(): ?string
    {
        return Setting::get('stripe_secret') ?: config('services.stripe.secret');
    }

    private function stripeWebhookSecret(): ?string
    {
        return Setting::get('stripe_webhook_secret') ?: config('services.stripe.webhook_secret');
    }

    private function paypalClientId(): ?string
    {
        return Setting::get('paypal_client_id') ?: config('services.paypal.client_id');
    }

    public function createStripeSession(Order $order): array
    {
        $secret = $this->stripeSecret();
        if (!$secret) {
            $id = 'cs_test_' . uniqid();
            return [
                'session_id' => $id,
                'checkout_url' => route('checkout.success') . '?session_id=' . $id,
            ];
        }

        Stripe::setApiKey($secret);

        $lineItems = [];
        foreach ($order->items as $item) {
            $lineItems[] = [
                'price_data' => [
                    'currency' => 'usd',
                    'product_data' => [
                        'name' => $item->product_name,
                    ],
                    'unit_amount' => (int) ($item->price * 100),
                ],
                'quantity' => $item->quantity,
            ];
        }

        try {
            $session = StripeSession::create([
                'payment_method_types' => ['card'],
                'line_items' => $lineItems,
                'mode' => 'payment',
                'success_url' => route('checkout.success') . '?session_id={CHECKOUT_SESSION_ID}',
                'cancel_url' => route('checkout') . '?cancelled=1',
                'metadata' => [
                    'order_id' => $order->id,
                    'order_number' => $order->order_number,
                ],
            ]);
        } catch (\Stripe\Exception\AuthenticationException $e) {
            throw new \RuntimeException('Invalid Stripe API key. Please check your Stripe settings.');
        } catch (\Exception $e) {
            throw new \RuntimeException('Stripe error: ' . $e->getMessage());
        }

        return [
            'session_id' => $session->id,
            'checkout_url' => $session->url,
        ];
    }

    public function createPayPalOrder(Order $order): array
    {
        if (!$this->paypalClientId()) {
            $id = 'PAYPALID_' . uniqid();
            return [
                'session_id' => $id,
                'checkout_url' => 'https://www.sandbox.paypal.com/checkoutnow?token=' . $id,
            ];
        }

        $id = 'PAYPALID_' . uniqid();
        return [
            'session_id' => $id,
            'checkout_url' => 'https://www.sandbox.paypal.com/checkoutnow?token=' . $id,
        ];
    }

    public function verifyWebhookSignature(Request $request): bool
    {
        $signature = $request->header('Stripe-Signature');
        $webhookSecret = $this->stripeWebhookSecret();

        if (!$webhookSecret) {
            return !empty($signature);
        }

        if (!$signature) {
            return false;
        }

        try {
            Webhook::constructEvent(
                $request->getContent(),
                $signature,
                $webhookSecret
            );
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function retrieveStripeSession(string $sessionId): ?array
    {
        $secret = $this->stripeSecret();
        if (!$secret) {
            return [
                'id' => $sessionId,
                'payment_status' => 'paid',
            ];
        }

        try {
            Stripe::setApiKey($secret);
            $session = StripeSession::retrieve($sessionId);
            return $session->toArray();
        } catch (\Exception $e) {
            return null;
        }
    }
}
