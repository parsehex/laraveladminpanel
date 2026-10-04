<?php

namespace Tests\Feature;

use App\Models\Part;
use App\Models\User;
use App\Models\UserPreference;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PageSizePreferenceTest extends TestCase
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
            'product_name' => 'Page Size Part '.$partNumber,
            'total_stock' => 1,
            'retail_price' => 10,
            'your_price' => 8,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);
    }

    public function test_choosing_a_page_size_persists_the_users_preference(): void
    {
        $user = $this->adminUser();
        $this->createPart($user, 'LIMIT-100');

        $response = $this->actingAs($user)->get(route('admin.parts.index', [
            'limit' => 100,
        ]));

        $response->assertOk();
        $response->assertViewHas('parts', fn ($parts) => $parts->perPage() === 100);

        $preference = UserPreference::query()
            ->where('user_id', $user->id)
            ->where('key', 'page_size')
            ->first();

        $this->assertSame(['limit' => 100], $preference?->value);
    }

    public function test_visiting_without_limit_uses_the_remembered_page_size(): void
    {
        $user = $this->adminUser();
        $this->createPart($user, 'LIMIT-200');

        UserPreference::query()->create([
            'user_id' => $user->id,
            'key' => 'page_size',
            'value' => ['limit' => 50],
        ]);

        $response = $this->actingAs($user)->get(route('admin.parts.index'));

        $response->assertOk();
        $response->assertViewHas('parts', fn ($parts) => $parts->perPage() === 50);
    }

    public function test_page_size_preference_is_shared_across_list_pages(): void
    {
        $user = $this->adminUser();
        $this->createPart($user, 'LIMIT-SHARED');

        $this->actingAs($user)->get(route('admin.parts.index', [
            'limit' => 100,
        ]))->assertOk();

        $response = $this->actingAs($user)->get(route('admin.models.index'));

        $response->assertOk();
        $response->assertViewHas('models', fn ($models) => $models->perPage() === 100);
    }

    public function test_invalid_limit_does_not_persist_a_preference(): void
    {
        $user = $this->adminUser();
        $this->createPart($user, 'LIMIT-300');

        $response = $this->actingAs($user)->get(route('admin.parts.index', [
            'limit' => 999,
        ]));

        $response->assertOk();
        $response->assertViewHas('parts', fn ($parts) => $parts->perPage() === 25);

        $this->assertDatabaseMissing('user_preferences', [
            'user_id' => $user->id,
            'key' => 'page_size',
        ]);
    }

    public function test_invalid_stored_page_size_is_forgotten_and_default_is_used(): void
    {
        $user = $this->adminUser();
        $this->createPart($user, 'LIMIT-400');

        UserPreference::query()->create([
            'user_id' => $user->id,
            'key' => 'page_size',
            'value' => ['limit' => 999],
        ]);

        $response = $this->actingAs($user)->get(route('admin.parts.index'));

        $response->assertOk();
        $response->assertViewHas('parts', fn ($parts) => $parts->perPage() === 25);

        $this->assertDatabaseMissing('user_preferences', [
            'user_id' => $user->id,
            'key' => 'page_size',
        ]);
    }
}
