<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RoleAndPermissionSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissions = [
            'admin.access',
            'dashboard.view',
            'products.create',
            'products.read',
            'products.update',
            'products.delete',
            'products.images.delete',
            'orders.read',
            'orders.update',
            'categories.create',
            'categories.read',
            'categories.update',
            'categories.delete',
            'users.create',
            'users.read',
            'users.update',
            'users.delete',
            'roles.create',
            'roles.read',
            'roles.update',
            'roles.delete',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(
                ['name' => $permission, 'guard_name' => 'web']
            );
        }

        $admin = Role::firstOrCreate(['name' => 'Admin', 'guard_name' => 'web']);
        $admin->syncPermissions(Permission::pluck('id')->toArray());

        Role::firstOrCreate(['name' => 'Client', 'guard_name' => 'web']);
    }
}
