<?php

return [
    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
        'scheme' => 'https',
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    // Configuración de Facebook
    'facebook' => [
        'app_id' => env('603275022244128'),
        'app_secret' => env('f54aa934d5b30c295299bb76390e3806'),
        'access_token' => env('EAAIkrOlnGSABO4jrpCxyeH8WtHZAwKfCxheh6BSbOOxXWZAB2l3eUY2Tye0gpJfjCsuxiEr8MkWXenwLVSKixHZC8HraT46kl3QGKr2pBLwscZAc8qbdZCiuoi6APZBNCvGmDTxpgBNFuPuuccmqO7oTGBfg9HaZBkOsaIZCTL6GPZCd9jxEHAxo87FtuDZCgVj1NUGlq7UT03Mvk4D7U0JJab32Pne5HiWjoZD'),
        'ad_account_id' => env('933248667753162'),
        'api_version' => 'v21.0',
    ],
];
