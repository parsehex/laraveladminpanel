<?php

namespace Tests\Feature;

use App\Models\ModuleNotificationSubscriber;
use App\Models\Suggestion;
use App\Models\User;
use App\Notifications\SuggestionCreatedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class SuggestionControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login_when_submitting_a_suggestion(): void
    {
        $this->post(route('admin.suggestions.store'), [
            'suggestion' => 'Make the scan button larger.',
            'urgency' => 'normal',
        ])->assertRedirectToRoute('login');
    }

    public function test_submitting_a_suggestion_notifies_subscribers(): void
    {
        $author = User::factory()->active()->create(['name' => 'Alex Rivera']);
        $subscriber = User::factory()->active()->create();
        ModuleNotificationSubscriber::query()->create([
            'module' => 'suggestions',
            'user_id' => $subscriber->id,
        ]);

        Notification::fake();

        $response = $this->actingAs($author)
            ->from(route('admin.dashboard'))
            ->post(route('admin.suggestions.store'), [
                'suggestion' => 'The scan page needs a bigger button.',
                'urgency' => 'high',
                'page_url' => 'https://example.test/admin/inventory',
            ]);

        $response->assertRedirectToRoute('admin.dashboard');
        $response->assertSessionHas('success');

        $suggestion = Suggestion::query()->where('user_id', $author->id)->firstOrFail();
        $this->assertSame('pending', $suggestion->status);

        Notification::assertSentTo(
            $subscriber,
            SuggestionCreatedNotification::class,
            function (SuggestionCreatedNotification $notification, array $channels) use ($subscriber, $suggestion): bool {
                $payload = $notification->toArray($subscriber);

                return $notification->suggestion->is($suggestion)
                    && $payload['title'] === 'New suggestion'
                    && $payload['message'] === 'Alex Rivera (High): The scan page needs a bigger button.'
                    && $payload['url'] === route('admin.dashboard').'#suggestions'
                    && $channels === ['database'];
            }
        );
        Notification::assertNotSentTo($author, SuggestionCreatedNotification::class);
    }

    public function test_submitting_a_suggestion_does_not_notify_the_author_or_inactive_subscribers(): void
    {
        $author = User::factory()->active()->create();
        $inactiveSubscriber = User::factory()->inactive()->create();
        ModuleNotificationSubscriber::query()->create([
            'module' => 'suggestions',
            'user_id' => $author->id,
        ]);
        ModuleNotificationSubscriber::query()->create([
            'module' => 'suggestions',
            'user_id' => $inactiveSubscriber->id,
        ]);

        Notification::fake();

        $this->actingAs($author)
            ->from(route('admin.dashboard'))
            ->post(route('admin.suggestions.store'), [
                'suggestion' => 'Add a parts shortcut.',
                'urgency' => 'low',
            ])
            ->assertRedirectToRoute('admin.dashboard')
            ->assertSessionHas('success');

        $this->assertDatabaseHas('suggestions', [
            'user_id' => $author->id,
            'suggestion' => 'Add a parts shortcut.',
            'urgency' => 'low',
        ]);
        Notification::assertNothingSent();
    }
}
