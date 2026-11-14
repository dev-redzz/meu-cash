<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('ncm', 8)->nullable();
            $table->string('cest', 7)->nullable();
            $table->string('cfop', 4)->nullable();
            $table->string('csosn', 3)->nullable();
            $table->unsignedTinyInteger('origin')->default(0);
            $table->string('unit', 6)->default('UN');
        });

        // Notas fiscais (NFC-e) emitidas para as vendas.
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sale_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedTinyInteger('model')->default(65);
            $table->string('environment', 12);
            $table->unsignedSmallInteger('series');
            $table->unsignedInteger('number');
            $table->string('status', 15)->default('pendente');
            $table->string('provider_id', 60)->nullable()->index();
            $table->string('access_key', 44)->nullable();
            $table->string('protocol', 30)->nullable();
            $table->decimal('total', 12, 2)->default(0);
            $table->text('message')->nullable();
            $table->timestamp('authorized_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();
            $table->unique(['company_id', 'environment', 'model', 'series', 'number']);
            $table->index(['company_id', 'sale_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoices');
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['ncm', 'cest', 'cfop', 'csosn', 'origin', 'unit']);
        });
    }
};
