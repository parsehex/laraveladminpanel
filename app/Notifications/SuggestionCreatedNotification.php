<?php

namespace App\Notifications;

use App\Models\Suggestion;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;

class SuggestionCreatedNotification extends Notification
{
    use Queueable;

    public function __construct(public Suggestion $suggestion) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $author = $this->suggestion->username ?: 'Staff';
        $urgency = ucfirst((string) $this->suggestion->urgency);
        $preview = Str::limit(trim((string) $this->suggestion->suggestion), 140);

        return [
            'title' => 'New suggestion',
            'message' => "{$author} ({$urgency}): {$preview}",
            'url' => route('admin.dashboard').'#suggestions',
            'suggestion_id' => $this->suggestion->id,
        ];
    }
}
