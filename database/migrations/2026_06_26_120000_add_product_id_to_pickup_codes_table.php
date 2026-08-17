<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pickup_codes', function (Blueprint $table) {
            // 取貨碼改綁「商品」而非「貨道」：發碼時只指定機台+商品，
            // 刷碼當下由 App 依本機即時庫存/鎖定/效期挑可出貨道（slot_no 對綁商品碼為 null）。
            // 既有綁貨道碼（slot_no 有值、product_id 為 null）行為不變，向後相容。
            $table->unsignedBigInteger('product_id')->nullable()->after('machine_id')
                ->comment('綁定商品（取貨碼改綁商品時使用；貨道由 App 刷碼當下挑）');
            $table->index('product_id');
        });
    }

    public function down(): void
    {
        Schema::table('pickup_codes', function (Blueprint $table) {
            $table->dropIndex(['product_id']);
            $table->dropColumn('product_id');
        });
    }
};
