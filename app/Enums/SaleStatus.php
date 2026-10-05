<?php

namespace App\Enums;

enum SaleStatus: string
{
    use HasOptions;

    case Concluida = 'concluida';
    case Cancelada = 'cancelada';

    public function label(): string
    {
        return match ($this) {
            self::Concluida => 'Concluída',
            self::Cancelada => 'Cancelada',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Concluida => 'success',
            self::Cancelada => 'danger',
        };
    }
}
