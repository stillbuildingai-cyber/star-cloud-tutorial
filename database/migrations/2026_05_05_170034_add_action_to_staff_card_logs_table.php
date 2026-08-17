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
            $table->string('action')->nullable()->after('order_id')->comment('執行動作');
            $table->string('remark')->nullable()->after('action')->comment('備註');
        });

        // 資料遷移
        DB::table('staff_card_logs')->where('status', 'verified')->where('action_type', 'swipe')->update(['action' => 'verify_success']);
        DB::table('staff_card_logs')->where('status', 'completed')->where('action_type', 'consume')->update(['action' => 'consume']);

        Schema::table('staff_card_logs', function (Blueprint $table) {
            $table->dropColumn(['status', 'action_type']);
        });
    }

    public function down(): void
    {
        Schema::table('staff_card_logs', function (Blueprint $table) {
            $table->string('status')->nullable()->after('order_id');
            $table->string('action_type')->nullable()->after('status');
        });

        // 恢復資料
        DB::table('staff_card_logs')->where('action', 'verify_success')->update(['status' => 'verified', 'action_type' => 'swipe']);
        DB::table('staff_card_logs')->where('action', 'consume')->update(['status' => 'completed', 'action_type' => 'consume']);

        Schema::table('staff_card_logs', function (Blueprint $table) {
            $table->dropColumn(['action', 'remark']);
        });
    }
};
