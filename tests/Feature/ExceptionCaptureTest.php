<?php

namespace Tests\Feature;

use App\Models\AppException;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ExceptionCaptureTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $permissions = collect(['admin.access', 'exceptions.read'])
            ->map(fn (string $name) => Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']))
            ->pluck('id');
        $adminRole->syncPermissions($permissions);

        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');
    }

    public function test_reported_exception_is_captured(): void
    {
        report(new \RuntimeException('Test payment failure'));

        $this->assertDatabaseHas('exceptions', [
            'class' => \RuntimeException::class,
            'message' => 'Test payment failure',
        ]);

        $exception = AppException::first();
        $this->assertSame('Test payment failure', $exception->message);
        $this->assertIsArray($exception->trace);
        $this->assertNotEmpty($exception->trace);
        $this->assertSame('testing', $exception->environment);
        $this->assertNotNull($exception->file);
        $this->assertNotNull($exception->line);
    }

    public function test_exceptions_page_requires_permission(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('admin.access');

        $this->actingAs($user)->get('/admin/exceptions')->assertForbidden();
    }

    public function test_exceptions_page_renders_for_admin(): void
    {
        report(new \RuntimeException('Test payment failure'));

        $this->actingAs($this->admin)
            ->get('/admin/exceptions')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Admin/Exceptions/Index'));
    }

    public function test_exceptions_data_endpoint_returns_captured_exceptions(): void
    {
        report(new \RuntimeException('First failure'));
        report(new \RuntimeException('Second failure'));

        $this->actingAs($this->admin)
            ->getJson('/admin/exceptions/data')
            ->assertOk()
            ->assertJsonCount(2, 'data');
    }

    public function test_exception_show_returns_full_record(): void
    {
        report(new \RuntimeException('Detail failure'));

        $exception = AppException::first();

        $this->actingAs($this->admin)
            ->getJson("/admin/exceptions/{$exception->id}")
            ->assertOk()
            ->assertJsonPath('class', \RuntimeException::class)
            ->assertJsonPath('message', 'Detail failure')
            ->assertJsonStructure(['trace' => []]);
    }

    public function test_test_endpoint_records_demo_exception(): void
    {
        $this->actingAs($this->admin)
            ->post('/admin/exceptions/test')
            ->assertRedirect();

        $this->assertDatabaseHas('exceptions', [
            'class' => \RuntimeException::class,
            'message' => 'Payment provider timeout after 30s (demo).',
        ]);
    }

    public function test_prune_command_deletes_only_old_records(): void
    {
        report(new \RuntimeException('Old failure'));
        $old = AppException::first();
        $old->update(['created_at' => now()->subDays(40)]);

        report(new \RuntimeException('Recent failure'));

        $this->artisan('exceptions:prune --days=30')->assertSuccessful();

        $this->assertDatabaseMissing('exceptions', ['id' => $old->id]);
        $this->assertDatabaseHas('exceptions', ['message' => 'Recent failure']);
    }
}
