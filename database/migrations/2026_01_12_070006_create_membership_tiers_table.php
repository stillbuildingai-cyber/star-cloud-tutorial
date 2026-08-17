<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 會員等級定義
     */
    public function up(): void
    {
        Schema::create('membership_tiers', function (Blueprint $table) {
            $table->id();
            $table->string('name')->comment('等級名稱');
            $table->decimal('annual_fee', 12, 2)->default(0)->comment('年費金額');
            $table->decimal('discount_rate', 4, 2)->default(1.00)->comment('折扣比例(0.95=95折)');
            $table->decimal('point_multiplier', 4, 2)->default(1.00)->comment('點數倍率');
            $table->text('description')->nullable()->comment('說明');
            $table->boolean('is_default')->default(false)->comment('是否為預設等級');
            $table->integer('sort_order')->default(0)->comment('排序');
            $table->timestamps();

            $table->index('is_default');
            $table->index('sort_order');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('membership_tiers');
    }
};
