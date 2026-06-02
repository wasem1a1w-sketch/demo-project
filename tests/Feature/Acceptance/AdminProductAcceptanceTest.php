<?php

namespace Tests\Feature\Acceptance;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminProductAcceptanceTest extends TestCase
{
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

    public function test_admin_product_lifecycle(): void
    {
        $response = $this->actingAs($this->admin)->get('/admin/products/create');
        $response->assertStatus(200);

        $response = $this->actingAs($this->admin)->post('/admin/products', [
            'name' => 'Test Product',
            'slug' => 'test-product',
            'price' => 99.99,
            'stock' => 50,
            'category_id' => $this->category->id,
            'is_active' => true,
            'is_featured' => false,
            'short_description' => 'A short description',
            'description' => 'A longer product description',
            'sku' => 'TST-001',
            'weight' => 500,
        ]);

        $response->assertRedirect(route('admin.products'));

        $this->assertDatabaseHas('products', [
            'name' => 'Test Product',
            'slug' => 'test-product',
            'price' => 99.99,
            'stock' => 50,
            'sku' => 'TST-001',
        ]);

        $product = Product::where('slug', 'test-product')->first();

        $this->assertDatabaseHas('user_activity_logs', [
            'type' => 'product_created',
            'user_id' => $this->admin->id,
        ]);

        $response = $this->actingAs($this->admin)->get("/admin/products/{$product->id}");
        $response->assertStatus(200);

        $response = $this->actingAs($this->admin)->get("/admin/products/{$product->id}/edit");
        $response->assertStatus(200);

        $response = $this->actingAs($this->admin)->post("/admin/products/{$product->id}", [
            'name' => 'Updated Product',
            'slug' => 'test-product',
            'price' => 79.99,
            'stock' => 30,
            'category_id' => $this->category->id,
            'is_active' => false,
            'is_featured' => true,
        ]);
        $response->assertRedirect(route('admin.products'));

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'name' => 'Updated Product',
            'price' => 79.99,
            'stock' => 30,
            'is_active' => false,
        ]);

        $this->assertDatabaseHas('user_activity_logs', [
            'type' => 'product_updated',
            'user_id' => $this->admin->id,
        ]);

        $response = $this->actingAs($this->admin)->delete("/admin/products/{$product->id}");
        $response->assertRedirect(route('admin.products'));

        $this->assertDatabaseMissing('products', ['id' => $product->id]);
        $this->assertDatabaseHas('user_activity_logs', [
            'type' => 'product_deleted',
            'user_id' => $this->admin->id,
        ]);
    }
}
