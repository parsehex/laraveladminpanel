<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('kit_catalog_parts')) {
            Schema::create('kit_catalog_parts', function (Blueprint $table) {
                $table->id();
                $table->string('part_number')->unique();
                $table->string('product_name')->nullable();
                $table->string('model_compatibility')->nullable();
                $table->unsignedInteger('total_stock')->default(0);
                $table->decimal('retail_price', 12, 2)->default(0);
                $table->decimal('your_price', 12, 2)->default(0);
                $table->string('cross_reference')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
                $table->softDeletes();

                $table->index('product_name');
                $table->index('model_compatibility');
            });
        }

        $now = now();
        $permissions = [
            ['name' => 'executive-dashboard.view', 'module_name' => 'executive dashboard', 'description' => 'View executive dashboard'],
            ['name' => 'kit-parts.view', 'module_name' => 'kit parts', 'description' => 'List kit parts'],
            ['name' => 'kit-parts.create', 'module_name' => 'kit parts', 'description' => 'Create kit parts'],
            ['name' => 'kit-parts.edit', 'module_name' => 'kit parts', 'description' => 'Edit kit parts'],
            ['name' => 'kit-parts.delete', 'module_name' => 'kit parts', 'description' => 'Delete kit parts'],
        ];

        foreach ($permissions as $permission) {
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

        $adminRoleId = DB::table('roles')->where('name', 'admin')->where('guard_name', 'web')->value('id');
        if ($adminRoleId) {
            $permissionIds = DB::table('permissions')->whereIn('name', array_column($permissions, 'name'))->pluck('id');
            foreach ($permissionIds as $permissionId) {
                DB::table('role_has_permissions')->updateOrInsert([
                    'permission_id' => $permissionId,
                    'role_id' => $adminRoleId,
                ]);
            }
        }
    }

    public function down(): void
    {
        $ids = DB::table('permissions')->whereIn('name', [
            'executive-dashboard.view',
            'kit-parts.view',
            'kit-parts.create',
            'kit-parts.edit',
            'kit-parts.delete',
        ])->pluck('id');

        DB::table('role_has_permissions')->whereIn('permission_id', $ids)->delete();
        DB::table('permissions')->whereIn('id', $ids)->delete();
        Schema::dropIfExists('kit_catalog_parts');
    }
};
