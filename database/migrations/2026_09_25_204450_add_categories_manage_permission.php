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

        DB::table('permissions')->updateOrInsert(
            ['name' => 'categories.manage', 'guard_name' => $guard],
            [
                'module_name' => 'categories',
                'slug' => 'categories.manage',
                'description' => 'Manage categories',
                'updated_at' => $now,
                'created_at' => $now,
            ]
        );

        $adminRoleId = DB::table('roles')
            ->where('name', 'admin')
            ->where('guard_name', $guard)
            ->value('id');

        if (! $adminRoleId) {
            return;
        }

        $permissionId = DB::table('permissions')
            ->where('name', 'categories.manage')
            ->where('guard_name', $guard)
            ->value('id');

        DB::table('role_has_permissions')->updateOrInsert([
            'permission_id' => $permissionId,
            'role_id' => $adminRoleId,
        ]);

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }

    public function down(): void
    {
        $permissionIds = DB::table('permissions')
            ->where('name', 'categories.manage')
            ->pluck('id');

        DB::table('role_has_permissions')->whereIn('permission_id', $permissionIds)->delete();
        DB::table('permissions')->whereIn('id', $permissionIds)->delete();

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }
};
