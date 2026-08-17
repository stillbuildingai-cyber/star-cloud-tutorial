<?php

namespace App\Jobs\Machine;

use App\Models\Machine\Machine;
use App\Services\Machine\MachineService;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessMachineError implements ShouldQueue
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
    public function handle(MachineService $machineService): void
    {
        $machine = Machine::withoutGlobalScopes()->where('serial_no', $this->serialNo)->first();

        if (!$machine) {
            Log::warning("MQTT Error Report: Machine not found", ['serial_no' => $this->serialNo]);
            return;
        }

        // 使用 MachineService 統一處理硬體代碼映射與記錄 (B013 核心邏輯)
        $machineService->recordErrorLog($machine, $this->payload);
    }
}
