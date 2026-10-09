<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        DB::table('permissions')->updateOrInsert(
            ['name' => 'appliance.move-units', 'guard_name' => 'web'],
            [
                'module_name' => 'appliances',
                'slug' => 'appliance.move-units',
                'description' => 'Move units from one truck to another',
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );

        $permissionId = DB::table('permissions')
            ->where('name', 'appliance.move-units')
            ->where('guard_name', 'web')
            ->value('id');

        $roleIds = DB::table('roles')
            ->where('guard_name', 'web')
            ->whereIn('name', ['admin', 'developer'])
            ->pluck('id');

        foreach ($roleIds as $roleId) {
            DB::table('role_has_permissions')->updateOrInsert([
                'permission_id' => $permissionId,
                'role_id' => $roleId,
            ]);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        $permissionId = DB::table('permissions')
            ->where('name', 'appliance.move-units')
            ->where('guard_name', 'web')
            ->value('id');

        if ($permissionId) {
            DB::table('role_has_permissions')->where('permission_id', $permissionId)->delete();
            DB::table('permissions')->where('id', $permissionId)->delete();
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
