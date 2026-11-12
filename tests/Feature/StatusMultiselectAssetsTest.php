<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\InventoryStatusSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class StatusMultiselectAssetsTest extends TestCase
{
    use RefreshDatabase;

    public function test_trucks_index_renders_colored_status_multiselect(): void
    {
        Permission::findOrCreate('trucks.view');
        $this->seed(InventoryStatusSeeder::class);

        $user = User::factory()->create();
        $user->givePermissionTo('trucks.view');

        $response = $this->actingAs($user)->get(route('admin.trucks.index'));

        $response->assertOk();
        $response->assertSee('data-status-filter', false);
        $response->assertSee('x-data="{ open: false }"', false);
        $response->assertSee('@click="open = !open"', false);
        $response->assertSee('background-color: #3b82f6', false);
        $response->assertSee('color: #111827', false);
        $response->assertSee('>Ready</span>', false);
    }

    public function test_inventory_index_renders_colored_status_multiselect(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $this->seed(InventoryStatusSeeder::class);

        $user = User::factory()->admin()->active()->create();
        $user->syncRoles(['admin']);

        $response = $this->actingAs($user)->get(route('admin.inventory.index'));

        $response->assertOk();
        $response->assertSee('data-status-filter', false);
        $response->assertSee('background-color: #3b82f6', false);
        $response->assertSee('background-color: #67d4f1', false);
        $response->assertSee('color: #111827', false);
    }
}
