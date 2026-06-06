<?php

namespace Tests\Feature\Admin;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class OrderAdminTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        foreach (['admin.access', 'orders.read', 'orders.update'] as $perm) {
            Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'web']);
        }
        $adminRole->syncPermissions(Permission::pluck('id')->toArray());

        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');
    }

    public function test_admin_can_list_orders(): void
    {
        Order::factory()->count(3)->create();

        $response = $this->actingAs($this->admin)->get('/admin/orders');

        $response->assertStatus(200);
    }

    public function test_admin_can_view_order(): void
    {
        $order = Order::factory()->create();

        $response = $this->actingAs($this->admin)->get("/admin/orders/{$order->id}");

        $response->assertStatus(200);
    }

    public function test_admin_can_update_order_status(): void
    {
        $order = \App\Models\Order::factory()->create(['status' => OrderStatus::Pending]);

        $response = $this->actingAs($this->admin)->put("/admin/orders/{$order->id}", [
            'status' => OrderStatus::Processing->value,
            'payment_status' => 'pending',
        ]);

        $response->assertStatus(302);
        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => OrderStatus::Processing,
        ]);
    }

    public function test_non_admin_cannot_manage_orders(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/admin/orders')->assertStatus(302);
        $this->actingAs($user)->put('/admin/orders/1', [])->assertStatus(302);
    }
}
