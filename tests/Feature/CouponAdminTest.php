<?php

namespace Tests\Feature\Admin;

use App\Models\Coupon;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CouponAdminTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $permissions = [
            'admin.access',
            'coupons.create', 'coupons.read', 'coupons.update', 'coupons.delete',
        ];
        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }
        $adminRole->syncPermissions(Permission::pluck('id')->toArray());

        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');
    }

    public function test_admin_can_list_coupons(): void
    {
        Coupon::factory()->count(3)->create();

        $response = $this->actingAs($this->admin)->get('/admin/coupons');

        $response->assertStatus(200);
    }

    public function test_admin_can_view_create_coupon_form(): void
    {
        $response = $this->actingAs($this->admin)->get('/admin/coupons/create');

        $response->assertStatus(200);
    }

    public function test_admin_can_create_coupon(): void
    {
        $response = $this->actingAs($this->admin)->post('/admin/coupons', [
            'code' => 'SUMMER20',
            'type' => 'percentage',
            'value' => 20,
            'min_order_amount' => 50,
            'max_discount_amount' => 100,
            'usage_limit' => 100,
            'is_active' => true,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('coupons', [
            'code' => 'SUMMER20',
            'type' => 'percentage',
            'value' => 20.00,
        ]);
    }

    public function test_admin_can_view_edit_coupon_form(): void
    {
        $coupon = Coupon::factory()->create();

        $response = $this->actingAs($this->admin)->get("/admin/coupons/{$coupon->id}/edit");

        $response->assertStatus(200);
    }

    public function test_admin_can_update_coupon(): void
    {
        $coupon = Coupon::factory()->create(['value' => 10]);

        $response = $this->actingAs($this->admin)->post("/admin/coupons/{$coupon->id}", [
            'code' => 'UPDATED20',
            'type' => 'fixed',
            'value' => 15,
            'is_active' => false,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('coupons', [
            'id' => $coupon->id,
            'code' => 'UPDATED20',
            'type' => 'fixed',
            'value' => 15.00,
            'is_active' => false,
        ]);
    }

    public function test_admin_can_delete_coupon(): void
    {
        $coupon = Coupon::factory()->create();

        $response = $this->actingAs($this->admin)->delete("/admin/coupons/{$coupon->id}");

        $response->assertRedirect();
        $this->assertDatabaseMissing('coupons', ['id' => $coupon->id]);
    }

    public function test_logs_coupon_created(): void
    {
        $this->actingAs($this->admin)->post('/admin/coupons', [
            'code' => 'LOGTEST',
            'type' => 'percentage',
            'value' => 10,
        ]);

        $this->assertDatabaseHas('user_activity_logs', [
            'type' => 'coupon_created',
            'user_id' => $this->admin->id,
            'description' => 'Coupon created: LOGTEST',
        ]);
    }

    public function test_logs_coupon_updated(): void
    {
        $coupon = Coupon::factory()->create(['code' => 'OLDCODE']);

        $this->actingAs($this->admin)->post("/admin/coupons/{$coupon->id}", [
            'code' => 'NEWCODE',
            'type' => 'percentage',
            'value' => 10,
        ]);

        $this->assertDatabaseHas('user_activity_logs', [
            'type' => 'coupon_updated',
            'user_id' => $this->admin->id,
            'description' => 'Coupon updated: NEWCODE',
        ]);
    }

    public function test_logs_coupon_deleted(): void
    {
        $coupon = Coupon::factory()->create(['code' => 'DELETEME']);

        $this->actingAs($this->admin)->delete("/admin/coupons/{$coupon->id}");

        $this->assertDatabaseHas('user_activity_logs', [
            'type' => 'coupon_deleted',
            'user_id' => $this->admin->id,
            'description' => 'Coupon deleted: DELETEME',
        ]);
    }

    public function test_non_admin_cannot_manage_coupons(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/admin/coupons')->assertStatus(302);
        $this->actingAs($user)->get('/admin/coupons/create')->assertStatus(302);
        $this->actingAs($user)->post('/admin/coupons', [])->assertStatus(302);
    }
}
