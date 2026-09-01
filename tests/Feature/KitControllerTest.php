<?php

namespace Tests\Feature;

use App\Models\Kit;
use App\Models\KitInventory;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KitControllerTest extends TestCase
{
    use RefreshDatabase;

    private function adminUser(): User
    {
        $this->seed(RolePermissionSeeder::class);

        $user = User::factory()->admin()->active()->create();
        $user->syncRoles(['admin']);

        return $user;
    }

    public function test_guest_is_redirected_to_login_when_storing_a_kit(): void
    {
        $response = $this->post(route('admin.kits.store'), [
            'code' => 'KIT-1',
            'name' => 'Test Kit',
        ]);

        $response->assertRedirectToRoute('login');
    }

    public function test_user_without_kits_manage_cannot_store_a_kit(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $user = User::factory()->active()->create(['role' => 'kit_maker']);
        $user->syncRoles(['kit_maker']);

        $this->actingAs($user)
            ->post(route('admin.kits.store'), [
                'code' => 'KIT-1',
                'name' => 'Test Kit',
            ])
            ->assertForbidden();

        $this->assertDatabaseMissing('kits', ['code' => 'KIT-1']);
    }

    public function test_authorized_user_can_create_a_kit_with_initial_stock_levels(): void
    {
        $user = $this->adminUser();

        $response = $this->actingAs($user)
            ->from(route('admin.kits.index'))
            ->post(route('admin.kits.store'), [
                'code' => 'kit-1',
                'name' => 'Test Kit',
                'sop' => 'Assemble carefully.',
                'amazon_min_level' => 5,
                'shopify_min_level' => 3,
            ]);

        $response->assertRedirectToRoute('admin.kits.index');
        $response->assertSessionHas('success');

        $kit = Kit::query()->where('code', 'KIT-1')->firstOrFail();
        $this->assertSame('Test Kit', $kit->name);

        $inventory = KitInventory::query()->where('part_name', 'KIT-1')->firstOrFail();
        $this->assertSame(5, $inventory->amazon_min_level);
        $this->assertSame(3, $inventory->shopify_min_level);
    }

    public function test_store_rejects_a_missing_kit_code(): void
    {
        $user = $this->adminUser();
        $kitCount = Kit::query()->count();

        $response = $this->actingAs($user)
            ->from(route('admin.kits.index'))
            ->post(route('admin.kits.store'), [
                'code' => '',
                'name' => 'Test Kit',
            ]);

        $response->assertRedirectToRoute('admin.kits.index');
        $response->assertSessionHasErrors(['code' => 'The code field is required.']);
        $this->assertSame($kitCount, Kit::query()->count());
    }

    public function test_store_rejects_a_duplicate_kit_code(): void
    {
        $user = $this->adminUser();
        Kit::create(['code' => 'KIT-1', 'name' => 'Existing Kit']);

        $response = $this->actingAs($user)
            ->from(route('admin.kits.index'))
            ->post(route('admin.kits.store'), [
                'code' => 'KIT-1',
                'name' => 'Duplicate Kit',
            ]);

        $response->assertRedirectToRoute('admin.kits.index');
        $response->assertSessionHasErrors(['code' => 'The code has already been taken.']);
    }

    public function test_authorized_user_can_update_a_kit_and_its_code_change_carries_inventory(): void
    {
        $user = $this->adminUser();
        $kit = Kit::create(['code' => 'KIT-1', 'name' => 'Original Name']);
        KitInventory::create([
            'part_name' => 'KIT-1',
            'amazon_stock' => 10,
            'shopify_stock' => 4,
            'amazon_min_level' => 2,
            'shopify_min_level' => 1,
        ]);

        $response = $this->actingAs($user)->patch(route('admin.kits.update', $kit), [
            'code' => 'KIT-2',
            'name' => 'Renamed Kit',
        ]);

        $response->assertRedirectToRoute('admin.kits.index', ['edit_kit' => $kit->id]);
        $response->assertSessionHas('success');

        $kit->refresh();
        $this->assertSame('KIT-2', $kit->code);
        $this->assertSame('Renamed Kit', $kit->name);

        $this->assertDatabaseMissing('kit_inventory', ['part_name' => 'KIT-1']);
        $inventory = KitInventory::query()->where('part_name', 'KIT-2')->firstOrFail();
        $this->assertSame(10, $inventory->amazon_stock);
        $this->assertSame(4, $inventory->shopify_stock);
    }

    public function test_update_rejects_a_missing_name(): void
    {
        $user = $this->adminUser();
        $kit = Kit::create(['code' => 'KIT-1', 'name' => 'Original Name']);

        $response = $this->actingAs($user)
            ->from(route('admin.kits.index'))
            ->patch(route('admin.kits.update', $kit), [
                'code' => 'KIT-1',
                'name' => '',
            ]);

        $response->assertRedirectToRoute('admin.kits.index');
        $response->assertSessionHasErrors(['name' => 'The name field is required.']);
        $this->assertSame('Original Name', $kit->fresh()->name);
    }
}
