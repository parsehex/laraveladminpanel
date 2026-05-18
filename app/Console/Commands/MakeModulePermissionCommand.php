<?php

namespace App\Console\Commands;

use App\Models\Permission;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class MakeModulePermissionCommand extends Command
{
    protected $signature = 'make:module-permission {module : StudlyCase module name, e.g. Product}';

    protected $description = 'Create standard CRUD permissions for a module (module.view, module.create, module.edit, module.delete)';

    public function handle(): int
    {
        $studly = trim($this->argument('module'));
        $module = Str::plural(Str::snake($studly));

        $guard = 'web';
        $actions = ['view', 'create', 'edit', 'delete'];

        foreach ($actions as $action) {
            $name = "{$module}.{$action}";
            $permission = Permission::query()->where('name', $name)->where('guard_name', $guard)->first();

            if ($permission) {
                $this->warn("Skipped (exists): {$name}");

                continue;
            }

            Permission::create([
                'name' => $name,
                'guard_name' => $guard,
                'module_name' => $module,
                'slug' => $name,
                'description' => ucfirst($action)." {$studly}",
            ]);

            $this->info("Created permission: {$name}");
        }

        $this->newLine();
        $this->comment('Assign these permissions to roles via Admin -> Roles or $role->givePermissionTo(...) in a seeder.');

        return self::SUCCESS;
    }
}
