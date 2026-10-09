<?php

return [
    'public_key'     => env('PAYMONGO_PUBLIC_KEY', ''),
    'secret_key'     => env('PAYMONGO_SECRET_KEY', ''),
    'webhook_secret' => env('PAYMONGO_WEBHOOK_SECRET', ''),
    'api_url'        => 'https://api.paymongo.com/v1',
];
