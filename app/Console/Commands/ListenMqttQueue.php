<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Log;
use App\Jobs\Machine\ProcessHeartbeat;
use App\Jobs\Machine\ProcessMachineError;
use App\Jobs\Machine\ProcessStatus;
use App\Jobs\Machine\ProcessMachineEvent;
use App\Jobs\Machine\ProcessCommandAck;

class ListenMqttQueue extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mqtt:listen';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Listen to the MQTT incoming queue from Redis and dispatch jobs';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $queueKey = config('mqtt.incoming_queue', 'mqtt_incoming_jobs');
        $this->info("Listening to MQTT queue: {$queueKey}...");

        while (true) {
            try {
                // BLPOP returns [key, value]. Timeout set to 30s to avoid persistent connection read errors.
                $result = Redis::blpop($queueKey, 30);

                if (!$result || !isset($result[1])) {
                    continue;
                }

                $data = json_decode($result[1], true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    Log::error("MQTT Listen: Failed to decode JSON", ['data' => $result[1]]);
                    continue;
                }

                $this->dispatchJob($data);

            } catch (\Exception $e) {
                $this->error("MQTT Listen Error: " . $e->getMessage());
                Log::error("MQTT Listen Error: " . $e->getMessage(), ['exception' => $e]);
                sleep(2); // 防止出錯時無限循環噴錯
            }
        }
    }

    /**
     * 分派任務至對應的 Job
     */
    protected function dispatchJob(array $data)
    {
        $type = $data['type'] ?? '';
        $serialNo = $data['serial_no'] ?? '';
        $payload = $data['payload'] ?? [];

        // 如果 payload 是 JSON 字串，則解析它
        if (is_string($payload)) {
            $decoded = json_decode($payload, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $payload = $decoded;
            }
        }

        if (empty($serialNo)) {
            Log::warning("MQTT Listen: Missing serialNo in message", [
                'type' => $type,
                'data' => $data,
                'raw_payload' => $payload
            ]);
            return;
        }

        switch ($type) {
            case 'heartbeat':
                // 確保 $payload 是陣列，若機台誤傳純數值則包裹之
                $finalPayload = is_array($payload) ? $payload : ['raw_data' => $payload];
                ProcessHeartbeat::dispatch($serialNo, $finalPayload);
                break;
            case 'ambient_temp':
                $finalPayload = is_array($payload) ? $payload : ['temperature' => $payload];
                \App\Jobs\Machine\ProcessAmbientTemp::dispatch($serialNo, $finalPayload);
                break;
            case 'status':
                ProcessStatus::dispatch($serialNo, $payload);
                break;
            case 'event':
                ProcessMachineEvent::dispatch($serialNo, $payload);
                break;
            case 'error':
                ProcessMachineError::dispatch($serialNo, $payload);
                break;
            case 'command_ack':
                // B055: 機台出貨結果 ACK (machine/{serial_no}/command/ack)
                ProcessCommandAck::dispatch($serialNo, $payload);
                break;
            default:
                Log::notice("MQTT Listen: Unknown message type [{$type}]", ['data' => $data]);
                break;
        }
    }
}
