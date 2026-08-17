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
        Schema::table('machines', function (Blueprint $table) {
            if (!Schema::hasColumn('machines', 'tax_invoice_enabled')) {
                $table->boolean('tax_invoice_enabled')->default(0)->after('invoice_status')->comment('電子發票開關');
            }
            if (!Schema::hasColumn('machines', 'card_terminal_enabled')) {
                $table->boolean('card_terminal_enabled')->default(0)->after('card_reader_no')->comment('刷卡機系統開關');
            }
            if (!Schema::hasColumn('machines', 'scan_pay_esun_enabled')) {
                $table->boolean('scan_pay_esun_enabled')->default(0)->after('card_terminal_enabled')->comment('玉山掃碼支付開關');
            }
            if (!Schema::hasColumn('machines', 'scan_pay_linepay_enabled')) {
                $table->boolean('scan_pay_linepay_enabled')->default(0)->after('scan_pay_esun_enabled')->comment('LinePay支付開關');
            }
            if (!Schema::hasColumn('machines', 'shopping_cart_enabled')) {
                $table->boolean('shopping_cart_enabled')->default(0)->after('scan_pay_linepay_enabled')->comment('購物車功能開關');
            }
            if (!Schema::hasColumn('machines', 'welcome_gift_enabled')) {
                $table->boolean('welcome_gift_enabled')->default(0)->after('shopping_cart_enabled')->comment('迎賓禮功能開關');
            }
            if (!Schema::hasColumn('machines', 'cash_module_enabled')) {
                $table->boolean('cash_module_enabled')->default(0)->after('welcome_gift_enabled')->comment('現金模組開關');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('machines', function (Blueprint $table) {
            $table->dropColumn([
                'tax_invoice_enabled',
                'card_terminal_enabled',
                'scan_pay_esun_enabled',
                'scan_pay_linepay_enabled',
                'shopping_cart_enabled',
                'welcome_gift_enabled',
                'cash_module_enabled',
            ]);
        });
    }
};
