<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 新增建單時快照欄位，記錄當下機台庫存狀態
     */
    public function up(): void
    {
        Schema::table('replenishment_order_items', function (Blueprint $table) {
            $table->integer('current_stock')->default(0)->after('quantity')->comment('建單時的機台庫存');
            $table->integer('max_stock')->default(0)->after('current_stock')->comment('貨道最大容量');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('replenishment_order_items', function (Blueprint $table) {
            $table->dropColumn(['current_stock', 'max_stock']);
        });
    }
};
