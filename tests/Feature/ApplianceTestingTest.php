<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\TestingFlow;
use App\Models\Truck;
use App\Models\TruckAppliance;
use App\Models\User;
use Database\Seeders\InventoryStatusSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApplianceTestingTest extends TestCase
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

    private function createFlow(): TestingFlow
    {
        return TestingFlow::query()->create([
            'slug' => 'washers',
            'name' => 'Washers',
            'version' => 1,
            'start' => '1',
            'steps' => [
                '1' => [
                    'id' => '1',
                    'question' => 'Does it power on?',
                    'type' => 'radio',
                    'note' => false,
                    'options' => [
                        [
                            'key' => 'yes',
                            'text' => 'Yes',
                            'next' => 'pass',
                            'status' => null,
                        ],
                        [
                            'key' => 'no',
                            'text' => 'No',
                            'next' => null,
                            'status' => 'Repair',
                        ],
                    ],
                ],
                'pass' => [
                    'id' => 'pass',
                    'question' => 'Unit passes',
                    'type' => 'none',
                    'note' => false,
                    'next' => null,
                    'status' => 'Show Room',
                    'options' => [],
                ],
            ],
        ]);
    }

    public function test_testing_page_offers_a_next_status_select(): void
    {
        $user = $this->adminUser();
        $this->createFlow();
        $category = Category::query()->create([
            'name' => 'Washers',
            'status' => 1,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);
        $truck = Truck::query()->create([
            'name' => 'Testing Truck',
            'units_on_truck' => 1,
            'cost_of_truck' => 100,
            'shipping_cost' => 0,
            'arrival_date' => now()->toDateString(),
            'status' => 'active',
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);
        $appliance = TruckAppliance::query()->create([
            'truck_id' => $truck->id,
            'category_id' => $category->id,
            'serial_number' => 'TEST-WASHER-1',
            'status' => 'Testing',
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        $response = $this->actingAs($user)->get(route('admin.inventory.testing.show', $appliance));

        $response->assertOk();
        $response->assertSee('name="resulting_status"', false);
        $response->assertSee('id="resulting-status"', false);
        $response->assertSee('data-pass-statuses', false);
        $response->assertSee('data-fail-statuses', false);
        $response->assertViewHas('passStatuses', ['Ready', 'Cleaning', 'Show Room']);
        $response->assertViewHas('failStatuses', ['Repair', 'Demanufacture']);
    }

    public function test_completing_testing_rejects_status_outside_pass_fail_choices(): void
    {
        $user = $this->adminUser();
        $this->createFlow();
        $category = Category::query()->create([
            'name' => 'Washers',
            'status' => 1,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);
        $truck = Truck::query()->create([
            'name' => 'Testing Truck',
            'units_on_truck' => 1,
            'cost_of_truck' => 100,
            'shipping_cost' => 0,
            'arrival_date' => now()->toDateString(),
            'status' => 'active',
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);
        $appliance = TruckAppliance::query()->create([
            'truck_id' => $truck->id,
            'category_id' => $category->id,
            'serial_number' => 'TEST-WASHER-REJECT',
            'status' => 'Testing',
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        $response = $this->actingAs($user)->from(route('admin.inventory.testing.show', $appliance))->post(
            route('admin.inventory.testing.store', $appliance),
            [
                'resulting_status' => 'Sold',
                'answers_json' => json_encode(['1' => ['answer' => 'yes', 'note' => '']]),
                'flow_slug' => 'washers',
                'flow_version' => 1,
            ],
        );

        $response->assertRedirect(route('admin.inventory.testing.show', $appliance));
        $response->assertSessionHasErrors('resulting_status');
        $this->assertDatabaseHas('truck_appliances', [
            'id' => $appliance->id,
            'status' => 'Testing',
        ]);
    }

    public function test_completing_testing_can_use_a_selected_status(): void
    {
        $user = $this->adminUser();
        $this->createFlow();
        $category = Category::query()->create([
            'name' => 'Washers',
            'status' => 1,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);
        $truck = Truck::query()->create([
            'name' => 'Testing Truck',
            'units_on_truck' => 1,
            'cost_of_truck' => 100,
            'shipping_cost' => 0,
            'arrival_date' => now()->toDateString(),
            'status' => 'active',
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);
        $appliance = TruckAppliance::query()->create([
            'truck_id' => $truck->id,
            'category_id' => $category->id,
            'serial_number' => 'TEST-WASHER-2',
            'status' => 'Testing',
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        $response = $this->actingAs($user)->post(route('admin.inventory.testing.store', $appliance), [
            'resulting_status' => 'Ready',
            'answers_json' => json_encode(['1' => ['answer' => 'yes', 'note' => '']]),
            'flow_slug' => 'washers',
            'flow_version' => 1,
            'notes' => 'Passed, send to ready instead of show room',
        ]);

        $response->assertRedirect(route('admin.inventory.show', $appliance));
        $this->assertDatabaseHas('truck_appliances', [
            'id' => $appliance->id,
            'status' => 'Ready',
        ]);
    }
}
