<?php

namespace Tests\Feature\Admin;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\Support\InteractsWithKafka;
use Tests\TestCase;

class ProductAdminTest extends TestCase
{
    use InteractsWithKafka;
    use RefreshDatabase;

    private User $admin;

    private Category $category;

    protected function setUp(): void
    {
        parent::setUp();

        $adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        foreach (['admin.access', 'products.create', 'products.read', 'products.update', 'products.delete'] as $perm) {
            Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'web']);
        }
        $adminRole->syncPermissions(Permission::pluck('id')->toArray());

        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');
        $this->category = Category::factory()->create(['is_active' => true]);
    }

    public function test_admin_can_list_products(): void
    {
        Product::factory()->count(3)->create(['category_id' => $this->category->id]);

        $response = $this->actingAs($this->admin)->get('/admin/products');

        $response->assertStatus(200);
    }

    public function test_admin_can_view_create_product_form(): void
    {
        $response = $this->actingAs($this->admin)->get('/admin/products/create');

        $response->assertStatus(200);
    }

    public function test_admin_can_create_product(): void
    {
        $response = $this->actingAs($this->admin)->post('/admin/products', [
            'name' => 'New Product',
            'slug' => 'new-product',
            'price' => 49.99,
            'stock' => 100,
            'category_id' => $this->category->id,
            'is_active' => true,
            'is_featured' => false,
            'short_description' => 'Short desc',
            'description' => 'Full description',
            'sku' => 'SKU-001',
            'weight' => 250,
        ]);

        $response->assertRedirect(route('admin.products'));
        $this->assertDatabaseHas('products', [
            'name' => 'New Product',
            'slug' => 'new-product',
            'price' => 49.99,
            'stock' => 100,
        ]);
    }

    public function test_admin_can_view_product(): void
    {
        $product = Product::factory()->create(['category_id' => $this->category->id]);

        $response = $this->actingAs($this->admin)->get("/admin/products/{$product->id}");

        $response->assertStatus(200);
    }

    public function test_admin_can_view_edit_product_form(): void
    {
        $product = Product::factory()->create(['category_id' => $this->category->id]);

        $response = $this->actingAs($this->admin)->get("/admin/products/{$product->id}/edit");

        $response->assertStatus(200);
    }

    public function test_admin_can_update_product(): void
    {
        $product = Product::factory()->create([
            'name' => 'Original Name',
            'category_id' => $this->category->id,
        ]);

        $response = $this->actingAs($this->admin)->post("/admin/products/{$product->id}", [
            'name' => 'Updated Name',
            'slug' => $product->slug,
            'price' => 99.99,
            'stock' => 200,
            'category_id' => $this->category->id,
            'is_active' => false,
        ]);

        $response->assertRedirect(route('admin.products'));
        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'name' => 'Updated Name',
            'price' => 99.99,
            'stock' => 200,
            'is_active' => false,
        ]);
    }

    public function test_admin_can_delete_product(): void
    {
        $product = Product::factory()->create(['category_id' => $this->category->id]);

        $response = $this->actingAs($this->admin)->delete("/admin/products/{$product->id}");

        $response->assertRedirect(route('admin.products'));
        $this->assertDatabaseMissing('products', ['id' => $product->id]);
    }

    public function test_logs_product_activity(): void
    {
        $this->fakeKafka();
        $this->actingAs($this->admin)->post('/admin/products', [
            'name' => 'Activity Test',
            'slug' => 'activity-test',
            'price' => 10.00,
            'stock' => 5,
            'category_id' => $this->category->id,
            'is_active' => true,
        ]);

        $product = Product::where('slug', 'activity-test')->first();

        $this->actingAs($this->admin)->post("/admin/products/{$product->id}", [
            'name' => 'Activity Updated',
            'slug' => 'activity-test',
            'price' => 15.00,
            'stock' => 10,
            'category_id' => $this->category->id,
        ]);

        $this->actingAs($this->admin)->delete("/admin/products/{$product->id}");

        $this->drainActivityLogs();

        $this->assertDatabaseHas('user_activity_logs', [
            'type' => 'product_created',
            'user_id' => $this->admin->id,
            'description' => 'Product created: Activity Test',
        ]);

        $this->assertDatabaseHas('user_activity_logs', [
            'type' => 'product_updated',
            'user_id' => $this->admin->id,
        ]);

        $this->assertDatabaseHas('user_activity_logs', [
            'type' => 'product_deleted',
            'user_id' => $this->admin->id,
        ]);
    }

    public function test_non_admin_cannot_manage_products(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/admin/products')->assertStatus(302);
        $this->actingAs($user)->get('/admin/products/create')->assertStatus(302);
        $this->actingAs($user)->post('/admin/products', [])->assertStatus(302);
        $this->actingAs($user)->delete('/admin/products/1')->assertStatus(302);
    }
}
