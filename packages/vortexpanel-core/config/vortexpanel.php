<?php

return [
    'path' => 'admin',

    // Auth guard to use
    'guard' => 'web',

    // Required permission to access admin (fallback if no policy)
    'access_permission' => env('VORTEXPANEL_ACCESS_PERMISSION', 'vortexpanel.access'),

    // Spatie permission defaults for auto-permission creation
    'permissions' => [
        'enabled' => true,
        'guard' => env('VORTEXPANEL_GUARD', 'web'),
        'admin_role' => env('VORTEXPANEL_ADMIN_ROLE', 'admin'),

        // CRUD abilities aligned with Laravel policy names
        'crud_abilities' => ['viewAny', 'view', 'create', 'update', 'delete'],

        // Auto behavior
        'create_on_make_resource' => true,
        'assign_to_admin_role' => true,
    ],

    // Middlewares applied to the admin route group
    'middleware' => ['web', 'auth', 'vortexpanel.access'],

    // Register your resources here (or later you can add auto-discovery)
    'resources' => [
        // App\VortexPanel\Resources\UserResource::class,
    ],

    // Cache
    'cache' => [
        'enabled' => true,
        'ttl_seconds' => 300,
        'prefix' => 'vortexpanel',
    ],

    // UI
    'ui' => [
        'brand' => 'VortexPanel',
        'default_theme' => 'dark',
    ],
];
