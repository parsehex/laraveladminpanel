<?php

namespace Tests\Feature;

use App\Models\Suggestion;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardSuggestionsTest extends TestCase
{
    use RefreshDatabase;

    private function adminUser(): User
    {
        $this->seed(RolePermissionSeeder::class);

        $user = User::factory()->admin()->active()->create(['name' => 'Alex Rivera']);
        $user->syncRoles(['admin']);

        return $user;
    }

    public function test_dashboard_defaults_to_pending_suggestions(): void
    {
        $admin = $this->adminUser();

        $this->createSuggestion($admin, [
            'suggestion' => 'Pending workflow idea',
            'status' => 'pending',
        ]);
        $this->createSuggestion($admin, [
            'suggestion' => 'Finished dashboard request',
            'status' => 'completed',
            'completed_by' => $admin->id,
            'completed_at' => now(),
        ]);

        $response = $this->actingAs($admin)->get(route('admin.dashboard'));

        $response->assertOk();
        $response->assertSee('Pending workflow idea');
        $response->assertDontSee('Finished dashboard request');
        $response->assertViewHas('suggestionStatus', 'pending');
        $response->assertViewHas('suggestions', fn ($suggestions) => $suggestions->total() === 1);
    }

    public function test_dashboard_can_filter_to_completed_suggestions(): void
    {
        $admin = $this->adminUser();

        $this->createSuggestion($admin, [
            'suggestion' => 'Pending workflow idea',
            'status' => 'pending',
        ]);
        $this->createSuggestion($admin, [
            'suggestion' => 'Finished dashboard request',
            'status' => 'completed',
            'completed_by' => $admin->id,
            'completed_at' => now(),
        ]);

        $response = $this->actingAs($admin)->get(route('admin.dashboard', [
            'suggestion_status' => 'completed',
        ]));

        $response->assertOk();
        $response->assertSee('Finished dashboard request');
        $response->assertDontSee('Pending workflow idea');
        $response->assertViewHas('suggestionStatus', 'completed');
        $response->assertViewHas('suggestions', fn ($suggestions) => $suggestions->total() === 1);
    }

    public function test_dashboard_can_filter_to_all_suggestions(): void
    {
        $admin = $this->adminUser();

        $this->createSuggestion($admin, [
            'suggestion' => 'Pending workflow idea',
            'status' => 'pending',
        ]);
        $this->createSuggestion($admin, [
            'suggestion' => 'Finished dashboard request',
            'status' => 'completed',
            'completed_by' => $admin->id,
            'completed_at' => now(),
        ]);

        $response = $this->actingAs($admin)->get(route('admin.dashboard', [
            'suggestion_status' => 'all',
        ]));

        $response->assertOk();
        $response->assertSee('Pending workflow idea');
        $response->assertSee('Finished dashboard request');
        $response->assertViewHas('suggestionStatus', 'all');
        $response->assertViewHas('suggestions', fn ($suggestions) => $suggestions->total() === 2);
    }

    public function test_invalid_suggestion_status_falls_back_to_pending(): void
    {
        $admin = $this->adminUser();

        $this->createSuggestion($admin, [
            'suggestion' => 'Pending workflow idea',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.dashboard', [
            'suggestion_status' => 'archived',
        ]));

        $response->assertOk();
        $response->assertViewHas('suggestionStatus', 'pending');
        $response->assertSee('Pending workflow idea');
    }

    public function test_dashboard_paginates_suggestions(): void
    {
        $admin = $this->adminUser();

        for ($i = 1; $i <= 11; $i++) {
            $this->createSuggestion($admin, [
                'suggestion' => sprintf('Suggestion item %02d', $i),
                'status' => 'pending',
                'created_at' => now()->subMinutes(12 - $i),
            ]);
        }

        $firstPage = $this->actingAs($admin)->get(route('admin.dashboard'));
        $firstPage->assertOk();
        $firstPage->assertSee('Suggestion item 11');
        $firstPage->assertSee('Suggestion item 02');
        $firstPage->assertDontSee('Suggestion item 01');
        $firstPage->assertViewHas('suggestions', function ($suggestions) {
            return $suggestions->total() === 11
                && $suggestions->perPage() === 10
                && $suggestions->count() === 10
                && $suggestions->hasPages()
                && $suggestions->first()->suggestion === 'Suggestion item 11'
                && $suggestions->last()->suggestion === 'Suggestion item 02';
        });

        $secondPage = $this->actingAs($admin)->get(route('admin.dashboard', [
            'suggestions_page' => 2,
        ]));
        $secondPage->assertOk();
        $secondPage->assertSee('Suggestion item 01');
        $secondPage->assertDontSee('Suggestion item 11');
        $secondPage->assertViewHas('suggestions', function ($suggestions) {
            return $suggestions->count() === 1
                && $suggestions->first()->suggestion === 'Suggestion item 01';
        });
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function createSuggestion(User $user, array $attributes): Suggestion
    {
        $suggestion = Suggestion::query()->create([
            'user_id' => $user->id,
            'username' => $user->name,
            'urgency' => 'normal',
            'responses' => [],
            ...$attributes,
        ]);

        if (isset($attributes['created_at'])) {
            $suggestion->forceFill([
                'created_at' => $attributes['created_at'],
                'updated_at' => $attributes['created_at'],
            ])->saveQuietly();
        }

        return $suggestion->refresh();
    }
}
