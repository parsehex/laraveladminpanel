<?php

namespace Tests\Feature;

use App\Models\Truck;
use App\Models\TruckAppliance;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InventoryControllerTest extends TestCase
{
    use RefreshDatabase;

    private function adminUser(): User
    {
        $this->seed(RolePermissionSeeder::class);

        $user = User::factory()->admin()->active()->create();
        $user->syncRoles(['admin']);

        return $user;
    }

    public function test_inventory_index_can_be_filtered_by_location(): void
    {
        $user = $this->adminUser();

        $truck = Truck::query()->create([
            'name' => 'Filter Truck',
            'units_on_truck' => 2,
            'cost_of_truck' => 1000,
            'shipping_cost' => 0,
            'arrival_date' => now()->toDateString(),
            'status' => 'active',
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        $matching = TruckAppliance::query()->create([
            'truck_id' => $truck->id,
            'serial_number' => 'LOC-MATCH-1',
            'product_name' => 'Washer A',
            'location' => 'Bay 1',
            'status' => 'Ready',
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        $other = TruckAppliance::query()->create([
            'truck_id' => $truck->id,
            'serial_number' => 'LOC-OTHER-1',
            'product_name' => 'Dryer B',
            'location' => 'Bay 2',
            'status' => 'Ready',
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        $response = $this->actingAs($user)->get(route('admin.inventory.index', [
            'location' => 'Bay 1',
        ]));

        $response->assertOk();
        $response->assertSee('Bay 1');
        $response->assertSee($matching->serial_number);
        $response->assertDontSee($other->serial_number);
        $response->assertViewHas('locations', function ($locations) {
            return $locations->contains('Bay 1') && $locations->contains('Bay 2');
        });
    }
}
