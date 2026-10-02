<?php

namespace Tests\Feature;

use App\Models\InventoryStatus;
use App\Models\Truck;
use App\Models\TruckAppliance;
use App\Models\User;
use Database\Seeders\InventoryStatusSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class InventoryStatusApplyAutoLocationTest extends TestCase
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

    public function test_authorized_user_can_apply_auto_location_to_items_in_status(): void
    {
        $user = $this->adminUser();
        $status = InventoryStatus::query()->where('name', 'Show Room')->firstOrFail();
        $status->update(['auto_location' => 'Front Floor']);
        $truck = $this->createTruck($user);

        $needsUpdate = TruckAppliance::query()->create([
            'truck_id' => $truck->id,
            'serial_number' => 'NEED-LOC-1',
            'status' => 'Show Room',
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);
        // Bypass the status-change auto-location hook so this item is out of sync.
        DB::table('truck_appliances')->where('id', $needsUpdate->id)->update(['location' => 'Old Bay']);

        $alreadyCurrent = TruckAppliance::query()->create([
            'truck_id' => $truck->id,
            'serial_number' => 'NEED-LOC-2',
            'status' => 'Show Room',
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);
        $otherStatus = TruckAppliance::query()->create([
            'truck_id' => $truck->id,
            'serial_number' => 'NEED-LOC-3',
            'status' => 'Ready',
            'location' => 'Bay 9',
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        $this->assertSame('Old Bay', $needsUpdate->fresh()->location);
        $this->assertSame('Front Floor', $alreadyCurrent->fresh()->location);

        $response = $this->actingAs($user)->post(route('admin.inventory-statuses.apply-auto-location', $status));

        $response->assertRedirectToRoute('admin.inventory-statuses.index');
        $response->assertSessionHas('success');
        $this->assertSame('Front Floor', $needsUpdate->fresh()->location);
        $this->assertSame('Front Floor', $alreadyCurrent->fresh()->location);
        $this->assertSame('Bay 9', $otherStatus->fresh()->location);
    }

    public function test_apply_auto_location_requires_configured_location(): void
    {
        $user = $this->adminUser();
        $status = InventoryStatus::query()->where('name', 'Triage')->firstOrFail();
        $status->update(['auto_location' => null]);

        $response = $this->actingAs($user)
            ->from(route('admin.inventory-statuses.index'))
            ->post(route('admin.inventory-statuses.apply-auto-location', $status));

        $response->assertRedirectToRoute('admin.inventory-statuses.index');
        $response->assertInvalid(['status' => 'Set an auto-location']);
    }

    public function test_technician_cannot_apply_auto_location(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $this->seed(InventoryStatusSeeder::class);
        $user = User::factory()->active()->create(['role' => 'technician']);
        $user->syncRoles(['technician']);
        $status = InventoryStatus::query()->where('name', 'Show Room')->firstOrFail();

        $this->actingAs($user)
            ->post(route('admin.inventory-statuses.apply-auto-location', $status))
            ->assertForbidden();
    }

    public function test_statuses_index_shows_apply_location_button_when_items_need_it(): void
    {
        $user = $this->adminUser();
        $status = InventoryStatus::query()->where('name', 'Show Room')->firstOrFail();
        $status->update(['auto_location' => 'Front Floor']);
        $truck = $this->createTruck($user);
        $appliance = TruckAppliance::query()->create([
            'truck_id' => $truck->id,
            'serial_number' => 'NEED-BTN-1',
            'status' => 'Show Room',
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);
        DB::table('truck_appliances')->where('id', $appliance->id)->update(['location' => null]);

        $response = $this->actingAs($user)->get(route('admin.inventory-statuses.index'));

        $response->assertOk();
        $response->assertSee('Apply location to 1', false);
    }
}
