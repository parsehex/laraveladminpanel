<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $guard = 'web';
        $now = now();

        $existing = DB::table('permissions')
            ->where('name', 'deliveries.delete')
            ->where('guard_name', $guard)
            ->first();

        if ($existing) {
            DB::table('permissions')
                ->where('id', $existing->id)
                ->update([
                    'name' => 'deliveries.complete',
                    'slug' => 'deliveries.complete',
                    'description' => 'Complete or restore deliveries',
                    'updated_at' => $now,
                ]);

            return;
        }

        DB::table('permissions')->updateOrInsert(
            ['name' => 'deliveries.complete', 'guard_name' => $guard],
            [
                'module_name' => 'deliveries',
                'slug' => 'deliveries.complete',
                'description' => 'Complete or restore deliveries',
                'updated_at' => $now,
                'created_at' => $now,
            ]
        );

        $roleId = DB::table('roles')->where('name', 'admin')->where('guard_name', $guard)->value('id');
        $permissionId = DB::table('permissions')
            ->where('name', 'deliveries.complete')
            ->where('guard_name', $guard)
            ->value('id');

        if ($roleId && $permissionId) {
            DB::table('role_has_permissions')->updateOrInsert([
                'permission_id' => $permissionId,
                'role_id' => $roleId,
            ]);
        }
    }

    public function down(): void
    {
        $guard = 'web';
        $now = now();

        $existing = DB::table('permissions')
            ->where('name', 'deliveries.complete')
            ->where('guard_name', $guard)
            ->first();

        if ($existing) {
            DB::table('permissions')
                ->where('id', $existing->id)
                ->update([
                    'name' => 'deliveries.delete',
                    'slug' => 'deliveries.delete',
                    'description' => 'Delete deliveries',
                    'updated_at' => $now,
                ]);
        }
    }
};
