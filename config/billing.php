<?php

return [
    // Liga a cobrança da assinatura das lojas. Enquanto estiver false, o Meu Cash é grátis para todos (versão beta).
    'enabled' => (bool) env('BILLING_ENABLED', false),

    // Dia em que você ligou a cobrança: as lojas que já existiam ganham o teste grátis a partir desta data.
    'starts_at' => env('BILLING_STARTS_AT'),

    'trial_days' => (int) env('BILLING_TRIAL_DAYS', 14),
    'grace_days' => (int) env('BILLING_GRACE_DAYS', 5),

    'plans' => [
        'mensal' => [
            'label' => 'Mensal',
            'price' => (float) env('BILLING_PRICE_MONTHLY', 39.90),
            'months' => 1,
        ],
        'anual' => [
            'label' => 'Anual',
            'price' => (float) env('BILLING_PRICE_YEARLY', 399.00),
            'months' => 12,
        ],
    ],

    // Conta do Mercado Pago que recebe as assinaturas (a SUA conta, dona da plataforma).
    'mercadopago_token' => env('MP_ACCESS_TOKEN'),
];
