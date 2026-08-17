<?php

namespace App\Console\Commands;

use App\Models\Transaction\Invoice;
use App\Services\Invoice\EcpayInvoiceService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * 電子發票對帳：掃 pending 發票，去綠界 GetIssue 查真實狀態。
 *
 * 機台開票回應掉包時 status 會停在 pending（不能當失敗，否則重開會重複）。
 * 本命令以 RelateNumber 向綠界查詢確認：已開→補登 issued；確認未開→標 failed（待補開）。
 * 僅處理機台有綠界發票設定者；未設定者一律略過，未開發票的機台零接觸。
 */
class ReconcileInvoicesCommand extends Command
{
    protected $signature = 'invoices:reconcile {--limit=100 : 單次處理筆數上限} {--max-retry=12 : 單張最多查詢次數}';

    protected $description = '對 pending 電子發票向綠界 GetIssue 查詢並補登/標記狀態';

    public function handle(EcpayInvoiceService $ecpay): int
    {
        $limit = (int) $this->option('limit');
        $maxRetry = (int) $this->option('max-retry');

        $invoices = Invoice::with(['machine.paymentConfig', 'order'])
            ->where('status', Invoice::STATUS_PENDING)
            ->whereNotNull('relate_number')
            ->where('retry_count', '<', $maxRetry)
            ->orderBy('id')
            ->limit($limit)
            ->get();

        if ($invoices->isEmpty()) {
            $this->info('No pending invoices to reconcile.');
            return self::SUCCESS;
        }

        $issued = 0;
        $failed = 0;
        $skipped = 0;

        foreach ($invoices as $invoice) {
            $machine = $invoice->machine;
            if (!$machine || !$ecpay->configForMachine($machine)) {
                // 機台無綠界設定 → 略過，不動狀態（不可能誤觸未開發票機台）
                $skipped++;
                continue;
            }

            $result = $ecpay->getIssue($machine, $invoice->relate_number);

            // 連線/解密失敗 → 本輪略過，僅累加重試次數待下輪
            if ($result === null) {
                $invoice->update([
                    'retry_count' => $invoice->retry_count + 1,
                    'last_checked_at' => now(),
                ]);
                $skipped++;
                continue;
            }

            $rtnCode = (string) ($result['RtnCode'] ?? '');
            $invoiceNo = $result['IIS_Number'] ?? ($result['InvoiceNumber'] ?? '');

            if ($rtnCode === '1' && !empty($invoiceNo)) {
                // 綠界確認已開立 → 補登
                $invoice->update([
                    'status' => Invoice::STATUS_ISSUED,
                    'invoice_no' => $invoiceNo,
                    'invoice_date' => $this->parseDate($result['IIS_Create_Date'] ?? null),
                    'random_number' => $result['IIS_Random_Number'] ?? $invoice->random_number,
                    'rtn_code' => $rtnCode,
                    'rtn_msg' => $result['RtnMsg'] ?? $invoice->rtn_msg,
                    'retry_count' => $invoice->retry_count + 1,
                    'last_checked_at' => now(),
                ]);
                $issued++;
                Log::info('Invoice reconciled as issued', [
                    'invoice_id' => $invoice->id,
                    'invoice_no' => $invoiceNo,
                ]);
            } else {
                // 綠界查無此發票 → 確認未開立，標 failed 待補開
                $invoice->update([
                    'status' => Invoice::STATUS_FAILED,
                    'rtn_code' => $rtnCode ?: $invoice->rtn_code,
                    'rtn_msg' => $result['RtnMsg'] ?? $invoice->rtn_msg,
                    'retry_count' => $invoice->retry_count + 1,
                    'last_checked_at' => now(),
                ]);
                $failed++;
            }
        }

        $this->info("Reconcile done. issued={$issued} failed={$failed} skipped={$skipped}");
        return self::SUCCESS;
    }

    private function parseDate(?string $raw): ?string
    {
        if (empty($raw)) {
            return null;
        }
        try {
            return \Carbon\Carbon::parse($raw)->toDateString();
        } catch (\Throwable $e) {
            return null;
        }
    }
}
