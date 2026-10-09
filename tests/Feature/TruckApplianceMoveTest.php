<?php

namespace Tests\Feature;

use App\Models\InventoryStatusHistory;
use App\Models\Truck;
use App\Models\TruckAppliance;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TruckApplianceMoveTest extends TestCase
{
    use RefreshDatabase;

    public function test_technician_cannot_see_or_move_selected_units(): void
    {
        $technician = $this->userWithRole('technician');
        $source = $this->createTruck($technician, 'Source');
        $destination = $this->createTruck($technician, 'Destination');
        $appliance = $this->createAppliance($source, $technician, ['unit_label' => 'Source-001']);

        $this->actingAs($technician)
            ->get(route('admin.trucks.show', $source))
            ->assertOk()
            ->assertDontSee('Move selected');

        $this->actingAs($technician)
            ->post(route('admin.trucks.appliances.move', $source), [
                'truck_id' => $destination->id,
                'appliance_ids' => [$appliance->id],
            ])
            ->assertForbidden();

        $this->assertSame($source->id, $appliance->fresh()->truck_id);
    }

    public function test_admin_can_move_selected_units_to_another_truck(): void
    {
        $admin = $this->userWithRole('admin');
        $source = $this->createTruck($admin, 'Source');
        $destination = $this->createTruck($admin, 'Destination');
        $leftBehind = $this->createAppliance($source, $admin, ['unit_label' => 'Source-001', 'serial_number' => 'STAY']);
        $moving = $this->createAppliance($source, $admin, ['unit_label' => 'Source-002', 'serial_number' => 'GO', 'status' => 'Ready']);
        $elsewhere = $this->createAppliance($destination, $admin, ['unit_label' => 'Destination-001']);

        $this->actingAs($admin)
            ->get(route('admin.trucks.show', $source))
            ->assertOk()
            ->assertSee('Move selected')
            ->assertSee('<option value="'.$destination->id.'">Destination</option>', false)
            ->assertDontSee('<option value="'.$source->id.'">Source</option>', false);

        $this->actingAs($admin)
            ->post(route('admin.trucks.appliances.move', $source), [
                'truck_id' => $destination->id,
                'appliance_ids' => [$moving->id],
            ])
            ->assertRedirect(route('admin.trucks.show', $source))
            ->assertSessionHas('success', 'Moved 1 unit to Destination.');

        $this->assertSame($destination->id, $moving->fresh()->truck_id);
        $this->assertSame('Source-002', $moving->fresh()->unit_label);
        $this->assertSame($source->id, $leftBehind->fresh()->truck_id);
        $this->assertSame($destination->id, $elsewhere->fresh()->truck_id);
        $this->assertDatabaseHas('inventory_status_histories', [
            'truck_appliance_id' => $moving->id,
            'status' => 'Ready',
            'notes' => 'Moved from Source to Destination.',
            'user_id' => $admin->id,
        ]);
    }

    public function test_move_rejects_units_that_are_not_on_the_source_truck(): void
    {
        $admin = $this->userWithRole('admin');
        $source = $this->createTruck($admin, 'Source');
        $destination = $this->createTruck($admin, 'Destination');
        $other = $this->createTruck($admin, 'Other');
        $onSource = $this->createAppliance($source, $admin, ['unit_label' => 'Source-001']);
        $notOnSource = $this->createAppliance($other, $admin, ['unit_label' => 'Other-001']);

        $this->actingAs($admin)
            ->from(route('admin.trucks.show', $source))
            ->post(route('admin.trucks.appliances.move', $source), [
                'truck_id' => $destination->id,
                'appliance_ids' => [$onSource->id, $notOnSource->id],
            ])
            ->assertRedirect(route('admin.trucks.show', $source))
            ->assertSessionHasErrors('appliance_ids');

        $this->assertSame($source->id, $onSource->fresh()->truck_id);
        $this->assertSame($other->id, $notOnSource->fresh()->truck_id);
        $this->assertSame(0, InventoryStatusHistory::query()->count());
    }

    public function test_move_rejects_the_current_truck_as_the_destination(): void
    {
        $admin = $this->userWithRole('admin');
        $source = $this->createTruck($admin, 'Source');
        $appliance = $this->createAppliance($source, $admin);

        $this->actingAs($admin)
            ->from(route('admin.trucks.show', $source))
            ->post(route('admin.trucks.appliances.move', $source), [
                'truck_id' => $source->id,
                'appliance_ids' => [$appliance->id],
            ])
            ->assertRedirect(route('admin.trucks.show', $source))
            ->assertSessionHasErrors('truck_id');

        $this->assertSame($source->id, $appliance->fresh()->truck_id);
    }

    private function userWithRole(string $role): User
    {
        $this->seed(RolePermissionSeeder::class);

        $user = User::factory()->state(['role' => $role])->active()->create();
        $user->syncRoles([$role]);

        return $user;
    }

    private function createTruck(User $user, string $name): Truck
    {
        return Truck::query()->create([
            'name' => $name,
            'units_on_truck' => 1,
            'cost_of_truck' => 0,
            'shipping_cost' => 0,
            'arrival_date' => now()->toDateString(),
            'status' => 'active',
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function createAppliance(Truck $truck, User $user, array $overrides = []): TruckAppliance
    {
        return TruckAppliance::query()->create(array_merge([
            'truck_id' => $truck->id,
            'serial_number' => 'SN',
            'product_name' => 'Washer',
            'status' => 'Ready',
            'price' => 0,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ], $overrides));
    }
}
