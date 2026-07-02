<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $permissions = [
            'sales.view' => 'List sales',
            'sales.create' => 'Create sales',
            'sales.edit' => 'Edit sales',
        ];

        $ids = [];
        foreach ($permissions as $name => $description) {
            DB::table('permissions')->updateOrInsert(
                ['name' => $name, 'guard_name' => 'web'],
                [
                    'name' => $name,
                    'guard_name' => 'web',
                    'module_name' => 'sales',
                    'slug' => $name,
                    'description' => $description,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );

            $ids[$name] = DB::table('permissions')->where(['name' => $name, 'guard_name' => 'web'])->value('id');
        }

        $rolePermissions = [
            'admin' => array_keys($permissions),
            'sales' => array_keys($permissions),
            'kit_assigner' => ['sales.view', 'sales.create'],
            'technician' => ['sales.view'],
        ];

        foreach ($rolePermissions as $roleName => $permissionNames) {
            $roleId = DB::table('roles')->where(['name' => $roleName, 'guard_name' => 'web'])->value('id');
            if (! $roleId) {
                continue;
            }

            foreach ($permissionNames as $permissionName) {
                $exists = DB::table('role_has_permissions')->where([
                    'permission_id' => $ids[$permissionName],
                    'role_id' => $roleId,
                ])->exists();

                if (! $exists) {
                    DB::table('role_has_permissions')->insert([
                        'permission_id' => $ids[$permissionName],
                        'role_id' => $roleId,
                    ]);
                }
            }
        }
    }

    public function down(): void
    {
        $ids = DB::table('permissions')->whereIn('name', ['sales.view', 'sales.create', 'sales.edit'])->pluck('id');
        DB::table('role_has_permissions')->whereIn('permission_id', $ids)->delete();
        DB::table('permissions')->whereIn('id', $ids)->delete();
    }
};
