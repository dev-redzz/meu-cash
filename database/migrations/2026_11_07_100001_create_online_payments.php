<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Pix gerado pelo cliente na área do cliente, para pagar uma parcela.
        Schema::create('online_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('installment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->string('provider_id', 40)->nullable()->index();
            $table->string('status', 20)->default('pendente');
            $table->decimal('amount', 12, 2);
            $table->text('qr_code')->nullable();
            $table->longText('qr_code_base64')->nullable();
            $table->string('ticket_url', 500)->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('checked_at')->nullable();
            $table->timestamps();
            $table->index(['company_id', 'installment_id', 'status']);
        });

        // Pagamentos da assinatura das lojas (vão para a conta do dono da plataforma).
        Schema::create('subscription_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('plan', 20);
            $table->decimal('amount', 12, 2);
            $table->string('status', 20)->default('pendente');
            $table->string('preference_id', 80)->nullable();
            $table->string('provider_id', 40)->nullable()->index();
            $table->string('method', 30)->nullable();
            $table->date('period_start')->nullable();
            $table->date('period_end')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });

        Schema::table('companies', function (Blueprint $table) {
            $table->date('paid_until')->nullable();
            $table->boolean('billing_exempt')->default(false);
        });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn(['paid_until', 'billing_exempt']);
        });
        Schema::dropIfExists('subscription_payments');
        Schema::dropIfExists('online_payments');
    }
};
