<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * 電子發票對帳/補開所需欄位。
 *
 * 背景：機台改走 MQTT finalize 後，發票結果（含 pending）隨交易一起上報；
 * 後台需要狀態機與冪等鍵，才能對 pending 去綠界查詢(GetIssue)、對 failed 補開(Issue)、
 * 對「已開票未出貨」作廢(Invalid)。全部欄位 nullable / 有預設，對既有資料與未開發票的機台零影響。
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            // 開立狀態：pending（已送出未回應）/ issued（已開）/ failed（綠界回失敗）/ void（已作廢）
            $table->string('status')->default('issued')->index()->after('invoice_no')
                ->comment('發票狀態: pending/issued/failed/void');
            // 綠界 RelateNumber（冪等鍵, = machineID + flow_id），查詢/補開用
            $table->string('relate_number')->nullable()->index()->after('status')
                ->comment('綠界 RelateNumber 冪等鍵');
            // 對帳排程：上次去綠界查詢的時間與已重試次數
            $table->timestamp('last_checked_at')->nullable()->after('machine_time')
                ->comment('上次向綠界查詢/補開的時間');
            $table->unsignedInteger('retry_count')->default(0)->after('last_checked_at')
                ->comment('查詢/補開重試次數');
            // 作廢稽核
            $table->timestamp('voided_at')->nullable()->after('retry_count')
                ->comment('作廢時間');
            $table->string('void_reason')->nullable()->after('voided_at')
                ->comment('作廢原因');
        });

        // 既有資料回填：有發票號→issued，否則→failed（未開發票的機台沒有任何列，等同 no-op）
        DB::table('invoices')
            ->whereNull('invoice_no')->orWhere('invoice_no', '')
            ->update(['status' => 'failed']);
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn([
                'status',
                'relate_number',
                'last_checked_at',
                'retry_count',
                'voided_at',
                'void_reason',
            ]);
        });
    }
};
