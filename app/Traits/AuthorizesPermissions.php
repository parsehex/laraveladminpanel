<?php

namespace App\Traits;

use Illuminate\Auth\Access\AuthorizationException;

trait AuthorizesPermissions
{
    /**
     * Authorize the current user for a Spatie permission string (registered on the Gate).
     *
     * @throws AuthorizationException
     */
    protected function authorizePermission(string $permission, ?string $message = null): void
    {
        if (! auth()->check() || ! auth()->user()->can($permission)) {
            throw new AuthorizationException($message ?? __('This action is unauthorized.'));
        }
    }
}
