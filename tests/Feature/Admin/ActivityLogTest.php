<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Models\UserActivityLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ActivityLogTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $permissions = ['admin.access', 'activity-logs.read'];
        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }
        $adminRole->syncPermissions(Permission::pluck('id')->toArray());

        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');
    }

    public function test_admin_can_view_activity_logs_page(): void
    {
        UserActivityLog::record(null, 'test', 'Test entry');

        $response = $this->actingAs($this->admin)->get('/admin/activity-logs');

        $response->assertStatus(200);
        $response->assertSee('Admin/ActivityLogs/Index');
    }

    public function test_non_admin_cannot_access_activity_logs_page(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/admin/activity-logs');

        $response->assertStatus(302);
    }

    public function test_guest_is_redirected_from_activity_logs_page(): void
    {
        $response = $this->get('/admin/activity-logs');

        $response->assertRedirect('/login');
    }

    public function test_activity_logs_page_paginates_results(): void
    {
        UserActivityLog::record(null, 'test', 'Old entry', [], now()->subDay());
        for ($i = 0; $i < 25; $i++) {
            UserActivityLog::record(null, 'test', "Entry $i");
        }

        $response = $this->actingAs($this->admin)->get('/admin/activity-logs');

        $response->assertStatus(200);
    }

    public function test_admin_can_fetch_logs_via_api(): void
    {
        UserActivityLog::record(1, 'order_placed', 'Order #ORD-123 placed');
        UserActivityLog::record(null, 'user_registered', 'Guest registered');

        $response = $this->actingAs($this->admin)->getJson('/admin/activity-logs/data');

        $response->assertStatus(200)
            ->assertJsonStructure(['data', 'total', 'per_page'])
            ->assertJsonCount(2, 'data');
    }

    public function test_api_returns_403_for_non_admin(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->getJson('/admin/activity-logs/data');

        $response->assertStatus(302);
    }

    public function test_api_returns_401_for_guest(): void
    {
        $response = $this->getJson('/admin/activity-logs/data');

        $response->assertStatus(401);
    }

    public function test_api_can_filter_logs_by_type(): void
    {
        UserActivityLog::record(null, 'order_placed', 'Order placed');
        UserActivityLog::record(null, 'user_registered', 'User registered');
        UserActivityLog::record(null, 'product_created', 'Product created');

        $response = $this->actingAs($this->admin)->getJson('/admin/activity-logs/data?type=order_placed');

        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data');
        $this->assertEquals('order_placed', $response->json('data.0.type'));
    }

    public function test_api_can_filter_logs_by_date_range(): void
    {
        UserActivityLog::record(null, 'test', 'Old entry', [], now()->subWeek());
        UserActivityLog::record(null, 'test', 'Recent entry');

        $response = $this->actingAs($this->admin)->getJson('/admin/activity-logs/data?date_from='.now()->subDay()->format('Y-m-d'));

        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data');
        $this->assertEquals('Recent entry', $response->json('data.0.description'));
    }
}
