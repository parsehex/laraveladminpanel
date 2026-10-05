<?php

namespace Tests\Feature;

use App\Models\Suggestion;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardSuggestionCompleteTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    public function test_developer_can_mark_a_suggestion_complete(): void
    {
        $developer = User::factory()->developer()->active()->create();
        $developer->syncRoles(['developer']);

        $suggestion = Suggestion::query()->create([
            'user_id' => $developer->id,
            'username' => $developer->name,
            'suggestion' => 'Ship the developer complete gate',
            'urgency' => 'normal',
            'status' => 'pending',
            'responses' => [],
        ]);

        $this->actingAs($developer)
            ->from(route('admin.dashboard'))
            ->patch(route('admin.dashboard.suggestions.complete', $suggestion))
            ->assertRedirect(route('admin.dashboard'));

        $this->assertDatabaseHas('suggestions', [
            'id' => $suggestion->id,
            'status' => 'completed',
            'completed_by' => $developer->id,
        ]);
    }

    public function test_admin_cannot_mark_a_suggestion_complete(): void
    {
        $admin = User::factory()->admin()->active()->create();
        $admin->syncRoles(['admin']);

        $suggestion = Suggestion::query()->create([
            'user_id' => $admin->id,
            'username' => $admin->name,
            'suggestion' => 'Admins should not complete feedback',
            'urgency' => 'normal',
            'status' => 'pending',
            'responses' => [],
        ]);

        $this->actingAs($admin)
            ->from(route('admin.dashboard'))
            ->patch(route('admin.dashboard.suggestions.complete', $suggestion))
            ->assertForbidden();

        $this->assertDatabaseHas('suggestions', [
            'id' => $suggestion->id,
            'status' => 'pending',
            'completed_by' => null,
        ]);
    }

    public function test_complete_button_is_only_shown_to_developers(): void
    {
        $developer = User::factory()->developer()->active()->create();
        $developer->syncRoles(['developer']);
        $admin = User::factory()->admin()->active()->create();
        $admin->syncRoles(['admin']);

        $suggestion = Suggestion::query()->create([
            'user_id' => $admin->id,
            'username' => $admin->name,
            'suggestion' => 'Visible complete control check',
            'urgency' => 'normal',
            'status' => 'pending',
            'responses' => [],
        ]);

        $this->actingAs($developer)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee(route('admin.dashboard.suggestions.complete', $suggestion), false);

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertDontSee(route('admin.dashboard.suggestions.complete', $suggestion), false);
    }
}
