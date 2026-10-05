<?php

namespace Tests\Feature;

use App\Models\Part;
use App\Models\User;
use App\Models\UserPreference;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserPreferenceControllerTest extends TestCase
{
    use RefreshDatabase;

    private function adminUser(): User
    {
        $this->seed(RolePermissionSeeder::class);

        $user = User::factory()->admin()->active()->create();
        $user->syncRoles(['admin']);

        return $user;
    }

    public function test_user_can_upsert_a_preference(): void
    {
        $user = $this->adminUser();

        $response = $this->actingAs($user)->putJson(route('admin.preferences.update'), [
            'key' => 'data_table.columns.partsIndexTableColumns',
            'value' => [
                'part_number' => true,
                'product_name' => false,
            ],
        ]);

        $response->assertOk()->assertJson(['ok' => true]);

        $preference = UserPreference::query()
            ->where('user_id', $user->id)
            ->where('key', 'data_table.columns.partsIndexTableColumns')
            ->first();

        $this->assertSame([
            'part_number' => true,
            'product_name' => false,
        ], $preference?->value);
    }

    public function test_user_can_forget_a_preference(): void
    {
        $user = $this->adminUser();

        UserPreference::query()->create([
            'user_id' => $user->id,
            'key' => 'data_table.columns.partsIndexTableColumns',
            'value' => ['product_name' => false],
        ]);

        $response = $this->actingAs($user)->deleteJson(route('admin.preferences.destroy'), [
            'key' => 'data_table.columns.partsIndexTableColumns',
        ]);

        $response->assertOk()->assertJson(['ok' => true]);

        $this->assertDatabaseMissing('user_preferences', [
            'user_id' => $user->id,
            'key' => 'data_table.columns.partsIndexTableColumns',
        ]);
    }

    public function test_preference_key_must_be_safe(): void
    {
        $user = $this->adminUser();

        $this->actingAs($user)->putJson(route('admin.preferences.update'), [
            'key' => 'data_table.columns/../evil',
            'value' => ['x' => true],
        ])->assertStatus(422);
    }

    public function test_parts_index_hydrates_saved_column_visibility(): void
    {
        $user = $this->adminUser();

        Part::query()->create([
            'part_number' => 'COL-1',
            'product_name' => 'Column Pref Part',
            'total_stock' => 1,
            'retail_price' => 10,
            'your_price' => 8,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        UserPreference::query()->create([
            'user_id' => $user->id,
            'key' => 'data_table.columns.partsIndexTableColumns',
            'value' => [
                'product_name' => false,
            ],
        ]);

        $response = $this->actingAs($user)->get(route('admin.parts.index'));

        $response->assertOk();
        // @js() hex-escapes quotes for safe HTML embedding.
        $response->assertSee('\u0022product_name\u0022:false', false);
    }
}
