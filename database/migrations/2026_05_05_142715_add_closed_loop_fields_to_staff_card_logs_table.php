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
        Schema::table('staff_card_logs', function (Blueprint $table) {
            $table->integer('payment_type')->after('machine_id')->nullable()->comment('支付/通行類型: 5:Pass, 11:Pickup, 41:Staff');
            $table->unsignedBigInteger('code_id')->after('payment_type')->nullable()->comment('對應資料表的ID');
            $table->unsignedBigInteger('order_id')->after('code_id')->nullable()->index()->comment('關聯訂單ID');
            $table->string('status')->after('order_id')->default('verified')->comment('狀態: verified, completed');
            
            // 讓原本的欄位可空，因為之後統一用 code_id
            $table->unsignedBigInteger('staff_card_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('staff_card_logs', function (Blueprint $table) {
            $table->dropColumn(['payment_type', 'code_id', 'order_id', 'status']);
            $table->unsignedBigInteger('staff_card_id')->nullable(false)->change();
        });
    }
};
