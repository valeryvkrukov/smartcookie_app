<?php

return [
    'stripe' => [
        'currency' => env('PAYMENTS_CURRENCY', 'usd'),
        'amount' => env('PAYMENTS_STRIPE_AMOUNT', 10000),
        'description' => env('PAYMENTS_STRIPE_DESCRIPTION', 'Tutoring Credits (Top-up)'),
    ],

    'venmo' => [
        'username' => env('PAYMENTS_VENMO_USERNAME', '@SmartCookieTutors'),
        'note' => env('PAYMENTS_VENMO_NOTE', 'SmartCookie Credits'),
    ],

    'zelle' => [
        'email' => env('PAYMENTS_ZELLE_EMAIL', 'payments@smartcookie.com'),
        'note' => env('PAYMENTS_ZELLE_NOTE', 'SmartCookie Credits'),
    ],
];
