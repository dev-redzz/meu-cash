<?php

namespace App\Services;

use App\Enums\CategoryType;
use App\Enums\UserRole;
use App\Models\Category;
use App\Models\Company;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CompanyProvisioner
{
    public const DEFAULT_CATEGORIES = ['Celulares', 'Acessórios', 'Eletrônicos', 'Peças', 'Outros'];

    public function create(array $companyData, array $owner): User
    {
        return DB::transaction(function () use ($companyData, $owner) {
            $company = Company::create([
                'name' => $companyData['name'],
                'document' => $companyData['document'] ?? null,
                'phone' => $companyData['phone'] ?? null,
                'active' => true,
            ]);

            $user = User::create([
                'company_id' => $company->id,
                'name' => $owner['name'],
                'email' => mb_strtolower($owner['email']),
                'google_id' => $owner['google_id'] ?? null,
                'avatar' => $owner['avatar'] ?? null,
                'role' => UserRole::Admin,
                'active' => true,
            ]);

            tenant()->run($company, function () {
                foreach (Setting::DEFAULTS as $key => $value) {
                    Setting::firstOrCreate(['key' => $key], ['value' => $value]);
                }

                foreach (self::DEFAULT_CATEGORIES as $name) {
                    Category::firstOrCreate(['name' => $name, 'type' => CategoryType::Produto->value]);
                }
            });

            return $user;
        });
    }
}
