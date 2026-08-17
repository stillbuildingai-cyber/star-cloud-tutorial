<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 儲值回饋規則
     */
    public function up(): void
    {
        Schema::create('deposit_bonus_rules', function (Blueprint $table) {
            $table->id();
            $table->string('name')->comment('規則名稱');
            $table->decimal('min_amount', 12, 2)->comment('最低儲值金額');
            $table->enum('bonus_type', ['fixed', 'percentage'])->comment('回饋類型');
            $table->decimal('bonus_value', 12, 2)->comment('回饋值');
            $table->boolean('is_active')->default(true);
            $table->datetime('start_at')->nullable()->comment('開始時間');
            $table->datetime('end_at')->nullable()->comment('結束時間');
            $table->timestamps();

            $table->index(['is_active', 'start_at', 'end_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('deposit_bonus_rules');
    }
};
