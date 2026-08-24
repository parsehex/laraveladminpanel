<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Suggestion;
use Illuminate\Http\Request;

class SuggestionController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'suggestion' => ['required', 'string', 'max:2000'],
            'urgency' => ['required', 'in:low,normal,high'],
            'page_url' => ['nullable', 'string', 'max:2048'],
        ]);

        Suggestion::create([
            'user_id' => $request->user()->id,
            'username' => $request->user()->name,
            'suggestion' => $data['suggestion'],
            'page_url' => $data['page_url'] ?: $request->fullUrl(),
            'urgency' => $data['urgency'],
            'status' => 'pending',
        ]);

        return back()->with('success', __('Suggestion submitted successfully.'));
    }
}
