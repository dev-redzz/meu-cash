<?php

namespace App\Enums;

enum ServiceStatus: string
{
    use HasOptions;

    case Orcamento = 'orcamento';
    case EmAndamento = 'em_andamento';
    case Concluido = 'concluido';
    case Cancelado = 'cancelado';

    public function label(): string
    {
        return match ($this) {
            self::Orcamento => 'Orçamento',
            self::EmAndamento => 'Em andamento',
            self::Concluido => 'Concluído',
            self::Cancelado => 'Cancelado',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Orcamento => 'info',
            self::EmAndamento => 'warning',
            self::Concluido => 'success',
            self::Cancelado => 'danger',
        };
    }
}
