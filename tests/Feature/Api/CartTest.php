<?php

namespace Tests\Feature\Api;

use App\Models\Category;
use App\Models\Product;
use App\Models\Coupon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Create a session for cart tests
    $this->sessionId = Str::uuid()->toString();
    $this->withSession(['cart_session' => $this->sessionId]);
});

describe('cart addition', function () {
    it('can add a product to cart', function () {
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
    });

    it('returns error for invalid product', function () {
        $response = $this->postJson("/api/cart/add", [
            'product_id' => 9999,
            'quantity' => 1,
        ]);

        $response->assertStatus(422);
    });

    it('returns error for insufficient stock', function () {
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
    });

    it('increments quantity for existing cart item', function () {
        $category = Category::factory()->create();
        $product = Product::factory()->create([
            'stock' => 20,
            'category_id' => $category->id,
        ]);

        // Add first time
        $this->postJson("/api/cart/add", [
            'product_id' => $product->id,
            'quantity' => 3,
        ]);

        // Add again
        $response = $this->postJson("/api/cart/add", [
            'product_id' => $product->id,
            'quantity' => 2,
        ]);

        $response->assertStatus(200);
        $items = collect($response->json('items'));
        $item = $items->firstWhere('product_id', $product->id);
        expect($item['quantity'])->toBe(5);
    });
});

describe('cart retrieval', function () {
    it('returns empty cart initially', function () {
        $response = $this->getJson("/api/cart");

        $response->assertStatus(200)
            ->assertJsonFragment(['items' => []]);
    });

    it('returns cart with items after adding', function () {
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
        expect($response->json('items'))->toHaveCount(1);
    });

    it('filters out deleted products from cart', function () {
        $category = Category::factory()->create();
        $product = Product::factory()->create([
            'stock' => 10,
            'category_id' => $category->id,
        ]);

        // Add to cart
        $this->postJson("/api/cart/add", [
            'product_id' => $product->id,
            'quantity' => 1,
        ]);

        // Delete the product
        $product->delete();

        $response = $this->getJson("/api/cart");
        $response->assertStatus(200);
        expect($response->json('items'))->toHaveCount(0);
    });
});

describe('cart update', function () {
    it('can update item quantity', function () {
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
        expect($item['quantity'])->toBe(5);
    });

    it('returns 404 for non-existent item', function () {
        $response = $this->patchJson("/api/cart/9999", [
            'quantity' => 1,
        ]);

        $response->assertStatus(404)
            ->assertJsonFragment(['message' => 'Item not found']);
    });

    it('returns error for insufficient stock on update', function () {
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
    });

    it('removes item when quantity is set to 0', function () {
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
        expect($response->json('items'))->toHaveCount(0);
    });
});

describe('cart item removal', function () {
    it('can remove an item from cart', function () {
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
        expect($response->json('items'))->toHaveCount(0);
    });

    it('silently succeeds for non-existent item removal', function () {
        $response = $this->deleteJson("/api/cart/9999");
        $response->assertStatus(200);
    });
});

describe('cart clear', function () {
    it('can clear the entire cart', function () {
        $category = Category::factory()->create();
        $product1 = Product::factory()->create(['stock' => 10, 'category_id' => $category->id]);
        $product2 = Product::factory()->create(['stock' => 10, 'category_id' => $category->id]);

        $this->postJson("/api/cart/add", ['product_id' => $product1->id, 'quantity' => 1]);
        $this->postJson("/api/cart/add", ['product_id' => $product2->id, 'quantity' => 2]);

        $response = $this->deleteJson("/api/cart/clear");

        $response->assertStatus(200);
        expect($response->json('items'))->toHaveCount(0);
    });

    it('clears coupon when cart is cleared', function () {
        $category = Category::factory()->create();
        $product = Product::factory()->create(['stock' => 10, 'category_id' => $category->id]);
        $coupon = Coupon::factory()->create();

        $this->postJson("/api/cart/add", ['product_id' => $product->id, 'quantity' => 1]);
        $this->postJson("/api/cart/coupon", ['code' => $coupon->code]);

        $this->deleteJson("/api/cart/clear");

        $response = $this->getJson("/api/cart");
        expect($response->json('coupon'))->toBeNull();
    });
});

describe('coupon operations', function () {
    it('can apply a valid coupon', function () {
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
        expect($response->json('coupon.code'))->toBe($coupon->code);
    });

    it('returns error for invalid coupon code', function () {
        $response = $this->postJson("/api/cart/coupon", ['code' => 'INVALID']);

        $response->assertStatus(422)
            ->assertJsonFragment(['message' => 'Invalid or expired coupon']);
    });

    it('returns error for expired coupon', function () {
        $coupon = Coupon::factory()->create([
            'valid_until' => now()->subDay(),
            'is_active' => true,
        ]);

        $response = $this->postJson("/api/cart/coupon", ['code' => $coupon->code]);

        $response->assertStatus(422)
            ->assertJsonFragment(['message' => 'Invalid or expired coupon']);
    });

    it('returns error for inactive coupon', function () {
        $coupon = Coupon::factory()->create([
            'is_active' => false,
        ]);

        $response = $this->postJson("/api/cart/coupon", ['code' => $coupon->code]);

        $response->assertStatus(422)
            ->assertJsonFragment(['message' => 'Invalid or expired coupon']);
    });

    it('can remove applied coupon', function () {
        $coupon = Coupon::factory()->create(['is_active' => true]);
        $category = Category::factory()->create();
        $product = Product::factory()->create(['stock' => 10, 'category_id' => $category->id]);

        $this->postJson("/api/cart/add", ['product_id' => $product->id, 'quantity' => 1]);
        $this->postJson("/api/cart/coupon", ['code' => $coupon->code]);

        $response = $this->deleteJson("/api/cart/coupon");

        $response->assertStatus(200);
        expect($response->json('coupon'))->toBeNull();
    });
});

describe('session isolation', function () {
    it('different sessions have different carts', function () {
        $category = Category::factory()->create();
        $product = Product::factory()->create(['stock' => 10, 'category_id' => $category->id]);

        // Add to cart with session 1
        $this->withSession(['cart_session' => Str::uuid()->toString()])
            ->postJson("/api/cart/add", ['product_id' => $product->id, 'quantity' => 1]);

        // Add to cart with session 2
        $response = $this->withSession(['cart_session' => Str::uuid()->toString()])
            ->getJson("/api/cart");

        $response->assertStatus(200);
        expect($response->json('items'))->toHaveCount(0);
    });
});
