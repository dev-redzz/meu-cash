<?php

return [
    // Conta da plataforma na Nuvem Fiscal (https://www.nuvemfiscal.com.br). Cada loja vira uma "empresa" dentro dela.
    'client_id' => env('NUVEMFISCAL_CLIENT_ID'),
    'client_secret' => env('NUVEMFISCAL_CLIENT_SECRET'),

    // true = ambiente de testes da Nuvem Fiscal (sandbox). Use enquanto estiver configurando.
    'sandbox' => (bool) env('NUVEMFISCAL_SANDBOX', true),

    'token_url' => 'https://auth.nuvemfiscal.com.br/oauth/token',
    'api_url' => 'https://api.nuvemfiscal.com.br',
    'sandbox_url' => 'https://api.sandbox.nuvemfiscal.com.br',
    'scope' => 'empresa cep nfce',
];
