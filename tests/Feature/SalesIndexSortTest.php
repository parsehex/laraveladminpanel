<?php

namespace Tests\Feature;

use App\Enums\ItemType;
use App\Models\Category;
use App\Models\Model as CatalogModel;
use App\Models\Truck;
use App\Models\TruckAppliance;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SalesIndexSortTest extends TestCase
{
    use RefreshDatabase;

    public function test_sales_index_can_sort_by_model_without_ambiguous_status(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $user = User::factory()->admin()->active()->create();
        $user->syncRoles(['admin']);

        $truck = Truck::query()->create([
            'name' => 'Sales Truck',
            'units_on_truck' => 1,
            'cost_of_truck' => 500,
            'shipping_cost' => 0,
            'arrival_date' => now()->toDateString(),
            'status' => 'active',
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);
        $category = Category::query()->create([
            'name' => 'Washers',
            'type' => ItemType::Appliance,
            'status' => 1,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);
        $model = CatalogModel::query()->create([
            'model_number' => 'WFW5620HW',
            'product_name' => 'Front Load Washer',
            'status' => 1,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);
        TruckAppliance::query()->create([
            'truck_id' => $truck->id,
            'category_id' => $category->id,
            'model_id' => $model->id,
            'serial_number' => 'SORT-MODEL-1',
            'status' => 'Ready',
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        $response = $this->actingAs($user)->get(route('admin.sales.index', [
            'sort' => 'model',
            'direction' => 'asc',
        ]));

        $response->assertOk();
        $response->assertSee('SORT-MODEL-1');
        $response->assertSee('WFW5620HW');
    }
}
