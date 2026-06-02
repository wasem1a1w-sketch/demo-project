<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductReview;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReviewTest extends TestCase
{
    use RefreshDatabase;

    private Category $category;
    private Product $product;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->category = Category::factory()->create(['is_active' => true]);
        $this->product = Product::factory()->create([
            'is_active' => true,
            'category_id' => $this->category->id,
        ]);
        $this->user = User::factory()->create();
    }

    public function test_user_can_create_review(): void
    {
        $response = $this->actingAs($this->user)->postJson("/api/products/{$this->product->id}/reviews", [
            'rating' => 5,
            'title' => 'Great product!',
            'body' => 'I really loved this product.',
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('product_reviews', [
            'user_id' => $this->user->id,
            'product_id' => $this->product->id,
            'rating' => 5,
            'title' => 'Great product!',
        ]);
    }

    public function test_public_can_list_approved_reviews(): void
    {
        ProductReview::factory()->count(2)->create([
            'product_id' => $this->product->id,
            'is_approved' => true,
        ]);
        ProductReview::factory()->create([
            'product_id' => $this->product->id,
            'is_approved' => false,
        ]);

        $response = $this->getJson("/api/products/{$this->product->id}/reviews");

        $response->assertStatus(200);
        $response->assertJsonCount(2, 'data');
    }

    public function test_user_can_update_own_review(): void
    {
        $review = ProductReview::factory()->create([
            'user_id' => $this->user->id,
            'product_id' => $this->product->id,
            'rating' => 3,
        ]);

        $response = $this->actingAs($this->user)->putJson("/api/products/{$this->product->id}/reviews/{$review->id}", [
            'rating' => 4,
            'title' => 'Updated title',
            'body' => 'Updated body',
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('product_reviews', [
            'id' => $review->id,
            'rating' => 4,
            'title' => 'Updated title',
        ]);
    }

    public function test_user_can_delete_own_review(): void
    {
        $review = ProductReview::factory()->create([
            'user_id' => $this->user->id,
            'product_id' => $this->product->id,
        ]);

        $response = $this->actingAs($this->user)->deleteJson("/api/products/{$this->product->id}/reviews/{$review->id}");

        $response->assertStatus(204);
        $this->assertDatabaseMissing('product_reviews', ['id' => $review->id]);
    }
}
