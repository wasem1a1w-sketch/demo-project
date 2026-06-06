<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Product;
use App\Models\User;
use App\Services\PaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PayPalIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private Category $category;

    protected function setUp(): void
    {
        parent::setUp();
        $this->category = Category::factory()->create(['is_active' => true]);
    }

    public function test_fallback_mode_creates_paypal_id_session(): void
    {
        $service = app(PaymentService::class);
        $user = User::factory()->create();
        $product = Product::factory()->create(['price' => 30.00, 'stock' => 10, 'is_active' => true, 'category_id' => $this->category->id]);
        $order = Order::factory()->create([
            'user_id' => $user->id,
            'total' => 30.00,
            'subtotal' => 30.00,
            'shipping_address' => '123 Main St',
        ]);
        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'product_name' => $product->name,
            'price' => 30.00,
            'quantity' => 1,
            'subtotal' => 30.00,
        ]);

        $payment = $service->processCheckout($order, 'paypal');

        $this->assertStringStartsWith('PAYPALID_', $payment->provider_session_id);
        $this->assertStringContainsString('paypal.com', $payment->provider_response['checkout_url']);
        $this->assertEquals('pending', $payment->status->value);
        $this->assertEquals('paypal', $payment->provider);
    }

    public function test_fallback_capture_returns_completed(): void
    {
        $service = app(PaymentService::class);

        $request = new \Illuminate\Http\Request(['token' => 'PAYPALID_fake_token']);
        $captureData = $service->capturePayPalOrder($request);

        $this->assertEquals('COMPLETED', $captureData['status']);
        $this->assertStringStartsWith('PAYPAL_CAPTURE_', $captureData['purchase_units'][0]['payments']['captures'][0]['id']);
    }

    public function test_full_mock_paypal_flow_session_to_capture(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create(['price' => 75.00, 'stock' => 10, 'is_active' => true, 'category_id' => $this->category->id]);
        $order = Order::factory()->create([
            'user_id' => $user->id,
            'payment_status' => 'pending',
            'payment_method' => 'paypal',
            'total' => 75.00,
            'subtotal' => 75.00,
            'shipping_address' => '123 Main St',
        ]);
        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'product_name' => $product->name,
            'price' => 75.00,
            'quantity' => 1,
            'subtotal' => 75.00,
        ]);

        $sessionResponse = $this->actingAs($user)->postJson('/api/payments/create-session', [
            'order_id' => $order->id,
        ]);
        $sessionResponse->assertStatus(200);
        $sessionId = $sessionResponse->json('session_id');
        $this->assertStringStartsWith('PAYPALID_', $sessionId);

        $captureResponse = $this->actingAs($user)->get("/api/payments/paypal/capture?token={$sessionId}");
        $captureResponse->assertStatus(302);

        $this->assertEquals('paid', $order->fresh()->payment_status->value);
        $payment = Payment::where('provider_session_id', $sessionId)->first();
        $this->assertEquals('paid', $payment->status->value);
    }

    public function test_paypal_cancel_marks_payment_expired(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create(['stock' => 5, 'is_active' => true, 'category_id' => $this->category->id]);
        $order = Order::factory()->create([
            'user_id' => $user->id,
            'payment_status' => 'pending',
            'payment_method' => 'paypal',
            'total' => 40.00,
            'subtotal' => 40.00,
            'shipping_address' => '123 Main St',
        ]);
        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'product_name' => $product->name,
            'price' => 40.00,
            'quantity' => 1,
            'subtotal' => 40.00,
        ]);
        $payment = Payment::create([
            'order_id' => $order->id,
            'provider' => 'paypal',
            'provider_session_id' => 'PAYPALID_cancel_test',
            'status' => PaymentStatus::Pending,
        ]);

        $response = $this->actingAs($user)->get('/api/payments/paypal/cancel?token=PAYPALID_cancel_test');

        $response->assertStatus(302);
        $this->assertEquals('expired', $payment->fresh()->status->value);
        $this->assertEquals(OrderStatus::Cancelled, $order->fresh()->status);
        $this->assertEquals(6, $product->fresh()->stock);
    }
}
