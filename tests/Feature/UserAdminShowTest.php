<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class UserAdminShowTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        foreach (['admin.access', 'users.read', 'users.update', 'users.delete'] as $perm) {
            Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'web']);
        }
        $adminRole->syncPermissions(Permission::pluck('id')->toArray());

        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');
    }

    public function test_admin_can_view_user_detail(): void
    {
        $user = User::factory()->create(['name' => 'John Doe', 'email' => 'john@example.com']);

        $response = $this->actingAs($this->admin)->get("/admin/users/{$user->id}");

        $response->assertStatus(200);
        $response->assertSee('John Doe');
        $response->assertSee('john@example.com');
    }

    public function test_admin_cannot_view_nonexistent_user(): void
    {
        $response = $this->actingAs($this->admin)->get('/admin/users/99999');

        $response->assertStatus(404);
    }
}
