<?php

namespace App\Services;

use App\Contracts\AuthorizationServiceContract;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Auth\Authenticatable;

class AuthorizationService implements AuthorizationServiceContract
{
    public function userCan(Authenticatable $user, string $permission): bool
    {
        return $user->can($permission);
    }

    public function userHasRole(Authenticatable $user, string $role): bool
    {
        return method_exists($user, 'hasRole') && $user->hasRole($role);
    }

    public function userHasAnyPermission(Authenticatable $user, array $permissions): bool
    {
        return method_exists($user, 'hasAnyPermission') && $user->hasAnyPermission($permissions);
    }

    public function userHasAllPermissions(Authenticatable $user, array $permissions): bool
    {
        return method_exists($user, 'hasAllPermissions') && $user->hasAllPermissions($permissions);
    }

    public function authorizePermission(Authenticatable $user, string $permission): void
    {
        if (! $this->userCan($user, $permission)) {
            throw new AuthorizationException(__('This action is unauthorized.'));
        }
    }
}
