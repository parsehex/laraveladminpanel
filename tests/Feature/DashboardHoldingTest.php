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

class DashboardHoldingTest extends TestCase
{
    use RefreshDatabase;

    private function adminUser(): User
    {
        $this->seed(RolePermissionSeeder::class);

        $user = User::factory()->admin()->active()->create(['name' => 'Alex Rivera']);
        $user->syncRoles(['admin']);

        return $user;
    }

    public function test_dashboard_lists_holding_appliances_with_notes(): void
    {
        $admin = $this->adminUser();
        $truck = $this->createTruck($admin);

        $holding = $this->createAppliance($truck, $admin, [
            'serial_number' => 'HOLD-1',
            'status' => 'Holding',
        ]);
        $holding->statusHistories()->create([
            'status' => 'Holding',
            'notes' => 'Waiting on customer decision',
            'parts_ordered' => false,
            'user_id' => $admin->id,
        ]);

        $holdingForParts = $this->createAppliance($truck, $admin, [
            'serial_number' => 'PARTS-1',
            'status' => 'Holding for parts',
        ]);
        $holdingForParts->statusHistories()->create([
            'status' => 'Holding for parts',
            'notes' => 'Need compressor',
            'parts_ordered' => true,
            'user_id' => $admin->id,
        ]);

        $response = $this->actingAs($admin)->get(route('admin.dashboard'));

        $response->assertOk();
        $response->assertSee('Appliances Holding');
        $response->assertSee('HOLD-1');
        $response->assertSee('Waiting on customer decision');
        $response->assertSee('Alex Rivera');
        $response->assertViewHas('holding', function ($holding) {
            return $holding->count() === 1
                && $holding->first()->serial_number === 'HOLD-1'
                && $holding->first()->statusHistories->first()?->user?->name === 'Alex Rivera';
        });
        $response->assertViewHas('holdingForParts', function ($holdingForParts) {
            return $holdingForParts->count() === 1
                && $holdingForParts->first()->serial_number === 'PARTS-1';
        });
    }

    public function test_dashboard_holding_excludes_furniture(): void
    {
        $admin = $this->adminUser();
        $truck = $this->createTruck($admin);
        $furnitureCategory = Category::query()->create([
            'name' => 'Sofas',
            'type' => ItemType::Furniture,
            'status' => 1,
            'created_by' => $admin->id,
            'updated_by' => $admin->id,
        ]);

        $this->createAppliance($truck, $admin, [
            'serial_number' => 'HOLD-APP-1',
            'status' => 'Holding',
        ]);
        $this->createAppliance($truck, $admin, [
            'category_id' => $furnitureCategory->id,
            'serial_number' => 'HOLD-FUR-1',
            'product_name' => 'Sofa B',
            'status' => 'Holding',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.dashboard'));

        $response->assertOk();
        $response->assertSee('HOLD-APP-1');
        $response->assertDontSee('HOLD-FUR-1');
        $response->assertViewHas('holding', function ($holding) {
            return $holding->count() === 1
                && $holding->first()->serial_number === 'HOLD-APP-1';
        });
    }

    public function test_dashboard_shows_empty_state_when_no_holding_appliances(): void
    {
        $admin = $this->adminUser();

        $response = $this->actingAs($admin)->get(route('admin.dashboard'));

        $response->assertOk();
        $response->assertSee('No appliances are currently holding.');
        $response->assertViewHas('holding', fn ($holding) => $holding->isEmpty());
    }

    private function createTruck(User $user): Truck
    {
        return Truck::query()->create([
            'name' => 'Holding Truck',
            'units_on_truck' => 1,
            'cost_of_truck' => 500,
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
            'serial_number' => 'SN-HOLD',
            'product_name' => 'Washer',
            'status' => 'Holding',
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ], $overrides));
    }
}
