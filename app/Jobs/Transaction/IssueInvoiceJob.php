<?php

namespace App\Jobs\Transaction;

use App\Models\Transaction\Invoice;
use App\Services\Invoice\EcpayInvoiceService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * 後台開立電子發票（取代機台直連綠界）。
 *
 * finalize 建立 pending 發票後派發；以 invoice.relate_number 為冪等鍵向綠界開立（Issue）。
 * 成功→issued、失敗→failed（待人工/排程補開）。連線失敗則 retry，由佇列重試機制接手；
 * 仍有殘留 pending 者由 invoices:reconcile（GetIssue）做最終查證，避免重複開立。
 */
class IssueInvoiceJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 30;

    public function __construct(public int $invoiceId)
    {
    }

    public function handle(EcpayInvoiceService $ecpay): void
    {
        $invoice = Invoice::with(['machine.paymentConfig', 'order.items'])->find($this->invoiceId);
        if (!$invoice) {
            return;
        }

        // 僅處理待開立；已開/失敗/作廢不重複觸發（冪等）
        if ($invoice->status !== Invoice::STATUS_PENDING) {
            return;
        }

        // 機台無綠界設定 → 留 pending（理論上不會發生：無設定機台不會帶 invoice）
        if (!$invoice->machine || !$ecpay->configForMachine($invoice->machine)) {
            Log::warning('IssueInvoiceJob: machine has no ECPay config, leave pending', [
                'invoice_id' => $invoice->id,
            ]);
            return;
        }

        $result = $ecpay->reissue($invoice);

        // 連線/解密失敗 → 丟例外讓佇列重試（不改狀態）
        if ($result === null) {
            $invoice->increment('retry_count');
            $invoice->update(['last_checked_at' => now()]);
            throw new \RuntimeException('ECPay issue returned null for invoice ' . $invoice->id);
        }

        $rtnCode = (string) ($result['RtnCode'] ?? '');
        $invoiceNo = $result['InvoiceNo'] ?? '';

        if ($rtnCode === '1' && !empty($invoiceNo)) {
            $invoice->update([
                'status' => Invoice::STATUS_ISSUED,
                'invoice_no' => $invoiceNo,
                'invoice_date' => $this->safeDate($result['InvoiceDate'] ?? null),
                'random_number' => $result['RandomNumber'] ?? $invoice->random_number,
                'rtn_code' => $rtnCode,
                'rtn_msg' => $result['RtnMsg'] ?? null,
                'last_checked_at' => now(),
            ]);
            Log::info('Invoice issued by backend', ['invoice_id' => $invoice->id, 'invoice_no' => $invoiceNo]);
            return;
        }

        // 綠界明確回失敗 → 標 failed（待補開），不重試
        $invoice->update([
            'status' => Invoice::STATUS_FAILED,
            'rtn_code' => $rtnCode ?: $invoice->rtn_code,
            'rtn_msg' => $result['RtnMsg'] ?? $invoice->rtn_msg,
            'last_checked_at' => now(),
        ]);
        Log::warning('Invoice issue failed', [
            'invoice_id' => $invoice->id,
            'rtn_code' => $rtnCode,
            'rtn_msg' => $result['RtnMsg'] ?? '',
        ]);
    }

    private function safeDate($raw): ?string
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
