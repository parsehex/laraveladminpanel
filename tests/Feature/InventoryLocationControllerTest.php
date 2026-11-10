<?php

namespace Tests\Feature;

use App\Models\InventoryLocation;
use App\Models\Truck;
use App\Models\TruckAppliance;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InventoryLocationControllerTest extends TestCase
{
    use RefreshDatabase;

    private function adminUser(): User
    {
        $this->seed(RolePermissionSeeder::class);

        $user = User::factory()->admin()->active()->create();
        $user->syncRoles(['admin']);

        return $user;
    }

    private function createTruck(User $user): Truck
    {
        return Truck::query()->create([
            'name' => 'Location Truck',
            'units_on_truck' => 2,
            'cost_of_truck' => 500,
            'shipping_cost' => 0,
            'arrival_date' => now()->toDateString(),
            'status' => 'active',
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);
    }

    private function createAppliance(User $user, Truck $truck, string $serial, string $location): TruckAppliance
    {
        return TruckAppliance::query()->create([
            'truck_id' => $truck->id,
            'serial_number' => $serial,
            'status' => 'Ready',
            'location' => $location,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);
    }

    public function test_guest_is_redirected_to_login_from_locations_index(): void
    {
        $response = $this->get(route('admin.inventory-locations.index'));

        $response->assertRedirectToRoute('login');
    }

    public function test_technician_cannot_open_locations_index(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $user = User::factory()->active()->create(['role' => 'technician']);
        $user->syncRoles(['technician']);

        $this->actingAs($user)
            ->get(route('admin.inventory-locations.index'))
            ->assertForbidden();
    }

    public function test_index_lists_catalog_and_uncatalogued_in_use_labels(): void
    {
        $user = $this->adminUser();
        $truck = $this->createTruck($user);
        $this->createAppliance($user, $truck, 'LOC-1', 'Showroom');
        $this->createAppliance($user, $truck, 'LOC-2', 'SHOWROOM');

        $response = $this->actingAs($user)->get(route('admin.inventory-locations.index'));

        $response->assertOk();
        $response->assertViewIs('admin.inventory-locations.index');
        $response->assertSee('Inventory');
        $response->assertSee('Showroom');
        $response->assertSee('SHOWROOM');
        $response->assertViewHas('catalog', function ($catalog) {
            return collect($catalog)->contains(fn (array $row) => $row['name'] === 'Inventory' && $row['item_count'] === 0);
        });
        $response->assertViewHas('uncatalogued', function ($uncatalogued) {
            $names = collect($uncatalogued)->pluck('name');

            return $names->contains('Showroom')
                && $names->contains('SHOWROOM')
                && $names->count() === 2;
        });
    }

    public function test_authorized_user_can_add_a_catalog_location(): void
    {
        $user = $this->adminUser();

        $response = $this->actingAs($user)->post(route('admin.inventory-locations.store'), [
            'name' => 'Receiving',
        ]);

        $response->assertRedirectToRoute('admin.inventory-locations.index');
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('inventory_locations', [
            'name' => 'Receiving',
        ]);
    }

    public function test_authorized_user_can_rename_a_location(): void
    {
        $user = $this->adminUser();
        $truck = $this->createTruck($user);
        $appliance = $this->createAppliance($user, $truck, 'LOC-1', 'Bay 1');

        $response = $this->actingAs($user)->post(route('admin.inventory-locations.rename'), [
            'from' => 'Bay 1',
            'to' => 'Bay One',
        ]);

        $response->assertRedirectToRoute('admin.inventory-locations.index');
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('truck_appliances', [
            'id' => $appliance->id,
            'location' => 'Bay One',
        ]);
    }

    public function test_renaming_a_catalog_location_updates_the_catalog_and_items(): void
    {
        $user = $this->adminUser();
        $truck = $this->createTruck($user);
        $this->createAppliance($user, $truck, 'LOC-1', 'Inventory');

        $response = $this->actingAs($user)->post(route('admin.inventory-locations.rename'), [
            'from' => 'Inventory',
            'to' => 'Main Floor',
        ]);

        $response->assertRedirectToRoute('admin.inventory-locations.index');
        $this->assertDatabaseHas('inventory_locations', ['name' => 'Main Floor']);
        $this->assertDatabaseMissing('inventory_locations', ['name' => 'Inventory']);
        $this->assertSame(1, TruckAppliance::query()->where('location', 'Main Floor')->count());
    }

    public function test_renaming_onto_an_existing_location_merges_the_sets(): void
    {
        $user = $this->adminUser();
        $truck = $this->createTruck($user);
        $this->createAppliance($user, $truck, 'LOC-1', 'Showroom');
        $this->createAppliance($user, $truck, 'LOC-2', 'SHOWROOM');

        $response = $this->actingAs($user)->post(route('admin.inventory-locations.rename'), [
            'from' => 'SHOWROOM',
            'to' => 'Showroom',
        ]);

        $response->assertRedirectToRoute('admin.inventory-locations.index');
        $response->assertSessionHas('success');
        $this->assertSame(2, TruckAppliance::query()->where('location', 'Showroom')->count());
        $this->assertSame(0, TruckAppliance::query()->where('location', 'SHOWROOM')->count());
    }

    public function test_rename_rejects_the_same_name(): void
    {
        $user = $this->adminUser();
        $truck = $this->createTruck($user);
        $this->createAppliance($user, $truck, 'LOC-1', 'Bay 1');

        $response = $this->actingAs($user)
            ->from(route('admin.inventory-locations.index'))
            ->post(route('admin.inventory-locations.rename'), [
                'from' => 'Bay 1',
                'to' => 'Bay 1',
            ]);

        $response->assertRedirectToRoute('admin.inventory-locations.index');
        $response->assertSessionHas('error');
        $this->assertDatabaseHas('truck_appliances', [
            'serial_number' => 'LOC-1',
            'location' => 'Bay 1',
        ]);
    }

    public function test_index_escapes_location_html(): void
    {
        $user = $this->adminUser();
        $truck = $this->createTruck($user);
        $this->createAppliance($user, $truck, 'LOC-1', '<script>alert(1)</script>');

        $response = $this->actingAs($user)->get(route('admin.inventory-locations.index'));

        $response->assertSee('<script>alert(1)</script>');
        $response->assertDontSee('<script>alert(1)</script>', false);
    }

    public function test_item_location_picker_includes_catalog_and_in_use_labels(): void
    {
        $user = $this->adminUser();
        $truck = $this->createTruck($user);
        $appliance = $this->createAppliance($user, $truck, 'LOC-1', 'SHOWROOM');
        InventoryLocation::query()->create(['name' => 'Receiving']);

        $response = $this->actingAs($user)->get(route('admin.inventory.show', $appliance));

        $response->assertOk();
        $response->assertViewHas('locations', function ($locations) {
            $labels = collect($locations)->pluck('label');

            return $labels->contains('Inventory')
                && $labels->contains('Receiving')
                && $labels->contains('SHOWROOM');
        });
    }
}
