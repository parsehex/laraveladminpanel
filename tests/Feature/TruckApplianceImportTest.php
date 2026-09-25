<?php

namespace Tests\Feature;

use App\Models\Truck;
use App\Models\TruckAppliance;
use App\Models\User;
use Database\Seeders\InventoryStatusSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class TruckApplianceImportTest extends TestCase
{
    use RefreshDatabase;

    public function test_authorized_user_can_import_appliances_from_csv(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $this->seed(InventoryStatusSeeder::class);

        $user = User::factory()->admin()->active()->create();
        $user->syncRoles(['admin']);

        $truck = Truck::query()->create([
            'name' => 'Test Truck',
            'units_on_truck' => 3,
            'cost_of_truck' => 1000,
            'shipping_cost' => 50,
            'arrival_date' => now()->toDateString(),
            'status' => 'active',
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        $csv = <<<'CSV'
Unit Label,Category,Sub Category,Brand,Model #,Product Name,Quantity,Our Cost,Serial #,Receiving Condition,MSRP,Fuel Type,Status,Total Parts Cost
Unit 1,Washer,Top Load,Whirlpool,WTW5000DW1,Top Load Washer,1,175.00,CX1234567,A-Grade,399.00,Electric,Testing,15.00
Unit 2,Dryer,Electric Dryer,Whirlpool,WED4815EW1,Electric Dryer,1,150.00,MX7654321,B-Grade,349.00,Electric,Ready,0.00
CSV;

        $file = UploadedFile::fake()->createWithContent('appliances.csv', $csv);

        $response = $this->actingAs($user)->post(route('admin.trucks.appliances.import', $truck), [
            'csv_file' => $file,
        ]);

        $response->assertRedirect(route('admin.trucks.show', $truck));
        $response->assertSessionHas('success');

        $this->assertDatabaseCount('truck_appliances', 2);
        $this->assertDatabaseHas('truck_appliances', [
            'truck_id' => $truck->id,
            'serial_number' => 'CX1234567',
            'status' => 'Testing',
            'msrp' => 399.00,
        ]);
        $this->assertDatabaseHas('truck_appliances', [
            'truck_id' => $truck->id,
            'serial_number' => 'MX7654321',
            'status' => 'Ready',
        ]);
    }

    public function test_import_updates_existing_appliance_when_serial_number_matches(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $this->seed(InventoryStatusSeeder::class);

        $user = User::factory()->admin()->active()->create();
        $user->syncRoles(['admin']);

        $truck = Truck::query()->create([
            'name' => 'Update Truck',
            'units_on_truck' => 1,
            'cost_of_truck' => 500,
            'shipping_cost' => 0,
            'arrival_date' => now()->toDateString(),
            'status' => 'active',
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        TruckAppliance::query()->create([
            'truck_id' => $truck->id,
            'serial_number' => 'CX1234567',
            'msrp' => 100,
            'price' => 10,
            'quantity' => 1,
            'status' => 'Triage',
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        $csv = <<<'CSV'
Unit Label,Category,Sub Category,Brand,Model #,Product Name,Quantity,Our Cost,Serial #,Receiving Condition,MSRP,Fuel Type,Status,Total Parts Cost
Unit 1,Washer,Top Load,Whirlpool,WTW5000DW1,Top Load Washer,1,175.00,CX1234567,A-Grade,399.00,Electric,Testing,15.00
CSV;

        $file = UploadedFile::fake()->createWithContent('appliances.csv', $csv);

        $this->actingAs($user)->post(route('admin.trucks.appliances.import', $truck), [
            'csv_file' => $file,
        ])->assertRedirect();

        $this->assertDatabaseCount('truck_appliances', 1);
        $this->assertDatabaseHas('truck_appliances', [
            'truck_id' => $truck->id,
            'serial_number' => 'CX1234567',
            'status' => 'Testing',
            'msrp' => 399.00,
        ]);
    }

    public function test_import_can_set_sold_info_on_appliances(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $this->seed(InventoryStatusSeeder::class);

        $user = User::factory()->admin()->active()->create(['name' => 'Importer User']);
        $user->syncRoles(['admin']);

        $truck = Truck::query()->create([
            'name' => 'Sold Truck',
            'units_on_truck' => 1,
            'cost_of_truck' => 500,
            'shipping_cost' => 0,
            'arrival_date' => now()->toDateString(),
            'status' => 'active',
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        $csv = <<<'CSV'
Unit Label,Category,Sub Category,Brand,Model #,Product Name,Quantity,Our Cost,Serial #,Receiving Condition,MSRP,Fuel Type,Status,Total Parts Cost,Sold Price,Sold By,Sold Date
Unit 1,Washer,Top Load,Whirlpool,WTW5000DW1,Top Load Washer,1,175.00,SOLD1234,A-Grade,399.00,Electric,Sold,15.00,275.00,Ben Smith,2026-08-15 14:30
CSV;

        $file = UploadedFile::fake()->createWithContent('appliances.csv', $csv);

        $this->actingAs($user)->post(route('admin.trucks.appliances.import', $truck), [
            'csv_file' => $file,
        ])->assertRedirect();

        $appliance = TruckAppliance::query()->where('serial_number', 'SOLD1234')->first();

        $this->assertNotNull($appliance);
        $this->assertSame('Sold', $appliance->status);
        $this->assertSame('275.00', $appliance->sold_price);
        $this->assertSame('Ben Smith', $appliance->sold_by);
        $this->assertSame('2026-08-15 14:30', $appliance->sold_at?->format('Y-m-d H:i'));
        $this->assertSame('Sold', $appliance->location);
    }

    public function test_import_keeps_an_unsold_status_when_sold_price_is_zero(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $this->seed(InventoryStatusSeeder::class);

        $user = User::factory()->admin()->active()->create();
        $user->syncRoles(['admin']);
        $truck = $this->truckFor($user, 'Unsold Truck');

        TruckAppliance::query()->create([
            'truck_id' => $truck->id,
            'unit_label' => 'SD-001-005',
            'serial_number' => '2280761206',
            'msrp' => 799,
            'price' => 228.99,
            'quantity' => 1,
            'status' => 'Scrap',
            'sold_price' => 0,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        $csv = <<<'CSV'
Unit Label,Category,Sub Category,Brand,Model #,Product Name,Quantity,Our Cost,Serial #,Receiving Condition,MSRP,Fuel Type,Status,Total Parts Cost,Sold Price,Sold By,Sold Date
SD-001-005,Dishwasher,Tall Tub,Beko,DUT36522X,Tall Tub Dishwasher,1,228.99,2280761206,A-Grade,799.00,N/A,Scrap,0.00,0.00,,
CSV;

        $this->actingAs($user)->post(route('admin.trucks.appliances.import', $truck), [
            'csv_file' => UploadedFile::fake()->createWithContent('appliances.csv', $csv),
        ])->assertRedirect();

        $this->assertDatabaseCount('truck_appliances', 1);

        $appliance = TruckAppliance::query()->where('unit_label', 'SD-001-005')->first();
        $this->assertNotNull($appliance);
        $this->assertSame('Scrap', $appliance->status);
        $this->assertNull($appliance->sold_price);
        $this->assertNull($appliance->sold_by);
    }

    public function test_import_matches_unit_label_before_a_shared_serial(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $this->seed(InventoryStatusSeeder::class);

        $user = User::factory()->admin()->active()->create();
        $user->syncRoles(['admin']);
        $truck = $this->truckFor($user, 'Shared Serial Truck');

        foreach (['SD-001-058' => 429, 'SD-001-060' => 379] as $label => $msrp) {
            TruckAppliance::query()->create([
                'truck_id' => $truck->id,
                'unit_label' => $label,
                'serial_number' => 'EZ59E1Z',
                'msrp' => $msrp,
                'price' => 100,
                'quantity' => 1,
                'status' => 'Sold',
                'created_by' => $user->id,
                'updated_by' => $user->id,
            ]);
        }

        $csv = <<<'CSV'
Unit Label,Category,Sub Category,Brand,Model #,Product Name,Quantity,Our Cost,Serial #,Receiving Condition,MSRP,Fuel Type,Status,Total Parts Cost,Sold Price,Sold By,Sold Date
SD-001-058,Refrigerators,Top Freezer,Vissani,MDTF10WHES4,White Top Freezer,1,122.95,EZ59E1Z,A-Grade,429.00,N/A,Sold,0.00,165.00,Ben,2026-01-02 10:00
SD-001-060,Refrigerators,Top Freezer,Vissani,MDTF10BKES4,Black Top Freezer,1,108.62,EZ59E1Z,A-Grade,379.00,N/A,Sold,0.00,200.00,Ben,2026-01-03 10:00
CSV;

        $this->actingAs($user)->post(route('admin.trucks.appliances.import', $truck), [
            'csv_file' => UploadedFile::fake()->createWithContent('appliances.csv', $csv),
        ])->assertRedirect();

        $this->assertDatabaseCount('truck_appliances', 2);
        $this->assertSame('429.00', TruckAppliance::query()->where('unit_label', 'SD-001-058')->value('msrp'));
        $this->assertSame('379.00', TruckAppliance::query()->where('unit_label', 'SD-001-060')->value('msrp'));
    }

    public function test_import_keeps_the_stored_serial_when_the_csv_value_is_damaged(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $this->seed(InventoryStatusSeeder::class);

        $user = User::factory()->admin()->active()->create();
        $user->syncRoles(['admin']);
        $truck = $this->truckFor($user, 'Serial Truck');

        TruckAppliance::query()->create([
            'truck_id' => $truck->id,
            'unit_label' => 'SD-001-008',
            'serial_number' => '7620409580228002630101',
            'msrp' => 799,
            'price' => 200,
            'quantity' => 1,
            'status' => 'Sold',
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);
        TruckAppliance::query()->create([
            'truck_id' => $truck->id,
            'unit_label' => 'SD-001-065',
            'serial_number' => '0420174',
            'msrp' => 379,
            'price' => 100,
            'quantity' => 1,
            'status' => 'Sold',
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);
        TruckAppliance::query()->create([
            'truck_id' => $truck->id,
            'unit_label' => 'SD-001-001',
            'serial_number' => '4C52410393  ',
            'msrp' => 1249,
            'price' => 300,
            'quantity' => 1,
            'status' => 'Sold',
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        $csv = <<<'CSV'
Unit Label,Category,Sub Category,Brand,Model #,Product Name,Quantity,Our Cost,Serial #,Receiving Condition,MSRP,Fuel Type,Status,Total Parts Cost
SD-001-008,Dishwasher,Tall Tub,Beko,DDT38532XIH,Tall Tub Dishwasher,1,228.99,762E21,A-Grade,799.00,N/A,Sold,0.00
SD-001-065,Refrigerators,Top Freezer,Vissani,MDTF10BKES4,Top Freezer,1,108.62,420174,A-Grade,379.00,N/A,Sold,0.00
SD-001-001,Washers,Front Load,Electrolux,ELFW7637AW2,Front Load Washer,1,357.97,4C52410393,A-Grade,1249.00,N/A,Sold,0.00
CSV;

        $this->actingAs($user)->post(route('admin.trucks.appliances.import', $truck), [
            'csv_file' => UploadedFile::fake()->createWithContent('appliances.csv', $csv),
        ])->assertRedirect();

        $this->assertDatabaseCount('truck_appliances', 3);
        $this->assertSame('7620409580228002630101', TruckAppliance::query()->where('unit_label', 'SD-001-008')->value('serial_number'));
        $this->assertSame('0420174', TruckAppliance::query()->where('unit_label', 'SD-001-065')->value('serial_number'));
        $this->assertSame('4C52410393', TruckAppliance::query()->where('unit_label', 'SD-001-001')->value('serial_number'));
    }

    public function test_user_without_permission_cannot_import_appliances(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $user = User::factory()->active()->create(['role' => 'user']);
        $user->syncRoles(['user']);

        $truck = Truck::query()->create([
            'name' => 'Locked Truck',
            'units_on_truck' => 1,
            'cost_of_truck' => 500,
            'shipping_cost' => 0,
            'arrival_date' => now()->toDateString(),
            'status' => 'active',
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        $file = UploadedFile::fake()->createWithContent('appliances.csv', "Unit Label,Category,Sub Category,Brand,Model #,Product Name,Quantity,Our Cost,Serial #,Receiving Condition,MSRP,Fuel Type,Status,Total Parts Cost\n");

        $this->actingAs($user)->post(route('admin.trucks.appliances.import', $truck), [
            'csv_file' => $file,
        ])->assertForbidden();
    }

    private function truckFor(User $user, string $name): Truck
    {
        return Truck::query()->create([
            'name' => $name,
            'units_on_truck' => 1,
            'cost_of_truck' => 500,
            'shipping_cost' => 0,
            'arrival_date' => now()->toDateString(),
            'status' => 'active',
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);
    }
}
