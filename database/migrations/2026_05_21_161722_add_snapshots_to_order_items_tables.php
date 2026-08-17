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
        Schema::table('transfer_order_items', function (Blueprint $table) {
            $table->string('snapshot_product_name')->nullable()->after('quantity');
            $table->string('snapshot_product_name_key')->nullable()->after('snapshot_product_name');
            $table->string('snapshot_product_barcode')->nullable()->after('snapshot_product_name_key');
            $table->string('snapshot_product_image_url')->nullable()->after('snapshot_product_barcode');
        });

        Schema::table('replenishment_order_items', function (Blueprint $table) {
            $table->string('snapshot_product_name')->nullable()->after('max_stock');
            $table->string('snapshot_product_name_key')->nullable()->after('snapshot_product_name');
            $table->string('snapshot_product_barcode')->nullable()->after('snapshot_product_name_key');
            $table->string('snapshot_product_image_url')->nullable()->after('snapshot_product_barcode');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transfer_order_items', function (Blueprint $table) {
            $table->dropColumn([
                'snapshot_product_name',
                'snapshot_product_name_key',
                'snapshot_product_barcode',
                'snapshot_product_image_url'
            ]);
        });

        Schema::table('replenishment_order_items', function (Blueprint $table) {
            $table->dropColumn([
                'snapshot_product_name',
                'snapshot_product_name_key',
                'snapshot_product_barcode',
                'snapshot_product_image_url'
            ]);
        });
    }
};
