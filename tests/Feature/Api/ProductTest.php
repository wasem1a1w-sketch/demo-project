<?php

namespace Tests\Feature\Api;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->category = Category::factory()->create(['is_active' => true]);
});

describe('product listing', function () {
    it('returns paginated products', function () {
        Product::factory()->count(15)->create([
            'is_active' => true,
            'category_id' => $this->category->id,
        ]);

        $response = $this->getJson('/api/products');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data',
                'total',
                'per_page',
                'current_page',
            ]);
    });

    it('only returns active products', function () {
        Product::factory()->create([
            'is_active' => true,
            'category_id' => $this->category->id,
        ]);
        Product::factory()->create([
            'is_active' => false,
            'category_id' => $this->category->id,
        ]);

        $response = $this->getJson('/api/products');

        expect($response->json('data'))->toHaveCount(1);
    });

    it('filters by category', function () {
        $category2 = Category::factory()->create(['is_active' => true]);
        Product::factory()->create([
            'is_active' => true,
            'category_id' => $this->category->id,
        ]);
        Product::factory()->create([
            'is_active' => true,
            'category_id' => $category2->id,
        ]);

        $response = $this->getJson("/api/products?category={$this->category->id}");

        expect($response->json('data'))->toHaveCount(1);
        expect($response->json('data.0.category.id'))->toBe($this->category->id);
    });

    it('searches by name and description', function () {
        Product::factory()->create([
            'name' => 'Blue T-Shirt',
            'description' => 'A comfortable shirt',
            'is_active' => true,
            'category_id' => $this->category->id,
        ]);
        Product::factory()->create([
            'name' => 'Red Shoes',
            'description' => 'Running shoes',
            'is_active' => true,
            'category_id' => $this->category->id,
        ]);

        $response = $this->getJson('/api/products?search=shirt');

        expect($response->json('data'))->toHaveCount(1);
        expect($response->json('data.0.name'))->toBe('Blue T-Shirt');
    });

    it('filters featured products', function () {
        Product::factory()->create([
            'is_active' => true,
            'is_featured' => true,
            'category_id' => $this->category->id,
        ]);
        Product::factory()->create([
            'is_active' => true,
            'is_featured' => false,
            'category_id' => $this->category->id,
        ]);

        $response = $this->getJson('/api/products?featured=1');

        expect($response->json('data'))->toHaveCount(1);
        expect($response->json('data.0.is_featured'))->toBeTrue();
    });

    it('sorts by price low to high', function () {
        Product::factory()->create([
            'name' => 'Cheap',
            'price' => 10,
            'is_active' => true,
            'category_id' => $this->category->id,
        ]);
        Product::factory()->create([
            'name' => 'Expensive',
            'price' => 100,
            'is_active' => true,
            'category_id' => $this->category->id,
        ]);

        $response = $this->getJson('/api/products?sort=price_low');

        expect($response->json('data.0.name'))->toBe('Cheap');
    });

    it('sorts by price high to low', function () {
        Product::factory()->create([
            'name' => 'Cheap',
            'price' => 10,
            'is_active' => true,
            'category_id' => $this->category->id,
        ]);
        Product::factory()->create([
            'name' => 'Expensive',
            'price' => 100,
            'is_active' => true,
            'category_id' => $this->category->id,
        ]);

        $response = $this->getJson('/api/products?sort=price_high');

        expect($response->json('data.0.name'))->toBe('Expensive');
    });

    it('includes product images', function () {
        $product = Product::factory()->create([
            'is_active' => true,
            'category_id' => $this->category->id,
        ]);
        ProductImage::factory()->create([
            'product_id' => $product->id,
            'is_primary' => true,
        ]);

        $response = $this->getJson('/api/products');

        expect($response->json('data.0.images'))->not->toBeEmpty();
    });
});

describe('single product', function () {
    it('returns product details with images and category', function () {
        $product = Product::factory()->create([
            'is_active' => true,
            'category_id' => $this->category->id,
        ]);
        ProductImage::factory()->count(3)->create(['product_id' => $product->id]);

        $response = $this->getJson("/api/products/{$product->slug}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'id',
                'name',
                'slug',
                'price',
                'images',
                'category',
            ]);
    });

    it('returns 404 for inactive product', function () {
        $product = Product::factory()->create([
            'is_active' => false,
            'category_id' => $this->category->id,
        ]);

        $response = $this->getJson("/api/products/{$product->slug}");

        $response->assertStatus(404);
    });

    it('returns 404 for non-existent product', function () {
        $response = $this->getJson('/api/products/non-existent-slug');

        $response->assertStatus(404);
    });
});

describe('categories endpoint', function () {
    it('returns active categories', function () {
        Category::factory()->create(['is_active' => true]);
        Category::factory()->create(['is_active' => false]);

        $response = $this->getJson('/api/categories');

        expect($response->json())->toHaveCount(2); // Including $this->category
    });
});
