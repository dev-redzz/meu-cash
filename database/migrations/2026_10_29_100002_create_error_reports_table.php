<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('error_reports', function (Blueprint $table) {
            $table->id();
            $table->string('fingerprint', 40)->unique();
            $table->string('class');
            $table->text('message');
            $table->string('file')->nullable();
            $table->unsignedInteger('line')->nullable();
            $table->string('url', 500)->nullable();
            $table->string('method', 10)->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('company_id')->nullable();
            $table->text('trace')->nullable();
            $table->unsignedInteger('count')->default(1);
            $table->timestamp('first_seen_at');
            $table->timestamp('last_seen_at')->index();
            $table->timestamp('notified_at')->nullable();
            $table->timestamp('resolved_at')->nullable()->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('error_reports');
    }
};
