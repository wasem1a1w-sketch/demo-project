<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\RoleRequest;
use App\Models\UserActivityLog;
use Inertia\Inertia;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    public function create()
    {
        $permissions = Permission::orderBy('name')->get();

        $grouped = $permissions->groupBy(function ($p) {
            $parts = explode('.', $p->name);
            return count($parts) > 1 ? $parts[0] : 'general';
        });

        return Inertia::render('Admin/Users/Roles/Create', [
            'groupedPermissions' => $grouped,
        ]);
    }

    public function store(RoleRequest $request)
    {
        $data = $request->validated();

        $role = Role::create(['name' => $data['name'], 'guard_name' => 'web']);

        if (!empty($data['permissions'])) {
            $role->syncPermissions($data['permissions']);
        }

        UserActivityLog::record(auth()->id(), 'role_created', "Role created: {$role->name}");

        return redirect()->route('admin.users', ['tab' => 'roles'])
            ->with('success', 'Role created successfully.');
    }

    public function edit(Role $role)
    {
        if (in_array($role->name, ['admin', 'client'])) {
            return redirect()->route('admin.users', ['tab' => 'roles'])
                ->with('error', "The '{$role->name}' role is a system role and cannot be modified.");
        }

        $role->load('permissions');

        $permissions = Permission::orderBy('name')->get();

        $grouped = $permissions->groupBy(function ($p) {
            $parts = explode('.', $p->name);
            return count($parts) > 1 ? $parts[0] : 'general';
        });

        return Inertia::render('Admin/Users/Roles/Edit', [
            'role' => $role,
            'rolePermissions' => $role->permissions->pluck('name'),
            'groupedPermissions' => $grouped,
        ]);
    }

    public function update(RoleRequest $request, Role $role)
    {
        if (in_array($role->name, ['admin', 'client'])) {
            return redirect()->route('admin.users', ['tab' => 'roles'])
                ->with('error', "The '{$role->name}' role is a system role and cannot be modified.");
        }

        $data = $request->validated();

        $role->update(['name' => $data['name']]);
        $role->syncPermissions($data['permissions'] ?? []);

        UserActivityLog::record(auth()->id(), 'role_updated', "Role updated: {$role->name}");

        return redirect()->route('admin.users', ['tab' => 'roles'])
            ->with('success', 'Role updated successfully.');
    }

    public function destroy(Role $role)
    {
        if ($role->name === 'admin' || $role->name === 'client') {
            return redirect()->route('admin.users', ['tab' => 'roles'])
                ->with('error', 'Cannot delete system roles.');
        }

        UserActivityLog::record(auth()->id(), 'role_deleted', "Role deleted: {$role->name}");

        $role->delete();

        return redirect()->route('admin.users', ['tab' => 'roles'])
            ->with('success', 'Role deleted successfully.');
    }
}
