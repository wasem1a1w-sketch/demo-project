<?php

namespace Tests\Feature\Admin;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SettingsTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        Setting::set('shipping_rate', 15);
        Setting::set('free_shipping_threshold', 100);
        Setting::set('tax_rate', 10);

        $adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $permissions = ['admin.access', 'settings.read', 'settings.update'];
        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }
        $adminRole->syncPermissions(Permission::pluck('id')->toArray());

        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');
    }

    public function test_admin_can_view_settings_page(): void
    {
        $response = $this->actingAs($this->admin)->get('/admin/settings');

        $response->assertStatus(200);
    }

    public function test_updates_shipping_rate(): void
    {
        $this->assertEquals('15', Setting::get('shipping_rate'));

        Setting::set('shipping_rate', '25');
        $this->assertEquals('25', Setting::get('shipping_rate'));

        $response = $this->actingAs($this->admin)->put('/admin/settings', [
            'shipping_rate' => 25,
            'free_shipping_threshold' => 100,
            'tax_rate' => 10,
        ]);

        $response->assertStatus(302);
        $response->assertSessionHasNoErrors();

        $stored = \Illuminate\Support\Facades\DB::table('settings')->where('key', 'shipping_rate')->first();
        $this->assertEquals('25', $stored->value);
    }

    public function test_updates_tax_rate(): void
    {
        $response = $this->actingAs($this->admin)->put('/admin/settings', [
            'shipping_rate' => 15,
            'free_shipping_threshold' => 100,
            'tax_rate' => 12,
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertEquals('12', Setting::get('tax_rate'));
    }

    public function test_updates_free_shipping_threshold(): void
    {
        $response = $this->actingAs($this->admin)->put('/admin/settings', [
            'shipping_rate' => 15,
            'free_shipping_threshold' => 200,
            'tax_rate' => 10,
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertEquals('200', Setting::get('free_shipping_threshold'));
    }

    public function test_rejects_invalid_tax_rate(): void
    {
        $response = $this->actingAs($this->admin)->put('/admin/settings', [
            'shipping_rate' => 15,
            'free_shipping_threshold' => 100,
            'tax_rate' => 150,
        ]);

        $response->assertSessionHasErrors('tax_rate');
        $this->assertEquals('10', Setting::get('tax_rate'));
    }

    public function test_rejects_negative_shipping_rate(): void
    {
        $response = $this->actingAs($this->admin)->put('/admin/settings', [
            'shipping_rate' => -5,
            'free_shipping_threshold' => 100,
            'tax_rate' => 10,
        ]);

        $response->assertSessionHasErrors('shipping_rate');
    }

    public function test_rejects_negative_threshold(): void
    {
        $response = $this->actingAs($this->admin)->put('/admin/settings', [
            'shipping_rate' => 15,
            'free_shipping_threshold' => -1,
            'tax_rate' => 10,
        ]);

        $response->assertSessionHasErrors('free_shipping_threshold');
    }

    public function test_guest_redirected(): void
    {
        $response = $this->get('/admin/settings');

        $response->assertRedirect('/login');
    }

    public function test_non_admin_cannot_access(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/admin/settings');

        $response->assertRedirect('/');
        $response->assertSessionHas('error', 'Access denied.');
    }
}
