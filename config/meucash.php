<?php

return [
    'admin' => [
        'name' => env('ADMIN_NAME', 'Administrador'),
        'email' => env('ADMIN_EMAIL', 'admin@meucash.local'),
        'password' => env('ADMIN_PASSWORD', 'admin123'),
    ],

    'product_expense_categories' => [
        'manutencao' => 'Manutenção',
        'pecas' => 'Peças',
        'limpeza' => 'Limpeza',
        'acessorios' => 'Acessórios',
        'reparo' => 'Reparo',
        'frete' => 'Frete',
        'outros' => 'Outros',
    ],

    'payable_categories' => [
        'compras' => 'Compras',
        'manutencao' => 'Manutenção',
        'hospedagem' => 'Hospedagem',
        'dominio' => 'Domínio',
        'publicidade' => 'Publicidade',
        'equipamentos' => 'Equipamentos',
        'outros' => 'Outros',
    ],

    'transaction_categories' => [
        'entrada' => [
            'outros_recebimentos' => 'Outros recebimentos',
            'aporte' => 'Aporte',
            'reembolso' => 'Reembolso',
        ],
        'saida' => [
            'despesa' => 'Despesa',
            'retirada' => 'Retirada',
            'estorno' => 'Estorno',
            'outros_pagamentos' => 'Outros pagamentos',
        ],
    ],

    'origins' => [
        'venda' => 'Venda',
        'parcela' => 'Parcela',
        'servico' => 'Serviço',
        'compra_produto' => 'Compra de produto',
        'despesa_produto' => 'Despesa de produto',
        'despesa_servico' => 'Despesa de serviço',
        'conta_pagar' => 'Conta paga',
        'manual' => 'Lançamento manual',
    ],

    'whatsapp' => [
        'driver' => env('WHATSAPP_DRIVER', 'link'),
        'api_token' => env('WHATSAPP_API_TOKEN'),
        'phone_number_id' => env('WHATSAPP_PHONE_NUMBER_ID'),
    ],
];
