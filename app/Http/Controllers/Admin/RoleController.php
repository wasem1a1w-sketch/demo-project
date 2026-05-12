<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
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

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255|unique:roles,name',
            'permissions' => 'array',
            'permissions.*' => ['string', Rule::exists('permissions', 'name')->where('guard_name', 'web')],
        ]);

        $role = Role::create(['name' => $data['name'], 'guard_name' => 'web']);

        if (!empty($data['permissions'])) {
            $role->syncPermissions($data['permissions']);
        }

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

    public function update(Request $request, Role $role)
    {
        if (in_array($role->name, ['admin', 'client'])) {
            return redirect()->route('admin.users', ['tab' => 'roles'])
                ->with('error', "The '{$role->name}' role is a system role and cannot be modified.");
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('roles', 'name')->ignore($role->id)],
            'permissions' => 'array',
            'permissions.*' => ['string', Rule::exists('permissions', 'name')->where('guard_name', 'web')],
        ]);

        $role->update(['name' => $data['name']]);

        $role->syncPermissions($data['permissions'] ?? []);

        return redirect()->route('admin.users', ['tab' => 'roles'])
            ->with('success', 'Role updated successfully.');
    }

    public function destroy(Role $role)
    {
        if ($role->name === 'admin' || $role->name === 'client') {
            return redirect()->route('admin.users', ['tab' => 'roles'])
                ->with('error', 'Cannot delete system roles.');
        }

        $role->delete();

        return redirect()->route('admin.users', ['tab' => 'roles'])
            ->with('success', 'Role deleted successfully.');
    }
}
