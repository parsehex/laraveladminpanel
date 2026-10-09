<?php

namespace Tests\Feature;

use App\Enums\ItemType;
use App\Models\Category;
use App\Models\Truck;
use App\Models\TruckAppliance;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RemoveOrphanedTruckItemsTest extends TestCase
{
    use RefreshDatabase;

    public function test_dry_run_leaves_items_on_a_deleted_truck(): void
    {
        [$orphan] = $this->orphanAndLiveItem();

        $this->artisan('inventory:remove-orphaned-truck-items')
            ->assertSuccessful()
            ->expectsOutputToContain('WF-002')
            ->expectsOutputToContain('Dry run');

        $this->assertNotSoftDeleted($orphan->fresh());
    }

    public function test_force_soft_deletes_only_items_on_deleted_trucks(): void
    {
        [$orphan, $live, $alreadyDeleted] = $this->orphanAndLiveItem();

        $this->artisan('inventory:remove-orphaned-truck-items', ['--force' => true])
            ->assertSuccessful()
            ->expectsOutputToContain('Soft-deleted 1 item.');

        $this->assertSoftDeleted($orphan->fresh());
        $this->assertNotSoftDeleted($live->fresh());
        $this->assertSoftDeleted($alreadyDeleted->fresh());
    }

    /**
     * @return array{0: TruckAppliance, 1: TruckAppliance, 2: TruckAppliance}
     */
    private function orphanAndLiveItem(): array
    {
        $user = User::factory()->admin()->active()->create();
        $deletedTruck = $this->truck($user, 'WF-002');
        $liveTruck = $this->truck($user, 'WF-001');
        $sofas = Category::query()->create([
            'name' => 'Sofas',
            'type' => ItemType::Furniture,
            'status' => 1,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        $orphan = TruckAppliance::query()->create([
            'truck_id' => $deletedTruck->id,
            'category_id' => $sofas->id,
            'serial_number' => 'ORPHAN-SOFA',
            'status' => 'Testing',
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);
        $live = TruckAppliance::query()->create([
            'truck_id' => $liveTruck->id,
            'category_id' => $sofas->id,
            'serial_number' => 'LIVE-SOFA',
            'status' => 'Testing',
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);
        $alreadyDeleted = TruckAppliance::query()->create([
            'truck_id' => $liveTruck->id,
            'category_id' => $sofas->id,
            'serial_number' => 'ALREADY-DELETED',
            'status' => 'Testing',
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);
        $alreadyDeleted->delete();
        $deletedTruck->delete();

        return [$orphan, $live, $alreadyDeleted];
    }

    private function truck(User $user, string $name): Truck
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
}
