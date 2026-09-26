<?php

namespace Tests\Feature;

use App\Models\Truck;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TruckIndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_shipping_cost_is_included_in_the_cost_breakdown_when_it_is_set(): void
    {
        $user = $this->adminUser();
        $this->createTruck($user, [
            'name' => 'No Shipping Truck',
            'cost_of_truck' => 1500,
            'shipping_cost' => 0,
        ]);
        $this->createTruck($user, [
            'name' => 'Shipped Truck',
            'cost_of_truck' => 800,
            'shipping_cost' => 250,
        ]);

        $response = $this->actingAs($user)->get(route('admin.trucks.index'));

        $response->assertSee('No Shipping Truck');
        $response->assertSee('$1,500.00');
        $response->assertSee('Shipped Truck');
        $response->assertSee('$1,050.00');
        $response->assertSee('Cost of truck:</strong> $800.00', false);
        $response->assertSee('Shipping:</strong> $250.00', false);
        $response->assertDontSee('>Shipping</span>', false);
        $response->assertDontSee('Shipping:</strong> $0.00', false);
    }

    private function adminUser(): User
    {
        $this->seed(RolePermissionSeeder::class);

        $user = User::factory()->admin()->active()->create();
        $user->syncRoles(['admin']);

        return $user;
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function createTruck(User $user, array $overrides): Truck
    {
        return Truck::query()->create([
            'name' => 'Truck',
            'units_on_truck' => 1,
            'cost_of_truck' => 0,
            'shipping_cost' => 0,
            'arrival_date' => now()->toDateString(),
            'status' => 'active',
            'created_by' => $user->id,
            'updated_by' => $user->id,
            ...$overrides,
        ]);
    }
}
