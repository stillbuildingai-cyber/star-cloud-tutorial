<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 為交易訂單與電子發票各自新增「管理者備註」欄位。
 * - orders.remark：訂單內部備註（出貨／客訴等）。
 * - invoices.remark：發票內部備註（作廢視窗亦寫入此欄，不影響送綠界的 void_reason）。
 * 兩欄皆 nullable，對既有資料零影響。
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->text('remark')->nullable()->after('metadata');
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->text('remark')->nullable()->after('void_reason');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('remark');
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn('remark');
        });
    }
};
