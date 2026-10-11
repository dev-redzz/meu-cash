<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('barcode', 50)->nullable()->after('model');
            $table->index(['company_id', 'barcode']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->decimal('commission_rate', 5, 2)->default(0)->after('role');
            $table->decimal('monthly_goal', 12, 2)->default(0)->after('commission_rate');
        });

        Schema::table('sales', function (Blueprint $table) {
            $table->decimal('commission_rate', 5, 2)->default(0)->after('profit');
            $table->decimal('commission', 12, 2)->default(0)->after('commission_rate');
        });
    }

    public function down(): void
    {
        Schema::table('sales', fn (Blueprint $table) => $table->dropColumn(['commission_rate', 'commission']));
        Schema::table('users', fn (Blueprint $table) => $table->dropColumn(['commission_rate', 'monthly_goal']));
        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex(['company_id', 'barcode']);
            $table->dropColumn('barcode');
        });
    }
};
