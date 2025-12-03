<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Dashboard Widget Cache Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for dashboard widget caching behavior.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Cache TTL (Time To Live)
    |--------------------------------------------------------------------------
    |
    | The number of seconds to cache widget computations.
    | Set to 0 to disable caching entirely.
    | Default: 300 seconds (5 minutes)
    |
    */

    'cache_ttl' => env('DASHBOARD_CACHE_TTL', 300),

    /*
    |--------------------------------------------------------------------------
    | Cache Tags
    |--------------------------------------------------------------------------
    |
    | Whether to use cache tags for selective invalidation.
    | Requires a cache driver that supports tags (Redis, Memcached).
    | If false, cache invalidation will use Cache::flush() for all widgets.
    |
    */

    'use_cache_tags' => env('DASHBOARD_USE_CACHE_TAGS', true),

    /*
    |--------------------------------------------------------------------------
    | Cache Tag Name
    |--------------------------------------------------------------------------
    |
    | The base cache tag name used for all dashboard widgets.
    | Widgets are tagged with both this global tag and a module-specific tag
    | (e.g., 'dashboard_widgets:blog') to enable selective invalidation.
    |
    | - Global tag: 'dashboard_widgets' - clears all widgets
    | - Module tag: 'dashboard_widgets:blog' - clears only Blog widgets
    |
    */

    'cache_tag' => 'dashboard_widgets',

    /*
    |--------------------------------------------------------------------------
    | Cache Key Environment Prefix
    |--------------------------------------------------------------------------
    |
    | Whether to prefix cache keys with the application environment.
    * This prevents cache collisions when multiple environments (dev/staging/prod)
    * share the same Redis instance.
    |
    | Default: true (enabled)
    |
    */

    'use_environment_prefix' => env('DASHBOARD_USE_ENV_PREFIX', true),
];
