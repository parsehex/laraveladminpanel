<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('permissions')->updateOrInsert(
            [
                'name' => 'inventory.view',
                'guard_name' => 'web',
            ],
            [
                'name' => 'inventory.view',
                'guard_name' => 'web',
                'module_name' => 'inventory',
                'slug' => 'inventory.view',
                'description' => 'List inventory',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        $permissionId = DB::table('permissions')->where([
            'name' => 'inventory.view',
            'guard_name' => 'web',
        ])->value('id');

        DB::table('roles')
            ->whereIn('name', ['admin', 'technician', 'kit_assigner'])
            ->where('guard_name', 'web')
            ->pluck('id')
            ->each(function ($roleId) use ($permissionId) {
                $exists = DB::table('role_has_permissions')->where([
                    'permission_id' => $permissionId,
                    'role_id' => $roleId,
                ])->exists();

                if (! $exists) {
                    DB::table('role_has_permissions')->insert([
                        'permission_id' => $permissionId,
                        'role_id' => $roleId,
                    ]);
                }
            });
    }

    public function down(): void
    {
        $permissionId = DB::table('permissions')->where([
            'name' => 'inventory.view',
            'guard_name' => 'web',
        ])->value('id');

        if (! $permissionId) {
            return;
        }

        DB::table('role_has_permissions')->where('permission_id', $permissionId)->delete();
        DB::table('permissions')->where('id', $permissionId)->delete();
    }
};
