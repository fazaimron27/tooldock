<?php

return [
    'probe_header' => 'X-Sandbox-Probe',
    'token_header' => 'X-Sandbox-Token',
    'token' => env('SANDBOX_INBOUND_TOKEN', null),
    'cache_key' => 'sandbox:inbound:entries',
    'max_entries' => 50,
    'apply_queue' => env('SANDBOX_APPLY_QUEUE', 'sandbox-apply'),
    'review_queue' => env('SANDBOX_REVIEW_QUEUE', 'sandbox-review'),
];
