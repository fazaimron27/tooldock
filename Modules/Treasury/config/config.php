<?php

return [
    'name' => 'Treasury',

    /*
    |--------------------------------------------------------------------------
    | Exchange Rate API Configuration
    |--------------------------------------------------------------------------
    |
    | Configure the ExchangeRate-API for currency conversion.
    | Get your free API key at: https://www.exchangerate-api.com/
    |
    */

    'exchange_rate_api_key' => env('EXCHANGE_RATE_API_KEY'),

    // How often to refresh rates (in hours). Default: 24 hours for free tier.
    'exchange_rate_refresh_hours' => env('EXCHANGE_RATE_REFRESH_HOURS', 24),
];
