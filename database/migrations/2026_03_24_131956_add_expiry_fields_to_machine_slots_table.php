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
        Schema::table('machine_slots', function (Blueprint $table) {
            $table->date('expiry_date')->nullable()->after('max_stock')->comment('商品效期');
            $table->string('batch_no')->nullable()->after('expiry_date')->comment('補貨批號');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('machine_slots', function (Blueprint $table) {
            $table->dropColumn(['expiry_date', 'batch_no']);
        });
    }
};
