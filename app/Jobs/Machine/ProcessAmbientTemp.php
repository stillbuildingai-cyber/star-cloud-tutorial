<?php
 
namespace App\Jobs\Machine;
 
use App\Models\Machine\Machine;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class ProcessAmbientTemp implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $serialNo;
    protected $payload;

    /**
     * Create a new job instance.
     */
    public function __construct(string $serialNo, $payload)
    {
        $this->serialNo = $serialNo;
        $this->payload = (array) $payload;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $machine = Machine::withoutGlobalScopes()->where('serial_no', $this->serialNo)->first();

        if (!$machine) {
            Log::warning("MQTT Ambient Temp: Machine not found", ['serial_no' => $this->serialNo]);
            return;
        }

        // 修改支援小數，四捨五入到小數點第 1 位
        $temp = null;
        if (isset($this->payload['temperature'])) {
            $temp = round((float) $this->payload['temperature'], 1);
        } elseif (isset($this->payload['ambient_temp'])) {
            $temp = round((float) $this->payload['ambient_temp'], 1);
        } elseif (isset($this->payload['raw_data'])) {
            $temp = round((float) $this->payload['raw_data'], 1);
        }

        if ($temp === null) {
            Log::warning("MQTT Ambient Temp: Missing temperature field in payload", [
                'serial_no' => $this->serialNo,
                'payload' => $this->payload
            ]);
            return;
        }

        Log::debug("ProcessAmbientTemp: Ambient temperature reported for {$this->serialNo}: {$temp}");

        $updateData = [
            'ambient_temperature' => $temp,
        ];

        // 讀取最後一次環境溫度，避免重複寫入相同溫度的歷史日誌
        $tempLogCacheKey = "machine:{$this->serialNo}:last_ambient_temp_log";
        $lastLogEntry = Cache::get($tempLogCacheKey);

        // 支援浮點數
        $oldTemp = isset($lastLogEntry['value']) ? (float)$lastLogEntry['value'] : $machine->ambient_temperature;

        if ($oldTemp === null || (float)$temp !== (float)$oldTemp) {
            Log::debug("ProcessAmbientTemp: Ambient temperature changed from {$oldTemp} to {$temp}. Triggering log.");
            \App\Jobs\Machine\ProcessStateLog::dispatch(
                $machine->id,
                $machine->company_id,
                "Ambient temperature reported: :temp°C",
                'info',
                ['temp' => $temp],
                'ambient_temp' // type 欄位
            );
        }

        // 快取設定為 float
        Cache::put($tempLogCacheKey, [
            'value' => (float)$temp,
            'at' => now()->toDateTimeString()
        ], 604800);

        $machine->update($updateData);
    }
}
