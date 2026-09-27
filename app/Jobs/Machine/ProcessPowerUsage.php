<?php

namespace App\Jobs\Machine;

use App\Models\Machine\Machine;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessPowerUsage implements ShouldQueue
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
            Log::warning("MQTT Power Usage: Machine not found", ['serial_no' => $this->serialNo]);
            return;
        }

        if (!isset($this->payload['watt']) || !isset($this->payload['kwh_total'])) {
            Log::warning("MQTT Power Usage: Missing watt/kwh_total field in payload", [
                'serial_no' => $this->serialNo,
                'payload' => $this->payload,
            ]);
            return;
        }

        $watt = round((float) $this->payload['watt'], 1);
        $kwhTotal = round((float) $this->payload['kwh_total'], 3);

        Log::debug("ProcessPowerUsage: Power usage reported for {$this->serialNo}: {$watt}W, cumulative {$kwhTotal}kWh");

        $machine->update([
            'power_watt' => $watt,
            'energy_kwh_total' => $kwhTotal,
        ]);

        // 跟環境溫度不同，用電量的累計計數器幾乎每次回報都會往上走，
        // 沒有「值不變就不用寫」這種去重空間，所以每一次回報都直接記錄，
        // 靠韌體的回報頻率（60 秒一次）本身控制寫入頻率就好。
        \App\Jobs\Machine\ProcessStateLog::dispatch(
            $machine->id,
            $machine->company_id,
            "Power usage reported: :watt W, cumulative :kwh kWh",
            'info',
            ['watt' => $watt, 'kwh' => $kwhTotal],
            'power_usage'
        );
    }
}
