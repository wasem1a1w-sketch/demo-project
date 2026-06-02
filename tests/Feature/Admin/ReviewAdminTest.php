<?php

namespace Tests\Feature\Admin;

use App\Models\Product;
use App\Models\ProductReview;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ReviewAdminTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        foreach (['admin.access', 'reviews.read', 'reviews.update', 'reviews.delete'] as $perm) {
            Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'web']);
        }
        $adminRole->syncPermissions(Permission::pluck('id')->toArray());

        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');
    }

    public function test_admin_can_list_reviews(): void
    {
        $user = User::factory()->create();
        ProductReview::factory()->count(3)->create([
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($this->admin)->get('/admin/reviews');

        $response->assertStatus(200);
    }

    public function test_admin_can_approve_review(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create();
        $review = ProductReview::factory()->create([
            'user_id' => $user->id,
            'product_id' => $product->id,
            'is_approved' => false,
        ]);

        $response = $this->actingAs($this->admin)->patch("/admin/reviews/{$review->id}/approve");

        $response->assertRedirect();
        $this->assertDatabaseHas('product_reviews', [
            'id' => $review->id,
            'is_approved' => true,
        ]);
    }

    public function test_admin_can_reject_review(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create();
        $review = ProductReview::factory()->create([
            'user_id' => $user->id,
            'product_id' => $product->id,
            'is_approved' => true,
        ]);

        $response = $this->actingAs($this->admin)->patch("/admin/reviews/{$review->id}/reject");

        $response->assertRedirect();
        $this->assertDatabaseHas('product_reviews', [
            'id' => $review->id,
            'is_approved' => false,
        ]);
    }

    public function test_admin_can_delete_review(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create();
        $review = ProductReview::factory()->create([
            'user_id' => $user->id,
            'product_id' => $product->id,
        ]);

        $response = $this->actingAs($this->admin)->delete("/admin/reviews/{$review->id}");

        $response->assertRedirect();
        $this->assertDatabaseMissing('product_reviews', ['id' => $review->id]);
    }

    public function test_non_admin_cannot_manage_reviews(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create();
        $review = ProductReview::factory()->create([
            'user_id' => $user->id,
            'product_id' => $product->id,
        ]);

        $this->actingAs($user)->get('/admin/reviews')->assertStatus(302);
        $this->actingAs($user)->delete("/admin/reviews/{$review->id}")->assertStatus(302);
    }
}
