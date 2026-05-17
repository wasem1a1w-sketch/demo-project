<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ProductAdminShowTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        foreach (['admin.access', 'products.read', 'products.update', 'products.delete'] as $perm) {
            Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'web']);
        }
        $adminRole->syncPermissions(Permission::pluck('id')->toArray());

        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');
    }

    public function test_admin_can_view_product_detail(): void
    {
        $category = Category::factory()->create(['is_active' => true]);
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'name' => 'Test Product Detail',
        ]);

        $response = $this->actingAs($this->admin)->get("/admin/products/{$product->id}");

        $response->assertStatus(200);
        $response->assertSee('Test Product Detail');
        $response->assertSee($category->name);
    }

    public function test_admin_can_view_product_detail_with_images(): void
    {
        $product = Product::factory()->create(['name' => 'Product With Images']);

        $response = $this->actingAs($this->admin)->get("/admin/products/{$product->id}");

        $response->assertStatus(200);
    }

    public function test_admin_cannot_view_nonexistent_product(): void
    {
        $response = $this->actingAs($this->admin)->get('/admin/products/99999');

        $response->assertStatus(404);
    }
}
