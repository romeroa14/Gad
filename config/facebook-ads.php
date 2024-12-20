<?php

return [
    'app_id' => env('FACEBOOK_APP_ID'),
    'app_secret' => env('FACEBOOK_APP_SECRET'),
    'access_token' => env('FACEBOOK_ACCESS_TOKEN'),
    'account_id' => env('FACEBOOK_AD_ACCOUNT_ID'),
    'sync_interval' => env('FACEBOOK_SYNC_INTERVAL', 60), // minutos
];
