<?php

namespace Tests\Feature\Api;

use App\Models\Category;
use App\Models\Coupon;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class CartTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->sessionId = Str::uuid()->toString();
        $this->withSession(['cart_session' => $this->sessionId]);
    }

    public function test_can_add_a_product_to_cart(): void
    {
        $category = Category::factory()->create();
        $product = Product::factory()->create([
            'price' => 99.99,
            'stock' => 10,
            'category_id' => $category->id,
        ]);

        $response = $this->postJson("/api/cart/add", [
            'product_id' => $product->id,
            'quantity' => 2,
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure(['items', 'coupon'])
            ->assertJsonFragment(['product_id' => $product->id])
            ->assertJsonFragment(['quantity' => 2]);
    }

    public function test_returns_error_for_invalid_product(): void
    {
        $response = $this->postJson("/api/cart/add", [
            'product_id' => 9999,
            'quantity' => 1,
        ]);

        $response->assertStatus(422);
    }

    public function test_returns_error_for_insufficient_stock(): void
    {
        $category = Category::factory()->create();
        $product = Product::factory()->create([
            'stock' => 2,
            'category_id' => $category->id,
        ]);

        $response = $this->postJson("/api/cart/add", [
            'product_id' => $product->id,
            'quantity' => 10,
        ]);

        $response->assertStatus(422)
            ->assertJsonFragment(['message' => 'Insufficient stock']);
    }

    public function test_increments_quantity_for_existing_cart_item(): void
    {
        $category = Category::factory()->create();
        $product = Product::factory()->create([
            'stock' => 20,
            'category_id' => $category->id,
        ]);

        $this->postJson("/api/cart/add", [
            'product_id' => $product->id,
            'quantity' => 3,
        ]);

        $response = $this->postJson("/api/cart/add", [
            'product_id' => $product->id,
            'quantity' => 2,
        ]);

        $response->assertStatus(200);
        $items = collect($response->json('items'));
        $item = $items->firstWhere('product_id', $product->id);
        $this->assertEquals(5, $item['quantity']);
    }

    public function test_returns_empty_cart_initially(): void
    {
        $response = $this->getJson("/api/cart");

        $response->assertStatus(200)
            ->assertJsonFragment(['items' => []]);
    }

    public function test_returns_cart_with_items_after_adding(): void
    {
        $category = Category::factory()->create();
        $product = Product::factory()->create([
            'stock' => 10,
            'category_id' => $category->id,
        ]);

        $this->postJson("/api/cart/add", [
            'product_id' => $product->id,
            'quantity' => 1,
        ]);

        $response = $this->getJson("/api/cart");

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('items'));
    }

    public function test_filters_out_deleted_products_from_cart(): void
    {
        $category = Category::factory()->create();
        $product = Product::factory()->create([
            'stock' => 10,
            'category_id' => $category->id,
        ]);

        $this->postJson("/api/cart/add", [
            'product_id' => $product->id,
            'quantity' => 1,
        ]);

        $product->delete();

        $response = $this->getJson("/api/cart");
        $response->assertStatus(200);
        $this->assertCount(0, $response->json('items'));
    }

    public function test_can_update_item_quantity(): void
    {
        $category = Category::factory()->create();
        $product = Product::factory()->create([
            'stock' => 20,
            'category_id' => $category->id,
        ]);

        $addResponse = $this->postJson("/api/cart/add", [
            'product_id' => $product->id,
            'quantity' => 1,
        ]);

        $itemId = $addResponse->json('items.0.id');

        $response = $this->patchJson("/api/cart/{$itemId}", [
            'quantity' => 5,
        ]);

        $response->assertStatus(200);
        $items = collect($response->json('items'));
        $item = $items->firstWhere('id', $itemId);
        $this->assertEquals(5, $item['quantity']);
    }

    public function test_returns_404_for_non_existent_item(): void
    {
        $response = $this->patchJson("/api/cart/9999", [
            'quantity' => 1,
        ]);

        $response->assertStatus(404)
            ->assertJsonFragment(['message' => 'Item not found']);
    }

    public function test_returns_error_for_insufficient_stock_on_update(): void
    {
        $category = Category::factory()->create();
        $product = Product::factory()->create([
            'stock' => 2,
            'category_id' => $category->id,
        ]);

        $addResponse = $this->postJson("/api/cart/add", [
            'product_id' => $product->id,
            'quantity' => 1,
        ]);

        $itemId = $addResponse->json('items.0.id');

        $response = $this->patchJson("/api/cart/{$itemId}", [
            'quantity' => 10,
        ]);

        $response->assertStatus(422)
            ->assertJsonFragment(['message' => 'Insufficient stock']);
    }

    public function test_removes_item_when_quantity_is_set_to_0(): void
    {
        $category = Category::factory()->create();
        $product = Product::factory()->create([
            'stock' => 10,
            'category_id' => $category->id,
        ]);

        $addResponse = $this->postJson("/api/cart/add", [
            'product_id' => $product->id,
            'quantity' => 1,
        ]);

        $itemId = $addResponse->json('items.0.id');

        $this->patchJson("/api/cart/{$itemId}", [
            'quantity' => 0,
        ]);

        $response = $this->getJson("/api/cart");
        $this->assertCount(0, $response->json('items'));
    }

    public function test_can_remove_an_item_from_cart(): void
    {
        $category = Category::factory()->create();
        $product = Product::factory()->create([
            'stock' => 10,
            'category_id' => $category->id,
        ]);

        $addResponse = $this->postJson("/api/cart/add", [
            'product_id' => $product->id,
            'quantity' => 1,
        ]);

        $itemId = $addResponse->json('items.0.id');

        $response = $this->deleteJson("/api/cart/{$itemId}");

        $response->assertStatus(200);
        $this->assertCount(0, $response->json('items'));
    }

    public function test_silently_succeeds_for_non_existent_item_removal(): void
    {
        $response = $this->deleteJson("/api/cart/9999");
        $response->assertStatus(200);
    }

    public function test_can_clear_the_entire_cart(): void
    {
        $category = Category::factory()->create();
        $product1 = Product::factory()->create(['stock' => 10, 'category_id' => $category->id]);
        $product2 = Product::factory()->create(['stock' => 10, 'category_id' => $category->id]);

        $this->postJson("/api/cart/add", ['product_id' => $product1->id, 'quantity' => 1]);
        $this->postJson("/api/cart/add", ['product_id' => $product2->id, 'quantity' => 2]);

        $response = $this->deleteJson("/api/cart/clear");

        $response->assertStatus(200);
        $this->assertCount(0, $response->json('items'));
    }

    public function test_clears_coupon_when_cart_is_cleared(): void
    {
        $category = Category::factory()->create();
        $product = Product::factory()->create(['stock' => 10, 'category_id' => $category->id]);
        $coupon = Coupon::factory()->create();

        $this->postJson("/api/cart/add", ['product_id' => $product->id, 'quantity' => 1]);
        $this->postJson("/api/cart/coupon", ['code' => $coupon->code]);

        $this->deleteJson("/api/cart/clear");

        $response = $this->getJson("/api/cart");
        $this->assertNull($response->json('coupon'));
    }

    public function test_can_apply_a_valid_coupon(): void
    {
        $coupon = Coupon::factory()->create([
            'type' => 'percentage',
            'value' => 10,
            'is_active' => true,
            'valid_from' => now()->subDay(),
            'valid_until' => now()->addDay(),
        ]);

        $category = Category::factory()->create();
        $product = Product::factory()->create(['stock' => 10, 'category_id' => $category->id]);

        $this->postJson("/api/cart/add", ['product_id' => $product->id, 'quantity' => 1]);

        $response = $this->postJson("/api/cart/coupon", ['code' => $coupon->code]);

        $response->assertStatus(200);
        $this->assertEquals($coupon->code, $response->json('coupon.code'));
    }

    public function test_returns_error_for_invalid_coupon_code(): void
    {
        $response = $this->postJson("/api/cart/coupon", ['code' => 'INVALID']);

        $response->assertStatus(422)
            ->assertJsonFragment(['message' => 'Invalid or expired coupon']);
    }

    public function test_returns_error_for_expired_coupon(): void
    {
        $coupon = Coupon::factory()->create([
            'valid_until' => now()->subDay(),
            'is_active' => true,
        ]);

        $response = $this->postJson("/api/cart/coupon", ['code' => $coupon->code]);

        $response->assertStatus(422)
            ->assertJsonFragment(['message' => 'Invalid or expired coupon']);
    }

    public function test_returns_error_for_inactive_coupon(): void
    {
        $coupon = Coupon::factory()->create([
            'is_active' => false,
        ]);

        $response = $this->postJson("/api/cart/coupon", ['code' => $coupon->code]);

        $response->assertStatus(422)
            ->assertJsonFragment(['message' => 'Invalid or expired coupon']);
    }

    public function test_can_remove_applied_coupon(): void
    {
        $coupon = Coupon::factory()->create(['is_active' => true]);
        $category = Category::factory()->create();
        $product = Product::factory()->create(['stock' => 10, 'category_id' => $category->id]);

        $this->postJson("/api/cart/add", ['product_id' => $product->id, 'quantity' => 1]);
        $this->postJson("/api/cart/coupon", ['code' => $coupon->code]);

        $response = $this->deleteJson("/api/cart/coupon");

        $response->assertStatus(200);
        $this->assertNull($response->json('coupon'));
    }

    public function test_different_sessions_have_different_carts(): void
    {
        $category = Category::factory()->create();
        $product = Product::factory()->create(['stock' => 10, 'category_id' => $category->id]);

        $this->withSession(['cart_session' => Str::uuid()->toString()])
            ->postJson("/api/cart/add", ['product_id' => $product->id, 'quantity' => 1]);

        $response = $this->withSession(['cart_session' => Str::uuid()->toString()])
            ->getJson("/api/cart");

        $response->assertStatus(200);
        $this->assertCount(0, $response->json('items'));
    }
}
