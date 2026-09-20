<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        $guard = 'web';
        $now = now();
        $definitions = [
            ['name' => 'inventory-statuses.manage', 'module_name' => 'inventory statuses', 'description' => 'Manage inventory statuses and auto-set locations'],
            ['name' => 'inventory-locations.manage', 'module_name' => 'inventory locations', 'description' => 'Rename and merge inventory location labels'],
        ];

        foreach ($definitions as $definition) {
            DB::table('permissions')->updateOrInsert(
                ['name' => $definition['name'], 'guard_name' => $guard],
                [
                    'module_name' => $definition['module_name'],
                    'slug' => $definition['name'],
                    'description' => $definition['description'],
                    'updated_at' => $now,
                    'created_at' => $now,
                ]
            );
        }

        $adminRoleId = DB::table('roles')
            ->where('name', 'admin')
            ->where('guard_name', $guard)
            ->value('id');

        if (! $adminRoleId) {
            return;
        }

        $permissionIds = DB::table('permissions')
            ->whereIn('name', ['inventory-statuses.manage', 'inventory-locations.manage'])
            ->where('guard_name', $guard)
            ->pluck('id');

        foreach ($permissionIds as $permissionId) {
            DB::table('role_has_permissions')->updateOrInsert([
                'permission_id' => $permissionId,
                'role_id' => $adminRoleId,
            ]);
        }

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }

    public function down(): void
    {
        $permissionIds = DB::table('permissions')
            ->whereIn('name', ['inventory-statuses.manage', 'inventory-locations.manage'])
            ->pluck('id');

        DB::table('role_has_permissions')->whereIn('permission_id', $permissionIds)->delete();
        DB::table('permissions')->whereIn('id', $permissionIds)->delete();

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }
};
