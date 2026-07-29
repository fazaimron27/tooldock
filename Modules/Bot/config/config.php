<?php

return [
    'name' => 'Bot',

    // Public-facing base URL used when generating signed connect links that must
    // be reachable by external services (Telegram, Discord).
    // Set BOT_CONNECT_URL to your tunnel URL (e.g. ngrok) in local development
    // when APP_URL is http://localhost and therefore not publicly accessible.
    'connect_url' => env('BOT_CONNECT_URL', env('APP_URL', 'http://localhost')),
];
