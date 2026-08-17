<?php

namespace App\Jobs\Machine;

use App\Models\Machine\Machine;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessTransaction implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $serialNo;
    protected $payload;

    /**
     * Create a new job instance.
     */
    public function __construct(string $serialNo, array $payload)
    {
        $this->serialNo = $serialNo;
        $this->payload = $payload;
    }

    /**
     * Execute the job.
     */
    public function handle(\App\Services\Transaction\TransactionService $transactionService, \App\Services\Machine\MqttService $mqttService): void
    {
        $machine = Machine::withoutGlobalScopes()->where('serial_no', $this->serialNo)->first();

        if (!$machine) {
            Log::warning("MQTT Transaction: Machine not found", ['serial_no' => $this->serialNo]);
            return;
        }

        $flowId = $this->payload['flow_id'] ?? 'N/A';

        try {
            // 將 serial_no 放入 payload 供 Service 使用
            $data = $this->payload;
            $data['serial_no'] = $this->serialNo;

            $transactionService->processTransaction($data);

            ProcessStateLog::dispatch(
                $machine->id,
                $machine->company_id,
                "Transaction processed: :id",
                'info',
                array_merge($this->payload, ['id' => $flowId]),
                'transaction'
            );

            // 發送成功回饋 (ACK)
            $mqttService->pushCommand($this->serialNo, 'transaction_ack', [
                'flow_id' => $flowId,
                'status' => 'success',
                'message' => 'Processed successfully'
            ], (string) $flowId);

        } catch (\Exception $e) {
            Log::error("Failed to process MQTT transaction for machine {$this->serialNo}: " . $e->getMessage(), [
                'payload' => $this->payload,
                'exception' => $e
            ]);

            // 發送失敗回饋 (ACK)
            $mqttService->pushCommand($this->serialNo, 'transaction_ack', [
                'flow_id' => $flowId,
                'status' => 'error',
                'message' => $e->getMessage()
            ], (string) $flowId);

            throw $e;
        }
    }
}
