<?php

namespace Tests\Feature;

use App\Models\InventoryStatus;
use App\Models\Truck;
use App\Models\TruckAppliance;
use App\Models\User;
use App\Support\InventoryStatusAppearance;
use Database\Seeders\InventoryStatusSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InventoryStatusColorTest extends TestCase
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

    public function test_seeder_persists_canonical_status_colors(): void
    {
        $this->seed(InventoryStatusSeeder::class);

        $this->assertDatabaseHas('inventory_statuses', [
            'name' => 'Ready',
            'color' => '#3b82f6',
        ]);
        $this->assertDatabaseHas('inventory_statuses', [
            'name' => 'Quality Control QC',
            'color' => '#16a34a',
        ]);
    }

    public function test_appearance_reads_color_from_the_database(): void
    {
        $this->seed(InventoryStatusSeeder::class);

        InventoryStatus::query()->where('name', 'Ready')->update(['color' => '#112233']);
        InventoryStatusAppearance::flush();

        $this->assertSame('#112233', InventoryStatusAppearance::background('Ready'));
        $this->assertSame(
            InventoryStatusAppearance::contrastingText('#112233'),
            InventoryStatusAppearance::colors('Ready')['text'],
        );
    }

    public function test_muted_filter_softens_status_colors_for_dense_tables(): void
    {
        $vibrant = '#3b82f6';
        $muted = InventoryStatusAppearance::mute($vibrant);

        $this->assertNotSame($vibrant, $muted);
        $this->assertMatchesRegularExpression('/^#[0-9a-f]{6}$/', $muted);
        $this->assertLessThan(
            InventoryStatusAppearance::contrastRatio($vibrant, '#ffffff'),
            InventoryStatusAppearance::contrastRatio($muted, '#ffffff'),
        );
    }

    public function test_authorized_user_can_update_status_color(): void
    {
        $user = $this->adminUser();
        $status = InventoryStatus::query()->where('name', 'Ready')->firstOrFail();

        $response = $this->actingAs($user)->patch(route('admin.inventory-statuses.update'), [
            'statuses' => [
                $status->id => [
                    'auto_location' => $status->auto_location,
                    'color' => '#abcdef',
                ],
            ],
        ]);

        $response->assertRedirectToRoute('admin.inventory-statuses.index');
        $this->assertDatabaseHas('inventory_statuses', [
            'id' => $status->id,
            'color' => '#abcdef',
        ]);
        $this->assertSame('#abcdef', InventoryStatusAppearance::background('Ready'));
    }

    public function test_create_status_requires_a_color(): void
    {
        $user = $this->adminUser();

        $response = $this->actingAs($user)
            ->from(route('admin.inventory-statuses.index'))
            ->post(route('admin.inventory-statuses.store'), [
                'name' => 'Paint Booth',
                'auto_location' => null,
            ]);

        $response->assertRedirectToRoute('admin.inventory-statuses.index');
        $response->assertInvalid(['color']);
    }

    public function test_trucks_index_uses_muted_status_chips(): void
    {
        $user = $this->adminUser();
        $truck = Truck::query()->create([
            'name' => 'Muted Chip Truck',
            'units_on_truck' => 1,
            'cost_of_truck' => 100,
            'shipping_cost' => 0,
            'arrival_date' => now()->toDateString(),
            'status' => 'active',
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);
        TruckAppliance::query()->create([
            'truck_id' => $truck->id,
            'serial_number' => 'MUTED-READY-1',
            'status' => 'Ready',
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        $mutedReady = InventoryStatusAppearance::colors('Ready', muted: true);

        $response = $this->actingAs($user)->get(route('admin.trucks.index'));

        $response->assertOk();
        $response->assertSee('background-color: '.$mutedReady['background'], false);
        $response->assertSee('status-chip-filter', false);
    }
}
