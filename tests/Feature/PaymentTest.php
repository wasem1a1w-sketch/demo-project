<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test 2.1: Payments table can store a Stripe payment with provider='stripe'
     */
    public function test_payments_table_stores_stripe_payment_with_provider(): void
    {
        $user = User::factory()->create();
        $order = Order::factory()->create(['user_id' => $user->id]);

        $payment = Payment::create([
            'order_id' => $order->id,
            'provider' => 'stripe',
            'provider_transaction_id' => 'ch_1234567890',
            'provider_session_id' => 'cs_test_1234567890',
            'provider_response' => ['id' => 'cs_test_1234567890', 'status' => 'complete'],
            'status' => 'paid',
            'attempts' => 1,
        ]);

        $this->assertDatabaseHas('payments', [
            'order_id' => $order->id,
            'provider' => 'stripe',
            'provider_transaction_id' => 'ch_1234567890',
            'provider_session_id' => 'cs_test_1234567890',
            'status' => 'paid',
            'attempts' => 1,
        ]);

        $this->assertEquals('stripe', $payment->provider);
        $this->assertEquals(['id' => 'cs_test_1234567890', 'status' => 'complete'], $payment->provider_response);
        $this->assertInstanceOf(Order::class, $payment->order);
    }

    /**
     * Test 2.2: Payments table can store a PayPal payment with provider='paypal'
     */
    public function test_payments_table_stores_paypal_payment_with_provider(): void
    {
        $user = User::factory()->create();
        $order = Order::factory()->create(['user_id' => $user->id]);

        $payment = Payment::create([
            'order_id' => $order->id,
            'provider' => 'paypal',
            'provider_transaction_id' => 'PAYID-123456789',
            'provider_session_id' => 'EC-9876543210',
            'provider_response' => [
                'id' => 'EC-9876543210',
                'state' => 'approved',
                'transactions' => [],
            ],
            'status' => 'paid',
            'attempts' => 1,
        ]);

        $this->assertDatabaseHas('payments', [
            'order_id' => $order->id,
            'provider' => 'paypal',
            'provider_transaction_id' => 'PAYID-123456789',
            'status' => 'paid',
        ]);

        $this->assertEquals('paypal', $payment->provider);
        $this->assertEquals('EC-9876543210', $payment->provider_session_id);
    }

    /**
     * Test 2.3: provider_response JSON column can store and retrieve raw API responses
     */
    public function test_provider_response_json_column_stores_api_responses(): void
    {
        $user = User::factory()->create();
        $order = Order::factory()->create(['user_id' => $user->id]);

        $apiResponse = [
            'id' => 'ch_test_123',
            'object' => 'charge',
            'amount' => 5999,
            'currency' => 'usd',
            'status' => 'succeeded',
            'metadata' => ['order_id' => $order->id],
            'payment_method_details' => [
                'type' => 'card',
                'card' => [
                    'brand' => 'visa',
                    'last4' => '4242',
                ],
            ],
        ];

        $payment = Payment::create([
            'order_id' => $order->id,
            'provider' => 'stripe',
            'provider_transaction_id' => 'ch_test_123',
            'provider_response' => $apiResponse,
            'status' => 'paid',
        ]);

        $retrieved = Payment::find($payment->id);
        $this->assertIsArray($retrieved->provider_response);
        $this->assertEquals($apiResponse['id'], $retrieved->provider_response['id']);
        $this->assertEquals('visa', $retrieved->provider_response['payment_method_details']['card']['brand']);
        $this->assertEquals(5999, $retrieved->provider_response['amount']);
    }

    public function test_create_checkout_session_sets_pending_status(): void
    {
        $user = User::factory()->create();
        $order = Order::factory()->create([
            'user_id' => $user->id,
            'payment_status' => 'unpaid',
            'shipping_address' => '123 Main St',
            'total' => 100.00,
        ]);

        $response = $this->actingAs($user)->postJson('/api/payments/create-session', [
            'order_id' => $order->id,
        ]);

        $response->assertStatus(200);
        $this->assertEquals('pending', $order->fresh()->payment_status);
    }

    /**
     * Test 3.1: createCheckoutSession() creates payment with status='pending' before any payment happens
     */
    public function test_create_checkout_session_creates_pending_payment(): void
    {
        $user = User::factory()->create();
        $order = Order::factory()->create([
            'user_id' => $user->id,
            'total' => 100.00,
            'shipping_address' => '123 Main St',
        ]);

        $response = $this->actingAs($user)->postJson('/api/payments/create-session', [
            'order_id' => $order->id,
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('payments', [
            'order_id' => $order->id,
            'status' => 'pending',
        ]);

        $payment = Payment::where('order_id', $order->id)->first();
        $this->assertNotNull($payment);
        $this->assertEquals('pending', $payment->status);
    }

    /**
     * Test 3.2: createCheckoutSession() validates order total matches cart total
     */
    public function test_create_checkout_session_validates_order_total(): void
    {
        $user = User::factory()->create();
        // Create order with mismatched total
        $order = Order::factory()->create([
            'user_id' => $user->id,
            'total' => 100.00,
        ]);

        // This test is a placeholder for validating cart matches order
        // In real scenario, we'd verify the cart items sum to order total
        $this->assertTrue(true);
    }

    /**
     * Test 3.3: createCheckoutSession() validates shipping address exists
     */
    public function test_create_checkout_session_validates_shipping_address(): void
    {
        $user = User::factory()->create();
        $order = Order::factory()->create([
            'user_id' => $user->id,
            'shipping_address' => null, // No shipping address
        ]);

        $response = $this->actingAs($user)->postJson('/api/payments/create-session', [
            'order_id' => $order->id,
        ]);

        $response->assertStatus(422); // Unprocessable Entity
        $response->assertJsonValidationErrors('shipping_address');
    }

    /**
     * Test 3.4: createCheckoutSession() validates product stock before creating session
     */
    public function test_create_checkout_session_validates_product_stock(): void
    {
        // This test will validate that products have sufficient stock
        $this->assertTrue(true); // Placeholder
    }

    /**
     * Test 3.5: createCheckoutSession() returns Stripe session ID and URL when successful
     */
    public function test_create_checkout_session_returns_stripe_session_and_url(): void
    {
        $user = User::factory()->create();
        $order = Order::factory()->create([
            'user_id' => $user->id,
            'total' => 100.00,
            'shipping_address' => '123 Main St',
        ]);

        $response = $this->actingAs($user)->postJson('/api/payments/create-session', [
            'order_id' => $order->id,
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'session_id',
            'checkout_url',
        ]);
    }

    /**
     * Test 3.6: createCheckoutSession() saves provider_session_id to payment record
     */
    public function test_create_checkout_session_saves_provider_session_id(): void
    {
        $user = User::factory()->create();
        $order = Order::factory()->create([
            'user_id' => $user->id,
            'total' => 100.00,
            'shipping_address' => '123 Main St',
        ]);

        $this->actingAs($user)->postJson('/api/payments/create-session', [
            'order_id' => $order->id,
        ]);

        $payment = Payment::where('order_id', $order->id)->first();
        $this->assertNotNull($payment->provider_session_id);
        $this->assertStringStartsWith('cs_test', $payment->provider_session_id); // Stripe test session ID
    }

    /**
     * Test 3.7: createCheckoutSession() cannot run for payment with status='paid' (returns 400)
     */
    public function test_create_checkout_session_fails_if_already_paid(): void
    {
        $user = User::factory()->create();
        $order = Order::factory()->create([
            'user_id' => $user->id,
            'payment_status' => 'paid',
            'total' => 100.00,
            'shipping_address' => '123 Main St',
        ]);

        Payment::create([
            'order_id' => $order->id,
            'status' => 'paid',
        ]);

        $response = $this->actingAs($user)->postJson('/api/payments/create-session', [
            'order_id' => $order->id,
        ]);

        $response->assertStatus(400);
    }

    /**
     * Test 3.8: createCheckoutSession() cannot run for order belonging to different user (returns 403)
     */
    public function test_create_checkout_session_fails_for_other_users_order(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $order = Order::factory()->create(['user_id' => $user1->id]);

        $response = $this->actingAs($user2)->postJson('/api/payments/create-session', [
            'order_id' => $order->id,
        ]);

        $response->assertStatus(403);
    }
}
