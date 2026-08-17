<?php

namespace App\Jobs\Transaction;

use App\Services\Transaction\TransactionService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessDispenseRecord implements ShouldQueue
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
        $orderNo = $this->data['order_no'] ?? 'N/A';

        try {
            $transactionService->recordDispense($this->data);

            // 發送成功回饋 (ACK)
            $mqttService->pushCommand($serialNo, 'dispense_ack', [
                'order_no' => $orderNo,
                'status' => 'success',
                'message' => 'Processed successfully'
            ], $orderNo);

        } catch (\Exception $e) {
            Log::error("Failed to record dispense for machine {$serialNo}: " . $e->getMessage(), [
                'data' => $this->data,
                'exception' => $e
            ]);

            // 發送失敗回饋 (ACK)
            $mqttService->pushCommand($serialNo, 'dispense_ack', [
                'order_no' => $orderNo,
                'status' => 'error',
                'message' => $e->getMessage()
            ], $orderNo);

            throw $e;
        }
    }
}
