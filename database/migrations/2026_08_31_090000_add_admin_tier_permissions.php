<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Replaces ad hoc `hasRole('admin') || $user->role === 'admin'` checks scattered
 * across controllers and views with proper Spatie permissions, so Super Admin
 * (via Gate::before) and any future admin-tier role stay in sync automatically.
 */
return new class extends Migration
{
    /**
     * @var array<int, array{name: string, module_name: string, description: string}>
     */
    private array $permissions = [
        [
            'name' => 'inventory.value.view',
            'module_name' => 'inventory',
            'description' => 'View inventory dollar-value totals',
        ],
        [
            'name' => 'appliance.cost-override',
            'module_name' => 'appliances',
            'description' => 'Apply cost % override to all trucks at once',
        ],
        [
            'name' => 'notification-settings.manage',
            'module_name' => 'notification settings',
            'description' => 'Manage module notification subscribers',
        ],
    ];

    public function up(): void
    {
        $now = now();

        foreach ($this->permissions as $permission) {
            DB::table('permissions')->updateOrInsert(
                ['name' => $permission['name'], 'guard_name' => 'web'],
                [
                    'module_name' => $permission['module_name'],
                    'slug' => $permission['name'],
                    'description' => $permission['description'],
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );
        }

        $roleId = DB::table('roles')->where('name', 'admin')->where('guard_name', 'web')->value('id');

        if (! $roleId) {
            return;
        }

        $permissionIds = DB::table('permissions')
            ->where('guard_name', 'web')
            ->whereIn('name', array_column($this->permissions, 'name'))
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
        $permissionIds = DB::table('permissions')
            ->where('guard_name', 'web')
            ->whereIn('name', array_column($this->permissions, 'name'))
            ->pluck('id');

        DB::table('role_has_permissions')->whereIn('permission_id', $permissionIds)->delete();
        DB::table('permissions')->whereIn('id', $permissionIds)->delete();
    }
};
