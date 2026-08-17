<?php

namespace App\Jobs\Transaction;

use App\Services\Transaction\TransactionService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessInvoice implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $data;

    /**
     * Create a new job instance.
     */
    public function __construct(array $data)
    {
        $this->data = $data;
    }

    /**
     * Execute the job.
     */
    public function handle(\App\Services\Transaction\TransactionService $transactionService, \App\Services\Machine\MqttService $mqttService): void
    {
        $serialNo = $this->data['serial_no'] ?? 'unknown';
        $invoiceNo = $this->data['invoice_no'] ?? 'N/A';

        try {
            $transactionService->recordInvoice($this->data);

            // 發送成功回饋 (ACK)
            $mqttService->pushCommand($serialNo, 'invoice_ack', [
                'invoice_no' => $invoiceNo,
                'status' => 'success',
                'message' => 'Processed successfully'
            ], $invoiceNo);

        } catch (\Exception $e) {
            Log::error('Failed to process invoice: ' . $e->getMessage(), [
                'data' => $this->data,
                'exception' => $e
            ]);

            // 發送失敗回饋 (ACK)
            $mqttService->pushCommand($serialNo, 'invoice_ack', [
                'invoice_no' => $invoiceNo,
                'status' => 'error',
                'message' => $e->getMessage()
            ], $invoiceNo);

            throw $e;
        }
    }
}
