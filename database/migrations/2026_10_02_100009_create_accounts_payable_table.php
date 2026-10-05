<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('accounts_payable', function (Blueprint $table) {
            $table->id();
            $table->string('description');
            $table->string('category', 30)->index();
            $table->decimal('amount', 12, 2);
            $table->date('due_date')->index();
            $table->date('paid_at')->nullable();
            $table->string('status', 20)->default('pendente')->index();
            $table->string('payment_method', 20)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('accounts_payable');
    }
};
