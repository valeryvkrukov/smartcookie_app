<?php

return [
    'default_rate_per_credit' => env('PAYMENTS_DEFAULT_RATE_PER_CREDIT') ? (float) env('PAYMENTS_DEFAULT_RATE_PER_CREDIT') : null,

    'stripe' => [
        'currency' => env('PAYMENTS_CURRENCY', 'usd'),
        'amount' => env('PAYMENTS_STRIPE_AMOUNT', 10000),
        'description' => env('PAYMENTS_STRIPE_DESCRIPTION', 'Tutoring Credits (Top-up)'),
    ],

    'venmo' => [
        'username' => env('PAYMENTS_VENMO_USERNAME', '@sofifed'),
        'note' => env('PAYMENTS_VENMO_NOTE', 'Tutoring Credits (Top-up)'),
    ],

    'zelle' => [
        'phone' => env('PAYMENTS_ZELLE_PHONE', '410-952-4967'),
        'note'  => env('PAYMENTS_ZELLE_NOTE', 'Tutoring Credits (Top-up)'),
    ],
];
