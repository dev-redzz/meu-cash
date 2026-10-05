<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name')->index();
            $table->string('brand', 100)->nullable()->index();
            $table->string('model', 100)->nullable();
            $table->date('purchase_date')->nullable()->index();
            $table->decimal('purchase_price', 12, 2)->default(0);
            $table->decimal('expenses_total', 12, 2)->default(0);
            $table->decimal('sale_price', 12, 2)->default(0);
            $table->unsignedInteger('initial_quantity')->default(1);
            $table->unsignedInteger('quantity')->default(1);
            $table->string('status', 20)->default('disponivel')->index();
            $table->text('notes')->nullable();
            $table->json('photos')->nullable();
            $table->timestamps();
        });

        Schema::create('product_expenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('description');
            $table->string('category', 30)->index();
            $table->decimal('amount', 12, 2);
            $table->date('date')->index();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_expenses');
        Schema::dropIfExists('products');
    }
};
