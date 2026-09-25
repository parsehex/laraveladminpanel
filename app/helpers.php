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

if (! function_exists('sidebarFolderIds')) {
    /**
     * @return list<string>
     */
    function sidebarFolderIds(): array
    {
        return ['inventory', 'kits', 'manage', 'procedures', 'users'];
    }
}

if (! function_exists('isApplianceInventoryNavActive')) {
    function isApplianceInventoryNavActive(): bool
    {
        return request()->routeIs('admin.inventory.*')
            && ! request()->routeIs('admin.inventory.scan*')
            && ! request()->routeIs('admin.inventory.testing*');
    }
}

if (! function_exists('isInventoryNavFolderActive')) {
    function isInventoryNavFolderActive(): bool
    {
        return isApplianceInventoryNavActive() || request()->routeIs('admin.inventory.scan*');
    }
}

if (! function_exists('isKitsNavFolderActive')) {
    function isKitsNavFolderActive(): bool
    {
        return request()->routeIs('admin.kit-parts.*');
    }
}

if (! function_exists('isProceduresNavFolderActive')) {
    function isProceduresNavFolderActive(): bool
    {
        return request()->routeIs('admin.deman-flows.*', 'admin.testing-flows.*');
    }
}

if (! function_exists('isUsersNavFolderActive')) {
    function isUsersNavFolderActive(): bool
    {
        return request()->routeIs(
            'admin.roles.*',
            'admin.notification-settings.*',
            'admin.user-actions.*',
        );
    }
}

if (! function_exists('isManageNavFolderActive')) {
    function isManageNavFolderActive(): bool
    {
        return request()->routeIs(
            'admin.users.*',
            'admin.categories.*',
            'admin.inventory-statuses.*',
            'admin.inventory-locations.*',
        ) || isProceduresNavFolderActive() || isUsersNavFolderActive();
    }
}

if (! function_exists('sidebarFoldersOpenForRequest')) {
    /**
     * Folders that must be open on first paint because the current page is inside them.
     * A link that is also a folder (Kits, Users) opens for a child page, not for its own page.
     *
     * @return list<string>
     */
    function sidebarFoldersOpenForRequest(): array
    {
        $open = [];

        if (isInventoryNavFolderActive()) {
            $open[] = 'inventory';
        }

        if (isKitsNavFolderActive()) {
            $open[] = 'kits';
        }

        if (isManageNavFolderActive()) {
            $open[] = 'manage';
        }

        if (isProceduresNavFolderActive()) {
            $open[] = 'procedures';
        }

        if (isUsersNavFolderActive()) {
            $open[] = 'users';
        }

        return $open;
    }
}
