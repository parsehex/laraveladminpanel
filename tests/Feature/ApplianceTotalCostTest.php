<?php

namespace Tests\Feature;

use App\Models\AppliancePart;
use App\Models\Truck;
use App\Models\TruckAppliance;
use App\Models\User;
use Database\Seeders\InventoryStatusSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class ApplianceTotalCostTest extends TestCase
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

    private function createTruck(User $user, string $name = 'Cost Truck'): Truck
    {
        return Truck::query()->create([
            'name' => $name,
            'units_on_truck' => 1,
            'cost_of_truck' => 1000,
            'shipping_cost' => 0,
            'arrival_date' => now()->toDateString(),
            'status' => 'active',
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);
    }

    private function createAppliance(Truck $truck, User $user, array $overrides = []): TruckAppliance
    {
        return TruckAppliance::query()->create(array_merge([
            'truck_id' => $truck->id,
            'serial_number' => 'COST-'.uniqid(),
            'product_name' => 'Washer',
            'brand' => 'Whirlpool',
            'price' => 200,
            'msrp' => 500,
            'status' => 'Ready',
            'quantity' => 1,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ], $overrides));
    }

    public function test_appliance_show_total_cost_is_our_cost_plus_live_parts(): void
    {
        $user = $this->adminUser();
        $truck = $this->createTruck($user);
        $appliance = $this->createAppliance($truck, $user, [
            'price' => 200,
            'msrp' => 999,
        ]);

        AppliancePart::query()->create([
            'truck_appliance_id' => $appliance->id,
            'description' => 'Belt',
            'cost' => 25.50,
            'user_id' => $user->id,
        ]);
        AppliancePart::query()->create([
            'truck_appliance_id' => $appliance->id,
            'description' => 'Knob',
            'cost' => 14.50,
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($user)->get(route('admin.inventory.show', $appliance));

        $response->assertOk();
        $response->assertSee('Total Cost');
        $response->assertSee('$240.00');
        $response->assertSee('Our Cost: $200.00 + Parts Cost: $40.00');
        $response->assertDontSee('Final Cost Valuation');
    }

    public function test_demanufacture_and_scrap_still_add_parts_cost(): void
    {
        $user = $this->adminUser();
        $truck = $this->createTruck($user);
        $appliance = $this->createAppliance($truck, $user, [
            'price' => 100,
            'status' => 'Demanufacture',
        ]);

        AppliancePart::query()->create([
            'truck_appliance_id' => $appliance->id,
            'description' => 'Salvage motor',
            'cost' => 40,
            'user_id' => $user->id,
        ]);

        $this->assertSame(140.0, $appliance->fresh()->totalCost());

        $appliance->update(['status' => 'Scrap']);
        $this->assertSame(140.0, $appliance->fresh()->totalCost());
    }

    public function test_adding_a_part_updates_inventory_list_total_cost(): void
    {
        $user = $this->adminUser();
        $truck = $this->createTruck($user);
        $appliance = $this->createAppliance($truck, $user, [
            'price' => 150,
            'serial_number' => 'PARTS-LIVE-1',
        ]);

        $this->actingAs($user)->post(route('admin.inventory.parts.store', $appliance), [
            'description' => 'Control board',
            'cost' => 75,
        ])->assertRedirect();

        $appliance->refresh();
        $this->assertSame(75.0, $appliance->partsCost());
        $this->assertSame(225.0, $appliance->totalCost());

        $response = $this->actingAs($user)->get(route('admin.inventory.index'));
        $response->assertOk();
        $response->assertSee('$225.00');
        $response->assertSee('PARTS-LIVE-1');
        $response->assertSee('data-cost-toggle>?</button>', false);
    }

    public function test_cost_breakdown_is_hidden_when_there_are_no_parts(): void
    {
        $user = $this->adminUser();
        $truck = $this->createTruck($user);
        $appliance = $this->createAppliance($truck, $user, [
            'price' => 180,
            'serial_number' => 'NO-PARTS-1',
        ]);

        $show = $this->actingAs($user)->get(route('admin.inventory.show', $appliance));
        $show->assertOk();
        $show->assertSee('Total Cost');
        $show->assertSee('$180.00');
        $show->assertSee('Our Cost: $180.00 + Parts Cost: $0.00');

        $index = $this->actingAs($user)->get(route('admin.inventory.index'));
        $index->assertOk();
        $index->assertSee('NO-PARTS-1');
        $index->assertDontSee('data-cost-toggle>?</button>', false);
    }

    public function test_csv_import_ignores_manual_total_parts_cost_column(): void
    {
        $user = $this->adminUser();
        $truck = $this->createTruck($user, 'Import Cost Truck');

        $csv = <<<'CSV'
Unit Label,Category,Sub Category,Brand,Model #,Product Name,Quantity,Our Cost,Serial #,Receiving Condition,MSRP,Fuel Type,Status,Total Parts Cost
Unit 1,Washer,Top Load,Whirlpool,WTW5000DW1,Top Load Washer,1,175.00,IGNOREPARTS1,A-Grade,399.00,Electric,Testing,999.00
CSV;

        $file = UploadedFile::fake()->createWithContent('appliances.csv', $csv);

        $this->actingAs($user)->post(route('admin.trucks.appliances.import', $truck), [
            'csv_file' => $file,
        ])->assertRedirect();

        $appliance = TruckAppliance::query()->where('serial_number', 'IGNOREPARTS1')->first();

        $this->assertNotNull($appliance);
        $this->assertSame(0.0, $appliance->partsCost());
        $this->assertSame((float) $appliance->price, $appliance->totalCost());
    }

    public function test_create_appliance_form_no_longer_includes_total_parts_cost_field(): void
    {
        $user = $this->adminUser();
        $truck = $this->createTruck($user);

        $response = $this->actingAs($user)->get(route('admin.trucks.show', $truck));

        $response->assertOk();
        $response->assertDontSee('name="total_parts_cost"', false);
        $response->assertDontSee('Total Parts Cost:');
    }
}
