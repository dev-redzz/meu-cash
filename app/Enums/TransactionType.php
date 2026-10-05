<?php

namespace App\Enums;

enum TransactionType: string
{
    use HasOptions;

    case Entrada = 'entrada';
    case Saida = 'saida';

    public function label(): string
    {
        return match ($this) {
            self::Entrada => 'Entrada',
            self::Saida => 'Saída',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Entrada => 'success',
            self::Saida => 'danger',
        };
    }
}
