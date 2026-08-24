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
            ['name' => 'testing-flows.manage', 'module_name' => 'testing flows', 'description' => 'Manage testing flow checklists'],
            ['name' => 'deman-flows.manage', 'module_name' => 'deman flows', 'description' => 'Manage demanufacture prompt checklists'],
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
            ->whereIn('name', ['testing-flows.manage', 'deman-flows.manage'])
            ->where('guard_name', $guard)
            ->pluck('id');

        foreach ($permissionIds as $permissionId) {
            DB::table('role_has_permissions')->updateOrInsert([
                'permission_id' => $permissionId,
                'role_id' => $adminRoleId,
            ]);
        }
    }

    public function down(): void
    {
        $permissionIds = DB::table('permissions')
            ->whereIn('name', ['testing-flows.manage', 'deman-flows.manage'])
            ->pluck('id');

        DB::table('role_has_permissions')->whereIn('permission_id', $permissionIds)->delete();
        DB::table('permissions')->whereIn('id', $permissionIds)->delete();
    }
};
