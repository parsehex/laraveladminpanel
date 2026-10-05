<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Create the local development account only.
     *
     * Production (and any non-local env) must never recreate this account —
     * operators may have changed the email/password after go-live. After a
     * prod dump restore this re-establishes admin@yopmail.com as developer.
     */
    public function run(): void
    {
        if (! app()->environment('local')) {
            return;
        }

        $developer = User::firstOrCreate(
            ['email' => 'admin@yopmail.com'],
            [
                'name' => 'Admin User',
                'password' => 'admin@123',
                'role' => 'developer',
                'status' => 'active',
            ]
        );

        $developer->forceFill([
            'role' => 'developer',
            'status' => 'active',
        ])->save();

        $developer->syncRoles(['developer']);
        $developer->syncPermissions([]);
    }
}
