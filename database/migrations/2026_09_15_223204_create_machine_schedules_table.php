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
        Schema::create('machine_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('machine_id')->constrained()->cascadeOnDelete();
            // 沿用 RemoteCommand 既有的 command_type 命名（power_on / power_off），
            // 排程觸發時直接把這個值當成 command_type 推進既有的下指令佇列，不用另外做轉換對照表。
            $table->enum('action', ['power_on', 'power_off']);
            // 星期幾要觸發，用逗號分隔的字串存（1=一...7=日，對應 Carbon::dayOfWeekIso）
            // 例如 "1,2,3,4,5" 代表週一到週五。存字串而不是拆成 7 個布林欄位，
            // 一來查詢時可以直接用 FIND_IN_SET 濾掉不必要的比對，二來欄位不會隨著需求長胖。
            $table->string('days_of_week');
            $table->time('time');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['is_active', 'time']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('machine_schedules');
    }
};
