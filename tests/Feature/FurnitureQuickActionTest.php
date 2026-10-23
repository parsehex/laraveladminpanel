<?php

namespace Tests\Feature;

use App\Enums\ItemType;
use App\Models\Category;
use App\Models\Model as CatalogModel;
use App\Models\Truck;
use App\Models\TruckAppliance;
use App\Models\User;
use App\Models\UserAction;
use Database\Seeders\InventoryStatusSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FurnitureQuickActionTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Truck $truck;

    private Category $tables;

    private CatalogModel $coffeeTable;

    private int $unitNumber = 0;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
        $this->seed(InventoryStatusSeeder::class);

        $this->user = User::factory()->admin()->active()->create();
        $this->user->syncRoles(['admin']);

        $this->truck = Truck::query()->create([
            'name' => 'WF-030',
            'units_on_truck' => 0,
            'cost_of_truck' => 1000,
            'shipping_cost' => 0,
            'arrival_date' => now()->toDateString(),
            'status' => 'active',
            'created_by' => $this->user->id,
            'updated_by' => $this->user->id,
        ]);

        $this->tables = Category::query()->create([
            'name' => 'Tables',
            'type' => ItemType::Furniture,
            'status' => 1,
            'created_by' => $this->user->id,
            'updated_by' => $this->user->id,
        ]);

        $this->coffeeTable = $this->catalogModel('OVL-35', 'Oval Coffee Table');
    }

    private function catalogModel(string $modelNumber, string $productName): CatalogModel
    {
        return CatalogModel::query()->create([
            'model_number' => $modelNumber,
            'product_name' => $productName,
            'category_id' => $this->tables->id,
            'brand' => 'Moog',
            'msrp' => 199,
            'status' => 1,
            'created_by' => $this->user->id,
            'updated_by' => $this->user->id,
        ]);
    }

    /**
     * Units are created a day apart so "oldest received first" is deterministic.
     */
    private function unit(string $status, ?CatalogModel $model = null): TruckAppliance
    {
        $this->unitNumber++;

        $unit = TruckAppliance::query()->create([
            'truck_id' => $this->truck->id,
            'category_id' => $this->tables->id,
            'model_id' => ($model ?? $this->coffeeTable)->id,
            'unit_label' => sprintf('WF-030-%03d', $this->unitNumber),
            'status' => $status,
            'created_by' => $this->user->id,
            'updated_by' => $this->user->id,
        ]);
        $unit->forceFill(['created_at' => now()->subDays(100 - $this->unitNumber)])->save();

        return $unit;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function act(array $data, ?CatalogModel $model = null, ?User $user = null)
    {
        $model ??= $this->coffeeTable;

        return $this->actingAs($user ?? $this->user)
            ->from(route('admin.inventory.furniture.show', $model))
            ->post(route('admin.inventory.furniture.actions', $model), $data);
    }

    public function test_selling_picks_the_oldest_eligible_units_at_the_price_each(): void
    {
        $testing = $this->unit('Testing');
        $oldestReady = $this->unit('Ready');
        $showRoom = $this->unit('Show Room');
        $newestReady = $this->unit('Ready');

        $response = $this->act(['action' => 'sell', 'count' => 2, 'sold_price' => '120.50']);

        $response->assertRedirect(route('admin.inventory.furniture.show', $this->coffeeTable));
        $response->assertSessionHas('success', 'Sold 2 units: WF-030-002, WF-030-003.');

        foreach ([$oldestReady, $showRoom] as $sold) {
            $sold->refresh();
            $this->assertSame('Sold', $sold->status);
            $this->assertSame('120.50', $sold->sold_price);
            $this->assertSame($this->user->name, $sold->sold_by);
            $this->assertNotNull($sold->sold_at);
            $this->assertDatabaseHas('inventory_status_histories', [
                'truck_appliance_id' => $sold->id,
                'status' => 'Sold',
                'notes' => 'Quick action from model page',
            ]);
        }

        $this->assertSame('Testing', $testing->refresh()->status);
        $this->assertSame('Ready', $newestReady->refresh()->status);
        $this->assertSame(2, UserAction::query()->where('action_type', 'showroom_sold')->count());

        $log = UserAction::query()->where('action_type', 'furniture_quick_action')->sole();
        $this->assertSame($this->coffeeTable->id, $log->extra['model_id']);
        $this->assertSame('sell', $log->extra['action']);
        $this->assertSame(2, $log->extra['count']);
        $this->assertSame('120.50', $log->extra['sold_price_each']);
        $this->assertSame([$oldestReady->id, $showRoom->id], $log->extra['unit_ids']);
        $this->assertFalse($log->extra['override']);
    }

    public function test_selling_more_than_are_eligible_requires_lowering_the_count_or_an_override(): void
    {
        $this->unit('Ready');
        $testing = $this->unit('Testing');

        $response = $this->act(['action' => 'sell', 'count' => 2, 'sold_price' => 100]);

        $response->assertSessionHasErrors(['count' => 'Only 1 units are eligible (units in Ready or Show Room). Lower the count or confirm the override.']);
        $this->assertSame(0, TruckAppliance::query()->where('status', 'Sold')->count());
        $this->assertSame('Testing', $testing->refresh()->status);
        $this->assertSame(0, UserAction::query()->where('action_type', 'furniture_quick_action')->count());
    }

    public function test_override_uses_eligible_units_first_and_never_scrap_or_sold(): void
    {
        $scrap = $this->unit('Scrap');
        $testing = $this->unit('Testing');
        $this->unit('Sold');
        $ready = $this->unit('Ready');

        $response = $this->act(['action' => 'sell', 'count' => 2, 'sold_price' => 80, 'override' => 1]);

        $response->assertSessionHas('success', 'Sold 2 units: WF-030-004, WF-030-002. 1 used the status override.');
        $this->assertSame('Sold', $ready->refresh()->status);
        $this->assertSame('Sold', $testing->refresh()->status);
        $this->assertSame('Scrap', $scrap->refresh()->status);
        $this->assertDatabaseHas('inventory_status_histories', [
            'truck_appliance_id' => $testing->id,
            'notes' => 'Quick action from model page · status rules overridden',
        ]);

        $log = UserAction::query()->where('action_type', 'furniture_quick_action')->sole();
        $this->assertTrue($log->extra['override']);
        $this->assertSame([$testing->id], $log->extra['overridden_unit_ids']);
    }

    public function test_override_still_needs_enough_units_outside_scrap_and_sold(): void
    {
        $this->unit('Scrap');
        $this->unit('Testing');

        $this->act(['action' => 'sell', 'count' => 2, 'sold_price' => 80, 'override' => 1])
            ->assertSessionHasErrors(['count' => 'Only 1 units of this model can take this action (units not Sold or Scrap). Lower the count.']);

        $this->assertSame(0, TruckAppliance::query()->where('status', 'Sold')->count());
    }

    public function test_checked_units_are_used_first_and_the_rest_are_picked_by_the_rules(): void
    {
        $oldestReady = $this->unit('Ready');
        $nextReady = $this->unit('Ready');
        $checked = $this->unit('Show Room');

        $this->act(['action' => 'sell', 'count' => 2, 'sold_price' => 50, 'unit_ids' => [$checked->id]])
            ->assertSessionHas('success', 'Sold 2 units: WF-030-003, WF-030-001.');

        $this->assertSame('Sold', $checked->refresh()->status);
        $this->assertSame('Sold', $oldestReady->refresh()->status);
        $this->assertSame('Ready', $nextReady->refresh()->status);
    }

    public function test_checked_units_that_are_not_eligible_need_the_override(): void
    {
        $this->unit('Ready');
        $testing = $this->unit('Testing');

        $this->act(['action' => 'sell', 'count' => 1, 'sold_price' => 50, 'unit_ids' => [$testing->id]])
            ->assertSessionHasErrors(['unit_ids' => 'Checked units WF-030-002 aren’t eligible (needs units in Ready or Show Room). Uncheck them or confirm the override.']);
        $this->assertSame('Testing', $testing->refresh()->status);

        $this->act(['action' => 'sell', 'count' => 1, 'sold_price' => 50, 'unit_ids' => [$testing->id], 'override' => 1])
            ->assertSessionHasNoErrors();
        $this->assertSame('Sold', $testing->refresh()->status);
    }

    public function test_count_cannot_be_lower_than_the_checked_units(): void
    {
        $first = $this->unit('Ready');
        $second = $this->unit('Ready');

        $this->act(['action' => 'sell', 'count' => 1, 'sold_price' => 50, 'unit_ids' => [$first->id, $second->id]])
            ->assertSessionHasErrors(['count' => 'The count can’t be lower than the 2 checked units.']);
    }

    public function test_checked_units_from_another_model_are_rejected(): void
    {
        $this->unit('Ready');
        $otherModelUnit = $this->unit('Ready', $this->catalogModel('KETER-135', 'Resin Deck Box'));

        $this->act(['action' => 'sell', 'count' => 1, 'sold_price' => 50, 'unit_ids' => [$otherModelUnit->id]])
            ->assertSessionHasErrors('unit_ids');

        $this->assertSame('Ready', $otherModelUnit->refresh()->status);
    }

    public function test_selling_requires_a_price(): void
    {
        $this->unit('Ready');

        $this->act(['action' => 'sell', 'count' => 1])
            ->assertSessionHasErrors(['sold_price' => 'Enter the sale price for one unit.']);
    }

    public function test_moving_to_show_room_only_takes_ready_units(): void
    {
        $testing = $this->unit('Testing');
        $ready = $this->unit('Ready');

        $this->act(['action' => 'status', 'status' => 'Show Room', 'count' => 1])
            ->assertSessionHas('success', 'Updated status on 1 unit: WF-030-002.');

        $this->assertSame('Show Room', $ready->refresh()->status);
        $this->assertSame('Testing', $testing->refresh()->status);
        $this->assertSame(1, UserAction::query()->where('action_type', 'showroom_sent')->where('item_id', $ready->id)->count());

        $this->act(['action' => 'status', 'status' => 'Show Room', 'count' => 1])
            ->assertSessionHasErrors('count');
    }

    public function test_marking_ready_moves_units_out_of_testing(): void
    {
        $first = $this->unit('Testing');
        $second = $this->unit('Testing');
        $scrap = $this->unit('Scrap');

        $this->act(['action' => 'status', 'status' => 'Ready', 'count' => 2])
            ->assertSessionHasNoErrors();

        $this->assertSame('Ready', $first->refresh()->status);
        $this->assertSame('Ready', $second->refresh()->status);
        $this->assertSame('Scrap', $scrap->refresh()->status);
    }

    public function test_status_changes_cannot_target_sold(): void
    {
        $this->unit('Ready');

        $this->act(['action' => 'status', 'status' => 'Sold', 'count' => 1])
            ->assertSessionHasErrors('status');
    }

    public function test_marking_assembled_skips_units_that_are_already_assembled(): void
    {
        $assembled = $this->unit('Ready');
        $assembled->markAssembled($this->user);
        $assembledAt = $assembled->furnitureDetails()->value('assembled_at');
        $unassembled = $this->unit('Testing');

        $this->act(['action' => 'assemble', 'count' => 1])
            ->assertSessionHas('success', 'Marked assembled 1 unit: WF-030-002.');

        $this->assertTrue($unassembled->refresh()->furnitureDetails->isAssembled());
        $this->assertSame($this->user->id, $unassembled->furnitureDetails->assembled_by);
        $this->assertEquals($assembledAt, $assembled->furnitureDetails()->value('assembled_at'));

        $this->act(['action' => 'assemble', 'count' => 1])->assertSessionHasErrors('count');
    }

    public function test_setting_location_skips_sold_units(): void
    {
        $sold = $this->unit('Sold');
        $ready = $this->unit('Ready');

        $this->act(['action' => 'location', 'count' => 1, 'location' => 'Back Lot'])
            ->assertSessionHasNoErrors();

        $this->assertSame('Back Lot', $ready->refresh()->location);
        $this->assertNotSame('Back Lot', $sold->refresh()->location);

        $log = UserAction::query()->where('action_type', 'furniture_quick_action')->sole();
        $this->assertSame('Back Lot', $log->extra['location']);
    }

    public function test_quick_actions_require_edit_permission(): void
    {
        $ready = $this->unit('Ready');
        $viewer = User::factory()->user()->active()->create();
        $viewer->syncRoles(['user']);
        $viewer->givePermissionTo('inventory.view');

        $this->act(['action' => 'sell', 'count' => 1, 'sold_price' => 50], user: $viewer)
            ->assertForbidden();
        $this->assertSame('Ready', $ready->refresh()->status);

        $this->actingAs($viewer)
            ->get(route('admin.inventory.furniture.show', $this->coffeeTable))
            ->assertOk()
            ->assertDontSee(route('admin.inventory.furniture.actions', $this->coffeeTable));
    }

    public function test_model_page_shows_the_quick_action_panel_with_availability(): void
    {
        $this->unit('Ready');
        $this->unit('Testing');

        $response = $this->actingAs($this->user)->get(route('admin.inventory.furniture.show', $this->coffeeTable));

        $response->assertOk();
        $response->assertSee(route('admin.inventory.furniture.actions', $this->coffeeTable));
        $response->assertSee('Sale price per unit');
        $response->assertViewHas('actionAvailability', function (array $availability): bool {
            return $availability['sell']['eligible'] === 1
                && $availability['sell']['override'] === 2
                && $availability['status:Ready']['eligible'] === 1
                && $availability['status:Ready']['override'] === 1
                && $availability['assemble']['eligible'] === 2;
        });
    }

    public function test_furniture_can_use_any_status_from_the_unit_page(): void
    {
        $unit = $this->unit('Ready');

        $this->actingAs($this->user)
            ->patch(route('admin.inventory.status.update', $unit), ['status' => 'Testing'])
            ->assertSessionHasNoErrors();

        $this->assertSame('Testing', $unit->refresh()->status);
    }
}
