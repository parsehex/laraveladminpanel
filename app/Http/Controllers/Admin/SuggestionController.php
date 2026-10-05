<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Suggestion;
use App\Notifications\SuggestionCreatedNotification;
use App\Support\ModuleNotifier;
use Illuminate\Http\Request;

class SuggestionController extends Controller
{
    public const NOTIFICATION_MODULE = 'suggestions';

    public function store(Request $request)
    {
        $data = $request->validate([
            'suggestion' => ['required', 'string', 'max:2000'],
            'urgency' => ['required', 'in:low,normal,high'],
            'page_url' => ['nullable', 'string', 'max:2048'],
        ]);

        $suggestion = Suggestion::create([
            'kind' => Suggestion::KIND_SUGGESTION,
            'user_id' => $request->user()->id,
            'username' => $request->user()->name,
            'suggestion' => $data['suggestion'],
            'page_url' => $data['page_url'] ?? $request->fullUrl(),
            'urgency' => $data['urgency'],
            'status' => 'pending',
        ]);

        ModuleNotifier::notify(
            self::NOTIFICATION_MODULE,
            new SuggestionCreatedNotification($suggestion),
            (int) $request->user()->id
        );

        return back()->with('success', __('Suggestion submitted successfully.'));
    }
}
