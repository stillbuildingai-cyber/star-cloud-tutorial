<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 機台狀態燈號查詢加速。
     * 連線(calculated_status)、下位機(hardware_status)、登入、刷卡機(card_terminal) 等狀態
     * 皆以 WHERE machine_id=? AND type=? AND is_resolved=? AND level=? 過濾未解決日誌，
     * 且在機台列表頁逐台執行。加複合索引避免隨日誌量成長而變慢。
     *
     * 註：正式環境 machine_logs 可能很大，加索引會短暫鎖表，建議離峰執行。
     */
    public function up(): void
    {
        Schema::table('machine_logs', function (Blueprint $table) {
            $table->index(['machine_id', 'type', 'is_resolved', 'level'], 'machine_logs_status_lookup_index');
        });
    }

    public function down(): void
    {
        Schema::table('machine_logs', function (Blueprint $table) {
            $table->dropIndex('machine_logs_status_lookup_index');
        });
    }
};
