<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/** Marca como resolvidos os erros corrigidos nesta versão. Se algum voltar, ele reaparece na lista. */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('error_reports')) {
            return;
        }

        DB::table('error_reports')->whereNull('resolved_at')
            ->where(function ($q) {
                $q->where('message', 'like', '%ZipArchive%')
                    ->orWhere('message', 'like', '%Unsupported file format: gz%')
                    ->orWhere('message', 'like', '%Mercado Pago: Payment not found%');
            })
            ->update(['resolved_at' => now()]);
    }

    public function down(): void
    {
    }
};
