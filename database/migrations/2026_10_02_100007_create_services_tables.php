<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('services', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->date('date')->index();
            $table->decimal('amount', 12, 2)->default(0);
            $table->decimal('expenses_total', 12, 2)->default(0);
            $table->decimal('down_payment', 12, 2)->default(0);
            $table->string('payment_method', 20);
            $table->unsignedSmallInteger('installments_count')->default(0);
            $table->string('status', 20)->default('em_andamento')->index();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('service_expenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_id')->constrained()->cascadeOnDelete();
            $table->string('description');
            $table->decimal('amount', 12, 2);
            $table->date('date');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_expenses');
        Schema::dropIfExists('services');
    }
};
