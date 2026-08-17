<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 建立調撥單 + 調撥單明細（含倉庫間調撥 & 機台退回倉庫）
     */
    public function up(): void
    {
        Schema::create('transfer_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained()->comment('所屬租戶');
            $table->string('order_no')->unique()->comment('調撥單號');
            $table->enum('type', ['warehouse_to_warehouse', 'machine_to_warehouse'])->comment('調撥類型');
            $table->foreignId('from_warehouse_id')->nullable()->constrained('warehouses')->comment('來源倉庫');
            $table->unsignedBigInteger('from_machine_id')->nullable()->comment('來源機台（機台退回時）');
            $table->foreignId('to_warehouse_id')->constrained('warehouses')->comment('目標倉庫');
            $table->enum('status', ['draft', 'pending', 'in_transit', 'completed', 'cancelled'])->default('draft')->comment('狀態');
            $table->text('note')->nullable()->comment('備註');
            $table->foreignId('created_by')->constrained('users')->comment('建立人');
            $table->timestamp('completed_at')->nullable()->comment('完成時間');
            $table->timestamps();
            $table->softDeletes();

            // 外鍵：from_machine_id 手動建立（因為可能為 null 且跨表）
            $table->foreign('from_machine_id')->references('id')->on('machines')->nullOnDelete();

            // 索引
            $table->index(['company_id', 'status']);
            $table->index(['company_id', 'created_at']);
            $table->index('type');
        });

        Schema::create('transfer_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transfer_order_id')->constrained()->cascadeOnDelete()->comment('所屬調撥單');
            $table->foreignId('product_id')->constrained()->comment('商品');
            $table->integer('quantity')->comment('調撥數量');
            $table->timestamps();

            // 索引
            $table->index('transfer_order_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transfer_order_items');
        Schema::dropIfExists('transfer_orders');
    }
};
