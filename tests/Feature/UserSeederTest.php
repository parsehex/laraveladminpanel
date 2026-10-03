<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_the_local_admin_when_environment_is_local(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $this->app['env'] = 'local';

        $this->app->make(UserSeeder::class)->run();

        $admin = User::query()->where('email', 'admin@yopmail.com')->first();

        $this->assertNotNull($admin);
        $this->assertSame('admin', $admin->role);
        $this->assertTrue($admin->hasRole('admin'));
    }

    public function test_it_does_not_create_the_admin_outside_local(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $this->app['env'] = 'production';

        $this->app->make(UserSeeder::class)->run();

        $this->assertDatabaseMissing('users', [
            'email' => 'admin@yopmail.com',
        ]);
    }

    public function test_it_does_not_overwrite_an_existing_local_admin(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $this->app['env'] = 'local';

        $existing = User::factory()->admin()->active()->create([
            'email' => 'admin@yopmail.com',
            'name' => 'Custom Admin Name',
        ]);

        $this->app->make(UserSeeder::class)->run();

        $this->assertSame(1, User::query()->where('email', 'admin@yopmail.com')->count());
        $this->assertSame('Custom Admin Name', $existing->fresh()->name);
    }
}
