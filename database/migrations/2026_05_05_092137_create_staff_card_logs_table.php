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
        Schema::create('staff_card_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('staff_card_id')->index()->comment('員工卡片ID');
            $table->unsignedBigInteger('machine_id')->index()->nullable()->comment('機台ID');
            $table->string('action_type')->default('swipe')->comment('動作類型');
            $table->json('raw_data')->nullable()->comment('原始API資料');
            $table->timestamp('created_at')->useCurrent()->comment('發生時間');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('staff_card_logs');
    }
};
