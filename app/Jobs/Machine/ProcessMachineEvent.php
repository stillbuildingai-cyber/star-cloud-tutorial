<?php

namespace App\Jobs\Machine;

use App\Models\Machine\Machine;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessMachineEvent implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $serialNo;
    protected $payload;

    /**
     * Create a new job instance.
     */
    public function __construct($serialNo, $payload)
    {
        $this->serialNo = $serialNo;
        $this->payload = $payload;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $machine = Machine::withoutGlobalScopes()->where('serial_no', $this->serialNo)->first();

        if (!$machine) {
            Log::warning("ProcessMachineEvent: Machine not found [{$this->serialNo}]");
            return;
        }

        $event = $this->payload['event'] ?? null;

        if (!$event) {
            Log::warning("ProcessMachineEvent: Missing event in payload", ['payload' => $this->payload]);
            return;
        }

        // 寫入環境溫度日誌，type 為 'ambient_temp' 以便正確歸類顯示於「環境溫度回傳」日誌中
        $machine->logs()->create([
            'company_id' => $machine->company_id,
            'type' => 'ambient_temp', // 歸類至環境溫度回傳
            'level' => 'info',
            'message' => $event, // 例如 "fanon" 或 "fanoff"
            'context' => $this->payload,
        ]);

        Log::info("ProcessMachineEvent: Machine [{$machine->serial_no}] recorded event [{$event}] to status logs");
    }
}
