<?php

namespace Tests\Feature\Admin;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductReview;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ReviewTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $permissions = ['admin.access', 'reviews.read', 'reviews.update', 'reviews.delete'];
        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }
        $adminRole->syncPermissions(Permission::pluck('id')->toArray());

        $category = Category::factory()->create(['is_active' => true]);

        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');

        $this->product = Product::factory()->create([
            'is_active' => true,
            'category_id' => $category->id,
        ]);
    }

    public function test_admin_can_list_all_reviews_including_unapproved(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $this->actingAs($user1)->postJson("/api/products/{$this->product->id}/reviews", [
            'rating' => 5, 'title' => 'Approved',
        ]);
        ProductReview::where('title', 'Approved')->update(['is_approved' => true]);

        $this->actingAs($user2)->postJson("/api/products/{$this->product->id}/reviews", [
            'rating' => 2, 'title' => 'Pending',
        ]);

        $response = $this->actingAs($this->admin)->getJson('/admin/reviews');

        $response->assertStatus(200);
        $titles = collect($response->json('data'))->pluck('title');
        $this->assertContains('Approved', $titles);
        $this->assertContains('Pending', $titles);
    }

    public function test_non_admin_cannot_access_reviews_list(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/admin/reviews');

        $response->assertRedirect('/');
        $response->assertSessionHas('error', 'Access denied.');
    }

    public function test_admin_can_approve_a_review(): void
    {
        $user = User::factory()->create();
        $review = $this->actingAs($user)->postJson("/api/products/{$this->product->id}/reviews", [
            'rating' => 4,
        ])->json();

        $response = $this->actingAs($this->admin)->patch("/admin/reviews/{$review['id']}/approve");

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('product_reviews', [
            'id' => $review['id'],
            'is_approved' => true,
        ]);
    }

    public function test_admin_can_reject_a_review(): void
    {
        $user = User::factory()->create();
        $review = $this->actingAs($user)->postJson("/api/products/{$this->product->id}/reviews", [
            'rating' => 4,
        ])->json();
        ProductReview::where('id', $review['id'])->update(['is_approved' => true]);

        $response = $this->actingAs($this->admin)->patch("/admin/reviews/{$review['id']}/reject");

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('product_reviews', [
            'id' => $review['id'],
            'is_approved' => false,
        ]);
    }

    public function test_admin_can_delete_a_review(): void
    {
        $user = User::factory()->create();
        $review = $this->actingAs($user)->postJson("/api/products/{$this->product->id}/reviews", [
            'rating' => 4,
        ])->json();

        $response = $this->actingAs($this->admin)->delete("/admin/reviews/{$review['id']}");

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseMissing('product_reviews', ['id' => $review['id']]);
    }
}
