<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();
        $permissionNames = [
            'kit-parts.view',
            'kit-parts.create',
            'kit-parts.edit',
        ];

        foreach ($permissionNames as $name) {
            DB::table('permissions')->updateOrInsert(
                ['name' => $name, 'guard_name' => 'web'],
                [
                    'module_name' => 'kit parts',
                    'slug' => $name,
                    'description' => match ($name) {
                        'kit-parts.view' => 'List kit parts',
                        'kit-parts.create' => 'Create kit parts',
                        'kit-parts.edit' => 'Edit kit parts',
                        default => $name,
                    },
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );
        }

        $roleId = DB::table('roles')->where('name', 'kit_assigner')->where('guard_name', 'web')->value('id');
        if (! $roleId) {
            return;
        }

        $permissionIds = DB::table('permissions')
            ->where('guard_name', 'web')
            ->whereIn('name', $permissionNames)
            ->pluck('id');

        foreach ($permissionIds as $permissionId) {
            DB::table('role_has_permissions')->updateOrInsert([
                'permission_id' => $permissionId,
                'role_id' => $roleId,
            ]);
        }
    }

    public function down(): void
    {
        $roleId = DB::table('roles')->where('name', 'kit_assigner')->where('guard_name', 'web')->value('id');
        if (! $roleId) {
            return;
        }

        $permissionIds = DB::table('permissions')
            ->where('guard_name', 'web')
            ->whereIn('name', ['kit-parts.view', 'kit-parts.create', 'kit-parts.edit'])
            ->pluck('id');

        DB::table('role_has_permissions')
            ->where('role_id', $roleId)
            ->whereIn('permission_id', $permissionIds)
            ->delete();
    }
};
