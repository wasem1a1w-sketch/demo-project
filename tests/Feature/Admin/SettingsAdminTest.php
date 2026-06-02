<?php

namespace Tests\Feature\Admin;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SettingsAdminTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        foreach (['admin.access', 'settings.read', 'settings.update'] as $perm) {
            Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'web']);
        }
        $adminRole->syncPermissions(Permission::pluck('id')->toArray());

        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');
    }

    public function test_admin_can_view_settings(): void
    {
        $response = $this->actingAs($this->admin)->get('/admin/settings');

        $response->assertStatus(200);
    }

    public function test_admin_can_update_settings(): void
    {
        $response = $this->actingAs($this->admin)->put('/admin/settings', [
            'shipping_rate' => 10,
            'free_shipping_threshold' => 100,
            'tax_rate' => 8,
            'stripe_key' => 'pk_test_123',
            'stripe_secret' => 'sk_test_123',
        ]);

        $response->assertStatus(302);
        $this->assertDatabaseHas('settings', [
            'key' => 'shipping_rate',
            'value' => '10',
        ]);
        $this->assertDatabaseHas('settings', [
            'key' => 'stripe_key',
            'value' => 'pk_test_123',
        ]);
    }

    public function test_non_admin_cannot_manage_settings(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/admin/settings')->assertStatus(302);
        $this->actingAs($user)->put('/admin/settings', [])->assertStatus(302);
    }
}
