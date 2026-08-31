<?php

declare(strict_types=1);

return [
    'name'              => 'Kraved',
    'tagline'           => 'Taste the sweetness in every bite.',
    // Change this to match how you open the site in the browser (no trailing slash)
    'url'               => getenv('APP_URL') ?: 'https://kraveddesserts.com',
    'env'               => getenv('APP_ENV') ?: 'local',
    'debug'             => true,
    'timezone'          => 'Europe/London',
    'currency_symbol'   => '£',
    'free_delivery_threshold' => 25.00,
    'session_name'      => 'kraved_session',
    'upload_max_mb'     => 5,
    'order_prefix'      => 'KRV',
];
