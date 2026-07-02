<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $permissionsTable = config('permission.table_names.permissions') ?: 'permissions';
        $rolesTable = config('permission.table_names.roles') ?: 'roles';

        Schema::table($permissionsTable, function (Blueprint $table) {
            $table->string('module_name', 120)->nullable()->index();
            $table->string('slug', 191)->nullable()->unique();
            $table->text('description')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
        });

        Schema::table($rolesTable, function (Blueprint $table) {
            $table->text('description')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        $permissionsTable = config('permission.table_names.permissions') ?: 'permissions';
        $rolesTable = config('permission.table_names.roles') ?: 'roles';

        Schema::table($permissionsTable, function (Blueprint $table) {
            $table->dropForeign(['created_by']);
            $table->dropColumn(['module_name', 'slug', 'description', 'created_by']);
        });

        Schema::table($rolesTable, function (Blueprint $table) {
            $table->dropForeign(['created_by']);
            $table->dropColumn(['description', 'created_by']);
        });
    }
};
