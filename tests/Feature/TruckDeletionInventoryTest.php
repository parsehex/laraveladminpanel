<?php

namespace Tests\Feature;

use App\Enums\ItemType;
use App\Models\Category;
use App\Models\Truck;
use App\Models\TruckAppliance;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TruckDeletionInventoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_deleting_a_truck_hides_its_items_from_inventory_lists(): void
    {
        $user = $this->adminUser();
        $deletedTruck = $this->truck($user, 'WF-002');
        $liveTruck = $this->truck($user, 'WF-001');
        $sofas = $this->category($user, 'Sofas', ItemType::Furniture);
        $washers = $this->category($user, 'Washers', ItemType::Appliance);

        $this->item($deletedTruck, $user, $sofas, 'ORPHAN-SOFA');
        $this->item($deletedTruck, $user, $washers, 'ORPHAN-WASHER');
        $this->item($liveTruck, $user, $sofas, 'LIVE-SOFA');
        $this->item($liveTruck, $user, $washers, 'LIVE-WASHER');

        $this->actingAs($user)
            ->delete(route('admin.trucks.destroy', $deletedTruck))
            ->assertRedirect(route('admin.trucks.index'));

        $this->assertSoftDeleted($deletedTruck);
        $this->assertSoftDeleted(TruckAppliance::withTrashed()->where('serial_number', 'ORPHAN-SOFA')->first());
        $this->assertSoftDeleted(TruckAppliance::withTrashed()->where('serial_number', 'ORPHAN-WASHER')->first());
        $this->assertNotSoftDeleted(TruckAppliance::query()->where('serial_number', 'LIVE-SOFA')->first());

        $furniture = $this->actingAs($user)->get(route('admin.inventory.furniture'));
        $furniture->assertOk();
        $furniture->assertSee('LIVE-SOFA');
        $furniture->assertDontSee('ORPHAN-SOFA');

        $appliances = $this->actingAs($user)->get(route('admin.inventory.index'));
        $appliances->assertOk();
        $appliances->assertSee('LIVE-WASHER');
        $appliances->assertDontSee('ORPHAN-WASHER');
    }

    public function test_items_left_on_a_deleted_truck_stay_off_inventory_lists(): void
    {
        $user = $this->adminUser();
        $deletedTruck = $this->truck($user, 'Old WF-002');
        $sofas = $this->category($user, 'Sofas', ItemType::Furniture);
        $washers = $this->category($user, 'Washers', ItemType::Appliance);

        $this->item($deletedTruck, $user, $sofas, 'LEFT-SOFA');
        $this->item($deletedTruck, $user, $washers, 'LEFT-WASHER');
        $deletedTruck->delete();

        $furniture = $this->actingAs($user)->get(route('admin.inventory.furniture'));
        $furniture->assertOk();
        $furniture->assertDontSee('LEFT-SOFA');

        $appliances = $this->actingAs($user)->get(route('admin.inventory.index'));
        $appliances->assertOk();
        $appliances->assertDontSee('LEFT-WASHER');
    }

    private function adminUser(): User
    {
        $this->seed(RolePermissionSeeder::class);

        $user = User::factory()->admin()->active()->create();
        $user->syncRoles(['admin']);

        return $user;
    }

    private function truck(User $user, string $name): Truck
    {
        return Truck::query()->create([
            'name' => $name,
            'units_on_truck' => 2,
            'cost_of_truck' => 1000,
            'shipping_cost' => 0,
            'arrival_date' => now()->toDateString(),
            'status' => 'active',
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);
    }

    private function category(User $user, string $name, ItemType $type): Category
    {
        return Category::query()->create([
            'name' => $name,
            'type' => $type,
            'status' => 1,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);
    }

    private function item(Truck $truck, User $user, Category $category, string $serial): TruckAppliance
    {
        return TruckAppliance::query()->create([
            'truck_id' => $truck->id,
            'category_id' => $category->id,
            'serial_number' => $serial,
            'status' => 'Ready',
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);
    }
}
