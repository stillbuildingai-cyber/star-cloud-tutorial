<?php

namespace App\Console\Commands;

use App\Models\Transaction\Order;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * 把「停留在 pending 太久」的交易標記為 abandoned（感應前取消 / 付款未完成）。
 *
 * 設計重點（過渡期安全）：
 *  - 只動 status = 'pending' 的單。線上 main 機台(晟崴/中國醫)從不送 pending，
 *    後台也就不會有它們的 pending 單 → 本命令永遠掃不到它們，零接觸。
 *  - 付款對話框逾時約 40 秒，預設門檻給到 15 分鐘，遠大於正常付款耗時；
 *    超過此門檻仍 pending，代表機台沒回成功/失敗（取消、斷線、App 重啟），視為 abandoned。
 *  - 不碰庫存、不碰金流：pending 本來就還沒出貨/扣庫存。
 */
class SweepAbandonedOrdersCommand extends Command
{
    protected $signature = 'orders:sweep-abandoned {--minutes=15 : 停留在 pending 超過幾分鐘視為 abandoned} {--limit=500 : 單次處理筆數上限}';

    protected $description = '將逾時停留在 pending 的交易標記為 abandoned';

    public function handle(): int
    {
        $minutes = max(1, (int) $this->option('minutes'));
        $limit = max(1, (int) $this->option('limit'));
        $threshold = now()->subMinutes($minutes);

        $orders = Order::withoutGlobalScopes()
            ->where('status', Order::STATUS_PENDING)
            ->where('created_at', '<', $threshold)
            ->orderBy('id')
            ->limit($limit)
            ->get();

        if ($orders->isEmpty()) {
            $this->info('No stale pending orders to sweep.');
            return self::SUCCESS;
        }

        $swept = 0;
        foreach ($orders as $order) {
            $order->update(['status' => Order::STATUS_ABANDONED]);
            $swept++;
        }

        Log::info('Swept abandoned orders', ['count' => $swept, 'older_than_minutes' => $minutes]);
        $this->info("Marked {$swept} pending order(s) as abandoned (older than {$minutes} min).");

        return self::SUCCESS;
    }
}
