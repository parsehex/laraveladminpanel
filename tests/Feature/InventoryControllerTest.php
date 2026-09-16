<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Truck;
use App\Models\TruckAppliance;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
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

    public function test_inventory_index_can_be_sorted_by_category(): void
    {
        $user = $this->adminUser();
        $truck = Truck::query()->create([
            'name' => 'Category Sort Truck',
            'units_on_truck' => 2,
            'cost_of_truck' => 1000,
            'shipping_cost' => 0,
            'arrival_date' => now()->toDateString(),
            'status' => 'active',
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);
        $washerCategory = Category::query()->create([
            'name' => 'Washers',
            'status' => 1,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);
        $dishwasherCategory = Category::query()->create([
            'name' => 'Dishwashers',
            'status' => 1,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);
        TruckAppliance::query()->create([
            'truck_id' => $truck->id,
            'category_id' => $washerCategory->id,
            'serial_number' => 'CATEGORY-WASHER',
            'status' => 'Ready',
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);
        TruckAppliance::query()->create([
            'truck_id' => $truck->id,
            'category_id' => $dishwasherCategory->id,
            'serial_number' => 'CATEGORY-DISHWASHER',
            'status' => 'Ready',
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        $response = $this->actingAs($user)->get(route('admin.inventory.index', [
            'sort' => 'category',
            'direction' => 'asc',
        ]));

        $response->assertOk();
        $response->assertViewHas('items', function ($items) {
            return $items->pluck('serial_number')->values()->all() === [
                'CATEGORY-DISHWASHER',
                'CATEGORY-WASHER',
            ];
        });
    }

    #[DataProvider('terminalStatusLocations')]
    public function test_terminal_status_updates_populate_the_location(string $status, string $location): void
    {
        $user = $this->adminUser();
        $truck = Truck::query()->create([
            'name' => 'Status Truck',
            'units_on_truck' => 1,
            'cost_of_truck' => 500,
            'shipping_cost' => 0,
            'arrival_date' => now()->toDateString(),
            'status' => 'active',
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);
        $appliance = TruckAppliance::query()->create([
            'truck_id' => $truck->id,
            'serial_number' => 'STATUS-1',
            'location' => 'Bay 1',
            'status' => 'Ready',
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        $response = $this->actingAs($user)->patch(route('admin.inventory.status.update', $appliance), [
            'status' => $status,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('truck_appliances', [
            'id' => $appliance->id,
            'status' => $status,
            'location' => $location,
        ]);
        $this->assertDatabaseHas('inventory_status_histories', [
            'truck_appliance_id' => $appliance->id,
            'status' => $status,
            'user_id' => $user->id,
        ]);
    }

    public function test_marking_an_item_sold_from_sales_populates_the_location(): void
    {
        $user = $this->adminUser();
        $truck = Truck::query()->create([
            'name' => 'Sales Truck',
            'units_on_truck' => 1,
            'cost_of_truck' => 500,
            'shipping_cost' => 0,
            'arrival_date' => now()->toDateString(),
            'status' => 'active',
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);
        $appliance = TruckAppliance::query()->create([
            'truck_id' => $truck->id,
            'serial_number' => 'SOLD-1',
            'location' => 'Bay 2',
            'status' => 'Ready',
            'price' => 100,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        $response = $this->actingAs($user)->post(route('admin.sales.mark-sold'), [
            'sale_type' => 'normal',
            'serial_number' => 'SOLD-1',
            'sold_price' => 250,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('truck_appliances', [
            'id' => $appliance->id,
            'status' => 'Sold',
            'location' => 'Sold',
            'sold_price' => 250,
        ]);
        $this->assertDatabaseHas('inventory_status_histories', [
            'truck_appliance_id' => $appliance->id,
            'status' => 'Sold',
            'user_id' => $user->id,
        ]);
    }

    public static function terminalStatusLocations(): array
    {
        return [
            'showroom' => ['Show Room', 'Showroom'],
            'scrap' => ['Scrap', 'Scrap'],
            'sent to ebay' => ['Sent To Ebay', 'Shopify Sales Ebay Department'],
            'sold' => ['Sold', 'Sold'],
            'video' => ['Video', 'Studio'],
        ];
    }
}
