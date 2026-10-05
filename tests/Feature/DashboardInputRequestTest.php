<?php

namespace Tests\Feature;

use App\Models\Suggestion;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardInputRequestTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    private function developer(): User
    {
        $user = User::factory()->developer()->active()->create(['name' => 'Dev User']);
        $user->syncRoles(['developer']);

        return $user;
    }

    private function admin(): User
    {
        $user = User::factory()->admin()->active()->create(['name' => 'Admin User']);
        $user->syncRoles(['admin']);

        return $user;
    }

    public function test_developer_can_create_an_input_request_with_a_custom_page_url(): void
    {
        $developer = $this->developer();

        $this->actingAs($developer)
            ->post(route('admin.dashboard.input-requests.store'), [
                'suggestion' => 'Should we rename Holding for parts?',
                'page_url' => 'https://example.test/admin/inventory',
            ])
            ->assertRedirect(route('admin.dashboard', [
                'feedback_kind' => Suggestion::KIND_INPUT_REQUEST,
                'suggestion_status' => 'pending',
            ]));

        $this->assertDatabaseHas('suggestions', [
            'kind' => Suggestion::KIND_INPUT_REQUEST,
            'suggestion' => 'Should we rename Holding for parts?',
            'page_url' => 'https://example.test/admin/inventory',
            'urgency' => 'normal',
            'status' => 'pending',
            'user_id' => $developer->id,
        ]);
    }

    public function test_admin_cannot_create_an_input_request(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->post(route('admin.dashboard.input-requests.store'), [
                'suggestion' => 'Admins should not post these',
                'page_url' => 'https://example.test/admin/dashboard',
            ])
            ->assertForbidden();

        $this->assertDatabaseMissing('suggestions', [
            'kind' => Suggestion::KIND_INPUT_REQUEST,
            'suggestion' => 'Admins should not post these',
        ]);
    }

    public function test_feedback_tab_hides_input_requests_and_shows_pending_badge_count(): void
    {
        $admin = $this->admin();
        $developer = $this->developer();

        Suggestion::query()->create([
            'kind' => Suggestion::KIND_SUGGESTION,
            'user_id' => $admin->id,
            'username' => $admin->name,
            'suggestion' => 'Staff feedback item',
            'urgency' => 'normal',
            'status' => 'pending',
            'responses' => [],
        ]);
        Suggestion::query()->create([
            'kind' => Suggestion::KIND_INPUT_REQUEST,
            'user_id' => $developer->id,
            'username' => $developer->name,
            'suggestion' => 'Open developer question',
            'urgency' => 'normal',
            'status' => 'pending',
            'responses' => [],
        ]);
        Suggestion::query()->create([
            'kind' => Suggestion::KIND_INPUT_REQUEST,
            'user_id' => $developer->id,
            'username' => $developer->name,
            'suggestion' => 'Completed developer question',
            'urgency' => 'normal',
            'status' => 'completed',
            'completed_by' => $developer->id,
            'completed_at' => now(),
            'responses' => [],
        ]);

        $feedbackTab = $this->actingAs($admin)->get(route('admin.dashboard'));
        $feedbackTab->assertOk();
        $feedbackTab->assertSee('Staff feedback item');
        $feedbackTab->assertDontSee('Open developer question');
        $feedbackTab->assertSee('Input Requests');
        $feedbackTab->assertSee('bg-amber-500', false);
        $feedbackTab->assertViewHas('feedbackKind', Suggestion::KIND_SUGGESTION);
        $feedbackTab->assertViewHas('pendingInputRequestCount', 1);

        $inputTab = $this->actingAs($admin)->get(route('admin.dashboard', [
            'feedback_kind' => Suggestion::KIND_INPUT_REQUEST,
        ]));
        $inputTab->assertOk();
        $inputTab->assertSee('Open developer question');
        $inputTab->assertDontSee('Staff feedback item');
        $inputTab->assertDontSee('Completed developer question');
        $inputTab->assertViewHas('feedbackKind', Suggestion::KIND_INPUT_REQUEST);
    }

    public function test_staff_can_reply_to_an_input_request(): void
    {
        $admin = $this->admin();
        $developer = $this->developer();

        $request = Suggestion::query()->create([
            'kind' => Suggestion::KIND_INPUT_REQUEST,
            'user_id' => $developer->id,
            'username' => $developer->name,
            'suggestion' => 'Need a workflow opinion',
            'urgency' => 'normal',
            'status' => 'pending',
            'responses' => [],
        ]);

        $this->actingAs($admin)
            ->from(route('admin.dashboard', ['feedback_kind' => Suggestion::KIND_INPUT_REQUEST]))
            ->post(route('admin.dashboard.suggestions.responses.store', $request), [
                'response' => 'I would keep the current labels.',
            ])
            ->assertRedirect(route('admin.dashboard', ['feedback_kind' => Suggestion::KIND_INPUT_REQUEST]));

        $request->refresh();

        $this->assertCount(1, $request->responses);
        $this->assertSame('Admin User', $request->responses[0]['user']);
        $this->assertSame('I would keep the current labels.', $request->responses[0]['message']);
    }

    public function test_only_developer_can_complete_an_input_request(): void
    {
        $admin = $this->admin();
        $developer = $this->developer();

        $request = Suggestion::query()->create([
            'kind' => Suggestion::KIND_INPUT_REQUEST,
            'user_id' => $developer->id,
            'username' => $developer->name,
            'suggestion' => 'Ready to close this ask',
            'urgency' => 'normal',
            'status' => 'pending',
            'responses' => [],
        ]);

        $this->actingAs($admin)
            ->patch(route('admin.dashboard.suggestions.complete', $request))
            ->assertForbidden();

        $this->actingAs($developer)
            ->from(route('admin.dashboard', ['feedback_kind' => Suggestion::KIND_INPUT_REQUEST]))
            ->patch(route('admin.dashboard.suggestions.complete', $request))
            ->assertRedirect(route('admin.dashboard', ['feedback_kind' => Suggestion::KIND_INPUT_REQUEST]));

        $this->assertDatabaseHas('suggestions', [
            'id' => $request->id,
            'status' => 'completed',
            'completed_by' => $developer->id,
        ]);
    }
}
