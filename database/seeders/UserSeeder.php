<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::firstOrCreate(
            ['email' => 'admin@yopmail.com'],
            [
                'name' => 'Admin User',
                'password' => 'admin@123',
                'role' => 'admin',
                'status' => 'active',
            ]
        );
        $admin->forceFill(['role' => 'admin', 'name' => 'Admin User', 'status' => 'active'])->save();
        $admin->syncRoles(['admin']);
        $admin->syncPermissions([]);

        // User::factory(20)->create()->each(function (User $user) {
        //     $roleName = match ($user->role) {
        //         'admin', 'Admin' => 'admin',
        //         'technician' => 'technician',
        //         'kit_assigner' => 'kit_assigner',
        //         default => 'user',
        //     };
        //     if (Role::where('name', $roleName)->exists()) {
        //         $user->syncRoles([$roleName]);
        //     }
        // });

        // $testUser = User::firstOrCreate(
        //     ['email' => 'user@example.com'],
        //     [
        //         'name' => 'Test User',
        //         'password' => 'password',
        //         'role' => 'user',
        //         'status' => 'active',
        //     ]
        // );
        // $testUser->forceFill(['role' => 'user', 'name' => 'Test User', 'status' => 'active'])->save();
        // $testUser->syncRoles(['user']);

        // $super = User::firstOrCreate(
        //     ['email' => 'superadmin@example.com'],
        //     [
        //         'name' => 'Super Admin',
        //         'password' => Hash::make('password'),
        //         'role' => 'Super Admin',
        //         'status' => 'active',
        //     ]
        // );
        // $super->syncRoles(['Super Admin']);
    }
}
