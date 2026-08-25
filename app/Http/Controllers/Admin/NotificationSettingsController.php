<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\ModuleNotifier;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class NotificationSettingsController extends Controller
{
    public function index(Request $request)
    {
        abort_unless($this->userIsAdmin($request->user()), 403);

        $staffUsers = User::query()
            ->where('status', 'active')
            ->orderBy('name')
            ->get(['id', 'name', 'email']);

        return view('admin.notification-settings.index', [
            'modules' => ModuleNotifier::modules(),
            'subscriberIdsByModule' => ModuleNotifier::subscriberIdsByModule(),
            'staffUsers' => $staffUsers,
        ]);
    }

    public function update(Request $request)
    {
        abort_unless($this->userIsAdmin($request->user()), 403);

        $moduleKeys = array_keys(ModuleNotifier::modules());

        $data = $request->validate([
            'subscribers' => ['nullable', 'array'],
            'subscribers.*' => ['nullable', 'array'],
            'subscribers.*.*' => ['integer', 'exists:users,id'],
            'module' => ['nullable', 'string', Rule::in($moduleKeys)],
        ]);

        // Save one module at a time when the form posts a single module key,
        // otherwise sync every known module from the full page form.
        if (! empty($data['module'])) {
            ModuleNotifier::sync($data['module'], $data['subscribers'][$data['module']] ?? []);
        } else {
            foreach ($moduleKeys as $module) {
                ModuleNotifier::sync($module, $data['subscribers'][$module] ?? []);
            }
        }

        return back()->with('success', __('Notification subscribers updated.'));
    }

    private function userIsAdmin(?User $user): bool
    {
        if (! $user) {
            return false;
        }

        return $user->hasRole('admin') || $user->role === 'admin';
    }
}
