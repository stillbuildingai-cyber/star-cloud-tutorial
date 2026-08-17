<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 取貨碼 / 通行碼 批次新增：以 batch_no 標記同一批產生的碼，供後台批次下載清單使用。
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pickup_codes', function (Blueprint $table) {
            $table->string('batch_no', 32)->nullable()->after('slug')->index();
        });

        Schema::table('pass_codes', function (Blueprint $table) {
            $table->string('batch_no', 32)->nullable()->after('slug')->index();
        });
    }

    public function down(): void
    {
        Schema::table('pickup_codes', function (Blueprint $table) {
            $table->dropIndex(['batch_no']);
            $table->dropColumn('batch_no');
        });

        Schema::table('pass_codes', function (Blueprint $table) {
            $table->dropIndex(['batch_no']);
            $table->dropColumn('batch_no');
        });
    }
};
