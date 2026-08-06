<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PulseTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $permission = Permission::firstOrCreate(['name' => 'admin.access', 'guard_name' => 'web']);
        $adminRole->syncPermissions([$permission->id]);

        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');
    }

    public function test_pulse_defaults_to_native_behavior_without_redirect(): void
    {
        $response = $this->actingAs($this->admin)->get('/pulse');

        $response->assertOk();
        $response->assertSessionMissing('errors');
        $this->assertNull($response->headers->get('Location'));
    }

    public function test_pulse_accepts_every_period_filter(): void
    {
        foreach (['1_hour', '6_hours', '24_hours', '7_days'] as $period) {
            $this->actingAs($this->admin)
                ->get("/pulse?period={$period}")
                ->assertOk();
        }
    }

    public function test_pulse_requires_admin_access(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/pulse');

        $response->assertStatus(403);
    }
}
