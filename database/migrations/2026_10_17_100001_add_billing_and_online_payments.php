<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->string('plan_period', 20)->nullable()->after('active');
            $table->timestamp('plan_expires_at')->nullable()->after('plan_period');
            $table->boolean('plan_exempt')->default(false)->after('plan_expires_at');
            $table->text('mp_access_token')->nullable()->after('plan_exempt');
            $table->string('mp_account', 120)->nullable()->after('mp_access_token');
            $table->string('mp_webhook_key', 40)->nullable()->unique()->after('mp_account');
        });

        DB::table('companies')->update([
            'plan_period' => 'teste',
            'plan_expires_at' => now()->addDays((int) config('billing.trial_days', 14)),
        ]);

        Schema::create('online_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('kind', 20);
            $table->string('external_reference', 64)->unique();
            $table->string('preference_id', 80)->nullable();
            $table->string('mp_payment_id', 40)->nullable();
            $table->foreignId('installment_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('period', 20)->nullable();
            $table->decimal('amount', 12, 2);
            $table->string('status', 20)->default('criado');
            $table->string('method', 30)->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'kind']);
            $table->index(['kind', 'mp_payment_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('online_payments');
        Schema::table('companies', function (Blueprint $table) {
            $table->dropUnique(['mp_webhook_key']);
            $table->dropColumn(['plan_period', 'plan_expires_at', 'plan_exempt', 'mp_access_token', 'mp_account', 'mp_webhook_key']);
        });
    }
};
