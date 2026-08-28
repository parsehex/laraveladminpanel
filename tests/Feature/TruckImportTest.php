<?php

namespace Tests\Feature;

use App\Models\Truck;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class TruckImportTest extends TestCase
{
    use RefreshDatabase;

    public function test_authorized_user_can_import_trucks_from_csv(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $user = User::factory()->admin()->active()->create();
        $user->syncRoles(['admin']);

        $csv = <<<'CSV'
Name,Units on Truck,Cost of Truck,Shipping Cost,Arrival Date,Status,Notes
Route 7 Delivery,24,185000.00,2500.00,2026-09-01,active,Regional route
Warehouse Transfer A,12,92000.50,0,2026-08-27,active,
CSV;

        $file = UploadedFile::fake()->createWithContent('trucks.csv', $csv);

        $response = $this->actingAs($user)->post(route('admin.trucks.import'), [
            'csv_file' => $file,
        ]);

        $response->assertRedirect(route('admin.trucks.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseCount('trucks', 2);
        $this->assertDatabaseHas('trucks', [
            'name' => 'Route 7 Delivery',
            'units_on_truck' => 24,
            'cost_of_truck' => 185000.00,
            'shipping_cost' => 2500.00,
            'status' => 'active',
            'notes' => 'Regional route',
        ]);
        $this->assertDatabaseHas('trucks', [
            'name' => 'Warehouse Transfer A',
            'units_on_truck' => 12,
            'cost_of_truck' => 92000.50,
            'shipping_cost' => 0,
        ]);
    }

    public function test_import_updates_existing_truck_when_name_matches(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $user = User::factory()->admin()->active()->create();
        $user->syncRoles(['admin']);

        Truck::query()->create([
            'name' => 'Route 7 Delivery',
            'units_on_truck' => 10,
            'cost_of_truck' => 1000,
            'shipping_cost' => 0,
            'arrival_date' => '2026-01-01',
            'status' => 'active',
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        $csv = <<<'CSV'
Name,Units on Truck,Cost of Truck,Shipping Cost,Arrival Date,Status,Notes
Route 7 Delivery,24,185000.00,2500.00,2026-09-01,active,Updated notes
CSV;

        $file = UploadedFile::fake()->createWithContent('trucks.csv', $csv);

        $this->actingAs($user)->post(route('admin.trucks.import'), [
            'csv_file' => $file,
        ])->assertRedirect();

        $this->assertDatabaseCount('trucks', 1);
        $this->assertDatabaseHas('trucks', [
            'name' => 'Route 7 Delivery',
            'units_on_truck' => 24,
            'cost_of_truck' => 185000.00,
            'shipping_cost' => 2500.00,
            'notes' => 'Updated notes',
        ]);
    }

    public function test_user_without_permission_cannot_import_trucks(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $user = User::factory()->active()->create(['role' => 'user']);
        $user->syncRoles(['user']);

        $file = UploadedFile::fake()->createWithContent('trucks.csv', "Name,Units on Truck,Cost of Truck,Shipping Cost,Arrival Date,Status,Notes\n");

        $this->actingAs($user)->post(route('admin.trucks.import'), [
            'csv_file' => $file,
        ])->assertForbidden();
    }
}
