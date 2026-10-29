<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Planos e assinaturas foram retirados por enquanto.
 * Apaga o que a cobrança tinha criado, se já tiver sido criado no banco.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('online_payments');

        if (Schema::hasColumn('companies', 'mp_webhook_key')) {
            Schema::table('companies', fn (Blueprint $table) => $table->dropUnique(['mp_webhook_key']));
        }

        $columns = array_values(array_filter(
            ['plan_period', 'plan_expires_at', 'plan_exempt', 'mp_access_token', 'mp_account', 'mp_webhook_key'],
            fn ($column) => Schema::hasColumn('companies', $column),
        ));

        if ($columns) {
            Schema::table('companies', fn (Blueprint $table) => $table->dropColumn($columns));
        }

        DB::table('notifications')->whereIn('type', ['plano', 'conta_proxima', 'conta_vencida'])->delete();
        DB::table('settings')->where('key', 'portal_payments')->delete();
    }

    public function down(): void
    {
    }
};
