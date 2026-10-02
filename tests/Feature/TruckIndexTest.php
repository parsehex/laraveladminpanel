<?php

namespace Tests\Feature;

use App\Models\AppliancePart;
use App\Models\Truck;
use App\Models\TruckAppliance;
use App\Models\User;
use Database\Seeders\InventoryStatusSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TruckIndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_shipping_cost_is_included_in_the_cost_breakdown_when_it_is_set(): void
    {
        $user = $this->adminUser();
        $this->createTruck($user, [
            'name' => 'No Shipping Truck',
            'cost_of_truck' => 1500,
            'shipping_cost' => 0,
        ]);
        $this->createTruck($user, [
            'name' => 'Shipped Truck',
            'cost_of_truck' => 800,
            'shipping_cost' => 250,
        ]);

        $response = $this->actingAs($user)->get(route('admin.trucks.index'));

        $response->assertSee('No Shipping Truck');
        $response->assertSee('$1,500.00');
        $response->assertSee('Shipped Truck');
        $response->assertSee('$1,050.00');
        $response->assertSee('Cost of truck:</strong> $800.00', false);
        $response->assertSee('Shipping:</strong> $250.00', false);
        $response->assertDontSee('>Shipping</span>', false);
        $response->assertDontSee('Shipping:</strong> $0.00', false);
    }

    public function test_item_status_filter_returns_trucks_with_matching_appliance_statuses(): void
    {
        $user = $this->adminUser();

        $readyTruck = $this->createTruck($user, ['name' => 'Ready Only Truck']);
        $this->createAppliance($readyTruck, $user, [
            'serial_number' => 'READY-1',
            'status' => 'Ready',
        ]);

        $testingTruck = $this->createTruck($user, ['name' => 'Testing Only Truck']);
        $this->createAppliance($testingTruck, $user, [
            'serial_number' => 'TEST-1',
            'status' => 'Testing',
        ]);

        $triageTruck = $this->createTruck($user, ['name' => 'Triage Null Truck']);
        $this->createAppliance($triageTruck, $user, [
            'serial_number' => 'TRIAGE-1',
            'status' => null,
        ]);

        $response = $this->actingAs($user)->get(route('admin.trucks.index', [
            'item_status' => ['Ready', 'Triage'],
        ]));

        $response->assertOk();
        $response->assertSee('Ready Only Truck');
        $response->assertSee('Triage Null Truck');
        $response->assertDontSee('Testing Only Truck');
        $response->assertDontSee('Active Inventory Cost Structure');
    }

    public function test_status_breakdown_chips_link_to_item_status_filters(): void
    {
        $user = $this->adminUser();
        $truck = $this->createTruck($user, ['name' => 'Chip Truck']);
        $this->createAppliance($truck, $user, [
            'serial_number' => 'CHIP-READY',
            'status' => 'Ready',
        ]);

        $indexResponse = $this->actingAs($user)->get(route('admin.trucks.index'));
        $indexResponse->assertOk();
        $indexResponse->assertSee('title="Filter by Ready"', false);
        $indexResponse->assertSee(
            'href="'.route('admin.trucks.index', ['item_status' => ['Ready']]).'"',
            false
        );

        $filteredResponse = $this->actingAs($user)->get(route('admin.trucks.index', [
            'item_status' => ['Ready'],
        ]));
        $filteredResponse->assertOk();
        $filteredResponse->assertSee('title="Remove Ready filter"', false);
        $filteredResponse->assertSee('status-chip-filter', false);
        $filteredResponse->assertSee('is-selected', false);
    }

    public function test_breakdown_shows_per_truck_status_counts_and_active_value(): void
    {
        $user = $this->adminUser();
        $alphaTruck = $this->createTruck($user, ['name' => 'Alpha Truck']);
        $betaTruck = $this->createTruck($user, ['name' => 'Beta Truck']);

        $readyUnit = $this->createAppliance($alphaTruck, $user, ['serial_number' => 'A-READY-1', 'status' => 'Ready', 'price' => 100]);
        $this->createAppliance($alphaTruck, $user, ['serial_number' => 'A-READY-2', 'status' => 'Ready', 'price' => 50]);
        $this->createAppliance($alphaTruck, $user, ['serial_number' => 'A-SOLD', 'status' => 'Sold', 'price' => 900]);
        $this->createAppliance($alphaTruck, $user, ['serial_number' => 'A-SHOW', 'status' => 'Show Room', 'price' => 800]);
        $this->createAppliance($betaTruck, $user, ['serial_number' => 'B-NULL', 'status' => null, 'price' => 30]);
        $this->createAppliance($betaTruck, $user, ['serial_number' => 'B-EMPTY', 'status' => '', 'price' => 20]);
        AppliancePart::query()->create([
            'truck_appliance_id' => $readyUnit->id,
            'part_number' => 'P-1',
            'description' => 'Part',
            'cost' => 25,
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($user)->get(route('admin.trucks.index'));

        $response->assertOk();
        $response->assertSee('Truck Inventory Breakdown');
        $breakdown = $response->viewData('breakdownRows');
        $rowsByTruck = collect($breakdown)->keyBy(fn (array $row) => $row['truck']->name);

        $this->assertEqualsCanonicalizing(['Triage', 'Ready', 'Show Room', 'Sold'], $response->viewData('breakdownStatuses'));
        $this->assertSame(2, $rowsByTruck['Alpha Truck']['counts']['Ready']);
        $this->assertSame(1, $rowsByTruck['Alpha Truck']['counts']['Sold']);
        $this->assertSame(0, $rowsByTruck['Alpha Truck']['counts']['Triage']);
        $this->assertSame(2, $rowsByTruck['Alpha Truck']['active_units']);
        $this->assertEqualsWithDelta(175.0, $rowsByTruck['Alpha Truck']['active_value'], 0.001);
        $this->assertSame(2, $rowsByTruck['Beta Truck']['counts']['Triage']);
        $this->assertEqualsWithDelta(50.0, $rowsByTruck['Beta Truck']['active_value'], 0.001);

        $totals = $response->viewData('breakdownTotals');
        $this->assertSame(4, $totals['active_units']);
        $this->assertEqualsWithDelta(225.0, $totals['active_value'], 0.001);
        $response->assertSee('$225.00');
    }

    public function test_breakdown_hides_money_columns_without_inventory_value_permission(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $this->seed(InventoryStatusSeeder::class);
        $technician = User::factory()->active()->create(['role' => 'technician']);
        $technician->syncRoles(['technician']);

        $truck = $this->createTruck($technician, ['name' => 'Tech Truck']);
        $this->createAppliance($truck, $technician, ['serial_number' => 'T-READY', 'status' => 'Ready', 'price' => 4321]);

        $response = $this->actingAs($technician)->get(route('admin.trucks.index'));

        $response->assertOk();
        $response->assertSee('Truck Inventory Breakdown');
        $response->assertDontSee('Active Value');
        $response->assertDontSee('$4,321.00');
    }

    public function test_breakdown_follows_filters_and_includes_trucks_beyond_the_first_page(): void
    {
        $user = $this->adminUser();
        foreach (range(1, 26) as $number) {
            $this->createTruck($user, ['name' => sprintf('Fleet Truck %02d', $number)]);
        }
        $this->createTruck($user, ['name' => 'Retired Fleet Truck', 'status' => 'inactive']);
        $this->createTruck($user, ['name' => 'Other Truck']);

        $response = $this->actingAs($user)->get(route('admin.trucks.index', [
            'search' => 'Fleet',
            'status' => 'active',
        ]));

        $response->assertOk();
        $breakdownNames = collect($response->viewData('breakdownRows'))->map(fn (array $row) => $row['truck']->name);

        $this->assertCount(25, $response->viewData('trucks'));
        $this->assertCount(26, $breakdownNames);
        $this->assertContains('Fleet Truck 26', $breakdownNames);
        $this->assertNotContains('Retired Fleet Truck', $breakdownNames);
        $this->assertNotContains('Other Truck', $breakdownNames);
    }

    public function test_breakdown_range_only_counts_units_added_in_range_and_drops_empty_trucks(): void
    {
        $user = $this->adminUser();
        $recentTruck = $this->createTruck($user, ['name' => 'Recent Truck']);
        $oldTruck = $this->createTruck($user, ['name' => 'Old Truck']);

        $this->createAppliance($recentTruck, $user, ['serial_number' => 'R-NEW', 'status' => 'Ready', 'price' => 40]);
        $recentOld = $this->createAppliance($recentTruck, $user, ['serial_number' => 'R-OLD', 'status' => 'Ready', 'price' => 60]);
        $oldUnit = $this->createAppliance($oldTruck, $user, ['serial_number' => 'O-OLD', 'status' => 'Testing', 'price' => 70]);
        TruckAppliance::query()->whereKey([$recentOld->id, $oldUnit->id])->update(['created_at' => now()->subDays(20)]);

        $response = $this->actingAs($user)->get(route('admin.trucks.index', ['cost_period' => 'weekly']));

        $response->assertOk();
        $response->assertSee('Showing Last 7 days.');
        $rows = collect($response->viewData('breakdownRows'));

        $this->assertSame(['Recent Truck'], $rows->map(fn (array $row) => $row['truck']->name)->all());
        $this->assertSame(1, $rows->first()['counts']['Ready']);
        $this->assertEqualsWithDelta(40.0, $response->viewData('breakdownTotals')['active_value'], 0.001);
        $this->assertSame(['Ready'], $response->viewData('breakdownStatuses'));
    }

    private function adminUser(): User
    {
        $this->seed(RolePermissionSeeder::class);
        $this->seed(InventoryStatusSeeder::class);

        $user = User::factory()->admin()->active()->create();
        $user->syncRoles(['admin']);

        return $user;
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function createTruck(User $user, array $overrides): Truck
    {
        return Truck::query()->create([
            'name' => 'Truck',
            'units_on_truck' => 1,
            'cost_of_truck' => 0,
            'shipping_cost' => 0,
            'arrival_date' => now()->toDateString(),
            'status' => 'active',
            'created_by' => $user->id,
            'updated_by' => $user->id,
            ...$overrides,
        ]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function createAppliance(Truck $truck, User $user, array $overrides = []): TruckAppliance
    {
        return TruckAppliance::query()->create(array_merge([
            'truck_id' => $truck->id,
            'serial_number' => 'SN-TRUCK',
            'product_name' => 'Washer',
            'status' => 'Ready',
            'price' => 0,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ], $overrides));
    }
}
