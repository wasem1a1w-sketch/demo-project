<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class PaymentTest extends TestCase
{
    use RefreshDatabase;

    private Category $category;

    protected function setUp(): void
    {
        parent::setUp();
        $this->category = Category::factory()->create(['is_active' => true]);
    }

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
        $product = Product::factory()->create(['price' => 100.00, 'stock' => 10, 'is_active' => true, 'category_id' => $this->category->id]);
        $order = Order::factory()->create([
            'user_id' => $user->id,
            'payment_status' => 'unpaid',
            'shipping_address' => '123 Main St',
            'total' => 100.00,
        ]);
        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'product_name' => $product->name,
            'price' => 100.00,
            'quantity' => 1,
            'subtotal' => 100.00,
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
        $product = Product::factory()->create(['price' => 100.00, 'stock' => 10, 'is_active' => true, 'category_id' => $this->category->id]);
        $order = Order::factory()->create([
            'user_id' => $user->id,
            'total' => 100.00,
            'shipping_address' => '123 Main St',
        ]);
        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'product_name' => $product->name,
            'price' => 100.00,
            'quantity' => 1,
            'subtotal' => 100.00,
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
     * Test 3.2: createCheckoutSession() validates order total matches cart items total
     */
    public function test_create_checkout_session_validates_order_total(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create(['price' => 50.00, 'stock' => 10, 'is_active' => true, 'category_id' => $this->category->id]);
        $order = Order::factory()->create([
            'user_id' => $user->id,
            'total' => 100.00,
            'shipping_address' => '123 Main St',
        ]);
        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'product_name' => $product->name,
            'price' => 50.00,
            'quantity' => 1,
            'subtotal' => 50.00,
        ]);

        $response = $this->actingAs($user)->postJson('/api/payments/create-session', [
            'order_id' => $order->id,
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('order_total');
    }

    /**
     * Test 3.3: createCheckoutSession() validates shipping address exists
     */
    public function test_create_checkout_session_validates_shipping_address(): void
    {
        $user = User::factory()->create();
        $order = Order::factory()->create([
            'user_id' => $user->id,
            'shipping_address' => null,
        ]);

        $response = $this->actingAs($user)->postJson('/api/payments/create-session', [
            'order_id' => $order->id,
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('shipping_address');
    }

    /**
     * Test 3.4: createCheckoutSession() validates product stock before creating session
     */
    public function test_create_checkout_session_validates_product_stock(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create(['stock' => 0, 'is_active' => true, 'category_id' => $this->category->id]);
        $order = Order::factory()->create([
            'user_id' => $user->id,
            'total' => 50.00,
            'subtotal' => 50.00,
            'shipping_address' => '123 Main St',
        ]);
        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'product_name' => $product->name,
            'price' => 50.00,
            'quantity' => 1,
            'subtotal' => 50.00,
        ]);

        $response = $this->actingAs($user)->postJson('/api/payments/create-session', [
            'order_id' => $order->id,
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('stock');
    }

    /**
     * Test 3.5: createCheckoutSession() returns Stripe session ID and URL when successful
     */
    public function test_create_checkout_session_returns_stripe_session_and_url(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create(['price' => 50.00, 'stock' => 10, 'is_active' => true, 'category_id' => $this->category->id]);
        $order = Order::factory()->create([
            'user_id' => $user->id,
            'total' => 50.00,
            'subtotal' => 50.00,
            'shipping_address' => '123 Main St',
        ]);
        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'product_name' => $product->name,
            'price' => 50.00,
            'quantity' => 1,
            'subtotal' => 50.00,
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
        $product = Product::factory()->create(['price' => 50.00, 'stock' => 10, 'is_active' => true, 'category_id' => $this->category->id]);
        $order = Order::factory()->create([
            'user_id' => $user->id,
            'total' => 50.00,
            'subtotal' => 50.00,
            'shipping_address' => '123 Main St',
        ]);
        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'product_name' => $product->name,
            'price' => 50.00,
            'quantity' => 1,
            'subtotal' => 50.00,
        ]);

        $this->actingAs($user)->postJson('/api/payments/create-session', [
            'order_id' => $order->id,
        ]);

        $payment = Payment::where('order_id', $order->id)->first();
        $this->assertNotNull($payment->provider_session_id);
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

    /**
     * Test 4.1: createCheckoutSession() creates PayPal session with provider='paypal'
     */
    public function test_create_checkout_session_creates_paypal_session(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create(['price' => 50.00, 'stock' => 10, 'is_active' => true, 'category_id' => $this->category->id]);
        $order = Order::factory()->create([
            'user_id' => $user->id,
            'total' => 50.00,
            'subtotal' => 50.00,
            'shipping_address' => '123 Main St',
            'payment_method' => 'paypal',
        ]);
        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'product_name' => $product->name,
            'price' => 50.00,
            'quantity' => 1,
            'subtotal' => 50.00,
        ]);

        $response = $this->actingAs($user)->postJson('/api/payments/create-session', [
            'order_id' => $order->id,
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'session_id',
            'checkout_url',
        ]);

        $payment = Payment::where('order_id', $order->id)->first();
        $this->assertEquals('paypal', $payment->provider);
    }

    /**
     * Test 6.1: Webhook rejects request with missing signature
     */
    public function test_webhook_rejects_missing_signature(): void
    {
        $response = $this->postJson('/api/payments/webhook', [
            'type' => 'checkout.session.completed',
            'data' => ['object' => ['id' => 'cs_test_123']],
        ]);

        $response->assertStatus(400);
    }

    /**
     * Test 6.2: Webhook checkout.session.completed updates payment to paid
     */
    public function test_webhook_checkout_completed_updates_payment_to_paid(): void
    {
        $user = User::factory()->create();
        $order = Order::factory()->create([
            'user_id' => $user->id,
            'payment_status' => 'pending',
            'total' => 100.00,
            'shipping_address' => '123 Main St',
        ]);
        $payment = Payment::create([
            'order_id' => $order->id,
            'provider' => 'stripe',
            'provider_session_id' => 'cs_test_abc123',
            'status' => Payment::STATUS_PENDING,
        ]);

        $response = $this->postJson('/api/payments/webhook', [
            'type' => 'checkout.session.completed',
            'data' => ['object' => ['id' => 'cs_test_abc123']],
        ], ['Stripe-Signature' => 'valid_test_signature']);

        $response->assertStatus(200);
        $this->assertEquals('paid', $payment->fresh()->status);
        $this->assertEquals('paid', $order->fresh()->payment_status);
    }

    /**
     * Test 6.3: Webhook checkout.session.completed decrements product stock
     */
    public function test_webhook_checkout_completed_decrements_stock(): void
    {
        Mail::fake();

        $user = User::factory()->create();
        $product = Product::factory()->create(['stock' => 10, 'is_active' => true, 'category_id' => $this->category->id]);
        $order = Order::factory()->create([
            'user_id' => $user->id,
            'payment_status' => 'pending',
            'total' => 50.00,
            'shipping_address' => '123 Main St',
        ]);
        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'product_name' => $product->name,
            'price' => 50.00,
            'quantity' => 3,
            'subtotal' => 150.00,
        ]);
        $payment = Payment::create([
            'order_id' => $order->id,
            'provider' => 'stripe',
            'provider_session_id' => 'cs_test_stock123',
            'status' => Payment::STATUS_PENDING,
        ]);

        $this->postJson('/api/payments/webhook', [
            'type' => 'checkout.session.completed',
            'data' => ['object' => ['id' => 'cs_test_stock123']],
        ], ['Stripe-Signature' => 'valid_test_signature']);

        $this->assertEquals(10, $product->fresh()->stock);
    }

    /**
     * Test 6.4: Webhook checkout.session.completed marks payment as paid without error
     */
    public function test_webhook_checkout_completed_marks_paid(): void
    {
        Mail::fake();

        $user = User::factory()->create();
        $product = Product::factory()->create(['stock' => 10, 'is_active' => true, 'category_id' => $this->category->id]);
        $order = Order::factory()->create([
            'user_id' => $user->id,
            'payment_status' => 'pending',
            'total' => 50.00,
            'shipping_address' => '123 Main St',
        ]);
        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'product_name' => $product->name,
            'price' => 50.00,
            'quantity' => 1,
            'subtotal' => 50.00,
        ]);
        Payment::create([
            'order_id' => $order->id,
            'provider' => 'stripe',
            'provider_session_id' => 'cs_test_cart123',
            'status' => Payment::STATUS_PENDING,
        ]);

        $response = $this->postJson('/api/payments/webhook', [
            'type' => 'checkout.session.completed',
            'data' => ['object' => ['id' => 'cs_test_cart123']],
        ], ['Stripe-Signature' => 'valid_test_signature']);

        $response->assertStatus(200);
        $this->assertEquals('paid', $payment = Payment::where('provider_session_id', 'cs_test_cart123')->first()->status);
    }

    /**
     * Test 6.5: Webhook checkout.session.completed sends order confirmation notification
     */
    public function test_webhook_checkout_completed_sends_confirmation(): void
    {
        Notification::fake();

        $user = User::factory()->create();
        $product = Product::factory()->create(['stock' => 10, 'is_active' => true, 'category_id' => $this->category->id]);
        $order = Order::factory()->create([
            'user_id' => $user->id,
            'payment_status' => 'pending',
            'total' => 50.00,
            'shipping_address' => '123 Main St',
        ]);
        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'product_name' => $product->name,
            'price' => 50.00,
            'quantity' => 1,
            'subtotal' => 50.00,
        ]);
        Payment::create([
            'order_id' => $order->id,
            'provider' => 'stripe',
            'provider_session_id' => 'cs_test_notify123',
            'status' => Payment::STATUS_PENDING,
        ]);

        $this->postJson('/api/payments/webhook', [
            'type' => 'checkout.session.completed',
            'data' => ['object' => ['id' => 'cs_test_notify123']],
        ], ['Stripe-Signature' => 'valid_test_signature']);

        Notification::assertSentTo(
            $user,
            \App\Notifications\OrderConfirmation::class,
            function ($notification) use ($order) {
                return $notification->order->id === $order->id;
            }
        );
    }

    /**
     * Test 6.6: Webhook payment_intent.payment_failed updates status to failed
     */
    public function test_webhook_payment_failed_updates_status_to_failed(): void
    {
        $user = User::factory()->create();
        $order = Order::factory()->create([
            'user_id' => $user->id,
            'payment_status' => 'pending',
            'total' => 100.00,
            'shipping_address' => '123 Main St',
        ]);
        $payment = Payment::create([
            'order_id' => $order->id,
            'provider' => 'stripe',
            'provider_transaction_id' => 'pi_fail_123',
            'status' => Payment::STATUS_PENDING,
        ]);

        $response = $this->postJson('/api/payments/webhook', [
            'type' => 'payment_intent.payment_failed',
            'data' => ['object' => ['id' => 'pi_fail_123']],
        ], ['Stripe-Signature' => 'valid_test_signature']);

        $response->assertStatus(200);
        $this->assertEquals('failed', $payment->fresh()->status);
        $this->assertEquals('failed', $order->fresh()->payment_status);
    }

    /**
     * Test 6.7: Webhook payment_intent.payment_failed restores product stock
     */
    public function test_webhook_payment_failed_restores_stock(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create(['stock' => 5, 'is_active' => true, 'category_id' => $this->category->id]);
        $order = Order::factory()->create([
            'user_id' => $user->id,
            'payment_status' => 'pending',
            'total' => 100.00,
            'shipping_address' => '123 Main St',
        ]);
        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'product_name' => $product->name,
            'price' => 100.00,
            'quantity' => 2,
            'subtotal' => 200.00,
        ]);
        $payment = Payment::create([
            'order_id' => $order->id,
            'provider' => 'stripe',
            'provider_transaction_id' => 'pi_restore_123',
            'status' => Payment::STATUS_PENDING,
        ]);

        $this->postJson('/api/payments/webhook', [
            'type' => 'payment_intent.payment_failed',
            'data' => ['object' => ['id' => 'pi_restore_123']],
        ], ['Stripe-Signature' => 'valid_test_signature']);

        $this->assertEquals(7, $product->fresh()->stock);
    }

    /**
     * Test 6.8: Webhook ignores unknown event types
     */
    public function test_webhook_ignores_unknown_event(): void
    {
        $response = $this->postJson('/api/payments/webhook', [
            'type' => 'charge.updated',
            'data' => ['object' => ['id' => 'ch_xyz']],
        ], ['Stripe-Signature' => 'valid_test_signature']);

        $response->assertStatus(200);
        $this->assertEquals(0, Payment::count());
    }

    /**
     * Test 7.1: confirmSuccess verifies with Stripe and marks payment as paid
     */
    public function test_confirm_success_verifies_and_marks_paid(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create(['stock' => 10, 'is_active' => true, 'category_id' => $this->category->id]);
        $order = Order::factory()->create([
            'user_id' => $user->id,
            'payment_status' => 'pending',
            'total' => 100.00,
            'subtotal' => 100.00,
            'shipping_address' => '123 Main St',
        ]);
        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'product_name' => $product->name,
            'price' => 100.00,
            'quantity' => 1,
            'subtotal' => 100.00,
        ]);
        $payment = Payment::create([
            'order_id' => $order->id,
            'provider' => 'stripe',
            'provider_session_id' => 'cs_test_success_123',
            'status' => Payment::STATUS_PENDING,
        ]);

        $response = $this->actingAs($user)->getJson('/api/payments/success?session_id=cs_test_success_123');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'order',
            'payment',
            'message',
        ]);
        $this->assertEquals('paid', $payment->fresh()->status);
        $this->assertEquals('paid', $order->fresh()->payment_status);
        $this->assertEquals(10, $product->fresh()->stock);
    }

    /**
     * Test 7.2: confirmSuccess returns 404 for invalid session_id
     */
    public function test_confirm_success_returns_404_for_invalid_session(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->getJson('/api/payments/success?session_id=cs_test_nonexistent');

        $response->assertStatus(404);
    }



    /**
     * Test 7.3: retryPayment fails for other user's order
     */
    public function test_retry_payment_fails_for_other_user(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $order = Order::factory()->create(['user_id' => $user1->id]);

        $response = $this->actingAs($user2)->postJson("/api/payments/{$order->id}/retry", [
            'order_id' => $order->id,
        ]);

        $response->assertStatus(403);
    }

    /**
     * Test 7.4: retryPayment increments attempts counter
     */
    public function test_retry_payment_increments_attempts(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create(['price' => 50.00, 'stock' => 10, 'is_active' => true, 'category_id' => $this->category->id]);
        $order = Order::factory()->create([
            'user_id' => $user->id,
            'payment_status' => 'failed',
            'total' => 50.00,
            'subtotal' => 50.00,
            'shipping_address' => '123 Main St',
        ]);
        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'product_name' => $product->name,
            'price' => 50.00,
            'quantity' => 1,
            'subtotal' => 50.00,
        ]);
        Payment::create([
            'order_id' => $order->id,
            'provider' => 'stripe',
            'status' => Payment::STATUS_FAILED,
            'attempts' => 1,
        ]);

        $response = $this->actingAs($user)->postJson("/api/payments/{$order->id}/retry", [
            'order_id' => $order->id,
        ]);

        $response->assertStatus(200);
        $payment = $order->payments()->orderByDesc('created_at')->first();
        $this->assertEquals(2, $payment->attempts);
    }

    /**
     * Test 7.5: retryPayment rejects when max attempts reached
     */
    public function test_retry_payment_rejects_max_attempts(): void
    {
        $user = User::factory()->create();
        $order = Order::factory()->create([
            'user_id' => $user->id,
            'payment_status' => 'failed',
            'total' => 100.00,
            'shipping_address' => '123 Main St',
        ]);
        Payment::create([
            'order_id' => $order->id,
            'provider' => 'stripe',
            'status' => Payment::STATUS_FAILED,
            'attempts' => 3,
        ]);

        $response = $this->actingAs($user)->postJson("/api/payments/{$order->id}/retry", [
            'order_id' => $order->id,
        ]);

        $response->assertStatus(400);
        $response->assertJson(['error' => 'Maximum retry attempts reached']);
    }

    /**
     * Test 7.6: retryPayment reuses existing payment, does not create duplicate
     */
    public function test_retry_payment_reuses_existing_payment(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create(['price' => 50.00, 'stock' => 10, 'is_active' => true, 'category_id' => $this->category->id]);
        $order = Order::factory()->create([
            'user_id' => $user->id,
            'payment_status' => 'failed',
            'total' => 50.00,
            'subtotal' => 50.00,
            'shipping_address' => '123 Main St',
        ]);
        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'product_name' => $product->name,
            'price' => 50.00,
            'quantity' => 1,
            'subtotal' => 50.00,
        ]);
        $originalPayment = Payment::create([
            'order_id' => $order->id,
            'provider' => 'stripe',
            'status' => Payment::STATUS_FAILED,
            'attempts' => 1,
        ]);

        $this->actingAs($user)->postJson("/api/payments/{$order->id}/retry", [
            'order_id' => $order->id,
        ]);

        $payments = $order->payments()->get();
        $this->assertCount(1, $payments, 'Retry should not create a new payment record');
        $this->assertEquals(2, $payments->first()->fresh()->attempts);
    }

    /**
     * Test 8.1: createCheckoutSession fails for order with expired payment
     */
    public function test_create_checkout_session_fails_for_expired_payment(): void
    {
        $user = User::factory()->create();
        $order = Order::factory()->create([
            'user_id' => $user->id,
            'payment_status' => 'expired',
            'total' => 100.00,
            'shipping_address' => '123 Main St',
        ]);
        Payment::create([
            'order_id' => $order->id,
            'provider' => 'stripe',
            'status' => Payment::STATUS_EXPIRED,
            'expires_at' => now()->subHour(),
        ]);

        $response = $this->actingAs($user)->postJson('/api/payments/create-session', [
            'order_id' => $order->id,
        ]);

        $response->assertStatus(400);
    }
}
