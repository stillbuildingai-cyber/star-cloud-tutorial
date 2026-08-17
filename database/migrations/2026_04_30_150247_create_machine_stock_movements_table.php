<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('machine_stock_movements', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->nullable()->index();
            $table->unsignedBigInteger('machine_id')->index();
            $table->unsignedBigInteger('product_id')->nullable()->index();
            $table->string('slot_no', 20);

            // 異動類型：補貨入庫、取貨碼消耗、遠端出貨、盤點調整、出貨失敗回退
            $table->enum('type', [
                'replenishment',   // 補貨單完成
                'pickup',          // 取貨碼核銷
                'remote_dispense', // 遠端出貨指令 (B055 樂觀扣除)
                'adjustment',      // B009 盤點回報 / 後台手動修改
                'rollback',        // B055 出貨失敗，庫存回退
            ]);

            $table->integer('quantity');       // 異動數量（正數=增加，負數=減少）
            $table->integer('before_qty');     // 異動前數量
            $table->integer('after_qty');      // 異動後數量

            // 多型關聯（可關聯補貨單、取貨碼、遠端指令等）
            $table->string('reference_type')->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->index(['reference_type', 'reference_id']);

            $table->text('note')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();

            $table->timestamps();

            // 複合索引：查詢特定機台某時段的異動
            $table->index(['machine_id', 'slot_no', 'created_at']);
            // 多租戶過濾 + 類型篩選
            $table->index(['company_id', 'type', 'created_at']);

            $table->foreign('machine_id')->references('id')->on('machines')->cascadeOnDelete();
            $table->foreign('product_id')->references('id')->on('products')->nullOnDelete();
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('machine_stock_movements');
    }
};
