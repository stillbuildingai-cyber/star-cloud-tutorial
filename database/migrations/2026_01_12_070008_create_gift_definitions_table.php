<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 禮品/福利定義
     */
    public function up(): void
    {
        Schema::create('gift_definitions', function (Blueprint $table) {
            $table->id();
            $table->string('name')->comment('禮品名稱');
            $table->enum('type', ['points', 'coupon', 'product', 'discount', 'cash'])->comment('禮品類型');
            $table->decimal('value', 12, 2)->default(0)->comment('數值');
            $table->foreignId('tier_id')->nullable()->constrained('membership_tiers')->nullOnDelete()->comment('適用等級');
            $table->enum('trigger', ['register', 'birthday', 'annual', 'upgrade', 'manual'])->comment('觸發條件');
            $table->integer('validity_days')->default(30)->comment('有效天數');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['is_active', 'trigger']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gift_definitions');
    }
};
