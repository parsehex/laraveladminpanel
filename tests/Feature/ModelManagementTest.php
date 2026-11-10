<?php

namespace Tests\Feature;

use App\Enums\ItemType;
use App\Models\Category;
use App\Models\Model as CatalogModel;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ModelManagementTest extends TestCase
{
    use RefreshDatabase;

    private function adminUser(): User
    {
        $this->seed(RolePermissionSeeder::class);

        $user = User::factory()->admin()->active()->create();
        $user->syncRoles(['admin']);

        return $user;
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

    private function catalogModel(User $user, Category $category, string $modelNumber = 'AT04900111GR'): CatalogModel
    {
        return CatalogModel::query()->create([
            'model_number' => $modelNumber,
            'product_name' => 'Kids desk set',
            'category_id' => $category->id,
            'brand' => 'Test Brand',
            'msrp' => 99.99,
            'status' => 1,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);
    }

    public function test_admin_can_move_a_model_from_unknown_to_furniture_category(): void
    {
        $admin = $this->adminUser();
        $unknown = $this->category($admin, 'Unknown', ItemType::Furniture);
        $furniture = $this->category($admin, 'Furniture', ItemType::Furniture);
        $model = $this->catalogModel($admin, $unknown);

        $this->actingAs($admin)
            ->from(route('admin.models.index', ['type' => 'furniture', 'search' => $model->model_number]))
            ->put(route('admin.models.update', $model), [
                '_form' => 'edit-'.$model->id,
                'model_number' => $model->model_number,
                'product_name' => $model->product_name,
                'brand' => $model->brand,
                'msrp' => $model->msrp,
                'item_type' => ItemType::Furniture->value,
                'category_id' => $furniture->id,
            ])
            ->assertRedirectToRoute('admin.models.index')
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('models', [
            'id' => $model->id,
            'category_id' => $furniture->id,
        ]);
    }

    public function test_admin_can_create_a_furniture_model(): void
    {
        $admin = $this->adminUser();
        $furniture = $this->category($admin, 'Furniture', ItemType::Furniture);

        $this->actingAs($admin)->post(route('admin.models.store'), [
            '_form' => 'create',
            'model_number' => 'FURN-100',
            'product_name' => 'Sofa',
            'brand' => 'Wayfair',
            'msrp' => 250,
            'item_type' => ItemType::Furniture->value,
            'category_id' => $furniture->id,
        ])->assertRedirectToRoute('admin.models.index');

        $this->assertDatabaseHas('models', [
            'model_number' => 'FURN-100',
            'category_id' => $furniture->id,
        ]);
    }

    public function test_item_type_must_match_the_selected_category(): void
    {
        $admin = $this->adminUser();
        $washers = $this->category($admin, 'Washers', ItemType::Appliance);
        $furniture = $this->category($admin, 'Furniture', ItemType::Furniture);
        $model = $this->catalogModel($admin, $washers, 'WASHER-1');

        $this->actingAs($admin)
            ->from(route('admin.models.index'))
            ->put(route('admin.models.update', $model), [
                '_form' => 'edit-'.$model->id,
                'model_number' => $model->model_number,
                'product_name' => $model->product_name,
                'brand' => $model->brand,
                'msrp' => $model->msrp,
                'item_type' => ItemType::Furniture->value,
                'category_id' => $washers->id,
            ])
            ->assertRedirectToRoute('admin.models.index')
            ->assertSessionHasErrors('category_id');

        $this->actingAs($admin)
            ->from(route('admin.models.index'))
            ->put(route('admin.models.update', $model), [
                '_form' => 'edit-'.$model->id,
                'model_number' => $model->model_number,
                'product_name' => $model->product_name,
                'brand' => $model->brand,
                'msrp' => $model->msrp,
                'item_type' => ItemType::Appliance->value,
                'category_id' => $furniture->id,
            ])
            ->assertRedirectToRoute('admin.models.index')
            ->assertSessionHasErrors('category_id');

        $this->assertDatabaseHas('models', [
            'id' => $model->id,
            'category_id' => $washers->id,
        ]);
    }

    public function test_models_index_can_be_filtered_by_type(): void
    {
        $admin = $this->adminUser();
        $washers = $this->category($admin, 'Washers', ItemType::Appliance);
        $furniture = $this->category($admin, 'Furniture', ItemType::Furniture);
        $this->catalogModel($admin, $washers, 'APP-MODEL');
        $this->catalogModel($admin, $furniture, 'FUR-MODEL');

        $this->actingAs($admin)
            ->get(route('admin.models.index', ['type' => 'furniture']))
            ->assertOk()
            ->assertSee('FUR-MODEL')
            ->assertDontSee('APP-MODEL');

        $this->actingAs($admin)
            ->get(route('admin.models.index', ['type' => 'appliance']))
            ->assertOk()
            ->assertSee('APP-MODEL')
            ->assertDontSee('FUR-MODEL');

        $this->actingAs($admin)
            ->get(route('admin.models.index', ['type' => 'all']))
            ->assertOk()
            ->assertSee('APP-MODEL')
            ->assertSee('FUR-MODEL');
    }
}
