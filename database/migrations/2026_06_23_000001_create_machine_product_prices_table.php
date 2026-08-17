<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 機台專屬定價：針對「特定機台 × 特定商品」覆蓋全域 products.price / member_price。
     *
     * - 無紀錄（或欄位為 NULL）= 沿用全域商品價（products.price / member_price）。
     * - B012 下發時，命中的商品會把 t063v03(機台價) / t060v30(會員價) 換成此處的值。
     * - 粒度為 (machine_id, product_id)：App 端價格模型是 product-level，同商品占多貨道共用同一價。
     */
    public function up(): void
    {
        Schema::create('machine_product_prices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('machine_id')->constrained('machines')->onDelete('cascade');
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
            $table->decimal('price', 10, 2)->nullable()->comment('機台專屬售價；NULL=用全域 products.price');
            $table->decimal('member_price', 10, 2)->nullable()->comment('機台專屬會員價；NULL=用全域 member_price（並夾為不高於機台售價）');
            $table->timestamps();

            // 一台機台對同一商品只有一筆覆蓋價
            $table->unique(['machine_id', 'product_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('machine_product_prices');
    }
};
