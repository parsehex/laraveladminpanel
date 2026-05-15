<?php

namespace Database\Factories;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class UserFactory extends Factory
{
    protected static ?string $password;

    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= \Illuminate\Support\Facades\Hash::make('password'),
            'role' => fake()->randomElement(['user', 'admin', 'technician', 'kit_assigner']),
            'status' => fake()->randomElement(['active', 'inactive']),
            'remember_token' => Str::random(10),
        ];
    }

    public function configure(): static
    {
        return $this->afterCreating(function (User $user) {
            if (! Role::query()->exists()) {
                return;
            }
            $roleName = match ($user->role) {
                'admin', 'Admin' => 'admin',
                'technician' => 'technician',
                'kit_assigner' => 'kit_assigner',
                default => 'user',
            };
            if (Role::where('name', $roleName)->exists()) {
                $user->syncRoles([$roleName]);
            }
        });
    }

    public function admin()
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'admin',
        ]);
    }

    public function user()
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'user',
        ]);
    }

    public function active()
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'active',
        ]);
    }

    public function inactive()
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'inactive',
        ]);
    }
}
