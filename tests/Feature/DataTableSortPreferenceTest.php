<?php

namespace Tests\Feature;

use App\Models\Part;
use App\Models\User;
use App\Models\UserPreference;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DataTableSortPreferenceTest extends TestCase
{
    use RefreshDatabase;

    private function adminUser(): User
    {
        $this->seed(RolePermissionSeeder::class);

        $user = User::factory()->admin()->active()->create();
        $user->syncRoles(['admin']);

        return $user;
    }

    private function createPart(User $user, string $partNumber): Part
    {
        return Part::query()->create([
            'part_number' => $partNumber,
            'product_name' => 'Preference Part '.$partNumber,
            'total_stock' => 1,
            'retail_price' => 10,
            'your_price' => 8,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);
    }

    public function test_sorting_a_table_persists_the_users_preference(): void
    {
        $user = $this->adminUser();
        $this->createPart($user, 'PREF-100');

        $response = $this->actingAs($user)->get(route('admin.parts.index', [
            'sort' => 'part_number',
            'direction' => 'asc',
        ]));

        $response->assertOk();

        $this->assertDatabaseHas('user_preferences', [
            'user_id' => $user->id,
            'key' => 'data_table.sort.partsIndexTableColumns',
        ]);

        $preference = UserPreference::query()
            ->where('user_id', $user->id)
            ->where('key', 'data_table.sort.partsIndexTableColumns')
            ->first();

        $this->assertSame([
            'sort' => 'part_number',
            'direction' => 'asc',
        ], $preference?->value);
    }

    public function test_visiting_without_sort_redirects_to_remembered_sort(): void
    {
        $user = $this->adminUser();
        $this->createPart($user, 'PREF-200');

        UserPreference::query()->create([
            'user_id' => $user->id,
            'key' => 'data_table.sort.partsIndexTableColumns',
            'value' => [
                'sort' => 'part_number',
                'direction' => 'desc',
            ],
        ]);

        $response = $this->actingAs($user)->get(route('admin.parts.index'));

        $response->assertRedirect(route('admin.parts.index', [
            'sort' => 'part_number',
            'direction' => 'desc',
        ]));
    }

    public function test_clearing_sort_forgets_the_users_preference(): void
    {
        $user = $this->adminUser();
        $this->createPart($user, 'PREF-300');

        UserPreference::query()->create([
            'user_id' => $user->id,
            'key' => 'data_table.sort.partsIndexTableColumns',
            'value' => [
                'sort' => 'part_number',
                'direction' => 'asc',
            ],
        ]);

        $response = $this->actingAs($user)->get(route('admin.parts.index').'?sort=');

        $response->assertOk();

        $this->assertDatabaseMissing('user_preferences', [
            'user_id' => $user->id,
            'key' => 'data_table.sort.partsIndexTableColumns',
        ]);
    }

    public function test_invalid_sort_does_not_persist_a_preference(): void
    {
        $user = $this->adminUser();
        $this->createPart($user, 'PREF-400');

        $response = $this->actingAs($user)->get(route('admin.parts.index', [
            'sort' => 'not_a_real_column',
            'direction' => 'asc',
        ]));

        $response->assertOk();

        $this->assertDatabaseMissing('user_preferences', [
            'user_id' => $user->id,
            'key' => 'data_table.sort.partsIndexTableColumns',
        ]);
    }
}
