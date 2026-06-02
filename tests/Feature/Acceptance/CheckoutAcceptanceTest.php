<?php

namespace Tests\Feature\Acceptance;

use App\Models\Category;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class CheckoutAcceptanceTest extends TestCase
{
    use RefreshDatabase;

    private Category $category;

    protected function setUp(): void
    {
        parent::setUp();
        $this->category = Category::factory()->create(['is_active' => true]);
    }

    public function test_full_stripe_checkout_flow(): void
    {
        Notification::fake();
        $user = User::factory()->create();
        $product = Product::factory()->create([
            'price' => 29.99,
            'stock' => 10,
            'is_active' => true,
            'category_id' => $this->category->id,
        ]);

        $orderResponse = $this->actingAs($user)->postJson('/api/orders', [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'address1' => '123 Main St',
            'address2' => 'Apt 4B',
            'city' => 'New York',
            'state' => 'NY',
            'postal_code' => '10001',
            'country' => 'US',
            'phone' => '+1234567890',
            'payment_method' => 'stripe',
            'items' => [['product_id' => $product->id, 'quantity' => 2, 'price' => 29.99]],
            'subtotal' => 59.98,
            'tax' => 5.00,
            'shipping' => 0,
            'discount' => 0,
            'total' => 64.98,
        ]);

        $orderResponse->assertStatus(200);
        $orderId = $orderResponse->json('order_id');
        $this->assertNotNull($orderId);

        $sessionResponse = $this->actingAs($user)->postJson('/api/payments/create-session', [
            'order_id' => $orderId,
        ]);
        $sessionResponse->assertStatus(200);
        $sessionId = $sessionResponse->json('session_id');

        $confirmResponse = $this->actingAs($user)->getJson("/api/payments/success?session_id={$sessionId}");
        $confirmResponse->assertStatus(200);

        $this->assertDatabaseHas('orders', [
            'id' => $orderId,
            'payment_status' => 'paid',
            'status' => 'pending',
        ]);

        $this->assertDatabaseHas('payments', [
            'order_id' => $orderId,
            'status' => 'paid',
        ]);

        $this->assertEquals(8, $product->fresh()->stock);

        Notification::assertSentTo($user, \App\Notifications\OrderConfirmation::class);
    }

    public function test_full_paypal_checkout_flow(): void
    {
        Notification::fake();
        $user = User::factory()->create();
        $product = Product::factory()->create([
            'price' => 49.99,
            'stock' => 5,
            'is_active' => true,
            'category_id' => $this->category->id,
        ]);

        $orderResponse = $this->actingAs($user)->postJson('/api/orders', [
            'first_name' => 'Jane',
            'last_name' => 'Smith',
            'address1' => '456 Oak Ave',
            'address2' => '',
            'city' => 'Los Angeles',
            'state' => 'CA',
            'postal_code' => '90001',
            'country' => 'US',
            'phone' => '+1987654321',
            'payment_method' => 'paypal',
            'items' => [['product_id' => $product->id, 'quantity' => 1, 'price' => 49.99]],
            'subtotal' => 49.99,
            'tax' => 4.50,
            'shipping' => 0,
            'discount' => 0,
            'total' => 54.49,
        ]);

        $orderResponse->assertStatus(200);
        $orderId = $orderResponse->json('order_id');

        $sessionResponse = $this->actingAs($user)->postJson('/api/payments/create-session', [
            'order_id' => $orderId,
        ]);
        $sessionResponse->assertStatus(200);
        $sessionId = $sessionResponse->json('session_id');

        $captureResponse = $this->actingAs($user)->get("/api/payments/paypal/capture?token={$sessionId}");
        $captureResponse->assertStatus(302);

        $this->assertDatabaseHas('orders', [
            'id' => $orderId,
            'payment_status' => 'paid',
        ]);

        $this->assertEquals(4, $product->fresh()->stock);

        Notification::assertSentTo($user, \App\Notifications\OrderConfirmation::class);
    }

    public function test_offline_checkout_flow(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create([
            'price' => 15.00,
            'stock' => 20,
            'is_active' => true,
            'category_id' => $this->category->id,
        ]);

        $orderResponse = $this->actingAs($user)->postJson('/api/orders', [
            'first_name' => 'Bob',
            'last_name' => 'Brown',
            'address1' => '789 Pine Rd',
            'address2' => '',
            'city' => 'Chicago',
            'state' => 'IL',
            'postal_code' => '60601',
            'country' => 'US',
            'phone' => '+1122334455',
            'payment_method' => 'offline',
            'items' => [['product_id' => $product->id, 'quantity' => 3, 'price' => 15.00]],
            'subtotal' => 45.00,
            'tax' => 3.60,
            'shipping' => 5.00,
            'discount' => 0,
            'total' => 53.60,
        ]);

        $orderResponse->assertStatus(200);
        $orderId = $orderResponse->json('order_id');

        $this->assertDatabaseHas('orders', [
            'id' => $orderId,
            'payment_method' => 'offline',
            'payment_status' => 'pending',
        ]);

        $this->assertDatabaseCount('payments', 0);
    }

    public function test_failed_payment_flow_restores_stock_and_cancels_order(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create([
            'price' => 20.00,
            'stock' => 10,
            'is_active' => true,
            'category_id' => $this->category->id,
        ]);

        $orderResponse = $this->actingAs($user)->postJson('/api/orders', [
            'first_name' => 'Alice',
            'last_name' => 'Green',
            'address1' => '321 Elm St',
            'address2' => '',
            'city' => 'Houston',
            'state' => 'TX',
            'postal_code' => '77001',
            'country' => 'US',
            'phone' => '+1555666777',
            'payment_method' => 'stripe',
            'items' => [['product_id' => $product->id, 'quantity' => 2, 'price' => 20.00]],
            'subtotal' => 40.00,
            'tax' => 3.20,
            'shipping' => 0,
            'discount' => 0,
            'total' => 43.20,
        ]);

        $orderId = $orderResponse->json('order_id');

        $this->actingAs($user)->postJson('/api/payments/create-session', [
            'order_id' => $orderId,
        ]);

        $payment = Payment::where('order_id', $orderId)->first();
        $this->assertNotNull($payment);

        $webhookResponse = $this->postJson('/api/payments/webhook', [
            'type' => 'payment_intent.payment_failed',
            'data' => ['object' => ['id' => $payment->provider_session_id]],
        ], ['Stripe-Signature' => 'valid_test_signature']);

        $webhookResponse->assertStatus(200);

        $this->assertEquals('failed', $payment->fresh()->status);
        $this->assertDatabaseHas('orders', ['id' => $orderId, 'payment_status' => 'failed']);
        $this->assertEquals(10, $product->fresh()->stock);
    }
}
