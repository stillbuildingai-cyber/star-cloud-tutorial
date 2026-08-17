<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 建立庫存異動紀錄表
     */
    public function up(): void
    {
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained()->comment('所屬租戶');
            $table->foreignId('warehouse_id')->constrained()->comment('異動倉庫');
            $table->foreignId('product_id')->constrained()->comment('商品');
            $table->enum('type', [
                'in',           // 入庫
                'out',          // 出庫（補貨扣減）
                'adjust',       // 盤點調整
                'damage',       // 報損
                'transfer_in',  // 調撥入庫
                'transfer_out', // 調撥出庫
            ])->comment('異動類型');
            $table->integer('quantity')->comment('異動數量（正數入庫、負數出庫）');
            $table->integer('before_qty')->comment('異動前數量');
            $table->integer('after_qty')->comment('異動後數量');
            $table->string('reference_type')->nullable()->comment('關聯單據類型（多態）');
            $table->unsignedBigInteger('reference_id')->nullable()->comment('關聯單據 ID');
            $table->string('note')->nullable()->comment('備註/原因');
            $table->foreignId('created_by')->constrained('users')->comment('操作人');
            $table->timestamp('created_at')->useCurrent();

            // 索引：高頻查詢
            $table->index(['company_id', 'created_at']);
            $table->index(['warehouse_id', 'product_id']);
            $table->index(['reference_type', 'reference_id']);
            $table->index('type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
    }
};
