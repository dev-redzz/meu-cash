<?php

namespace App\Enums;

enum CategoryType: string
{
    use HasOptions;

    case Produto = 'produto';

    public function label(): string
    {
        return 'Produtos';
    }

    public function color(): string
    {
        return 'primary';
    }
}
