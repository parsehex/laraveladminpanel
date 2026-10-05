<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Adds the developer staff role and suggestions.complete permission.
 * Only developer receives suggestions.complete; admin keeps every other permission.
 */
return new class extends Migration
{
    private const PERMISSION = 'suggestions.complete';

    private const DEVELOPER_ROLE = 'developer';

    public function up(): void
    {
        $now = now();

        DB::table('permissions')->updateOrInsert(
            ['name' => self::PERMISSION, 'guard_name' => 'web'],
            [
                'module_name' => 'suggestions',
                'slug' => self::PERMISSION,
                'description' => 'Mark website feedback/suggestions as complete',
                'created_at' => $now,
                'updated_at' => $now,
            ],
        );

        $permissionId = DB::table('permissions')
            ->where('name', self::PERMISSION)
            ->where('guard_name', 'web')
            ->value('id');

        $adminRoleId = DB::table('roles')
            ->where('name', 'admin')
            ->where('guard_name', 'web')
            ->value('id');

        if ($adminRoleId && $permissionId) {
            DB::table('role_has_permissions')
                ->where('role_id', $adminRoleId)
                ->where('permission_id', $permissionId)
                ->delete();
        }

        $developerRoleId = DB::table('roles')->where([
            'name' => self::DEVELOPER_ROLE,
            'guard_name' => 'web',
        ])->value('id');

        if (! $developerRoleId) {
            $developerRoleId = DB::table('roles')->insertGetId([
                'name' => self::DEVELOPER_ROLE,
                'guard_name' => 'web',
                'description' => 'Seeded developer role',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $permissionIds = collect();

        if ($adminRoleId) {
            $permissionIds = DB::table('role_has_permissions')
                ->where('role_id', $adminRoleId)
                ->pluck('permission_id');
        }

        if ($permissionId) {
            $permissionIds = $permissionIds->push($permissionId)->unique()->values();
        }

        foreach ($permissionIds as $id) {
            DB::table('role_has_permissions')->updateOrInsert([
                'permission_id' => $id,
                'role_id' => $developerRoleId,
            ]);
        }
    }

    public function down(): void
    {
        $developerRoleId = DB::table('roles')
            ->where('name', self::DEVELOPER_ROLE)
            ->where('guard_name', 'web')
            ->value('id');

        if ($developerRoleId) {
            DB::table('model_has_roles')->where('role_id', $developerRoleId)->delete();
            DB::table('role_has_permissions')->where('role_id', $developerRoleId)->delete();
            DB::table('roles')->where('id', $developerRoleId)->delete();
        }

        $permissionId = DB::table('permissions')
            ->where('name', self::PERMISSION)
            ->where('guard_name', 'web')
            ->value('id');

        if ($permissionId) {
            DB::table('role_has_permissions')->where('permission_id', $permissionId)->delete();
            DB::table('model_has_permissions')->where('permission_id', $permissionId)->delete();
            DB::table('permissions')->where('id', $permissionId)->delete();
        }
    }
};
