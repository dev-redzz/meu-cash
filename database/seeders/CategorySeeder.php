<?php

namespace Database\Seeders;

use App\Enums\CategoryType;
use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            CategoryType::Produto->value => ['iPhone', 'Celulares', 'Fones', 'Notebooks', 'Peças', 'Acessórios'],
            CategoryType::Servico->value => ['Desenvolvimento de sites', 'Manutenção', 'Hospedagem', 'Domínio', 'Desenvolvimento de sistemas', 'Outros'],
        ];

        foreach ($categories as $type => $names) {
            foreach ($names as $name) {
                Category::firstOrCreate(['name' => $name, 'type' => $type]);
            }
        }
    }
}
