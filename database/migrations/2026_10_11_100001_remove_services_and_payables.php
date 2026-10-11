<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('installments', 'service_id')) {
            DB::table('payments')->whereNotNull('service_id')->delete();
            DB::table('installments')->whereNotNull('service_id')->delete();

            Schema::table('payments', function (Blueprint $table) {
                $table->dropForeign(['service_id']);
                $table->dropColumn('service_id');
            });

            Schema::table('installments', function (Blueprint $table) {
                $table->dropForeign(['service_id']);
                $table->dropColumn('service_id');
            });
        }

        Schema::dropIfExists('service_expenses');
        Schema::dropIfExists('services');
        Schema::dropIfExists('accounts_payable');

        DB::table('categories')->where('type', 'servico')->delete();
    }

    public function down(): void
    {
    }
};
