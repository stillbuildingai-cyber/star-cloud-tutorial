<?php

namespace App\Services\Machine;

use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;

class MqttCommandService
{
    /**
     * 發送指令至機台
     *
     * @param string $serialNo 機台序號
     * @param string $command 指令名稱 (例如 dispense, reboot)
     * @param array $payload 指令參數
     * @return string Message ID
     */
    public function sendCommand(string $serialNo, string $command, array $payload = []): string
    {
        $messageId = 'MSG_' . Str::random(16);
        $queueKey = config('mqtt.outgoing_queue', 'mqtt_outgoing_commands');

        $data = [
            'target' => $serialNo,
            'command' => $command,
            'payload' => $payload,
            'message_id' => $messageId,
            'timestamp' => now()->toIso8601String(),
        ];

        Redis::rpush($queueKey, json_encode($data));

        return $messageId;
    }
}
