<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Subcategory;
use App\Models\Truck;
use App\Models\TruckAppliance;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_a_category(): void
    {
        $admin = $this->adminUser();

        $this->actingAs($admin)->post(route('admin.categories.store'), [
            'name' => 'Freezers',
        ])->assertRedirectToRoute('admin.categories.index');

        $this->assertDatabaseHas('categories', [
            'name' => 'Freezers',
            'status' => 1,
        ]);
    }

    public function test_duplicate_category_name_is_rejected(): void
    {
        $admin = $this->adminUser();
        Category::query()->create([
            'name' => 'Ranges',
            'status' => 1,
        ]);

        $this->actingAs($admin)
            ->from(route('admin.categories.index'))
            ->post(route('admin.categories.store'), [
                'name' => 'Ranges',
            ])
            ->assertRedirectToRoute('admin.categories.index')
            ->assertSessionHasErrors('name');

        $this->assertSame(1, Category::query()->where('name', 'Ranges')->count());
    }

    public function test_renaming_a_category_keeps_its_id(): void
    {
        $admin = $this->adminUser();
        $category = Category::query()->create([
            'name' => 'Heater',
            'status' => 1,
        ]);

        $this->actingAs($admin)->patch(route('admin.categories.update', $category), [
            'name' => 'Heaters',
            'status' => 1,
        ])->assertRedirectToRoute('admin.categories.index');

        $this->assertDatabaseHas('categories', [
            'id' => $category->id,
            'name' => 'Heaters',
        ]);
        $this->assertDatabaseMissing('categories', [
            'name' => 'Heater',
        ]);
    }

    public function test_deactivating_a_category_hides_it_from_the_dropdown(): void
    {
        $admin = $this->adminUser();
        $category = Category::query()->create([
            'name' => 'Pedestal',
            'status' => 1,
        ]);

        $this->actingAs($admin)->patch(route('admin.categories.update', $category), [
            'name' => 'Pedestal',
            'status' => 0,
        ])->assertRedirectToRoute('admin.categories.index');

        $names = collect($this->actingAs($admin)->getJson(route('admin.dropdowns.categories'))->json('data'))
            ->pluck('text')
            ->all();

        $this->assertNotContains('Pedestal', $names);
    }

    public function test_user_without_permission_cannot_open_categories(): void
    {
        $technician = $this->technicianUser();

        $this->actingAs($technician)
            ->get(route('admin.categories.index'))
            ->assertForbidden();
    }

    public function test_admin_can_create_the_same_subcategory_name_on_two_categories(): void
    {
        $admin = $this->adminUser();
        $washers = Category::query()->create(['name' => 'Washers', 'status' => 1]);
        $dryers = Category::query()->create(['name' => 'Dryers', 'status' => 1]);

        $this->actingAs($admin)->post(route('admin.categories.subcategories.store', $washers), [
            'subcategory_name' => 'Top Load',
        ])->assertRedirectToRoute('admin.categories.index');

        $this->actingAs($admin)->post(route('admin.categories.subcategories.store', $dryers), [
            'subcategory_name' => 'Top Load',
        ])->assertRedirectToRoute('admin.categories.index');

        $this->assertDatabaseHas('subcategories', [
            'category_id' => $washers->id,
            'name' => 'Top Load',
            'status' => 1,
        ]);
        $this->assertDatabaseHas('subcategories', [
            'category_id' => $dryers->id,
            'name' => 'Top Load',
            'status' => 1,
        ]);
    }

    public function test_duplicate_subcategory_name_within_a_category_is_rejected(): void
    {
        $admin = $this->adminUser();
        $category = Category::query()->create(['name' => 'Washers', 'status' => 1]);
        Subcategory::query()->create([
            'category_id' => $category->id,
            'name' => 'Top Load',
            'status' => 1,
        ]);

        $this->actingAs($admin)
            ->from(route('admin.categories.index'))
            ->post(route('admin.categories.subcategories.store', $category), [
                'subcategory_name' => 'Top Load',
            ])
            ->assertRedirectToRoute('admin.categories.index')
            ->assertSessionHasErrors(['subcategory_name'], errorBag: 'subcategory-create-'.$category->id);

        $this->assertSame(1, Subcategory::query()->where('category_id', $category->id)->where('name', 'Top Load')->count());
    }

    public function test_renaming_a_subcategory_updates_items_in_that_category(): void
    {
        $admin = $this->adminUser();
        $washers = Category::query()->create(['name' => 'Washers', 'status' => 1]);
        $dryers = Category::query()->create(['name' => 'Dryers', 'status' => 1]);
        $subcategory = Subcategory::query()->create([
            'category_id' => $washers->id,
            'name' => 'Top Load',
            'status' => 1,
        ]);
        $truck = Truck::query()->create([
            'name' => 'Category Truck',
            'units_on_truck' => 1,
            'cost_of_truck' => 500,
            'shipping_cost' => 0,
            'arrival_date' => now()->toDateString(),
            'status' => 'active',
            'created_by' => $admin->id,
            'updated_by' => $admin->id,
        ]);
        $washer = TruckAppliance::query()->create([
            'truck_id' => $truck->id,
            'category_id' => $washers->id,
            'subcategory' => 'Top Load',
            'created_by' => $admin->id,
            'updated_by' => $admin->id,
        ]);
        $dryer = TruckAppliance::query()->create([
            'truck_id' => $truck->id,
            'category_id' => $dryers->id,
            'subcategory' => 'Top Load',
            'created_by' => $admin->id,
            'updated_by' => $admin->id,
        ]);

        $this->actingAs($admin)->get(route('admin.categories.index'))
            ->assertOk()
            ->assertViewHas('categories', function ($categories) use ($subcategory) {
                $loaded = $categories->firstWhere('name', 'Washers')->subcategories->firstWhere('id', $subcategory->id);

                return $loaded->item_count === 1;
            });

        $this->actingAs($admin)->patch(route('admin.categories.subcategories.update', [$washers, $subcategory]), [
            'subcategory_name' => 'High Efficiency',
            'status' => 1,
        ])->assertRedirectToRoute('admin.categories.index');

        $this->assertDatabaseHas('subcategories', [
            'id' => $subcategory->id,
            'category_id' => $washers->id,
            'name' => 'High Efficiency',
        ]);
        $this->assertSame('High Efficiency', $washer->fresh()->subcategory);
        $this->assertSame('Top Load', $dryer->fresh()->subcategory);
    }

    public function test_deactivating_a_subcategory_hides_it_from_the_dropdown(): void
    {
        $admin = $this->adminUser();
        $category = Category::query()->create(['name' => 'Washers', 'status' => 1]);
        $subcategory = Subcategory::query()->create([
            'category_id' => $category->id,
            'name' => 'Top Load',
            'status' => 1,
        ]);

        $this->actingAs($admin)->patch(route('admin.categories.subcategories.update', [$category, $subcategory]), [
            'subcategory_name' => 'Top Load',
            'status' => 0,
        ])->assertRedirectToRoute('admin.categories.index');

        $names = collect($this->actingAs($admin)->getJson(route('admin.dropdowns.subcategories', [
            'category' => 'Washers',
        ]))->json('data'))->pluck('text')->all();

        $this->assertNotContains('Top Load', $names);
    }

    public function test_subcategory_update_for_another_category_is_not_found(): void
    {
        $admin = $this->adminUser();
        $washers = Category::query()->create(['name' => 'Washers', 'status' => 1]);
        $dryers = Category::query()->create(['name' => 'Dryers', 'status' => 1]);
        $subcategory = Subcategory::query()->create([
            'category_id' => $washers->id,
            'name' => 'Top Load',
            'status' => 1,
        ]);

        $this->actingAs($admin)->patch(route('admin.categories.subcategories.update', [$dryers, $subcategory]), [
            'subcategory_name' => 'Front Load',
            'status' => 1,
        ])->assertNotFound();

        $this->assertSame('Top Load', $subcategory->fresh()->name);
    }

    public function test_user_without_permission_cannot_manage_subcategories(): void
    {
        $technician = $this->technicianUser();
        $category = Category::query()->create(['name' => 'Washers', 'status' => 1]);
        $subcategory = Subcategory::query()->create([
            'category_id' => $category->id,
            'name' => 'Top Load',
            'status' => 1,
        ]);

        $this->actingAs($technician)
            ->post(route('admin.categories.subcategories.store', $category), [
                'subcategory_name' => 'Front Load',
            ])
            ->assertForbidden();

        $this->actingAs($technician)
            ->patch(route('admin.categories.subcategories.update', [$category, $subcategory]), [
                'subcategory_name' => 'Front Load',
                'status' => 0,
            ])
            ->assertForbidden();
    }

    private function adminUser(): User
    {
        $this->seed(RolePermissionSeeder::class);

        $user = User::factory()->admin()->active()->create();
        $user->syncRoles(['admin']);

        return $user;
    }

    private function technicianUser(): User
    {
        $this->seed(RolePermissionSeeder::class);

        $user = User::factory()->active()->create(['role' => 'technician']);
        $user->syncRoles(['technician']);

        return $user;
    }
}
