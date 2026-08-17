<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            // 消費者選擇的貨道/格子號（格子櫃=櫃號如 501，VMC=貨道號）。
            // 由 App transaction finalize 的 items[].slot_no 帶入；不管出貨成功或失敗都記錄。
            $table->string('slot_no', 20)->nullable()->after('product_name');
        });
    }

    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropColumn('slot_no');
        });
    }
};
