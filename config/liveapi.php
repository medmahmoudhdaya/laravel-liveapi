<?php

return [
    /**
     * Enable or disable traffic capture.
     * Even if true, it will be hard-disabled in production.
     */
    'enabled' => env('LIVEAPI_ENABLED', true),

    /**
     * Stop schema evolution. Set to true once your documentation is stable.
     */
    'frozen' => env('LIVEAPI_FROZEN', false),

    /**
     * The directory where snapshots and the openapi.json will be stored.
     */
    'storage_path' => storage_path('liveapi'),

    /**
     * Sensitive headers that should never be recorded.
     */
    'except_headers' => [
        'authorization',
        'cookie',
        'x-csrf-token',
        'x-xsrf-token',
    ],

    /**
     * Mask sensitive request/response body fields.
     */
    'mask_fields' => [
        'password',
        'password_confirmation',
        'token',
        'card_number',
    ],
];