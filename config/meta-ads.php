<?php

return [
    'app_id' => env('META_APP_ID'),
    'app_secret' => env('META_APP_SECRET'),
    'access_token' => encrypt(env('META_ACCESS_TOKEN')),
    'account_id' => env('META_AD_ACCOUNT_ID'),
]; 