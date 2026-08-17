<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 現金支付面額明細：orders 增加 cash_detail JSON 欄位。
     * 記錄本筆現金交易「收」進的各面額張數（仟/伍佰/佰/50/10/5/1），
     * 供銷售紀錄在支付金額下方顯示「收：仟:x、伍佰:x…　找:x」。
     * 找零金額沿用既有 change_amount，不另存。
     * 僅現金(payment_type=9)交易會帶此欄，其餘交易為 null，不影響既有行為。
     */
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (!Schema::hasColumn('orders', 'cash_detail')) {
                $table->json('cash_detail')->nullable()
                    ->comment('現金收款面額明細 {b1000,b500,b100,c50,c10,c5,c1}')
                    ->after('change_amount');
            }
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (Schema::hasColumn('orders', 'cash_detail')) {
                $table->dropColumn('cash_detail');
            }
        });
    }
};
