<?php

namespace Tests\Feature\Api;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductTest extends TestCase
{
    use RefreshDatabase;

    private Category $category;

    protected function setUp(): void
    {
        parent::setUp();

        $this->category = Category::factory()->create(['is_active' => true]);
    }

    public function test_returns_paginated_products(): void
    {
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
    }

    public function test_only_returns_active_products(): void
    {
        Product::factory()->create([
            'is_active' => true,
            'category_id' => $this->category->id,
        ]);
        Product::factory()->create([
            'is_active' => false,
            'category_id' => $this->category->id,
        ]);

        $response = $this->getJson('/api/products');

        $this->assertCount(1, $response->json('data'));
    }

    public function test_filters_by_category(): void
    {
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

        $this->assertCount(1, $response->json('data'));
        $this->assertEquals($this->category->id, $response->json('data.0.category.id'));
    }

    public function test_searches_by_name_and_description(): void
    {
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

        $this->assertCount(1, $response->json('data'));
        $this->assertEquals('Blue T-Shirt', $response->json('data.0.name'));
    }

    public function test_filters_featured_products(): void
    {
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

        $this->assertCount(1, $response->json('data'));
        $this->assertTrue($response->json('data.0.is_featured'));
    }

    public function test_sorts_by_price_low_to_high(): void
    {
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

        $this->assertEquals('Cheap', $response->json('data.0.name'));
    }

    public function test_sorts_by_price_high_to_low(): void
    {
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

        $this->assertEquals('Expensive', $response->json('data.0.name'));
    }

    public function test_includes_product_images(): void
    {
        $product = Product::factory()->create([
            'is_active' => true,
            'category_id' => $this->category->id,
        ]);
        ProductImage::factory()->create([
            'product_id' => $product->id,
            'is_primary' => true,
        ]);

        $response = $this->getJson('/api/products');

        $this->assertNotEmpty($response->json('data.0.images'));
    }

    public function test_returns_product_details_with_images_and_category(): void
    {
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
    }

    public function test_returns_404_for_inactive_product(): void
    {
        $product = Product::factory()->create([
            'is_active' => false,
            'category_id' => $this->category->id,
        ]);

        $response = $this->getJson("/api/products/{$product->slug}");

        $response->assertStatus(404);
    }

    public function test_returns_404_for_non_existent_product(): void
    {
        $response = $this->getJson('/api/products/non-existent-slug');

        $response->assertStatus(404);
    }

    public function test_returns_active_categories(): void
    {
        Category::factory()->create(['is_active' => true]);
        Category::factory()->create(['is_active' => false]);

        $response = $this->getJson('/api/categories');

        $this->assertCount(2, $response->json()); // Including $this->category
    }
}
