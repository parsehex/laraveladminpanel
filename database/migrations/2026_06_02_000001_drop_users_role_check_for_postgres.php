<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement('ALTER TABLE users DROP CONSTRAINT IF EXISTS users_role_check');
        DB::statement("ALTER TABLE users ALTER COLUMN role TYPE VARCHAR(64)");
        DB::statement("ALTER TABLE users ALTER COLUMN role SET DEFAULT 'user'");
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement('ALTER TABLE users DROP CONSTRAINT IF EXISTS users_role_check');
        DB::statement("ALTER TABLE users ALTER COLUMN role TYPE VARCHAR(64)");
        DB::statement("ALTER TABLE users ALTER COLUMN role SET DEFAULT 'user'");
    }
};
