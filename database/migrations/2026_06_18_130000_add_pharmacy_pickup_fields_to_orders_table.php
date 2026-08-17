<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 領藥單模組：orders 增加訂單類型/批價單號/建單者欄位。
     * order_type 預設 sale，既有資料一律視為一般銷售，不影響線上行為。
     */
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (!Schema::hasColumn('orders', 'order_type')) {
                $table->string('order_type', 20)->default('sale')->index()
                    ->comment('訂單類型: sale=一般銷售, pharmacy_pickup=領藥單')->after('order_no');
            }
            if (!Schema::hasColumn('orders', 'pricing_slip_no')) {
                $table->string('pricing_slip_no', 64)->nullable()
                    ->comment('批價單號 (領藥單藥師填寫之依據)')->after('order_type');
            }
            if (!Schema::hasColumn('orders', 'created_by')) {
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete()
                    ->comment('建單者 (領藥單由後台藥師建立)')->after('pricing_slip_no');
            }
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (Schema::hasColumn('orders', 'created_by')) {
                $table->dropForeign(['created_by']);
                $table->dropColumn('created_by');
            }
            if (Schema::hasColumn('orders', 'pricing_slip_no')) {
                $table->dropColumn('pricing_slip_no');
            }
            if (Schema::hasColumn('orders', 'order_type')) {
                $table->dropColumn('order_type');
            }
        });
    }
};
