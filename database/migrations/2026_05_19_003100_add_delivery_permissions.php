<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $guard = 'web';
        $now = now();
        $definitions = [
            ['name' => 'deliveries.view', 'module_name' => 'deliveries', 'description' => 'List deliveries'],
            ['name' => 'deliveries.create', 'module_name' => 'deliveries', 'description' => 'Create deliveries'],
            ['name' => 'deliveries.delete', 'module_name' => 'deliveries', 'description' => 'Delete deliveries'],
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

        $rolePermissions = [
            'admin' => ['deliveries.view', 'deliveries.create', 'deliveries.delete'],
            'technician' => ['deliveries.view'],
            'kit_assigner' => ['deliveries.view', 'deliveries.create'],
        ];

        foreach ($rolePermissions as $roleName => $permissionNames) {
            $roleId = DB::table('roles')->where('name', $roleName)->where('guard_name', $guard)->value('id');

            if (! $roleId) {
                continue;
            }

            $permissionIds = DB::table('permissions')
                ->whereIn('name', $permissionNames)
                ->where('guard_name', $guard)
                ->pluck('id');

            foreach ($permissionIds as $permissionId) {
                DB::table('role_has_permissions')->updateOrInsert([
                    'permission_id' => $permissionId,
                    'role_id' => $roleId,
                ]);
            }
        }
    }

    public function down(): void
    {
        $permissionIds = DB::table('permissions')
            ->whereIn('name', ['deliveries.view', 'deliveries.create', 'deliveries.delete'])
            ->pluck('id');

        DB::table('role_has_permissions')->whereIn('permission_id', $permissionIds)->delete();
        DB::table('permissions')->whereIn('id', $permissionIds)->delete();
    }
};
