<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Super administrator role
    |--------------------------------------------------------------------------
    |
    | Users with this Spatie role bypass all authorization checks via
    | Gate::before in AuthServiceProvider.
    |
    */
    'super_admin_role' => env('AUTH_SUPER_ADMIN_ROLE', 'Super Admin'),

    /*
    |--------------------------------------------------------------------------
    | Roles that may access the admin panel (session "staff")
    |--------------------------------------------------------------------------
    */
    'staff_roles' => [
        'Super Admin',
        'admin',
        'technician',
        'sales',
        'kit_assigner',
    ],

    /*
    |--------------------------------------------------------------------------
    | Legacy column mapping
    |--------------------------------------------------------------------------
    |
    | The users.role string column is kept in sync with the primary Spatie role
    | for filters, exports, and backward compatibility.
    |
    */
    'legacy_admin_role_values' => [
        'admin',
        'Admin',
        'Super Admin',
        'technician',
        'sales',
        'kit_assigner',
        'Manager',
    ],

    /*
    |--------------------------------------------------------------------------
    | Roles that cannot be deleted or renamed from the UI
    |--------------------------------------------------------------------------
    */
    'protected_role_names' => [
        'Super Admin',
        'admin',
        'technician',
        'kit_assigner',
        'user',
    ],
];
