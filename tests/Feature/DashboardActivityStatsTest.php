<?php

namespace Tests\Feature;

use App\Models\CustomSale;
use App\Models\Truck;
use App\Models\TruckAppliance;
use App\Models\User;
use App\Models\UserAction;
use Carbon\Carbon;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardActivityStatsTest extends TestCase
{
    use RefreshDatabase;

    private function adminUser(): User
    {
        $this->seed(RolePermissionSeeder::class);

        $user = User::factory()->admin()->active()->create(['name' => 'Alex Rivera']);
        $user->syncRoles(['admin']);

        return $user;
    }

    public function test_dashboard_activity_stats_use_user_actions_for_the_selected_period(): void
    {
        Carbon::setTestNow('2026-09-30 12:00:00');

        $admin = $this->adminUser();
        $worker = User::factory()->active()->create(['name' => 'Jordan Lee']);

        $truck = Truck::query()->create([
            'name' => 'Stats Truck',
            'units_on_truck' => 1,
            'cost_of_truck' => 500,
            'shipping_cost' => 0,
            'arrival_date' => now()->toDateString(),
            'status' => 'active',
            'created_by' => $admin->id,
            'updated_by' => $admin->id,
        ]);

        $appliance = TruckAppliance::query()->create([
            'truck_id' => $truck->id,
            'serial_number' => 'DASH-1',
            'product_name' => 'Washer',
            'msrp' => 899.50,
            'status' => 'Ready',
            'created_by' => $admin->id,
            'updated_by' => $admin->id,
        ]);

        UserAction::query()->create([
            'username' => $worker->name,
            'user_id' => $worker->id,
            'action_type' => 'create_appliance',
            'item_id' => $appliance->id,
            'created_at' => now()->subDays(2),
        ]);
        UserAction::query()->create([
            'username' => $worker->name,
            'user_id' => $worker->id,
            'action_type' => 'test_unit',
            'item_id' => $appliance->id,
            'created_at' => now()->subDays(2),
        ]);
        UserAction::query()->create([
            'username' => $worker->name,
            'user_id' => $worker->id,
            'action_type' => 'mark_sold',
            'item_id' => $appliance->id,
            'created_at' => now()->subDays(2),
        ]);

        UserAction::query()->create([
            'username' => $worker->name,
            'user_id' => $worker->id,
            'action_type' => 'add_truck',
            'item_id' => null,
            'created_at' => now()->subDays(40),
        ]);

        $weekly = $this->actingAs($admin)->get(route('admin.dashboard', ['period' => 'weekly']));
        $weekly->assertOk();
        $weekly->assertSee('Jordan Lee');
        $weekly->assertSee('Last 7 days');
        $weekly->assertViewHas('activityRows', function ($rows) {
            $row = $rows->firstWhere('username', 'Jordan Lee');

            return $row
                && $row['units_added'] === 1
                && $row['units_tested'] === 1
                && $row['repaired'] === 1
                && $row['sales_marked'] === 1
                && $row['trucks_added'] === 0
                && abs($row['total_msrp_added'] - 899.50) < 0.01;
        });

        $daily = $this->actingAs($admin)->get(route('admin.dashboard', ['period' => 'daily']));
        $daily->assertOk();
        $daily->assertDontSee('Jordan Lee');
        $daily->assertViewHas('activityRows', fn ($rows) => $rows->isEmpty());

        $yearly = $this->actingAs($admin)->get(route('admin.dashboard', ['period' => 'yearly']));
        $yearly->assertOk();
        $yearly->assertSee('Last 365 days');
        $yearly->assertViewHas('activityRows', function ($rows) {
            $row = $rows->firstWhere('username', 'Jordan Lee');

            return $row
                && $row['units_added'] === 1
                && $row['trucks_added'] === 1;
        });

        Carbon::setTestNow();
    }

    public function test_operations_kpis_follow_the_selected_period(): void
    {
        Carbon::setTestNow('2026-09-30 12:00:00');

        $admin = $this->adminUser();

        $truck = Truck::query()->create([
            'name' => 'KPI Truck',
            'units_on_truck' => 3,
            'cost_of_truck' => 1000,
            'shipping_cost' => 0,
            'arrival_date' => now()->toDateString(),
            'status' => 'active',
            'created_by' => $admin->id,
            'updated_by' => $admin->id,
        ]);

        $this->createApplianceAt($truck->id, $admin->id, [
            'serial_number' => 'KPI-READY',
            'price' => 100,
            'status' => 'Ready',
        ], now()->subDays(2));

        $this->createApplianceAt($truck->id, $admin->id, [
            'serial_number' => 'KPI-OLD',
            'price' => 400,
            'status' => 'Ready',
        ], now()->subDays(40));

        $this->createApplianceAt($truck->id, $admin->id, [
            'serial_number' => 'KPI-SOLD',
            'price' => 50,
            'sold_price' => 275,
            'status' => 'Sold',
            'sold_at' => now()->subDays(3),
        ], now()->subDays(20), now()->subDays(3));

        $this->createCustomSaleAt([
            'model_number' => 'CUSTOM-1',
            'serial_number' => 'CUSTOM-SER',
            'sold_price' => 125,
            'sold_by' => $admin->name,
            'created_by' => $admin->id,
        ], now()->subDays(1));

        $this->createCustomSaleAt([
            'model_number' => 'CUSTOM-OLD',
            'serial_number' => 'CUSTOM-OLD-SER',
            'sold_price' => 999,
            'sold_by' => $admin->name,
            'created_by' => $admin->id,
        ], now()->subDays(60));

        $weekly = $this->actingAs($admin)->get(route('admin.dashboard', ['period' => 'weekly']));
        $weekly->assertOk();
        $weekly->assertSee('Units Added');
        $weekly->assertViewHas('stats', function (array $stats) {
            return $stats['total_units'] === 1
                && abs((float) $stats['inventory_value'] - 100.0) < 0.01
                && $stats['sold_units'] === 1
                && abs((float) $stats['sales_total'] - 400.0) < 0.01;
        });

        $daily = $this->actingAs($admin)->get(route('admin.dashboard', ['period' => 'daily']));
        $daily->assertOk();
        $daily->assertViewHas('stats', function (array $stats) {
            return $stats['total_units'] === 0
                && abs((float) $stats['inventory_value']) < 0.01
                && $stats['sold_units'] === 0
                && abs((float) $stats['sales_total'] - 125.0) < 0.01;
        });

        Carbon::setTestNow();
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function createApplianceAt(
        int $truckId,
        int $userId,
        array $attributes,
        Carbon $createdAt,
        ?Carbon $updatedAt = null,
    ): TruckAppliance {
        $appliance = TruckAppliance::query()->create([
            'truck_id' => $truckId,
            'created_by' => $userId,
            'updated_by' => $userId,
            ...$attributes,
        ]);

        $appliance->forceFill([
            'created_at' => $createdAt,
            'updated_at' => $updatedAt ?? $createdAt,
        ])->saveQuietly();

        return $appliance->refresh();
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function createCustomSaleAt(array $attributes, Carbon $createdAt): CustomSale
    {
        $sale = CustomSale::query()->create($attributes);

        $sale->forceFill([
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ])->saveQuietly();

        return $sale->refresh();
    }
}
