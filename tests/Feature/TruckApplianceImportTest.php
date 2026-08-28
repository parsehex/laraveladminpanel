<?php

namespace Tests\Feature;

use App\Models\Truck;
use App\Models\TruckAppliance;
use App\Models\User;
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
}
