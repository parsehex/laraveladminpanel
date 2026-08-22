<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Schema;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $guard = 'web';

        $definitions = [
            ['name' => 'admin.dashboard', 'module_name' => 'dashboard', 'description' => 'Access admin dashboard'],
            ['name' => 'executive-dashboard.view', 'module_name' => 'executive dashboard', 'description' => 'View executive dashboard'],
            ['name' => 'users.view', 'module_name' => 'users', 'description' => 'List users'],
            ['name' => 'users.create', 'module_name' => 'users', 'description' => 'Create users'],
            ['name' => 'users.edit', 'module_name' => 'users', 'description' => 'Edit users'],
            ['name' => 'users.delete', 'module_name' => 'users', 'description' => 'Delete users'],
            ['name' => 'roles.view', 'module_name' => 'roles', 'description' => 'List roles'],
            ['name' => 'roles.create', 'module_name' => 'roles', 'description' => 'Create roles'],
            ['name' => 'roles.edit', 'module_name' => 'roles', 'description' => 'Edit roles'],
            ['name' => 'roles.delete', 'module_name' => 'roles', 'description' => 'Delete roles'],
            ['name' => 'category.create', 'module_name' => 'categories', 'description' => 'Create categories'],
            ['name' => 'parts.view', 'module_name' => 'parts', 'description' => 'List parts'],
            ['name' => 'parts.create', 'module_name' => 'parts', 'description' => 'Create parts'],
            ['name' => 'parts.edit', 'module_name' => 'parts', 'description' => 'Edit parts'],
            ['name' => 'parts.delete', 'module_name' => 'parts', 'description' => 'Delete parts'],
            ['name' => 'kit-parts.view', 'module_name' => 'kit parts', 'description' => 'List kit parts'],
            ['name' => 'kit-parts.create', 'module_name' => 'kit parts', 'description' => 'Create kit parts'],
            ['name' => 'kit-parts.edit', 'module_name' => 'kit parts', 'description' => 'Edit kit parts'],
            ['name' => 'kit-parts.delete', 'module_name' => 'kit parts', 'description' => 'Delete kit parts'],
            ['name' => 'models.view', 'module_name' => 'models', 'description' => 'List models'],
            ['name' => 'models.create', 'module_name' => 'models', 'description' => 'Create models'],
            ['name' => 'models.edit', 'module_name' => 'models', 'description' => 'Edit models'],
            ['name' => 'models.delete', 'module_name' => 'models', 'description' => 'Delete models'],
            ['name' => 'inventory.view', 'module_name' => 'inventory', 'description' => 'List inventory'],
            ['name' => 'testing-flows.manage', 'module_name' => 'testing flows', 'description' => 'Manage testing flow checklists'],
            ['name' => 'sales.view', 'module_name' => 'sales', 'description' => 'List sales'],
            ['name' => 'sales.create', 'module_name' => 'sales', 'description' => 'Create sales'],
            ['name' => 'sales.edit', 'module_name' => 'sales', 'description' => 'Edit sales'],
            ['name' => 'deliveries.view', 'module_name' => 'deliveries', 'description' => 'List deliveries'],
            ['name' => 'deliveries.create', 'module_name' => 'deliveries', 'description' => 'Create deliveries'],
            ['name' => 'deliveries.delete', 'module_name' => 'deliveries', 'description' => 'Delete deliveries'],
            ['name' => 'kits.view', 'module_name' => 'kits', 'description' => 'View kits module'],
            ['name' => 'kits.manage', 'module_name' => 'kits', 'description' => 'Manage kits, inventory, and assignments'],
            ['name' => 'kits.build', 'module_name' => 'kits', 'description' => 'Build assigned kits and send messages'],
            ['name' => 'appliance.create', 'module_name' => 'appliances', 'description' => 'Create truck appliances'],
            ['name' => 'appliance.edit', 'module_name' => 'appliances', 'description' => 'Edit truck appliances'],
            ['name' => 'appliance.delete', 'module_name' => 'appliances', 'description' => 'Delete truck appliances'],
            ['name' => 'trucks.view', 'module_name' => 'trucks', 'description' => 'List trucks'],
            ['name' => 'trucks.create', 'module_name' => 'trucks', 'description' => 'Create trucks'],
            ['name' => 'trucks.edit', 'module_name' => 'trucks', 'description' => 'Edit trucks'],
            ['name' => 'trucks.delete', 'module_name' => 'trucks', 'description' => 'Delete trucks'],
        ];

        foreach ($definitions as $def) {
            Permission::firstOrCreate(
                ['name' => $def['name'], 'guard_name' => $guard],
                [
                    'module_name' => $def['module_name'],
                    'slug' => $def['name'],
                    'description' => $def['description'],
                ]
            );
        }

        /*
        | Replace all roles so lowercase names (admin, user, …) are not merged with
        | legacy PascalCase rows under case-insensitive collations or legacy data constraints.
        | Capture role→user ids first so assignments can be restored after recreate.
        */
        $roleUserIds = [];
        foreach (Role::query()->get(['id', 'name']) as $role) {
            $roleUserIds[strtolower((string) $role->name)] = $role->users()->pluck('id')->all();
        }

        Schema::disableForeignKeyConstraints();
        try {
            Role::query()->delete();
        } finally {
            Schema::enableForeignKeyConstraints();
        }

        /*
        | Ben's Appliances–style roles (lowercase, as in legacy Manage UI).
        | Adjust permission lists when you add Trucks, Parts, Kits, etc.
        */
        $roles = [
            'admin' => Permission::pluck('name')->all(),
            'technician' => [
                'admin.dashboard',
                'users.view',
                'inventory.view',
                'sales.view',
                'deliveries.view',
                'kits.view', 'kits.build',
                'parts.view',
                'models.view',
                'appliance.edit',
                'trucks.view', 'trucks.edit',
            ],
            'kit_assigner' => [
                'admin.dashboard',
                'inventory.view',
                'sales.view', 'sales.create',
                'deliveries.view', 'deliveries.create',
                'kits.view', 'kits.manage', 'kits.build',
                'parts.view',
                'models.view',
                'appliance.create', 'appliance.edit',
                'trucks.view', 'trucks.create', 'trucks.edit',
            ],
            'kit_maker' => [
                'admin.dashboard',
                'kits.view', 'kits.build',
            ],
            'user' => [],
        ];

        foreach ($roles as $roleName => $permissionNames) {
            $role = Role::create([
                'name' => $roleName,
                'guard_name' => $guard,
                'description' => 'Seeded '.$roleName.' role',
            ]);

            $role->syncPermissions(Arr::wrap($permissionNames));

            foreach ($roleUserIds[$roleName] ?? [] as $userId) {
                $role->users()->syncWithoutDetaching([$userId]);
            }
        }

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    }
}
