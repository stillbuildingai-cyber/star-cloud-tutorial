<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 領藥單一張單多貨道：slot_no 改為可空。
     * 一般取貨碼仍寫入 slot_no（單貨道）；領藥單的多貨道由 order_items × machine_slots 解析，
     * 故 slot_no 允許為 null，讀取端依 order_type 區分。
     */
    public function up(): void
    {
        Schema::table('pickup_codes', function (Blueprint $table) {
            $table->string('slot_no')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('pickup_codes', function (Blueprint $table) {
            $table->string('slot_no')->nullable(false)->change();
        });
    }
};
