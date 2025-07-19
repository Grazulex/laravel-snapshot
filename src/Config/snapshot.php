<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Default Storage Driver
    |--------------------------------------------------------------------------
    |
    | This option controls the default storage driver that will be used
    | to store snapshot data. Supported: "database", "file", "array"
    |
    */
    'default' => 'database',

    /*
    |--------------------------------------------------------------------------
    | Storage Drivers
    |--------------------------------------------------------------------------
    |
    | Here you may configure as many snapshot storage drivers as you wish.
    | You may even configure multiple drivers of the same type.
    |
    */
    'drivers' => [
        'database' => [
            'driver' => 'database',
            'table' => 'snapshots',
        ],

        'file' => [
            'driver' => 'file',
            'path' => storage_path('snapshots'),
        ],

        'array' => [
            'driver' => 'array',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Serialization Options
    |--------------------------------------------------------------------------
    |
    | Configure how models should be serialized by default
    |
    */
    'serialization' => [
        'include_hidden' => false,
        'include_timestamps' => true,
        'include_relationships' => true,
        'max_relationship_depth' => 3,
    ],

    /*
    |--------------------------------------------------------------------------
    | Snapshot Retention
    |--------------------------------------------------------------------------
    |
    | Configure how long snapshots should be kept
    |
    */
    'retention' => [
        'enabled' => true,
        'days' => 30,
        'auto_cleanup' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Automatic Snapshots
    |--------------------------------------------------------------------------
    |
    | Configure automatic snapshot creation on model events
    |
    */
    'automatic' => [
        'enabled' => false,
        'events' => ['created', 'updated', 'deleted'],
        'exclude_fields' => ['updated_at', 'created_at', 'password', 'remember_token'],
        'models' => [
            // 'App\Models\User' => ['created', 'updated'],
            // 'App\Models\Order' => ['created', 'updated', 'deleted'],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Scheduled Snapshots
    |--------------------------------------------------------------------------
    |
    | Configure scheduled snapshot creation via cron
    |
    */
    'scheduled' => [
        'enabled' => false,
        'default_frequency' => 'daily',
        'models' => [
            // 'App\Models\User' => 'daily',
            // 'App\Models\Order' => 'hourly',
            // 'App\Models\Invoice' => 'weekly',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Reports & Analytics
    |--------------------------------------------------------------------------
    |
    | Configure snapshot reporting and analytics features
    |
    */
    'reports' => [
        'enabled' => true,
        'formats' => ['html', 'json', 'csv'],
        'template' => 'default',
        'max_timeline_entries' => 100,
        'include_diffs' => true,
    ],
];
