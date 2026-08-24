<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_actions', function (Blueprint $table) {
            $table->id();
            $table->string('username')->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action_type', 50);
            $table->foreignId('item_id')->nullable()->constrained('truck_appliances')->nullOnDelete();
            $table->json('extra')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index('action_type');
            $table->index('created_at');
            $table->index('username');
        });

        DB::table('permissions')->updateOrInsert(
            [
                'name' => 'user-actions.view',
                'guard_name' => 'web',
            ],
            [
                'name' => 'user-actions.view',
                'guard_name' => 'web',
                'module_name' => 'user actions',
                'slug' => 'user-actions.view',
                'description' => 'View the user action log',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        $permissionId = DB::table('permissions')->where([
            'name' => 'user-actions.view',
            'guard_name' => 'web',
        ])->value('id');

        DB::table('roles')
            ->where('name', 'admin')
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
            'name' => 'user-actions.view',
            'guard_name' => 'web',
        ])->value('id');

        if ($permissionId) {
            DB::table('role_has_permissions')->where('permission_id', $permissionId)->delete();
            DB::table('permissions')->where('id', $permissionId)->delete();
        }

        Schema::dropIfExists('user_actions');
    }
};
