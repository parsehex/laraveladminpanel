<?php

namespace Tests\Feature;

use App\Models\Truck;
use App\Models\TruckAppliance;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TruckUnitLabelFixTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_cannot_see_or_run_the_unit_label_fix(): void
    {
        $admin = $this->userWithRole('admin');
        $truck = $this->createTruck($admin, 'Gamma');
        $appliance = $this->createAppliance($truck, $admin, ['unit_label' => 'Old-001']);

        $this->actingAs($admin)
            ->get(route('admin.trucks.show', $truck))
            ->assertOk()
            ->assertDontSee('Fix unit labels');

        $this->actingAs($admin)
            ->post(route('admin.trucks.unit-labels.fix', $truck), ['sort' => 'unit_label'])
            ->assertForbidden();

        $this->assertSame('Old-001', $appliance->fresh()->unit_label);
    }

    public function test_developer_preview_keeps_unit_label_numbers_and_lists_changes(): void
    {
        $developer = $this->userWithRole('developer');
        $truck = $this->createTruck($developer, 'Gamma');
        $this->createAppliance($truck, $developer, ['unit_label' => 'Other-001', 'serial_number' => 'LOSER']);
        $this->createAppliance($truck, $developer, ['unit_label' => 'Gamma-001', 'serial_number' => 'KEEPER']);
        $this->createAppliance($truck, $developer, ['unit_label' => 'Gamma-2', 'serial_number' => 'PAD']);
        $this->createAppliance($truck, $developer, ['unit_label' => null, 'serial_number' => 'BLANK']);
        $this->createAppliance($truck, $developer, ['unit_label' => 'Gamma-005', 'serial_number' => 'DONE']);

        $show = $this->actingAs($developer)->get(route('admin.trucks.show', $truck));
        $show->assertOk();
        $show->assertSee('Fix unit labels');
        $show->assertSee('Unit Label');
        $show->assertSee('Added order');

        $preview = $this->actingAs($developer)->getJson(route('admin.trucks.unit-labels.preview', [
            'truck' => $truck,
            'sort' => 'unit_label',
        ]));

        $preview->assertOk();
        $preview->assertJsonPath('keeps_numbers', true);
        $preview->assertJsonPath('unchanged', 2);
        $preview->assertJsonPath('changes.0.to', 'Gamma-002');
        $preview->assertJsonPath('changes.0.serial_number', 'PAD');
        $preview->assertJsonPath('changes.1.from', 'Other-001');
        $preview->assertJsonPath('changes.1.to', 'Gamma-003');
        $preview->assertJsonPath('changes.2.from', null);
        $preview->assertJsonPath('changes.2.to', 'Gamma-004');
    }

    public function test_developer_can_rename_labels_in_status_order(): void
    {
        $developer = $this->userWithRole('developer');
        $truck = $this->createTruck($developer, 'Gamma');
        $firstReady = $this->createAppliance($truck, $developer, [
            'unit_label' => 'junk',
            'status' => 'Ready',
            'serial_number' => 'R1',
        ]);
        $sold = $this->createAppliance($truck, $developer, [
            'unit_label' => 'junk-sold',
            'status' => 'Sold',
            'serial_number' => 'S1',
        ]);
        $secondReady = $this->createAppliance($truck, $developer, [
            'unit_label' => 'Gamma-009',
            'status' => 'Ready',
            'serial_number' => 'R2',
        ]);

        $this->actingAs($developer)
            ->post(route('admin.trucks.unit-labels.fix', $truck), ['sort' => 'status'])
            ->assertRedirect(route('admin.trucks.show', $truck))
            ->assertSessionHas('success', 'Updated 3 unit labels.');

        $this->assertSame('Gamma-001', $firstReady->fresh()->unit_label);
        $this->assertSame('Gamma-002', $secondReady->fresh()->unit_label);
        $this->assertSame('Gamma-003', $sold->fresh()->unit_label);
        $this->assertSame($developer->id, $firstReady->fresh()->updated_by);
    }

    public function test_unit_label_sort_keeps_numbers_when_confirmed(): void
    {
        $developer = $this->userWithRole('developer');
        $truck = $this->createTruck($developer, 'Gamma');
        $kept = $this->createAppliance($truck, $developer, ['unit_label' => 'OldName-004', 'serial_number' => 'KEEP']);
        $missing = $this->createAppliance($truck, $developer, ['unit_label' => 'no-number', 'serial_number' => 'NEW']);

        $this->actingAs($developer)
            ->post(route('admin.trucks.unit-labels.fix', $truck), ['sort' => 'unit_label'])
            ->assertRedirect(route('admin.trucks.show', $truck));

        $this->assertSame('Gamma-004', $kept->fresh()->unit_label);
        $this->assertSame('Gamma-001', $missing->fresh()->unit_label);
    }

    public function test_unknown_sort_is_rejected(): void
    {
        $developer = $this->userWithRole('developer');
        $truck = $this->createTruck($developer, 'Gamma');

        $this->actingAs($developer)
            ->post(route('admin.trucks.unit-labels.fix', $truck), ['sort' => 'price'])
            ->assertSessionHasErrors('sort');
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
