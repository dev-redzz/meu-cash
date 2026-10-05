<?php

namespace App\Enums;

enum ProductStatus: string
{
    use HasOptions;

    case Disponivel = 'disponivel';
    case Reservado = 'reservado';
    case Vendido = 'vendido';
    case Manutencao = 'manutencao';
    case Cancelado = 'cancelado';

    public function label(): string
    {
        return match ($this) {
            self::Disponivel => 'Disponível',
            self::Reservado => 'Reservado',
            self::Vendido => 'Vendido',
            self::Manutencao => 'Em manutenção',
            self::Cancelado => 'Cancelado',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Disponivel => 'success',
            self::Reservado => 'info',
            self::Vendido => 'secondary',
            self::Manutencao => 'warning',
            self::Cancelado => 'danger',
        };
    }

    public static function inStock(): array
    {
        return [self::Disponivel->value, self::Reservado->value, self::Manutencao->value];
    }
}
