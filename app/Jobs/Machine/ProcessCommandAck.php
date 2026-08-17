<?php

namespace App\Jobs\Machine;

use App\Models\Machine\Machine;
use App\Models\Machine\MachineStockMovement;
use App\Models\Machine\RemoteCommand;
use App\Services\Machine\MachineService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessCommandAck implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected string $serialNo;
    protected array $payload;

    /**
     * 建立 Job 實例
     *
     * @param string $serialNo 機台序號（由 Go Gateway 從 Topic 路徑注入）
     * @param array $payload MQTT Payload，包含 command_id, result, stock
     */
    public function __construct(string $serialNo, array $payload)
    {
        $this->serialNo = $serialNo;
        $this->payload = $payload;
    }

    /**
     * 處理 B055 出貨回報 ACK
     * 原始邏輯移植自 MachineController::reportDispenseResult()
     */
    public function handle(MachineService $machineService): void
    {
        $commandId = $this->payload['command_id'] ?? null;
        $resultCode = $this->payload['result'] ?? null;
        $stockReported = $this->payload['stock'] ?? null;

        if (empty($commandId)) {
            Log::warning('MQTT CommandAck: Missing command_id', [
                'serial_no' => $this->serialNo,
                'payload' => $this->payload
            ]);
            return;
        }

        $command = RemoteCommand::find($commandId);

        if (!$command) {
            Log::warning('MQTT CommandAck: Command not found', [
                'serial_no' => $this->serialNo,
                'command_id' => $commandId
            ]);
            return;
        }

        $machine = $command->machine;

        // 【優先執行】更新機台為在線狀態 (只要收到訊息就代表在線，不受指令狀態影響)
        if ($machine) {
            $machine->update([
                'last_heartbeat_at' => now(),
                'status' => 'online',
            ]);
        }

        // 核心邏輯：只有處於 pending 狀態的指令才處理後續的業務邏輯 (如庫存異動)
        if ($command->status !== 'pending') {
            Log::info('MQTT CommandAck: Command already processed, ignoring business logic', [
                'command_id' => $commandId,
                'current_status' => $command->status,
            ]);
            return;
        }

        // OTA 更新例外處理：APK 下載/安裝為長時程作業，機台會持續回報「進度」ACK
        // (result="progress"，如「Downloading 45%」「Installing...」)。這些屬於過程回報，
        // 不可視為終態，否則指令會在「剛開始下載」就被標記完成，與機台實際版本不符。
        // 僅將進度文字寫入 note 供後台檢視，並保持 pending，等待最終 success/failed。
        if ($command->command_type === 'update_app' && $resultCode === 'progress') {
            $command->update(['note' => $this->payload['message'] ?? $command->note]);
            Log::info('MQTT CommandAck: update_app progress (kept pending)', [
                'command_id' => $commandId,
                'message' => $this->payload['message'] ?? null,
            ]);
            return;
        }

        // 判定執行結果 (result: "success" 或 "0" 代表成功)
        $status = in_array($resultCode, ['success', '0'], true) ? 'success' : 'failed';

        $payloadUpdates = [
            'reported_stock' => $stockReported,
            'report_result' => $resultCode,
        ];

        $machine = $command->machine;

        if ($command->command_type === 'dispense') {
            // 遠端出貨指令僅紀錄狀態，不再執行庫存預扣或回滾。
            // 真正的庫存異動將由機台發送的 Transaction Finalize (結案) 流程處理。
            $msgKey = ($status === 'success') ? "Remote dispense successful for slot :slot" : "Remote dispense failed for slot :slot";
            $level = ($status === 'success') ? 'info' : 'error';

            ProcessStateLog::dispatch(
                $machine->id,
                $machine->company_id,
                $msgKey,
                $level,
                ['slot' => $command->payload['slot_no'] ?? 'N/A']
            );
        }

 elseif ($command->command_type === 'reload_stock') {
            // 若同步失敗，自動 Rollback 回滾庫存
            if ($status === 'failed' && isset($command->payload['slot_no'], $command->payload['old'])) {
                $slotNo = $command->payload['slot_no'];
                $slot = $machine->slots()->where('slot_no', $slotNo)->first();

                if ($slot) {
                    $oldData = $command->payload['old'];
                    $slot->update([
                        'stock' => $oldData['stock'],
                        'expiry_date' => $oldData['expiry_date'] ?? null,
                        'batch_no' => $oldData['batch_no'] ?? null,
                    ]);

                    Log::warning('MQTT CommandAck failed, rolled back inventory', [
                        'serial_no' => $this->serialNo,
                        'slot_no' => $slotNo,
                        'rolled_back_to' => $oldData
                    ]);
                }
            }
        }

        $command->update([
            'status' => $status,
            'executed_at' => now(),
            'payload' => array_merge($command->payload, $payloadUpdates),
        ]);

        // 記錄維護類指令到機台日誌 (MachineLog)
        if (in_array($command->command_type, ['reboot', 'reboot_force', 'reboot_card', 'lock', 'unlock', 'checkout', 'change', 'reload_stock', 'update_products', 'update_ads', 'update_settings'], true)) {
            $msgKey = $status === 'success' ? 'log.command.success' : 'log.command.failed';
            $level = $status === 'success' ? 'info' : 'warning';
            
            ProcessStateLog::dispatch(
                $machine->id,
                $machine->company_id,
                $msgKey,
                $level,
                ['type' => __("command.{$command->command_type}")]
            );
        }

        Log::info("MQTT CommandAck processed", [
            'serial_no' => $this->serialNo,
            'command_id' => $commandId,
            'status' => $status,
        ]);
    }
}
