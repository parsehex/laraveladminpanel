<?php

namespace Tests\Feature;

use App\Models\Truck;
use App\Models\TruckAppliance;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SaleLookupAmbiguityTest extends TestCase
{
    use RefreshDatabase;

    public function test_numeric_input_that_matches_id_and_a_different_serial_requires_a_choice(): void
    {
        $user = $this->adminUser();
        [$byId, $bySerial] = $this->conflictingUnits($user);

        $response = $this->actingAs($user)->from(route('admin.sales.index'))->post(route('admin.sales.mark-sold'), [
            'sale_type' => 'normal',
            'serial_number' => (string) $byId->id,
            'sold_price' => 199,
        ]);

        $response->assertRedirect(route('admin.sales.index'));
        $response->assertSessionHas('sale_lookup_conflict');
        $response->assertSessionHas('warning');
        $this->assertSame('Ready', $byId->fresh()->status);
        $this->assertSame('Ready', $bySerial->fresh()->status);
    }

    public function test_conflict_can_be_resolved_as_the_item_id_match(): void
    {
        $user = $this->adminUser();
        [$byId, $bySerial] = $this->conflictingUnits($user);

        $response = $this->actingAs($user)->post(route('admin.sales.mark-sold'), [
            'sale_type' => 'normal',
            'serial_number' => (string) $byId->id,
            'sold_price' => 199,
            'appliance_id' => $byId->id,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $this->assertSame('Sold', $byId->fresh()->status);
        $this->assertSame('Ready', $bySerial->fresh()->status);
    }

    public function test_conflict_can_be_resolved_as_the_serial_match(): void
    {
        $user = $this->adminUser();
        [$byId, $bySerial] = $this->conflictingUnits($user);

        $response = $this->actingAs($user)->post(route('admin.sales.mark-sold'), [
            'sale_type' => 'normal',
            'serial_number' => (string) $byId->id,
            'sold_price' => 250,
            'appliance_id' => $bySerial->id,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $this->assertSame('Ready', $byId->fresh()->status);
        $this->assertSame('Sold', $bySerial->fresh()->status);
        $this->assertSame('250.00', $bySerial->fresh()->sold_price);
    }

    public function test_sticker_url_still_resolves_as_item_id_without_conflict(): void
    {
        $user = $this->adminUser();
        [$byId, $bySerial] = $this->conflictingUnits($user);

        $response = $this->actingAs($user)->post(route('admin.sales.mark-sold'), [
            'sale_type' => 'normal',
            'serial_number' => 'https://example.test/admin/inventory/'.$byId->id,
            'sold_price' => 175,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $response->assertSessionMissing('sale_lookup_conflict');
        $this->assertSame('Sold', $byId->fresh()->status);
        $this->assertSame('Ready', $bySerial->fresh()->status);
    }

    /**
     * @return array{0: TruckAppliance, 1: TruckAppliance}
     */
    private function conflictingUnits(User $user): array
    {
        $truck = Truck::query()->create([
            'name' => 'Sale Ambiguity Truck',
            'units_on_truck' => 2,
            'cost_of_truck' => 1000,
            'shipping_cost' => 0,
            'arrival_date' => now()->toDateString(),
            'status' => 'active',
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        $byId = TruckAppliance::query()->create([
            'truck_id' => $truck->id,
            'serial_number' => 'ALPHA-1',
            'product_name' => 'Matched by ID',
            'status' => 'Ready',
            'price' => 100,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        $bySerial = TruckAppliance::query()->create([
            'truck_id' => $truck->id,
            'serial_number' => (string) $byId->id,
            'product_name' => 'Matched by serial',
            'status' => 'Ready',
            'price' => 100,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        return [$byId, $bySerial];
    }

    private function adminUser(): User
    {
        $this->seed(RolePermissionSeeder::class);

        $user = User::factory()->admin()->active()->create();
        $user->syncRoles(['admin']);

        return $user;
    }
}
