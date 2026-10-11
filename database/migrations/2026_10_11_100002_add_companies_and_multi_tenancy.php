<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const TENANT_TABLES = [
        'settings', 'audit_logs', 'customers', 'categories', 'products', 'product_expenses',
        'sales', 'sale_items', 'installments', 'payments', 'financial_transactions',
        'notifications', 'whatsapp_messages',
    ];

    public function up(): void
    {
        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('document', 20)->nullable();
            $table->string('phone', 20)->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('company_id')->nullable()->after('id')->constrained()->cascadeOnDelete();
            $table->string('google_id')->nullable()->unique()->after('email');
            $table->string('avatar')->nullable()->after('google_id');
            $table->string('password')->nullable()->change();
        });

        Schema::create('company_invitations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('email')->index();
            $table->string('role', 20)->default('funcionario');
            $table->foreignId('invited_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('accepted_at')->nullable();
            $table->timestamps();
            $table->unique(['company_id', 'email']);
        });

        foreach (self::TENANT_TABLES as $name) {
            Schema::table($name, function (Blueprint $table) {
                $table->foreignId('company_id')->nullable()->after('id')->constrained()->cascadeOnDelete();
            });
        }

        $this->moveExistingDataToFirstCompany();

        Schema::table('settings', function (Blueprint $table) {
            $table->dropUnique(['key']);
            $table->unique(['company_id', 'key']);
        });

        Schema::table('categories', function (Blueprint $table) {
            $table->dropUnique(['name', 'type']);
            $table->unique(['company_id', 'name', 'type']);
        });

        Schema::table('sales', function (Blueprint $table) {
            $table->dropUnique(['code']);
            $table->unique(['company_id', 'code']);
        });

        Schema::table('notifications', function (Blueprint $table) {
            $table->dropUnique(['key']);
            $table->unique(['company_id', 'key']);
        });
    }

    private function moveExistingDataToFirstCompany(): void
    {
        $hasData = DB::table('users')->exists() || DB::table('sales')->exists() || DB::table('products')->exists();

        if (! $hasData) {
            return;
        }

        $name = DB::table('settings')->where('key', 'company_name')->value('value') ?: 'Minha empresa';
        $phone = DB::table('settings')->where('key', 'company_phone')->value('value') ?: null;

        $companyId = DB::table('companies')->insertGetId([
            'name' => $name,
            'phone' => $phone,
            'active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('users')->update(['company_id' => $companyId]);

        foreach (self::TENANT_TABLES as $table) {
            DB::table($table)->update(['company_id' => $companyId]);
        }

        DB::table('settings')->whereIn('key', ['company_name', 'company_phone'])->delete();

        $owner = mb_strtolower(trim((string) env('OWNER_EMAIL', '')));

        if ($owner !== '' && ! DB::table('users')->where('email', $owner)->exists()) {
            $adminId = DB::table('users')->where('role', 'admin')->orderBy('id')->value('id');

            if ($adminId) {
                DB::table('users')->where('id', $adminId)->update(['email' => $owner]);
            }
        }
    }

    public function down(): void
    {
    }
};
