<?php

namespace App\Services;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Auth;

class PermissionService
{
    /**
     * Whether the given user holds a permission (respects Gate::before / Spatie).
     */
    public static function check(string $permission, ?Authenticatable $user = null): bool
    {
        $user = $user ?? Auth::user();

        return $user && $user->can($permission);
    }

    /**
     * Abort with 403 unless the permission passes.
     */
    public static function checkOrFail(string $permission, ?Authenticatable $user = null): void
    {
        if (! static::check($permission, $user)) {
            abort(403, __('This action is unauthorized.'));
        }
    }
}
