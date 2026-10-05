<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\DestroyUserPreferenceRequest;
use App\Http\Requests\UpdateUserPreferenceRequest;
use App\Support\UserPreferences;
use Illuminate\Http\JsonResponse;

class UserPreferenceController extends Controller
{
    public function update(UpdateUserPreferenceRequest $request): JsonResponse
    {
        $validated = $request->validated();

        UserPreferences::put(
            $request->user(),
            $validated['key'],
            $validated['value'],
        );

        return response()->json(['ok' => true]);
    }

    public function destroy(DestroyUserPreferenceRequest $request): JsonResponse
    {
        UserPreferences::forget(
            $request->user(),
            $request->validated('key'),
        );

        return response()->json(['ok' => true]);
    }
}
