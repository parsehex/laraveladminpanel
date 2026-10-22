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
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class FurnitureModelTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Category $tables;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);

        $this->user = User::factory()->admin()->active()->create();
        $this->user->syncRoles(['admin']);

        $this->tables = $this->category('Tables', ItemType::Furniture);
    }

    private function category(string $name, ItemType $type): Category
    {
        return Category::query()->create([
            'name' => $name,
            'type' => $type,
            'status' => 1,
            'created_by' => $this->user->id,
            'updated_by' => $this->user->id,
        ]);
    }

    private function truck(string $name): Truck
    {
        return Truck::query()->create([
            'name' => $name,
            'units_on_truck' => 0,
            'cost_of_truck' => 1000,
            'shipping_cost' => 0,
            'arrival_date' => now()->toDateString(),
            'status' => 'active',
            'created_by' => $this->user->id,
            'updated_by' => $this->user->id,
        ]);
    }

    private function catalogModel(string $modelNumber, string $productName, ?Category $category = null): CatalogModel
    {
        return CatalogModel::query()->create([
            'model_number' => $modelNumber,
            'product_name' => $productName,
            'category_id' => ($category ?? $this->tables)->id,
            'brand' => 'Moog',
            'msrp' => 199,
            'status' => 1,
            'created_by' => $this->user->id,
            'updated_by' => $this->user->id,
        ]);
    }

    private function unit(Truck $truck, ?CatalogModel $model, string $unitLabel, string $status = 'Testing', ?Category $category = null): TruckAppliance
    {
        return TruckAppliance::query()->create([
            'truck_id' => $truck->id,
            'category_id' => ($category ?? $this->tables)->id,
            'model_id' => $model?->id,
            'unit_label' => $unitLabel,
            'product_name' => $model?->product_name ?? 'Loose item',
            'status' => $status,
            'created_by' => $this->user->id,
            'updated_by' => $this->user->id,
        ]);
    }

    public function test_furniture_list_groups_units_by_model_by_default(): void
    {
        $truck = $this->truck('WF-010');
        $coffeeTable = $this->catalogModel('OVL-35', 'Oval Coffee Table');
        $deckBox = $this->catalogModel('KETER-135', 'Resin Deck Box');
        $this->unit($truck, $coffeeTable, 'WF-010-001');
        $this->unit($truck, $coffeeTable, 'WF-010-002');
        $this->unit($truck, $coffeeTable, 'WF-010-003', 'Ready');
        $this->unit($truck, $deckBox, 'WF-010-004');

        $response = $this->actingAs($this->user)->get(route('admin.inventory.furniture'));

        $response->assertOk();
        $response->assertSee('Oval Coffee Table');
        $response->assertSee('Resin Deck Box');
        $response->assertSee(route('admin.inventory.furniture.show', $coffeeTable));
        $response->assertSee('Testing (2)');
        $response->assertSee('Ready (1)');
        $response->assertSee('WF-010 <span class="text-gray-500">(3)</span>', false);
        $response->assertDontSee('WF-010-001');
    }

    public function test_grouped_list_excludes_appliances_and_units_on_deleted_trucks(): void
    {
        $liveTruck = $this->truck('Live Truck');
        $deletedTruck = $this->truck('Deleted Truck');
        $sofa = $this->catalogModel('SOFA-1', 'Live Sofa');
        $orphan = $this->catalogModel('ORPHAN-1', 'Orphaned Chair');
        $washers = $this->category('Washers', ItemType::Appliance);
        $washer = $this->catalogModel('WASH-1', 'Front Load Washer', $washers);
        $this->unit($liveTruck, $sofa, 'LIVE-001');
        $this->unit($deletedTruck, $sofa, 'GONE-001');
        $this->unit($deletedTruck, $orphan, 'GONE-002');
        $this->unit($liveTruck, $washer, 'WASH-001', 'Ready', $washers);
        $deletedTruck->delete();

        $response = $this->actingAs($this->user)->get(route('admin.inventory.furniture'));

        $response->assertOk();
        $response->assertSee('Live Sofa');
        $response->assertSee('Testing (1)');
        $response->assertDontSee('Deleted Truck');
        $response->assertDontSee('Orphaned Chair');
        $response->assertDontSee('Front Load Washer');
    }

    public function test_status_filter_limits_models_but_keeps_their_full_counts(): void
    {
        $truck = $this->truck('WF-011');
        $coffeeTable = $this->catalogModel('OVL-35', 'Oval Coffee Table');
        $deckBox = $this->catalogModel('KETER-135', 'Resin Deck Box');
        $this->unit($truck, $coffeeTable, 'WF-011-001');
        $this->unit($truck, $coffeeTable, 'WF-011-002', 'Ready');
        $this->unit($truck, $deckBox, 'WF-011-003');

        $response = $this->actingAs($this->user)->get(route('admin.inventory.furniture', ['status' => ['Ready']]));

        $response->assertOk();
        $response->assertSee('Oval Coffee Table');
        $response->assertSee('Testing (1)');
        $response->assertSee('Ready (1)');
        $response->assertDontSee('Resin Deck Box');
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function groupedSortColumns(): array
    {
        return collect(['product', 'model', 'brand', 'category', 'units', 'last_received'])
            ->mapWithKeys(fn (string $column) => [$column => [$column]])
            ->all();
    }

    #[DataProvider('groupedSortColumns')]
    public function test_grouped_list_can_be_sorted_and_paginated(string $column): void
    {
        $truck = $this->truck('WF-021');
        $coffeeTable = $this->catalogModel('OVL-35', 'Oval Coffee Table');
        $deckBox = $this->catalogModel('KETER-135', 'Resin Deck Box');
        $this->unit($truck, $coffeeTable, 'WF-021-001');
        $this->unit($truck, $coffeeTable, 'WF-021-002');
        $this->unit($truck, $deckBox, 'WF-021-003');
        $this->unit($truck, null, 'WF-021-004');

        $response = $this->actingAs($this->user)->get(route('admin.inventory.furniture', [
            'sort' => $column,
            'direction' => 'asc',
            'limit' => 25,
        ]));

        $response->assertOk();
        $response->assertSeeInOrder(match ($column) {
            'units' => ['Resin Deck Box', 'Oval Coffee Table'],
            'product' => ['No model assigned', 'Oval Coffee Table', 'Resin Deck Box'],
            'model' => ['Resin Deck Box', 'Oval Coffee Table', 'No model assigned'],
            default => ['Oval Coffee Table'],
        });
    }

    public function test_grouped_list_shows_every_model_when_page_size_is_all(): void
    {
        $truck = $this->truck('WF-022');
        $coffeeTable = $this->catalogModel('OVL-35', 'Oval Coffee Table');
        $this->unit($truck, $coffeeTable, 'WF-022-001');
        $this->unit($truck, $this->catalogModel('KETER-135', 'Resin Deck Box'), 'WF-022-002');
        $this->unit($truck, $this->catalogModel('GUS-1053', 'Picture Rail'), 'WF-022-003');

        $response = $this->actingAs($this->user)->get(route('admin.inventory.furniture', ['limit' => 'all']));

        $response->assertOk();
        $response->assertSee('Oval Coffee Table');
        $response->assertSee('Resin Deck Box');
        $response->assertSee('Picture Rail');
    }

    public function test_units_without_a_model_share_one_group(): void
    {
        $truck = $this->truck('WF-012');
        $this->unit($truck, null, 'WF-012-001');
        $this->unit($truck, null, 'WF-012-002', 'Ready');

        $response = $this->actingAs($this->user)->get(route('admin.inventory.furniture'));

        $response->assertOk();
        $response->assertSee('No model assigned');
        $response->assertSee('Testing (1)');
        $response->assertSee('Ready (1)');
    }

    public function test_furniture_list_remembers_the_chosen_view(): void
    {
        $truck = $this->truck('WF-013');
        $this->unit($truck, $this->catalogModel('OVL-35', 'Oval Coffee Table'), 'WF-013-001');

        $this->actingAs($this->user)
            ->get(route('admin.inventory.furniture', ['view' => 'units']))
            ->assertOk()
            ->assertSee('WF-013-001');

        $this->actingAs($this->user)
            ->get(route('admin.inventory.furniture'))
            ->assertOk()
            ->assertSee('WF-013-001')
            ->assertSee('Print selected stickers');

        $this->actingAs($this->user)
            ->get(route('admin.inventory.furniture', ['view' => 'models']))
            ->assertOk()
            ->assertDontSee('WF-013-001');
    }

    public function test_model_page_shows_units_and_truck_breakdown(): void
    {
        $firstTruck = $this->truck('WF-014');
        $secondTruck = $this->truck('WF-015');
        $coffeeTable = $this->catalogModel('OVL-35', 'Oval Coffee Table');
        $otherModel = $this->catalogModel('KETER-135', 'Resin Deck Box');
        $this->unit($firstTruck, $coffeeTable, 'WF-014-001');
        $this->unit($firstTruck, $coffeeTable, 'WF-014-002', 'Ready');
        $this->unit($secondTruck, $coffeeTable, 'WF-015-001');
        $this->unit($secondTruck, $otherModel, 'WF-015-002');

        $response = $this->actingAs($this->user)->get(route('admin.inventory.furniture.show', $coffeeTable));

        $response->assertOk();
        $response->assertSee('Oval Coffee Table');
        $response->assertSee('OVL-35');
        $response->assertSee('$199.00');
        $response->assertSee('Testing (2)');
        $response->assertSee('Ready (1)');
        $response->assertSee('WF-014');
        $response->assertSee('WF-015');
        $response->assertSee('WF-014-001');
        $response->assertSee('WF-014-002');
        $response->assertSee('WF-015-001');
        $response->assertDontSee('WF-015-002');
    }

    public function test_model_page_status_filter_narrows_the_units_table(): void
    {
        $truck = $this->truck('WF-016');
        $coffeeTable = $this->catalogModel('OVL-35', 'Oval Coffee Table');
        $this->unit($truck, $coffeeTable, 'WF-016-001');
        $this->unit($truck, $coffeeTable, 'WF-016-002', 'Ready');

        $response = $this->actingAs($this->user)->get(route('admin.inventory.furniture.show', [
            'model' => $coffeeTable,
            'status' => ['Ready'],
        ]));

        $response->assertOk();
        $response->assertSee('WF-016-002');
        $response->assertDontSee('WF-016-001');
        $response->assertSee('Testing (1)');
    }

    public function test_model_page_returns_404_without_live_furniture_units(): void
    {
        $washers = $this->category('Washers', ItemType::Appliance);
        $washer = $this->catalogModel('WASH-1', 'Front Load Washer', $washers);
        $this->unit($this->truck('WF-017'), $washer, 'WASH-001', 'Ready', $washers);
        $unused = $this->catalogModel('UNUSED-1', 'Unused Table');

        $this->actingAs($this->user)
            ->get(route('admin.inventory.furniture.show', $washer))
            ->assertNotFound();

        $this->actingAs($this->user)
            ->get(route('admin.inventory.furniture.show', $unused))
            ->assertNotFound();
    }

    public function test_model_page_requires_inventory_view_permission(): void
    {
        $coffeeTable = $this->catalogModel('OVL-35', 'Oval Coffee Table');
        $this->unit($this->truck('WF-018'), $coffeeTable, 'WF-018-001');
        $userWithoutAccess = User::factory()->user()->active()->create();

        $this->get(route('admin.inventory.furniture.show', $coffeeTable))
            ->assertRedirect(route('login'));

        $this->actingAs($userWithoutAccess)
            ->get(route('admin.inventory.furniture.show', $coffeeTable))
            ->assertForbidden();
    }

    public function test_unit_page_links_to_its_model_page(): void
    {
        $truck = $this->truck('WF-019');
        $coffeeTable = $this->catalogModel('OVL-35', 'Oval Coffee Table');
        $unit = $this->unit($truck, $coffeeTable, 'WF-019-001');
        $this->unit($truck, $coffeeTable, 'WF-019-002');

        $response = $this->actingAs($this->user)->get(route('admin.inventory.show', $unit));

        $response->assertOk();
        $response->assertSee(route('admin.inventory.furniture.show', $coffeeTable));
        $response->assertSee('2 units of this model');
    }

    public function test_model_page_keeps_the_furniture_nav_link_active(): void
    {
        $coffeeTable = $this->catalogModel('OVL-35', 'Oval Coffee Table');
        $this->unit($this->truck('WF-020'), $coffeeTable, 'WF-020-001');

        $response = $this->actingAs($this->user)->get(route('admin.inventory.furniture.show', $coffeeTable));

        $response->assertOk();
        $this->assertMatchesRegularExpression(
            '/href="'.preg_quote(route('admin.inventory.furniture'), '/').'"[^>]*\bis-active\b/',
            $response->getContent(),
        );
    }
}
