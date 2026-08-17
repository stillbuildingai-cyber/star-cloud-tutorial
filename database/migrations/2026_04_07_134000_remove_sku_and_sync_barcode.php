<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. 從 products 表移除 sku
        Schema::table('products', function (Blueprint $table) {
            // 因為公司外鍵正在使用這個複合索引，必須先補一個獨立索引給公司
            $table->index('company_id');
            // 現在可以安全移除含有 sku 的索引了
            $table->dropIndex(['company_id', 'sku']);
            $table->dropColumn('sku');
        });

        // 2. 從 order_items 表移除 sku 並新增 barcode
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropColumn('sku');
            $table->string('barcode')->nullable()->after('product_name')->comment('商品條碼 (備份)');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropColumn('barcode');
            $table->string('sku')->nullable()->after('product_name')->comment('商品編號 (備份)');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->string('sku')->nullable()->after('name_dictionary_key')->comment('商品編號');
            $table->index(['company_id', 'sku']);
        });
    }
};
