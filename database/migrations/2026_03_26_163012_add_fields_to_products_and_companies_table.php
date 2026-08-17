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
        // 擴充 products 表
        Schema::table('products', function (Blueprint $table) {
            $table->string('spec')->nullable()->after('name')->comment('規格');
            $table->string('manufacturer')->nullable()->after('barcode')->comment('生產公司');
            $table->integer('track_limit')->default(0)->after('manufacturer')->comment('履帶貨道上限');
            $table->integer('spring_limit')->default(0)->after('track_limit')->comment('彈簧貨道上限');
            $table->decimal('member_price', 10, 2)->default(0)->after('price')->comment('會員價');
            $table->json('metadata')->nullable()->after('is_active')->comment('進階 Metadata (點數、物料代碼等)');
        });

        // 擴充 companies 表
        Schema::table('companies', function (Blueprint $table) {
            $table->json('settings')->nullable()->after('note')->comment('客戶功能設定 (Feature Toggles)');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn([
                'spec',
                'manufacturer',
                'track_limit',
                'spring_limit',
                'member_price',
                'metadata'
            ]);
        });

        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn('settings');
        });
    }
};
