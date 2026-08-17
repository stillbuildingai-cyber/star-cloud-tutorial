<?php

namespace App\Jobs\Transaction;

use App\Models\Machine\Machine;
use App\Services\Transaction\TransactionService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use App\Jobs\Machine\ProcessStateLog;
use App\Models\Transaction\Order;
use App\Services\Machine\MachineService;

class ProcessTransactionFinalized implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /** 走實體刷卡機(Nexsys)的支付類型：1 信用卡、2 電子票證、10 手機感應支付，共用同一台機器。 */
    private const CARD_TERMINAL_PAYMENT_TYPES = [1, 2, 10];

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
            Log::warning("MQTT Transaction Finalized: Machine not found", ['serial_no' => $this->serialNo]);
            return;
        }

        $flowId = $this->payload['order']['flow_id'] ?? ($this->payload['flow_id'] ?? 'N/A');

        try {
            $data = $this->payload;
            $data['serial_no'] = $this->serialNo;

            $order = $transactionService->finalizeTransaction($data);

            ProcessStateLog::dispatch(
                $machine->id,
                $machine->company_id,
                "Transaction finalized: :id",
                'info',
                [
                    'id' => $order->order_no,
                    'flow_id' => $order->flow_id
                ],
                'transaction'
            );

            // 刷卡機健康狀態同步：成功消警 / 失敗寫 card_terminal 日誌
            $this->syncCardTerminalStatus($machine, $order);

            Log::info("MQTT Transaction [finalize] processed successfully", [
                'serial_no' => $this->serialNo,
                'order_no' => $order->order_no
            ]);

            // 發送成功回饋 (ACK)
            $mqttService->pushCommand($this->serialNo, 'transaction_ack', [
                'flow_id' => $flowId,
                'order_no' => $order->order_no,
                'status' => 'success',
                'message' => 'Processed successfully'
            ], (string) $flowId);

        } catch (\Exception $e) {
            Log::error("Failed to process MQTT transaction finalized for machine {$this->serialNo}: " . $e->getMessage(), [
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

    /**
     * 刷卡機 (信用卡/電子票證/手機支付，payment_type 1/2/10) 狀態同步。
     * 刷卡機沒有斷線心跳，所以靠交易結果維護燈號：
     *  - 成功交易 → 自動消除該機台先前未解決的刷卡機警告，恢復正常。
     *  - 失敗交易(payment_response 帶 NFC 回應碼) → 寫一筆 type='card_terminal' 的 warning 日誌
     *    (warning 級依 MachineLog 既有規則不會觸發 Discord)。
     */
    private function syncCardTerminalStatus(Machine $machine, Order $order): void
    {
        if (!in_array((int) $order->payment_type, self::CARD_TERMINAL_PAYMENT_TYPES, true)) {
            return;
        }

        // 刷卡成功 → 消警，恢復刷卡機燈號
        if ((int) $order->payment_status === Order::PAYMENT_STATUS_SUCCESS) {
            $machine->logs()
                ->where('type', 'card_terminal')
                ->where('is_resolved', false)
                ->update(['is_resolved' => true]);
            return;
        }

        // 刷卡失敗 → 解析 App 端格式 "[電文] | code=XXXX" 取回應碼
        if (!preg_match('/\|\s*code=([A-Za-z0-9_]+)/', (string) $order->payment_response, $m)) {
            return; // 無刷卡機回應碼(例如顧客未感應就放棄)，不視為硬體訊號問題
        }
        $code = $m[1];
        if ($code === '0000') {
            return; // 0000 為成功碼，理論上不會走到失敗分支，保險過濾
        }

        ProcessStateLog::dispatch(
            $machine->id,
            $machine->company_id,
            'Card payment failed',
            'warning',
            ['card_code' => $code, 'order_no' => $order->order_no],
            'card_terminal'
        );
    }
}
