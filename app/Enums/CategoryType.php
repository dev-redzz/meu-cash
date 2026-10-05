<?php

namespace App\Enums;

enum CategoryType: string
{
    use HasOptions;

    case Produto = 'produto';
    case Servico = 'servico';

    public function label(): string
    {
        return match ($this) {
            self::Produto => 'Produtos',
            self::Servico => 'Serviços',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Produto => 'primary',
            self::Servico => 'info',
        };
    }
}
