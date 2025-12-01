<?php

return [
    'name' => 'AuditLog',

    /*
    |--------------------------------------------------------------------------
    | Audit Log Retention
    |--------------------------------------------------------------------------
    |
    | This option controls how long audit logs are retained in the database.
    | The value is specified in days. Audit logs older than this value
    | can be cleaned up using the auditlog:cleanup command.
    |
    */

    'retention_days' => env('AUDITLOG_RETENTION_DAYS', 90),

    /*
    |--------------------------------------------------------------------------
    | Enable Scheduled Cleanup
    |--------------------------------------------------------------------------
    |
    | When enabled, the audit log cleanup command will run automatically
    | on a schedule defined in the service provider.
    |
    */

    'scheduled_cleanup_enabled' => env('AUDITLOG_SCHEDULED_CLEANUP_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Model Types Cache TTL
    |--------------------------------------------------------------------------
    |
    | The time-to-live for the cached model types list in seconds.
    | This cache is used to avoid querying the database on every index load.
    |
    */

    'model_types_cache_ttl' => env('AUDITLOG_MODEL_TYPES_CACHE_TTL', 3600),

    /*
    |--------------------------------------------------------------------------
    | Export Chunk Size
    |--------------------------------------------------------------------------
    |
    | The number of records to process at a time during CSV export.
    | Larger values use more memory but may be faster. Smaller values
    | use less memory but may be slower for very large exports.
    |
    */

    'export_chunk_size' => env('AUDITLOG_EXPORT_CHUNK_SIZE', 500),
];
