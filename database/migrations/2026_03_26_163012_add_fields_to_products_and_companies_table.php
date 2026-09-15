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
        // 擴充 companies 表
        // 註：products 表擴充區塊（spec/manufacturer/track_limit/spring_limit/member_price/metadata）
        // 已隨教學版移除商品/販賣機模組而拿掉，companies.settings 為智慧插座教學版仍在用的
        // 客戶功能設定 (品牌自訂等)，予以保留。
        Schema::table('companies', function (Blueprint $table) {
            $table->json('settings')->nullable()->after('note')->comment('客戶功能設定 (Feature Toggles)');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn('settings');
        });
    }
};
