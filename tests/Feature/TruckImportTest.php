<?php

namespace Tests\Feature;

use App\Models\Truck;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class TruckImportTest extends TestCase
{
    use RefreshDatabase;

    public function test_authorized_user_can_import_trucks_from_csv(): void
    {
        $user = $this->adminUser();

        $csv = <<<'CSV'
Name,Units on Truck,Cost of Truck,Shipping Cost,Arrival Date,Status,Notes
Route 7 Delivery,24,185000.00,2500.00,2026-09-01,active,Regional route
Warehouse Transfer A,12,92000.50,0,2026-08-27,active,
CSV;

        $this->importAndConfirm($user, $csv)
            ->assertRedirect(route('admin.trucks.index'))
            ->assertSessionHas('success');

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

    public function test_upload_redirects_to_review_without_writing_trucks(): void
    {
        $user = $this->adminUser();

        $csv = <<<'CSV'
Name,Units on Truck,Cost of Truck,Shipping Cost,Arrival Date,Status,Notes
Route 7 Delivery,24,185000.00,2500.00,2026-09-01,active,Regional route
CSV;

        $this->actingAs($user)->post(route('admin.trucks.import'), [
            'csv_file' => UploadedFile::fake()->createWithContent('trucks.csv', $csv),
        ])->assertRedirect(route('admin.trucks.import.review'));

        $this->assertDatabaseCount('trucks', 0);

        $this->actingAs($user)
            ->get(route('admin.trucks.import.review'))
            ->assertOk()
            ->assertSee('Will create')
            ->assertSee('Route 7 Delivery');
    }

    public function test_preview_shows_later_duplicate_name_as_update(): void
    {
        $user = $this->adminUser();

        $csv = <<<'CSV'
Name,Units on Truck,Cost of Truck,Shipping Cost,Arrival Date,Status,Notes
Route 7 Delivery,24,185000.00,2500.00,2026-09-01,active,First
Route 7 Delivery,30,190000.00,2500.00,2026-09-01,active,Second
CSV;

        $this->actingAs($user)->post(route('admin.trucks.import'), [
            'csv_file' => UploadedFile::fake()->createWithContent('trucks.csv', $csv),
        ])->assertRedirect(route('admin.trucks.import.review'));

        $this->actingAs($user)
            ->get(route('admin.trucks.import.review'))
            ->assertOk()
            ->assertSee('Will create')
            ->assertSee('Will update')
            ->assertSee('Units on Truck:')
            ->assertSee('Notes:');

        $this->importAndConfirm($user, $csv)->assertRedirect();

        $this->assertDatabaseCount('trucks', 1);
        $this->assertDatabaseHas('trucks', [
            'name' => 'Route 7 Delivery',
            'units_on_truck' => 30,
            'notes' => 'Second',
        ]);
    }

    public function test_import_updates_existing_truck_when_name_matches(): void
    {
        $user = $this->adminUser();

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

        $this->importAndConfirm($user, $csv)->assertRedirect();

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

    private function adminUser(): User
    {
        $this->seed(RolePermissionSeeder::class);

        $user = User::factory()->admin()->active()->create();
        $user->syncRoles(['admin']);

        return $user;
    }

    private function importAndConfirm(User $user, string $csv): TestResponse
    {
        $this->actingAs($user)->post(route('admin.trucks.import'), [
            'csv_file' => UploadedFile::fake()->createWithContent('trucks.csv', $csv),
        ])->assertRedirect(route('admin.trucks.import.review'));

        return $this->actingAs($user)->post(route('admin.trucks.import.confirm'));
    }
}
