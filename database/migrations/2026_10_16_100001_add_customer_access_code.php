<?php

use App\Models\Customer;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->string('access_code', 12)->nullable()->unique()->after('notes');
        });

        DB::table('customers')->whereNull('access_code')->orderBy('id')->pluck('id')->each(function ($id) {
            DB::table('customers')->where('id', $id)->update(['access_code' => Customer::newAccessCode()]);
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropUnique(['access_code']);
            $table->dropColumn('access_code');
        });
    }
};
