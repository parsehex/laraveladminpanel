<?php

namespace Tests\Feature;

use App\Enums\ItemType;
use App\Models\Category;
use App\Models\FurnitureDetail;
use App\Models\Model as CatalogModel;
use App\Models\Truck;
use App\Models\TruckAppliance;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class FurnitureInventoryTest extends TestCase
{
    use RefreshDatabase;

    private function adminUser(): User
    {
        $this->seed(RolePermissionSeeder::class);

        $user = User::factory()->admin()->active()->create();
        $user->syncRoles(['admin']);

        return $user;
    }

    private function truck(User $user): Truck
    {
        return Truck::query()->create([
            'name' => 'Furniture Truck',
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

    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public static function applianceWorkflowRoutes(): array
    {
        return [
            'testing' => ['get', 'admin.inventory.testing.show'],
            'repair' => ['get', 'admin.inventory.repair.show'],
            'demanufacture' => ['get', 'admin.inventory.deman.show'],
            'parts' => ['post', 'admin.inventory.parts.store'],
        ];
    }

    public function test_guest_is_redirected_from_the_furniture_inventory_page(): void
    {
        $this->get(route('admin.inventory.furniture'))
            ->assertRedirect(route('login'));
    }

    public function test_inventory_pages_list_only_their_item_type(): void
    {
        $user = $this->adminUser();
        $truck = $this->truck($user);
        $washers = $this->category($user, 'Washers', ItemType::Appliance);
        $sofas = $this->category($user, 'Sofas', ItemType::Furniture);

        TruckAppliance::query()->create([
            'truck_id' => $truck->id,
            'category_id' => $washers->id,
            'serial_number' => 'APP-PAGE-1',
            'status' => 'Ready',
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);
        TruckAppliance::query()->create([
            'truck_id' => $truck->id,
            'category_id' => $sofas->id,
            'serial_number' => 'FUR-PAGE-1',
            'status' => 'Ready',
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);
        TruckAppliance::query()->create([
            'truck_id' => $truck->id,
            'serial_number' => 'UNCAT-PAGE-1',
            'status' => 'Ready',
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        $appliances = $this->actingAs($user)->get(route('admin.inventory.index'));

        $appliances->assertOk();
        $appliances->assertSee('APP-PAGE-1');
        $appliances->assertSee('UNCAT-PAGE-1');
        $appliances->assertDontSee('FUR-PAGE-1');

        $furniture = $this->actingAs($user)->get(route('admin.inventory.furniture', ['view' => 'units']));

        $furniture->assertOk();
        $furniture->assertSee('FUR-PAGE-1');
        $furniture->assertDontSee('APP-PAGE-1');
        $furniture->assertDontSee('UNCAT-PAGE-1');
    }

    public function test_furniture_item_page_hides_appliance_workflows(): void
    {
        $user = $this->adminUser();
        $truck = $this->truck($user);
        $sofas = $this->category($user, 'Sofas', ItemType::Furniture);
        $item = TruckAppliance::query()->create([
            'truck_id' => $truck->id,
            'category_id' => $sofas->id,
            'product_name' => 'Sofa B',
            'status' => 'Testing',
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        $response = $this->actingAs($user)->get(route('admin.inventory.show', $item));

        $response->assertOk();
        $response->assertSee('Furniture Detail');
        $response->assertDontSee('Appliance Detail');
        $response->assertDontSee('Start Testing');
        $response->assertDontSee('Parts Used');
        $this->assertMatchesRegularExpression(
            '/href="'.preg_quote(route('admin.inventory.furniture'), '/').'"[^>]*\bis-active\b/',
            $response->getContent(),
        );
        $this->assertDoesNotMatchRegularExpression(
            '/href="'.preg_quote(route('admin.inventory.index'), '/').'"[^>]*\bis-active\b/',
            $response->getContent(),
        );
    }

    #[DataProvider('applianceWorkflowRoutes')]
    public function test_appliance_workflow_routes_return_404_for_furniture(string $method, string $routeName): void
    {
        $user = $this->adminUser();
        $truck = $this->truck($user);
        $sofas = $this->category($user, 'Sofas', ItemType::Furniture);
        $item = TruckAppliance::query()->create([
            'truck_id' => $truck->id,
            'category_id' => $sofas->id,
            'product_name' => 'Sofa B',
            'status' => 'Ready',
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        $this->actingAs($user)->call($method, route($routeName, $item))
            ->assertNotFound();
    }

    public function test_serial_number_is_required_for_an_appliance_category(): void
    {
        $user = $this->adminUser();
        $truck = $this->truck($user);
        $this->category($user, 'Washers', ItemType::Appliance);

        $response = $this->actingAs($user)->post(route('admin.trucks.appliances.store', $truck), [
            'truck_id' => $truck->id,
            'category' => 'Washers',
            'model_number' => 'WF123',
            'brand' => 'GE',
            'receiving_condition' => 'A-Grade',
        ]);

        $response->assertInvalid(['serial_number' => 'The serial number field is required.']);
    }

    public function test_furniture_can_be_received_without_a_serial_or_model(): void
    {
        $user = $this->adminUser();
        $truck = $this->truck($user);
        $sofas = $this->category($user, 'Sofas', ItemType::Furniture);

        $response = $this->actingAs($user)->post(route('admin.trucks.appliances.store', $truck), [
            'truck_id' => $truck->id,
            'category' => 'Sofas',
            'brand' => 'Ashley',
            'product_name' => 'Sofa B',
            'receiving_condition' => 'B-Grade',
        ]);

        $response->assertRedirect(route('admin.trucks.show', $truck));

        $item = TruckAppliance::query()->where('product_name', 'Sofa B')->first();

        $this->assertNotNull($item);
        $this->assertSame($sofas->id, $item->category_id);
        $this->assertNull($item->serial_number);
        $this->assertNull($item->model_id);
        $this->assertTrue(FurnitureDetail::query()->where('truck_appliance_id', $item->id)->exists());
    }

    public function test_item_category_cannot_change_between_appliances_and_furniture(): void
    {
        $user = $this->adminUser();
        $truck = $this->truck($user);
        $washers = $this->category($user, 'Washers', ItemType::Appliance);
        $this->category($user, 'Sofas', ItemType::Furniture);
        $item = TruckAppliance::query()->create([
            'truck_id' => $truck->id,
            'category_id' => $washers->id,
            'serial_number' => 'APP-MOVE-1',
            'brand' => 'GE',
            'receiving_condition' => 'A-Grade',
            'status' => 'Ready',
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        $response = $this->actingAs($user)->put(route('admin.trucks.appliances.update', [$truck, $item]), [
            'truck_id' => $truck->id,
            'category' => 'Sofas',
            'model_number' => 'WF123',
            'serial_number' => 'APP-MOVE-1',
            'brand' => 'GE',
            'receiving_condition' => 'A-Grade',
        ]);

        $response->assertInvalid(['category' => 'An item cannot move between appliances and furniture.']);
        $this->assertSame($washers->id, $item->fresh()->category_id);
    }

    public function test_sales_page_can_be_filtered_by_item_type(): void
    {
        $user = $this->adminUser();
        $truck = $this->truck($user);
        $washers = $this->category($user, 'Washers', ItemType::Appliance);
        $sofas = $this->category($user, 'Sofas', ItemType::Furniture);

        TruckAppliance::query()->create([
            'truck_id' => $truck->id,
            'category_id' => $washers->id,
            'serial_number' => 'APP-SALE-1',
            'status' => 'Ready',
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);
        TruckAppliance::query()->create([
            'truck_id' => $truck->id,
            'category_id' => $sofas->id,
            'serial_number' => 'FUR-SALE-1',
            'status' => 'Ready',
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        $response = $this->actingAs($user)->get(route('admin.sales.index', [
            'item_type' => ItemType::Furniture->value,
        ]));

        $response->assertSee('FUR-SALE-1');
        $response->assertDontSee('APP-SALE-1');
        $response->assertDontSee('data-col="type"', false);
    }

    public function test_sales_page_shows_the_type_column_when_all_types_are_listed(): void
    {
        $user = $this->adminUser();
        $truck = $this->truck($user);
        $washers = $this->category($user, 'Washers', ItemType::Appliance);
        $sofas = $this->category($user, 'Sofas', ItemType::Furniture);

        TruckAppliance::query()->create([
            'truck_id' => $truck->id,
            'category_id' => $washers->id,
            'serial_number' => 'APP-SALE-1',
            'status' => 'Ready',
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);
        TruckAppliance::query()->create([
            'truck_id' => $truck->id,
            'category_id' => $sofas->id,
            'serial_number' => 'FUR-SALE-1',
            'status' => 'Ready',
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        $response = $this->actingAs($user)->get(route('admin.sales.index', [
            'item_type' => 'all',
        ]));

        $response->assertSee('APP-SALE-1');
        $response->assertSee('FUR-SALE-1');
        $response->assertSee('data-col="type"', false);
    }

    public function test_sales_page_defaults_to_appliances_and_hides_the_type_column(): void
    {
        $user = $this->adminUser();
        $truck = $this->truck($user);
        $washers = $this->category($user, 'Washers', ItemType::Appliance);
        $sofas = $this->category($user, 'Sofas', ItemType::Furniture);

        TruckAppliance::query()->create([
            'truck_id' => $truck->id,
            'category_id' => $washers->id,
            'serial_number' => 'APP-SALE-1',
            'status' => 'Ready',
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);
        TruckAppliance::query()->create([
            'truck_id' => $truck->id,
            'category_id' => $sofas->id,
            'serial_number' => 'FUR-SALE-1',
            'status' => 'Ready',
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        $response = $this->actingAs($user)->get(route('admin.sales.index'));

        $response->assertSee('APP-SALE-1');
        $response->assertDontSee('FUR-SALE-1');
        $response->assertDontSee('data-col="type"', false);
    }

    public function test_normal_sale_can_mark_furniture_sold_by_item_id(): void
    {
        $user = $this->adminUser();
        $truck = $this->truck($user);
        $sofas = $this->category($user, 'Sofas', ItemType::Furniture);
        $item = TruckAppliance::query()->create([
            'truck_id' => $truck->id,
            'category_id' => $sofas->id,
            'product_name' => 'Sofa B',
            'status' => 'Ready',
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        $response = $this->actingAs($user)->post(route('admin.sales.mark-sold'), [
            'sale_type' => 'normal',
            'serial_number' => (string) $item->id,
            'sold_price' => 150,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $this->assertSame('Sold', $item->fresh()->status);
        $this->assertSame('150.00', $item->fresh()->sold_price);
    }

    private function furnitureModel(User $user, Category $category, string $modelNumber, float $msrp = 100): CatalogModel
    {
        return CatalogModel::query()->create([
            'model_number' => $modelNumber,
            'product_name' => 'Model '.$modelNumber,
            'category_id' => $category->id,
            'brand' => 'Chemika',
            'msrp' => $msrp,
            'status' => 1,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function itemForm(Truck $truck, array $overrides = []): array
    {
        return [
            'truck_id' => $truck->id,
            'category' => 'Sofas',
            'brand' => 'Chemika',
            'product_name' => 'Manual Recliner',
            'receiving_condition' => 'A-Grade',
            ...$overrides,
        ];
    }

    public function test_editing_a_furniture_unit_keeps_its_model(): void
    {
        $user = $this->adminUser();
        $truck = $this->truck($user);
        $sofas = $this->category($user, 'Sofas', ItemType::Furniture);
        $model = $this->furnitureModel($user, $sofas, 'REC-40', 749.99);
        $item = TruckAppliance::query()->create([
            'truck_id' => $truck->id,
            'category_id' => $sofas->id,
            'model_id' => $model->id,
            'brand' => 'Chemika',
            'msrp' => 749.99,
            'receiving_condition' => 'A-Grade',
            'status' => 'Ready',
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        $this->actingAs($user)
            ->put(route('admin.trucks.appliances.update', [$truck, $item]), $this->itemForm($truck, [
                'model_number' => 'REC-40',
                'msrp' => 699,
                'item_type' => 'furniture',
            ]))
            ->assertRedirect(route('admin.trucks.show', $truck));

        $this->assertSame($model->id, $item->fresh()->model_id);
        $this->assertSame(1, CatalogModel::query()->count());
    }

    public function test_changing_a_furniture_model_number_moves_the_unit_to_that_model(): void
    {
        $user = $this->adminUser();
        $truck = $this->truck($user);
        $sofas = $this->category($user, 'Sofas', ItemType::Furniture);
        $current = $this->furnitureModel($user, $sofas, 'REC-40');
        $other = $this->furnitureModel($user, $sofas, 'REC-50', 300);
        $item = TruckAppliance::query()->create([
            'truck_id' => $truck->id,
            'category_id' => $sofas->id,
            'model_id' => $current->id,
            'brand' => 'Chemika',
            'receiving_condition' => 'A-Grade',
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        $this->actingAs($user)
            ->put(route('admin.trucks.appliances.update', [$truck, $item]), $this->itemForm($truck, ['model_number' => 'rec-50', 'msrp' => 100]))
            ->assertSessionHasNoErrors();

        $this->assertSame($other->id, $item->fresh()->model_id);
        $this->assertSame(2, CatalogModel::query()->count());
    }

    public function test_adding_furniture_with_a_model_number_reuses_or_creates_a_furniture_model(): void
    {
        $user = $this->adminUser();
        $truck = $this->truck($user);
        $sofas = $this->category($user, 'Sofas', ItemType::Furniture);
        $existing = $this->furnitureModel($user, $sofas, 'REC-40', 749.99);

        $this->actingAs($user)
            ->post(route('admin.trucks.appliances.store', $truck), $this->itemForm($truck, ['model_number' => 'REC-40', 'msrp' => 500]))
            ->assertSessionHasNoErrors();
        $this->actingAs($user)
            ->post(route('admin.trucks.appliances.store', $truck), $this->itemForm($truck, ['model_number' => 'SOFA-9', 'product_name' => 'Hana Sofa', 'msrp' => 900]))
            ->assertSessionHasNoErrors();

        $this->assertSame($existing->id, $truck->appliances()->where('product_name', 'Manual Recliner')->value('model_id'));

        $created = CatalogModel::query()->where('model_number', 'SOFA-9')->sole();
        $this->assertSame($sofas->id, $created->category_id);
        $this->assertSame($created->id, $truck->appliances()->where('product_name', 'Hana Sofa')->value('model_id'));
    }

    public function test_new_categories_from_the_item_form_take_the_chosen_item_type(): void
    {
        $user = $this->adminUser();
        $truck = $this->truck($user);

        $this->actingAs($user)
            ->post(route('admin.trucks.appliances.store', $truck), $this->itemForm($truck, ['category' => 'Recliners', 'item_type' => 'furniture']))
            ->assertSessionHasNoErrors();

        $recliners = Category::query()->where('name', 'Recliners')->sole();
        $this->assertSame(ItemType::Furniture, $recliners->type);
        $this->assertTrue($truck->appliances()->sole()->isFurniture());
    }

    public function test_adding_an_item_rejects_a_category_of_the_other_type(): void
    {
        $user = $this->adminUser();
        $truck = $this->truck($user);
        $this->category($user, 'Washers', ItemType::Appliance);

        $this->actingAs($user)
            ->post(route('admin.trucks.appliances.store', $truck), $this->itemForm($truck, ['category' => 'Washers', 'item_type' => 'furniture']))
            ->assertInvalid(['category' => '“Washers” is an appliance category. Switch the item type or choose another category.']);

        $this->assertSame(0, TruckAppliance::query()->count());
    }

    public function test_item_form_type_toggle_defaults_to_the_trucks_items_and_locks_on_edit(): void
    {
        $user = $this->adminUser();
        $truck = $this->truck($user);
        $sofas = $this->category($user, 'Sofas', ItemType::Furniture);
        TruckAppliance::query()->create([
            'truck_id' => $truck->id,
            'category_id' => $sofas->id,
            'unit_label' => 'Furniture Truck-001',
            'brand' => 'Chemika',
            'receiving_condition' => 'A-Grade',
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        $response = $this->actingAs($user)->get(route('admin.trucks.show', $truck));

        $response->assertOk();
        $response->assertSee('name="item_type" value="furniture"', false);
        $response->assertDontSee('name="item_type" value="appliance"', false);
        $response->assertSee('Furniture item — shows furniture categories');
        $response->assertSee('Furniture item — the type can’t change after it’s added');
        $response->assertSee('(optional — groups identical pieces)');

        $emptyTruck = Truck::query()->create([
            'name' => 'Appliance Truck',
            'units_on_truck' => 0,
            'cost_of_truck' => 0,
            'shipping_cost' => 0,
            'arrival_date' => now()->toDateString(),
            'status' => 'active',
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        $this->actingAs($user)
            ->get(route('admin.trucks.show', $emptyTruck))
            ->assertOk()
            ->assertSee('name="item_type" value="appliance"', false);
    }

    public function test_model_dropdown_can_be_limited_to_furniture_models(): void
    {
        $user = $this->adminUser();
        $sofas = $this->category($user, 'Sofas', ItemType::Furniture);
        $washers = $this->category($user, 'Washers', ItemType::Appliance);
        $this->furnitureModel($user, $sofas, 'REC-40');
        $this->furnitureModel($user, $washers, 'WASH-1');

        $furniture = $this->actingAs($user)->getJson(route('admin.dropdowns.models', ['type' => 'furniture']));

        $furniture->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', 'REC-40')
            ->assertJsonPath('data.0.text', 'REC-40 — Model REC-40');

        $this->actingAs($user)
            ->getJson(route('admin.dropdowns.models'))
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.text', 'REC-40');
    }
}
