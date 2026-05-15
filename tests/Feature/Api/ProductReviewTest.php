<?php

namespace Tests\Feature\Api;

use App\Models\AdminNotification;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductReview;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductReviewTest extends TestCase
{
    use RefreshDatabase;

    private Category $category;

    protected function setUp(): void
    {
        parent::setUp();

        $this->category = Category::factory()->create(['is_active' => true]);
    }

    public function test_returns_empty_reviews_list_for_product_with_no_reviews(): void
    {
        $product = Product::factory()->create([
            'is_active' => true,
            'category_id' => $this->category->id,
        ]);

        $response = $this->getJson("/api/products/{$product->id}/reviews");

        $response->assertStatus(200);
        $response->assertJson(['data' => []]);
    }

    public function test_guest_cannot_create_a_review(): void
    {
        $product = Product::factory()->create([
            'is_active' => true,
            'category_id' => $this->category->id,
        ]);

        $response = $this->postJson("/api/products/{$product->id}/reviews", [
            'rating' => 5,
            'title' => 'Great product',
            'body' => 'I love this!',
        ]);

        $response->assertStatus(401);
    }

    public function test_authenticated_user_can_create_a_review(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create([
            'is_active' => true,
            'category_id' => $this->category->id,
        ]);

        $response = $this->actingAs($user)->postJson("/api/products/{$product->id}/reviews", [
            'rating' => 5,
            'title' => 'Great product',
            'body' => 'I love this!',
        ]);

        $response->assertStatus(201);
        $response->assertJson([
            'rating' => 5,
            'title' => 'Great product',
            'body' => 'I love this!',
        ]);
    }

    public function test_unapproved_reviews_are_hidden_from_other_users(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $product = Product::factory()->create([
            'is_active' => true,
            'category_id' => $this->category->id,
        ]);

        $this->actingAs($user1)->postJson("/api/products/{$product->id}/reviews", [
            'rating' => 5,
            'title' => 'Visible',
        ]);

        ProductReview::where('title', 'Visible')->update(['is_approved' => true]);

        $this->actingAs($user2)->postJson("/api/products/{$product->id}/reviews", [
            'rating' => 3,
            'title' => 'Hidden',
        ]);

        $response = $this->actingAs($user1)->getJson("/api/products/{$product->id}/reviews");
        $reviewTitles = collect($response->json('data'))->pluck('title');

        $this->assertContains('Visible', $reviewTitles);
        $this->assertNotContains('Hidden', $reviewTitles);
    }

    public function test_rating_is_required_and_must_be_between_1_and_5(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create([
            'is_active' => true,
            'category_id' => $this->category->id,
        ]);

        $response = $this->actingAs($user)->postJson("/api/products/{$product->id}/reviews", [
            'title' => 'No rating',
        ]);
        $response->assertStatus(422);

        $response = $this->actingAs($user)->postJson("/api/products/{$product->id}/reviews", [
            'rating' => 0,
        ]);
        $response->assertStatus(422);

        $response = $this->actingAs($user)->postJson("/api/products/{$product->id}/reviews", [
            'rating' => 6,
        ]);
        $response->assertStatus(422);
    }

    public function test_user_can_only_submit_one_review_per_product(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create([
            'is_active' => true,
            'category_id' => $this->category->id,
        ]);

        $this->actingAs($user)->postJson("/api/products/{$product->id}/reviews", [
            'rating' => 5,
        ])->assertStatus(201);

        $this->actingAs($user)->postJson("/api/products/{$product->id}/reviews", [
            'rating' => 3,
        ])->assertStatus(422);
    }

    public function test_user_can_update_own_review(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create([
            'is_active' => true,
            'category_id' => $this->category->id,
        ]);

        $review = $this->actingAs($user)->postJson("/api/products/{$product->id}/reviews", [
            'rating' => 3,
            'body' => 'Okay product',
        ])->json();

        $response = $this->actingAs($user)->putJson(
            "/api/products/{$product->id}/reviews/{$review['id']}",
            ['rating' => 5, 'body' => 'Updated opinion!']
        );

        $response->assertStatus(200);
        $response->assertJson([
            'rating' => 5,
            'body' => 'Updated opinion!',
        ]);
    }

    public function test_user_cannot_update_another_users_review(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $product = Product::factory()->create([
            'is_active' => true,
            'category_id' => $this->category->id,
        ]);

        $review = $this->actingAs($user1)->postJson("/api/products/{$product->id}/reviews", [
            'rating' => 4,
        ])->json();

        $response = $this->actingAs($user2)->putJson(
            "/api/products/{$product->id}/reviews/{$review['id']}",
            ['rating' => 1]
        );

        $response->assertStatus(403);
    }

    public function test_user_can_delete_own_review(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create([
            'is_active' => true,
            'category_id' => $this->category->id,
        ]);

        $review = $this->actingAs($user)->postJson("/api/products/{$product->id}/reviews", [
            'rating' => 4,
        ])->json();

        $response = $this->actingAs($user)->deleteJson(
            "/api/products/{$product->id}/reviews/{$review['id']}"
        );

        $response->assertStatus(204);
        $this->assertDatabaseMissing('product_reviews', ['id' => $review['id']]);
    }

    public function test_user_cannot_delete_another_users_review(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $product = Product::factory()->create([
            'is_active' => true,
            'category_id' => $this->category->id,
        ]);

        $review = $this->actingAs($user1)->postJson("/api/products/{$product->id}/reviews", [
            'rating' => 4,
        ])->json();

        $response = $this->actingAs($user2)->deleteJson(
            "/api/products/{$product->id}/reviews/{$review['id']}"
        );

        $response->assertStatus(403);
    }

    public function test_creates_admin_notification_when_review_is_submitted(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create([
            'is_active' => true,
            'category_id' => $this->category->id,
        ]);

        $this->actingAs($user)->postJson("/api/products/{$product->id}/reviews", [
            'rating' => 4,
            'title' => 'Nice!',
            'body' => 'Great product indeed',
        ]);

        $this->assertDatabaseHas('admin_notifications', [
            'type' => 'review_submitted',
        ]);

        $notification = AdminNotification::where('type', 'review_submitted')->first();
        $this->assertNotNull($notification);
        $this->assertEquals($product->id, $notification->data['product_id']);
        $this->assertEquals($user->id, $notification->data['user_id']);
        $this->assertEquals(4, $notification->data['rating']);
    }

    public function test_product_listing_includes_average_rating(): void
    {
        $product = Product::factory()->create([
            'is_active' => true,
            'category_id' => $this->category->id,
        ]);
        $product2 = Product::factory()->create([
            'is_active' => true,
            'category_id' => $this->category->id,
        ]);

        $user = User::factory()->create();
        $this->actingAs($user)->postJson("/api/products/{$product->id}/reviews", [
            'rating' => 4,
        ]);
        ProductReview::where('product_id', $product->id)->update(['is_approved' => true]);

        $this->actingAs($user)->postJson("/api/products/{$product2->id}/reviews", [
            'rating' => 5,
        ]);
        ProductReview::where('product_id', $product2->id)->update(['is_approved' => true]);

        $response = $this->getJson('/api/products');

        $response->assertStatus(200);
        $responseProduct = collect($response->json('data'))->firstWhere('id', $product->id);
        $this->assertNotNull($responseProduct);
        $this->assertEquals(4.0, $responseProduct['reviews_avg_rating']);
        $this->assertEquals(1, $responseProduct['reviews_count']);
    }

    public function test_product_detail_includes_average_rating(): void
    {
        $product = Product::factory()->create([
            'is_active' => true,
            'category_id' => $this->category->id,
        ]);

        $user = User::factory()->create();
        $this->actingAs($user)->postJson("/api/products/{$product->id}/reviews", [
            'rating' => 5,
        ]);
        ProductReview::where('product_id', $product->id)->update(['is_approved' => true]);

        $response = $this->getJson("/api/products/{$product->slug}");

        $response->assertStatus(200);
        $this->assertEquals(5.0, $response->json('reviews_avg_rating'));
        $this->assertEquals(1, $response->json('reviews_count'));
    }
}
