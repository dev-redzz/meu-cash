<?php

namespace App\Enums;

enum PaymentMethod: string
{
    use HasOptions;

    case Dinheiro = 'dinheiro';
    case Pix = 'pix';
    case Debito = 'debito';
    case Credito = 'credito';
    case Transferencia = 'transferencia';
    case Parcelado = 'parcelado';
    case Outro = 'outro';

    public function label(): string
    {
        return match ($this) {
            self::Dinheiro => 'Dinheiro',
            self::Pix => 'Pix',
            self::Debito => 'Débito',
            self::Credito => 'Crédito',
            self::Transferencia => 'Transferência',
            self::Parcelado => 'Parcelado',
            self::Outro => 'Outro',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Dinheiro => 'success',
            self::Pix => 'info',
            self::Debito => 'primary',
            self::Credito => 'primary',
            self::Transferencia => 'info',
            self::Parcelado => 'warning',
            self::Outro => 'secondary',
        };
    }

    public static function receiving(): array
    {
        return array_diff_key(self::options(), [self::Parcelado->value => true]);
    }
}
