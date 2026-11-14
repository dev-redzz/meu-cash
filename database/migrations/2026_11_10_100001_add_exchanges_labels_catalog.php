<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Trocas: o cliente devolve peças da venda e leva outras.
        Schema::create('sale_exchanges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sale_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->json('returned');
            $table->json('added');
            $table->decimal('returned_total', 12, 2)->default(0);
            $table->decimal('added_total', 12, 2)->default(0);
            $table->decimal('difference', 12, 2)->default(0);
            $table->string('settlement', 20);
            $table->string('payment_method', 20)->nullable();
            $table->string('notes', 500)->nullable();
            $table->timestamps();
        });

        // Catálogo online
        Schema::table('companies', function (Blueprint $table) {
            $table->string('catalog_slug', 60)->nullable()->unique();
        });

        Schema::table('products', function (Blueprint $table) {
            $table->boolean('in_catalog')->default(true);
        });
    }

    public function down(): void
    {
        Schema::table('products', fn (Blueprint $table) => $table->dropColumn('in_catalog'));
        Schema::table('companies', function (Blueprint $table) {
            $table->dropUnique(['catalog_slug']);
            $table->dropColumn('catalog_slug');
        });
        Schema::dropIfExists('sale_exchanges');
    }
};
