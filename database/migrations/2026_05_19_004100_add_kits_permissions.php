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
            ['name' => 'kits.view', 'module_name' => 'kits', 'description' => 'View kits module'],
            ['name' => 'kits.manage', 'module_name' => 'kits', 'description' => 'Manage kits, inventory, and assignments'],
            ['name' => 'kits.build', 'module_name' => 'kits', 'description' => 'Build assigned kits and send messages'],
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

        DB::table('roles')->updateOrInsert(
            ['name' => 'kit_maker', 'guard_name' => $guard],
            [
                'description' => 'Seeded kit_maker role',
                'updated_at' => $now,
                'created_at' => $now,
            ]
        );

        $rolePermissions = [
            'admin' => ['kits.view', 'kits.manage', 'kits.build'],
            'kit_assigner' => ['kits.view', 'kits.manage', 'kits.build'],
            'kit_maker' => ['kits.view', 'kits.build'],
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
            ->whereIn('name', ['kits.view', 'kits.manage', 'kits.build'])
            ->pluck('id');

        DB::table('role_has_permissions')->whereIn('permission_id', $permissionIds)->delete();
        DB::table('permissions')->whereIn('id', $permissionIds)->delete();
    }
};
