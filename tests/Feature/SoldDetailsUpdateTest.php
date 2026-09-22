<?php

namespace Tests\Feature;

use App\Models\Truck;
use App\Models\TruckAppliance;
use App\Models\User;
use Database\Seeders\InventoryStatusSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SoldDetailsUpdateTest extends TestCase
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

    private function soldAppliance(User $user, array $overrides = []): TruckAppliance
    {
        $truck = Truck::query()->create([
            'name' => 'Sold Details Truck',
            'units_on_truck' => 1,
            'cost_of_truck' => 500,
            'shipping_cost' => 0,
            'arrival_date' => now()->toDateString(),
            'status' => 'active',
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        return TruckAppliance::query()->create(array_merge([
            'truck_id' => $truck->id,
            'serial_number' => 'SOLD-EDIT-1',
            'status' => 'Sold',
            'location' => null,
            'price' => 100,
            'sold_price' => null,
            'sold_by' => null,
            'sold_at' => null,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ], $overrides));
    }

    public function test_sales_edit_updates_sold_price_sold_by_and_sold_at(): void
    {
        $user = $this->adminUser();
        $appliance = $this->soldAppliance($user);
        $appliance->forceFill(['location' => null])->saveQuietly();

        $response = $this->actingAs($user)->patch(route('admin.sales.sold-price.update', $appliance), [
            'sold_price' => 425.50,
            'sold_by' => 'Sales Rep',
            'sold_at' => '2026-09-20T14:30',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('truck_appliances', [
            'id' => $appliance->id,
            'status' => 'Sold',
            'location' => 'Sold',
            'sold_price' => 425.50,
            'sold_by' => 'Sales Rep',
        ]);

        $appliance->refresh();
        $this->assertSame('2026-09-20 14:30', $appliance->sold_at?->format('Y-m-d H:i'));
    }

    public function test_inventory_sold_details_edit_updates_appliance_sale_fields(): void
    {
        $user = $this->adminUser();
        $appliance = $this->soldAppliance($user, [
            'sold_price' => 200,
            'sold_by' => 'Old Name',
            'sold_at' => '2026-01-01 10:00:00',
            'location' => 'Sold',
        ]);

        $response = $this->actingAs($user)->patch(route('admin.inventory.sold-details.update', $appliance), [
            'sold_price' => 350,
            'sold_by' => 'Floor Staff',
            'sold_at' => '2026-09-21T09:15',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('truck_appliances', [
            'id' => $appliance->id,
            'status' => 'Sold',
            'sold_price' => 350,
            'sold_by' => 'Floor Staff',
        ]);

        $appliance->refresh();
        $this->assertSame('2026-09-21 09:15', $appliance->sold_at?->format('Y-m-d H:i'));
    }

    public function test_appliance_details_shows_sold_price_when_status_is_sold_without_sold_by(): void
    {
        $user = $this->adminUser();
        $appliance = $this->soldAppliance($user, [
            'sold_price' => 275,
            'sold_by' => null,
            'location' => 'Sold',
        ]);

        $response = $this->actingAs($user)->get(route('admin.inventory.show', $appliance));

        $response->assertOk();
        $response->assertSee('Sold Price');
        $response->assertSee('275.00');
        $response->assertSee('Edit Sold Details');
    }

    public function test_sales_edit_rejects_non_sold_appliances(): void
    {
        $user = $this->adminUser();
        $appliance = $this->soldAppliance($user, [
            'status' => 'Ready',
            'location' => 'Bay 1',
            'serial_number' => 'NOT-SOLD-1',
        ]);

        $response = $this->actingAs($user)->patch(route('admin.sales.sold-price.update', $appliance), [
            'sold_price' => 100,
            'sold_by' => 'Sales Rep',
        ]);

        $response->assertNotFound();
    }
}
