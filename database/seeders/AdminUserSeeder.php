<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        if (User::where('role', UserRole::Admin->value)->exists()) {
            return;
        }

        User::create([
            'name' => config('meucash.admin.name'),
            'email' => config('meucash.admin.email'),
            'password' => config('meucash.admin.password'),
            'role' => UserRole::Admin,
            'active' => true,
        ]);
    }
}
