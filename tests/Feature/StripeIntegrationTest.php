<?php

namespace Tests\Feature;

use App\Enums\PaymentStatus;
use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Product;
use App\Models\User;
use App\Services\PaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StripeIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private Category $category;

    protected function setUp(): void
    {
        parent::setUp();
        $this->category = Category::factory()->create(['is_active' => true]);
    }

    public function test_fallback_mode_creates_cs_test_session_id(): void
    {
        $service = app(PaymentService::class);
        $user = User::factory()->create();
        $product = Product::factory()->create(['price' => 25.00, 'stock' => 10, 'is_active' => true, 'category_id' => $this->category->id]);
        $order = Order::factory()->create([
            'user_id' => $user->id,
            'total' => 25.00,
            'subtotal' => 25.00,
            'shipping_address' => '123 Main St',
        ]);
        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'product_name' => $product->name,
            'price' => 25.00,
            'quantity' => 1,
            'subtotal' => 25.00,
        ]);

        $payment = $service->processCheckout($order, 'stripe');

        $this->assertStringStartsWith('cs_test_', $payment->provider_session_id);
        $this->assertStringContainsString('checkout.stripe.com', $payment->provider_response['checkout_url']);
        $this->assertEquals('pending', $payment->status->value);
    }

    public function test_fallback_retrieve_session_returns_paid_status(): void
    {
        $service = app(PaymentService::class);

        $sessionData = $service->retrieveStripeSession('cs_test_fake_123');

        $this->assertIsArray($sessionData);
        $this->assertEquals('paid', $sessionData['payment_status']);
        $this->assertEquals('cs_test_fake_123', $sessionData['id']);
    }

    public function test_full_mock_flow_session_to_confirmed_paid(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create(['price' => 50.00, 'stock' => 10, 'is_active' => true, 'category_id' => $this->category->id]);
        $order = Order::factory()->create([
            'user_id' => $user->id,
            'payment_status' => 'pending',
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

        $sessionResponse = $this->actingAs($user)->postJson('/api/payments/create-session', [
            'order_id' => $order->id,
        ]);
        $sessionResponse->assertStatus(200);
        $sessionId = $sessionResponse->json('session_id');
        $this->assertStringStartsWith('cs_test_', $sessionId);

        $confirmResponse = $this->actingAs($user)->getJson("/api/payments/success?session_id={$sessionId}");
        $confirmResponse->assertStatus(200);

        $this->assertEquals('paid', $order->fresh()->payment_status->value);
        $payment = Payment::where('provider_session_id', $sessionId)->first();
        $this->assertEquals('paid', $payment->status->value);
    }

    public function test_webhook_rejects_missing_signature(): void
    {
        $response = $this->postJson('/api/payments/webhook', [
            'type' => 'checkout.session.completed',
            'data' => ['object' => ['id' => 'cs_test_123']],
        ]);

        $response->assertStatus(400);
    }

    public function test_webhook_accepted_with_signature(): void
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
            'provider_session_id' => 'cs_test_webhook_123',
            'status' => PaymentStatus::Pending,
        ]);

        $response = $this->postJson('/api/payments/webhook', [
            'type' => 'checkout.session.completed',
            'data' => ['object' => ['id' => 'cs_test_webhook_123']],
        ], ['Stripe-Signature' => 'valid_test_signature']);

        $response->assertStatus(200);
        $this->assertEquals('paid', $payment->fresh()->status->value);
        $this->assertEquals('paid', $order->fresh()->payment_status->value);
    }
}
