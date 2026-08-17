<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 建立倉庫庫存表（每個倉庫各商品的即時庫存）
     */
    public function up(): void
    {
        Schema::create('warehouse_stocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('warehouse_id')->constrained()->cascadeOnDelete()->comment('所屬倉庫');
            $table->foreignId('product_id')->constrained()->comment('商品');
            $table->integer('quantity')->default(0)->comment('現有庫存數量');
            $table->integer('safety_stock')->default(0)->comment('安全庫存量');
            $table->timestamps();

            // 唯一約束：同一倉庫同一商品只能有一筆記錄
            $table->unique(['warehouse_id', 'product_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('warehouse_stocks');
    }
};
