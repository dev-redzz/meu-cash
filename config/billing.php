<?php

return [
    // A cobrança do plano só liga quando o Access Token da sua conta Mercado Pago estiver configurado.
    'enabled' => (bool) env('BILLING_ENABLED', true),
    'mp_access_token' => env('MERCADOPAGO_ACCESS_TOKEN'),

    'trial_days' => (int) env('PLAN_TRIAL_DAYS', 14),
    'grace_days' => (int) env('PLAN_GRACE_DAYS', 3),

    'plans' => [
        'mensal' => [
            'label' => 'Mensal',
            'price' => (float) env('PLAN_PRICE_MONTHLY', 49.90),
            'months' => 1,
        ],
        'anual' => [
            'label' => 'Anual',
            'price' => (float) env('PLAN_PRICE_YEARLY', 479.00),
            'months' => 12,
        ],
    ],
];
