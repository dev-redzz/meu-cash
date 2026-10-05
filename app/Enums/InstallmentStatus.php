<?php

namespace App\Enums;

enum InstallmentStatus: string
{
    use HasOptions;

    case Pendente = 'pendente';
    case Pago = 'pago';
    case Vencido = 'vencido';
    case Cancelado = 'cancelado';

    public function label(): string
    {
        return match ($this) {
            self::Pendente => 'Pendente',
            self::Pago => 'Pago',
            self::Vencido => 'Vencido',
            self::Cancelado => 'Cancelado',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Pendente => 'warning',
            self::Pago => 'success',
            self::Vencido => 'danger',
            self::Cancelado => 'secondary',
        };
    }

    public static function open(): array
    {
        return [self::Pendente->value, self::Vencido->value];
    }
}
