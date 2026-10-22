<?php

namespace Tests\Feature;

use App\Enums\ItemType;
use App\Enums\TimeActivity;
use App\Models\Category;
use App\Models\FurnitureDetail;
use App\Models\TimeEntry;
use App\Models\Truck;
use App\Models\TruckAppliance;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FurnitureAssemblyTest extends TestCase
{
    use RefreshDatabase;

    private function adminUser(): User
    {
        $this->seed(RolePermissionSeeder::class);

        $user = User::factory()->admin()->active()->create();
        $user->syncRoles(['admin']);

        return $user;
    }

    private function viewer(): User
    {
        $this->seed(RolePermissionSeeder::class);

        $user = User::factory()->user()->active()->create();
        $user->syncRoles(['user']);
        $user->givePermissionTo('inventory.view');

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

    private function item(User $user, Truck $truck, Category $category, string $serial): TruckAppliance
    {
        return TruckAppliance::query()->create([
            'truck_id' => $truck->id,
            'category_id' => $category->id,
            'serial_number' => $serial,
            'status' => 'Ready',
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);
    }

    public function test_guest_is_redirected_from_clock_in(): void
    {
        $user = $this->adminUser();
        $item = $this->item($user, $this->truck($user), $this->category($user, 'Sofas', ItemType::Furniture), 'FUR-GUEST');

        $this->post(route('admin.inventory.assembly.clock-in', $item))
            ->assertRedirect(route('login'));
    }

    public function test_user_without_edit_permission_cannot_clock_in(): void
    {
        $viewer = $this->viewer();
        $item = $this->item($viewer, $this->truck($viewer), $this->category($viewer, 'Sofas', ItemType::Furniture), 'FUR-VIEW');

        $this->actingAs($viewer)
            ->post(route('admin.inventory.assembly.clock-in', $item))
            ->assertForbidden();
    }

    public function test_clock_in_and_out_record_the_signed_in_user(): void
    {
        $user = $this->adminUser();
        $item = $this->item($user, $this->truck($user), $this->category($user, 'Sofas', ItemType::Furniture), 'FUR-CLOCK');
        $this->travelTo('2026-10-11 12:00:00');

        $this->actingAs($user)
            ->post(route('admin.inventory.assembly.clock-in', $item))
            ->assertRedirect()
            ->assertSessionHas('success');

        $entry = TimeEntry::query()->sole();
        $this->assertSame($item->id, $entry->trackable_id);
        $this->assertSame(TruckAppliance::class, $entry->trackable_type);
        $this->assertSame(TimeActivity::Assembly, $entry->activity);
        $this->assertSame($user->id, $entry->user_id);
        $this->assertSame($user->name, $entry->user_name);
        $this->assertTrue($entry->started_at->equalTo(now()));
        $this->assertNull($entry->ended_at);

        $this->travelTo('2026-10-11 13:30:00');

        $this->actingAs($user)
            ->post(route('admin.inventory.assembly.clock-out', $item))
            ->assertRedirect()
            ->assertSessionHas('success');

        $entry->refresh();
        $this->assertTrue($entry->ended_at->equalTo(now()));
        $this->assertSame(5400, $entry->durationSeconds());
    }

    public function test_clock_out_without_an_open_session_leaves_other_users_clocked_in(): void
    {
        $user = $this->adminUser();
        $other = User::factory()->admin()->active()->create();
        $other->syncRoles(['admin']);
        $item = $this->item($user, $this->truck($user), $this->category($user, 'Sofas', ItemType::Furniture), 'FUR-OPEN');

        $this->actingAs($other)->post(route('admin.inventory.assembly.clock-in', $item));

        $this->actingAs($user)
            ->from(route('admin.inventory.show', $item))
            ->post(route('admin.inventory.assembly.clock-out', $item))
            ->assertRedirect(route('admin.inventory.show', $item))
            ->assertSessionHas('error');

        $open = TimeEntry::query()->open()->get();
        $this->assertCount(1, $open);
        $this->assertSame($other->id, $open->first()->user_id);
        $this->assertNull($open->first()->ended_at);
    }

    public function test_two_people_can_be_clocked_into_the_same_piece(): void
    {
        $user = $this->adminUser();
        $other = User::factory()->admin()->active()->create();
        $other->syncRoles(['admin']);
        $item = $this->item($user, $this->truck($user), $this->category($user, 'Sofas', ItemType::Furniture), 'FUR-TWO');

        $this->actingAs($user)->post(route('admin.inventory.assembly.clock-in', $item));
        $this->actingAs($other)->post(route('admin.inventory.assembly.clock-in', $item));
        $this->actingAs($user)->post(route('admin.inventory.assembly.clock-out', $item));

        $this->assertSame(1, TimeEntry::query()->whereNotNull('ended_at')->where('user_id', $user->id)->count());
        $this->assertSame(1, TimeEntry::query()->open()->where('user_id', $other->id)->count());
    }

    public function test_appliance_assembly_routes_return_404(): void
    {
        $user = $this->adminUser();
        $item = $this->item($user, $this->truck($user), $this->category($user, 'Washers', ItemType::Appliance), 'APP-404');

        $routes = [
            ['post', 'admin.inventory.assembly.clock-in'],
            ['post', 'admin.inventory.assembly.clock-out'],
            ['post', 'admin.inventory.assembly.assembled.store'],
            ['delete', 'admin.inventory.assembly.assembled.destroy'],
        ];

        foreach ($routes as [$method, $name]) {
            $this->actingAs($user)->{$method}(route($name, $item))->assertNotFound();
        }

        $this->assertSame(0, TimeEntry::query()->count());
        $this->assertNull($item->furnitureDetails);
    }

    public function test_marking_assembled_does_not_change_time_entries(): void
    {
        $user = $this->adminUser();
        $item = $this->item($user, $this->truck($user), $this->category($user, 'Sofas', ItemType::Furniture), 'FUR-FLAG');

        $this->actingAs($user)->post(route('admin.inventory.assembly.clock-in', $item));

        $this->actingAs($user)
            ->post(route('admin.inventory.assembly.assembled.store', $item))
            ->assertRedirect()
            ->assertSessionHas('success');

        $detail = $item->furnitureDetails()->first();
        $this->assertNotNull($detail?->assembled_at);
        $this->assertSame($user->id, $detail->assembled_by);
        $this->assertSame($user->name, $detail->assembled_by_name);
        $this->assertNull(TimeEntry::query()->sole()->ended_at);

        $this->actingAs($user)
            ->delete(route('admin.inventory.assembly.assembled.destroy', $item))
            ->assertRedirect();

        $this->assertNull($item->furnitureDetails()->first()?->assembled_at);
        $this->assertNull(TimeEntry::query()->sole()->ended_at);
    }

    public function test_furniture_list_shows_assembled_state_and_category_averages(): void
    {
        $user = $this->adminUser();
        $truck = $this->truck($user);
        $sofas = $this->category($user, 'Sofas', ItemType::Furniture);
        $chairs = $this->category($user, 'Chairs', ItemType::Furniture);
        $washers = $this->category($user, 'Washers', ItemType::Appliance);
        $shortSofa = $this->item($user, $truck, $sofas, 'SOFA-SHORT');
        $longSofa = $this->item($user, $truck, $sofas, 'SOFA-LONG');
        $openOnly = $this->item($user, $truck, $sofas, 'SOFA-OPEN');
        $chair = $this->item($user, $truck, $chairs, 'CHAIR-1');
        $washer = $this->item($user, $truck, $washers, 'WASHER-1');

        FurnitureDetail::query()->create([
            'truck_appliance_id' => $shortSofa->id,
            'assembled_at' => now(),
            'assembled_by' => $user->id,
            'assembled_by_name' => $user->name,
        ]);

        $this->completedSession($shortSofa, $user, 3600);
        $this->completedSession($longSofa, $user, 7200);
        $this->completedSession($chair, $user, 1800);
        $this->completedSession($washer, $user, 9999);
        TimeEntry::factory()->for($openOnly, 'trackable')->create([
            'user_id' => $user->id,
            'user_name' => $user->name,
            'started_at' => now()->subHours(10),
            'ended_at' => null,
        ]);

        $response = $this->actingAs($user)->get(route('admin.inventory.furniture', ['view' => 'units']));

        $response->assertOk();
        $response->assertSee('Assembled');
        $response->assertSee('SOFA-SHORT');
        $response->assertSee('Yes');
        $response->assertSee('No');
        $response->assertSee('Sofas');
        $response->assertSee('1h 30m');
        $response->assertSee('Chairs');
        $response->assertSee('30m');
        $response->assertDontSee('Washers');
        $response->assertDontSee('WASHER-1');
    }

    public function test_furniture_item_page_shows_the_assembly_panel(): void
    {
        $user = $this->adminUser();
        $item = $this->item($user, $this->truck($user), $this->category($user, 'Sofas', ItemType::Furniture), 'FUR-PAGE');
        $appliance = $this->item($user, $this->truck($user), $this->category($user, 'Washers', ItemType::Appliance), 'APP-PAGE');

        $this->actingAs($user)->post(route('admin.inventory.assembly.clock-in', $item));

        $furniture = $this->actingAs($user)->get(route('admin.inventory.show', $item));
        $furniture->assertOk();
        $furniture->assertSee('Assembly');
        $furniture->assertSee('Clock out');
        $furniture->assertSee($user->name);
        $furniture->assertSee('In progress');

        $this->actingAs($user)
            ->get(route('admin.inventory.show', $appliance))
            ->assertOk()
            ->assertDontSee('Mark assembled');
    }

    private function completedSession(TruckAppliance $item, User $user, int $seconds): void
    {
        TimeEntry::factory()->for($item, 'trackable')->create([
            'user_id' => $user->id,
            'user_name' => $user->name,
            'started_at' => now()->subSeconds($seconds),
            'ended_at' => now(),
        ]);
    }
}
