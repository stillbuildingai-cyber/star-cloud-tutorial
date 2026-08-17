<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 建立補貨單 + 補貨單明細
     */
    public function up(): void
    {
        Schema::create('replenishment_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained()->comment('所屬租戶');
            $table->string('order_no')->unique()->comment('補貨單號');
            $table->foreignId('warehouse_id')->constrained()->comment('出貨倉庫');
            $table->unsignedBigInteger('machine_id')->comment('目標機台');
            $table->enum('status', ['pending', 'prepared', 'delivering', 'completed', 'cancelled'])->default('pending')->comment('狀態');
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete()->comment('指派補貨人員');
            $table->text('note')->nullable()->comment('備註');
            $table->foreignId('created_by')->constrained('users')->comment('建立人');
            $table->timestamp('completed_at')->nullable()->comment('完成時間');
            $table->timestamps();
            $table->softDeletes();

            // 外鍵
            $table->foreign('machine_id')->references('id')->on('machines');

            // 索引
            $table->index(['company_id', 'status']);
            $table->index(['company_id', 'created_at']);
            $table->index('machine_id');
            $table->index('assigned_to');
        });

        Schema::create('replenishment_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('replenishment_order_id')->constrained()->cascadeOnDelete()->comment('所屬補貨單');
            $table->foreignId('product_id')->constrained()->comment('商品');
            $table->string('slot_no')->nullable()->comment('貨道編號');
            $table->integer('quantity')->comment('補貨數量');
            $table->timestamps();

            // 索引
            $table->index('replenishment_order_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('replenishment_order_items');
        Schema::dropIfExists('replenishment_orders');
    }
};
