<?php

use Illuminate\Contracts\Auth\Authenticatable;

if (! function_exists('canAccess')) {
    function canAccess(string $permission, ?Authenticatable $user = null): bool
    {
        $user = $user ?? auth()->user();

        return $user && $user->can($permission);
    }
}

if (! function_exists('hasRole')) {
    function hasRole(string $role, ?Authenticatable $user = null): bool
    {
        $user = $user ?? auth()->user();

        return $user && method_exists($user, 'hasRole') && $user->hasRole($role);
    }
}

if (! function_exists('hasAnyPermission')) {
    /**
     * @param  array<int, string>  $permissions
     */
    function hasAnyPermission(array $permissions, ?Authenticatable $user = null): bool
    {
        $user = $user ?? auth()->user();

        return $user && method_exists($user, 'hasAnyPermission') && $user->hasAnyPermission($permissions);
    }
}

if (! function_exists('hasAllPermissions')) {
    /**
     * @param  array<int, string>  $permissions
     */
    function hasAllPermissions(array $permissions, ?Authenticatable $user = null): bool
    {
        $user = $user ?? auth()->user();

        return $user && method_exists($user, 'hasAllPermissions') && $user->hasAllPermissions($permissions);
    }
}
