<?php

return [
    'name' => 'Groups',

    /*
    |--------------------------------------------------------------------------
    | Large Group Threshold
    |--------------------------------------------------------------------------
    |
    | Groups with more members than this threshold will use count placeholders
    | in audit logs instead of loading all member names. This improves
    | performance for large groups by avoiding expensive queries.
    |
    */
    'large_group_threshold' => env('GROUPS_LARGE_GROUP_THRESHOLD', 100),

    /*
    |--------------------------------------------------------------------------
    | Member Data Chunk Size
    |--------------------------------------------------------------------------
    |
    | When loading member data for audit logs, this is the chunk size used
    | to prevent memory exhaustion with very large groups.
    |
    */
    'member_data_chunk_size' => env('GROUPS_MEMBER_DATA_CHUNK_SIZE', 1000),
];
