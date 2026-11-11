<?php

namespace Tests\Feature;

use App\Models\InventoryStatus;
use App\Models\Truck;
use App\Models\TruckAppliance;
use App\Models\User;
use Database\Seeders\InventoryStatusSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InventoryStatusOrderingTest extends TestCase
{
    use RefreshDatabase;

    private function adminUser(): User
    {
        $this->seed(RolePermissionSeeder::class);
        $this->seed(InventoryStatusSeeder::class);

        $user = User::factory()->admin()->active()->create();
        $user->syncRoles(['admin']);

        return $user;
    }

    public function test_active_names_are_alphabetical(): void
    {
        $this->seed(InventoryStatusSeeder::class);

        $names = InventoryStatus::activeNames();

        $sorted = $names;
        sort($sorted, SORT_STRING);

        $this->assertSame($sorted, $names);
        $this->assertSame('Breakdown', $names[0]);
    }

    public function test_unit_page_status_select_is_alphabetical(): void
    {
        $user = $this->adminUser();
        $truck = Truck::query()->create([
            'name' => 'Status Order Truck',
            'units_on_truck' => 1,
            'cost_of_truck' => 100,
            'shipping_cost' => 0,
            'arrival_date' => now()->toDateString(),
            'status' => 'active',
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);
        $appliance = TruckAppliance::query()->create([
            'truck_id' => $truck->id,
            'serial_number' => 'STATUS-ORDER-1',
            'status' => 'Ready',
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        $response = $this->actingAs($user)->get(route('admin.inventory.show', $appliance));

        $response->assertOk();
        $response->assertViewHas('statuses', function ($statuses) {
            $sorted = $statuses;
            sort($sorted, SORT_STRING);

            return $statuses === $sorted;
        });
    }
}
