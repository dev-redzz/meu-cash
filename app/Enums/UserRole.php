<?php

namespace App\Enums;

enum UserRole: string
{
    use HasOptions;

    case Admin = 'admin';
    case Funcionario = 'funcionario';

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Administrador',
            self::Funcionario => 'Funcionário',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Admin => 'dark',
            self::Funcionario => 'secondary',
        };
    }
}
