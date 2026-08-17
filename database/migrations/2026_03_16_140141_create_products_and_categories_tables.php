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
        Schema::create('product_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained('companies')->onDelete('cascade');
            $table->string('name');
            $table->string('name_dictionary_key')->nullable()->comment('多語系 Key');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained('companies')->onDelete('cascade');
            $table->foreignId('category_id')->nullable()->constrained('product_categories')->onDelete('set null');
            $table->string('name');
            $table->string('name_dictionary_key')->nullable()->comment('多語系 Key');
            $table->string('sku')->nullable()->comment('商品編號');
            $table->decimal('price', 10, 2)->default(0)->comment('售價');
            $table->decimal('cost', 10, 2)->nullable()->comment('成本');
            $table->string('image')->nullable()->comment('圖片 URL');
            $table->string('barcode')->nullable()->comment('條碼');
            $table->boolean('is_timer_product')->default(false)->comment('是否為計時型商品');
            $table->boolean('is_active')->default(true)->comment('是否上架');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['company_id', 'sku']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
        Schema::dropIfExists('product_categories');
    }
};
