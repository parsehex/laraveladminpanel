<?php

namespace App\Support;

use App\Models\User;

class PanelRedirector
{
    /**
     * @var array<int, array{permission: string, route: string}>
     */
    private const DESTINATIONS = [
        ['permission' => 'admin.dashboard', 'route' => 'admin.dashboard'],
        ['permission' => 'trucks.view', 'route' => 'admin.trucks.index'],
        ['permission' => 'inventory.view', 'route' => 'admin.inventory.index'],
        ['permission' => 'parts.view', 'route' => 'admin.parts.index'],
        ['permission' => 'models.view', 'route' => 'admin.models.index'],
        ['permission' => 'sales.view', 'route' => 'admin.sales.index'],
        ['permission' => 'deliveries.view', 'route' => 'admin.deliveries.index'],
        ['permission' => 'kits.view', 'route' => 'admin.kits.index'],
        ['permission' => 'users.view', 'route' => 'admin.users.index'],
        ['permission' => 'roles.view', 'route' => 'admin.roles.index'],
    ];

    public static function routeNameFor(User $user): string
    {
        foreach (self::DESTINATIONS as $destination) {
            if ($user->can($destination['permission'])) {
                return $destination['route'];
            }
        }

        return 'admin.profile.edit';
    }
}
