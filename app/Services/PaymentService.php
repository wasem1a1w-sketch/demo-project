<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
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

    private function paypalClientSecret(): ?string
    {
        return Setting::get('paypal_secret') ?: config('services.paypal.secret');
    }

    public function createStripeSession(Order $order): array
    {
        $secret = $this->stripeSecret();
        if (!$secret) {
            $id = 'cs_test_' . uniqid();
            return [
                'session_id' => $id,
                'checkout_url' => 'https://checkout.stripe.com/pay/' . $id,
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

        $clientId = $this->paypalClientId();
        $clientSecret = $this->paypalClientSecret();

        if (!$clientId || !$clientSecret) {
            throw new \Exception("PayPal credentials missing.");
        }

        // 1. Get Access Token using Basic Auth
        $authResponse = Http::asForm()
            ->withBasicAuth($clientId, $clientSecret)
            ->post('https://api-m.sandbox.paypal.com/v1/oauth2/token', [
                'grant_type' => 'client_credentials',
            ]);

        if ($authResponse->failed()) {
            throw new \Exception("PayPal Authentication Failed: " . $authResponse->body());
        }

        $accessToken = $authResponse->json('access_token');

        // 2. Build order payload with application_context
        $payload = [
            'intent' => 'CAPTURE',
            'application_context' => [
                'return_url' => route('paypal.capture'),
                'cancel_url' => route('paypal.cancel'),
                'user_action' => 'PAY_NOW',
                'shipping_preference' => 'GET_FROM_FILE',
            ],
            'purchase_units' => [[
                'amount' => [
                    'currency_code' => 'USD',
                    'value' => number_format($order->total, 2, '.', ''),
                ]
            ]]
        ];

        $orderResponse = Http::withToken($accessToken)
            ->asJson()
            ->post('https://api-m.sandbox.paypal.com/v2/checkout/orders', $payload);

        if ($orderResponse->failed()) {
            throw new \Exception("PayPal Order Creation Failed: " . $orderResponse->body());
        }

        // 3. Find the checkout ('approve') link from the response array
        $links = $orderResponse->json('links', []);
        foreach ($links as $link) {
            if ($link['rel'] === 'approve') {
                return [
                    'session_id' => $orderResponse->json('id'), // The real PayPal Order ID
                    'checkout_url' => $link['href'],           // The working checkout link
                ];
            }
        }

        throw new \Exception("Failed to locate PayPal approve link.");
    }

    public function capturePayPalOrder(Request $request): array
    {
        $orderId = $request->query('token');

        if (!$orderId) {
            throw new \Exception("Missing PayPal token in request.");
        }

        $clientId = $this->paypalClientId();
        $clientSecret = $this->paypalClientSecret();

        if (!$clientId || !$clientSecret) {
            return [
                'id' => $orderId,
                'status' => 'COMPLETED',
                'purchase_units' => [[
                    'payments' => [
                        'captures' => [[
                            'id' => 'PAYPAL_CAPTURE_' . uniqid(),
                            'status' => 'COMPLETED',
                        ]]
                    ]
                ]]
            ];
        }

        $authResponse = Http::asForm()
            ->withBasicAuth($clientId, $clientSecret)
            ->post('https://api-m.sandbox.paypal.com/v1/oauth2/token', [
                'grant_type' => 'client_credentials',
            ]);

        if ($authResponse->failed()) {
            throw new \Exception("PayPal Authentication Failed: " . $authResponse->body());
        }

        $accessToken = $authResponse->json('access_token');

        $captureResponse = Http::withToken($accessToken)
            ->withBody('{}', 'application/json')
            ->post("https://api-m.sandbox.paypal.com/v2/checkout/orders/{$orderId}/capture");

        if ($captureResponse->failed()) {
            throw new \Exception("PayPal Capture Failed: " . $captureResponse->body());
        }

        return $captureResponse->json();
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
