<?php

namespace App\Contracts;

use Illuminate\Contracts\Auth\Authenticatable;

interface AuthorizationServiceContract
{
    public function userCan(Authenticatable $user, string $permission): bool;

    public function userHasRole(Authenticatable $user, string $role): bool;

    /**
     * @param  array<int, string>  $permissions
     */
    public function userHasAnyPermission(Authenticatable $user, array $permissions): bool;

    /**
     * @param  array<int, string>  $permissions
     */
    public function userHasAllPermissions(Authenticatable $user, array $permissions): bool;

    /**
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function authorizePermission(Authenticatable $user, string $permission): void;
}
