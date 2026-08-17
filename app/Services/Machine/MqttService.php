<?php

namespace App\Services\Machine;

use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Log;

class MqttService
{
    /**
     * 推播雲端指令 (Downstream Command) 給機台
     * 遵循 framework.md 中的 Redis 橋接機制，推入 mqtt_outgoing_commands 佇列，由 Go Gateway 接手發佈。
     *
     * @param string $serialNo 機台序號
     * @param string $command 指令名稱 (例如: update_inventory, dispense)
     * @param array $payload 指令的內容資料
     * @param string $commandId 唯一指令識別碼 (通常對應 RemoteCommand 的 ID)
     * @return bool
     */
    public function pushCommand(string $serialNo, string $command, array $payload, string $commandId): bool
    {
        try {
            $message = [
                'serial_no' => $serialNo,
                'command' => $command,
                'payload' => $payload,
                'command_id' => (string) $commandId,
                'timestamp' => now()->toIso8601String(),
            ];

            // 系統預設 outgoing queue 名稱 (Go Gateway 預設讀取此佇列)
            $queueKey = config('app.mqtt_outgoing_queue', 'mqtt_outgoing_commands');
            
            // Laravel 的 Redis Facade 會自動加上 database prefix
            Redis::rpush($queueKey, json_encode($message));

            Log::info("MQTT Command Pushed to Queue", [
                'queue' => $queueKey,
                'serial_no' => $serialNo,
                'command' => $command,
                'command_id' => $commandId
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error("Failed to push MQTT command", [
                'serial_no' => $serialNo,
                'command' => $command,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
}
