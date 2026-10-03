<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Create the local development admin only.
     *
     * Production (and any non-local env) must never recreate this account —
     * operators may have changed the email/password after go-live.
     */
    public function run(): void
    {
        if (! app()->environment('local')) {
            return;
        }

        $admin = User::firstOrCreate(
            ['email' => 'admin@yopmail.com'],
            [
                'name' => 'Admin User',
                'password' => 'admin@123',
                'role' => 'admin',
                'status' => 'active',
            ]
        );

        $admin->syncRoles(['admin']);
        $admin->syncPermissions([]);
    }
}
