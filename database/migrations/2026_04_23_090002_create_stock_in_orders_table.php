<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 建立入庫單 + 入庫單明細
     */
    public function up(): void
    {
        Schema::create('stock_in_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained()->comment('所屬租戶');
            $table->foreignId('warehouse_id')->constrained()->comment('入庫目標倉庫');
            $table->string('order_no')->unique()->comment('入庫單號');
            $table->enum('status', ['draft', 'completed'])->default('draft')->comment('狀態');
            $table->text('note')->nullable()->comment('備註');
            $table->foreignId('created_by')->constrained('users')->comment('建立人');
            $table->timestamp('completed_at')->nullable()->comment('入庫完成時間');
            $table->timestamps();
            $table->softDeletes();

            // 索引
            $table->index(['company_id', 'status']);
            $table->index(['company_id', 'created_at']);
        });

        Schema::create('stock_in_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stock_in_order_id')->constrained()->cascadeOnDelete()->comment('所屬入庫單');
            $table->foreignId('product_id')->constrained()->comment('商品');
            $table->integer('quantity')->comment('入庫數量');
            $table->string('batch_no')->nullable()->comment('批號');
            $table->date('expiry_date')->nullable()->comment('有效期限');
            $table->timestamps();

            // 索引
            $table->index('stock_in_order_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_in_order_items');
        Schema::dropIfExists('stock_in_orders');
    }
};
