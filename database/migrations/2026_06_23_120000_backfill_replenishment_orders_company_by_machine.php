<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * 補貨單歸屬以「機台所綁公司」為準。
 * 既有單據（特別是系統管理員建立、company_id 寫成建立者 null 的）依機台回填，
 * 否則篩選列選公司或租戶帳號登入皆看不到這些補貨單。
 */
return new class extends Migration
{
    public function up(): void
    {
        // 以 machines.company_id 校正 replenishment_orders.company_id（含 NULL 安全比較）
        DB::statement(<<<'SQL'
            UPDATE replenishment_orders AS ro
            INNER JOIN machines AS m ON m.id = ro.machine_id
            SET ro.company_id = m.company_id
            WHERE NOT (ro.company_id <=> m.company_id)
        SQL);
    }

    public function down(): void
    {
        // 資料校正，無法可靠還原
    }
};
