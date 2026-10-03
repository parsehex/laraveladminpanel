<?php

namespace Tests\Feature;

use App\Models\AppliancePart;
use App\Models\Part;
use App\Models\Truck;
use App\Models\TruckAppliance;
use App\Models\User;
use Database\Seeders\InventoryStatusSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AppliancePartStoreTest extends TestCase
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

    private function createAppliance(User $user): TruckAppliance
    {
        $truck = Truck::query()->create([
            'name' => 'Parts Truck',
            'units_on_truck' => 1,
            'cost_of_truck' => 1000,
            'shipping_cost' => 0,
            'arrival_date' => now()->toDateString(),
            'status' => 'active',
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        return TruckAppliance::query()->create([
            'truck_id' => $truck->id,
            'serial_number' => 'PART-ADD-1',
            'product_name' => 'Washer',
            'brand' => 'Whirlpool',
            'status' => 'Ready',
            'price' => 100,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);
    }

    public function test_new_part_requires_part_number_when_not_selecting_catalog_part(): void
    {
        $user = $this->adminUser();
        $appliance = $this->createAppliance($user);

        $response = $this->actingAs($user)->from(route('admin.inventory.show', $appliance))->post(
            route('admin.inventory.parts.store', $appliance),
            [
                'description' => 'Control board',
                'cost' => 75,
            ]
        );

        $response->assertRedirect(route('admin.inventory.show', $appliance));
        $response->assertSessionHasErrors('part_number');
        $this->assertDatabaseCount('parts', 0);
        $this->assertDatabaseCount('appliance_parts', 0);
    }

    public function test_new_part_creates_catalog_entry_with_provided_part_number(): void
    {
        $user = $this->adminUser();
        $appliance = $this->createAppliance($user);

        $response = $this->actingAs($user)->post(route('admin.inventory.parts.store', $appliance), [
            'description' => 'Control board',
            'part_number' => 'W10820039',
            'cost' => 75,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $part = Part::query()->where('part_number', 'W10820039')->first();
        $this->assertNotNull($part);
        $this->assertSame('Control board', $part->product_name);

        $this->assertDatabaseHas('appliance_parts', [
            'truck_appliance_id' => $appliance->id,
            'part_id' => $part->id,
            'part_number' => 'W10820039',
            'description' => 'Control board',
        ]);
    }

    public function test_selecting_existing_catalog_part_does_not_require_part_number(): void
    {
        $user = $this->adminUser();
        $appliance = $this->createAppliance($user);

        $catalogPart = Part::query()->create([
            'part_number' => 'EXISTING-1',
            'product_name' => 'Door seal',
            'total_stock' => 2,
            'retail_price' => 40,
            'your_price' => 35,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        $response = $this->actingAs($user)->post(route('admin.inventory.parts.store', $appliance), [
            'part_id' => $catalogPart->id,
            'description' => 'Door seal',
            'cost' => 35,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $this->assertDatabaseCount('parts', 1);
        $this->assertDatabaseHas('appliance_parts', [
            'truck_appliance_id' => $appliance->id,
            'part_id' => $catalogPart->id,
            'part_number' => 'EXISTING-1',
        ]);
    }

    public function test_new_part_rejects_duplicate_active_part_number(): void
    {
        $user = $this->adminUser();
        $appliance = $this->createAppliance($user);

        Part::query()->create([
            'part_number' => 'DUP-1',
            'product_name' => 'Existing part',
            'total_stock' => 1,
            'retail_price' => 10,
            'your_price' => 10,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        $response = $this->actingAs($user)->from(route('admin.inventory.show', $appliance))->post(
            route('admin.inventory.parts.store', $appliance),
            [
                'description' => 'Different description',
                'part_number' => 'DUP-1',
                'cost' => 20,
            ]
        );

        $response->assertRedirect(route('admin.inventory.show', $appliance));
        $response->assertSessionHasErrors('part_number');
        $this->assertSame(0, AppliancePart::query()->count());
    }

    public function test_add_part_form_no_longer_promises_auto_generated_part_numbers(): void
    {
        $user = $this->adminUser();
        $appliance = $this->createAppliance($user);

        $response = $this->actingAs($user)->get(route('admin.inventory.show', $appliance));

        $response->assertOk();
        $response->assertDontSee('Auto-generated after save');
        $response->assertSee('name="part_number"', false);
    }
}
