<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationSettingsControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_settings_page_lists_kit_and_suggestion_modules(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $admin = User::factory()->admin()->active()->create();
        $admin->syncRoles(['admin']);

        $this->actingAs($admin)
            ->get(route('admin.notification-settings.index'))
            ->assertOk()
            ->assertSee('Notify when a kit is assigned. The person assigned the kit is always included.')
            ->assertSee('Notify when a staff member submits a suggestion.');
    }
}
