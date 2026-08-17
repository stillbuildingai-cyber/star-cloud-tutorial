<?php

namespace App\Jobs\Machine;

use App\Models\Machine\Machine;
use App\Jobs\Machine\ProcessStateLog;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessStatus implements ShouldQueue
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
            Log::warning("ProcessStatus: Machine not found [{$this->serialNo}]");
            return;
        }

        // Get status from payload, e.g. {"status": "offline"}
        $status = $this->payload['status'] ?? null;

        if (!$status) {
            Log::warning("ProcessStatus: Missing status in payload", ['payload' => $this->payload]);
            return;
        }

        $oldStatus = $machine->status;
        
        $updateData = ['status' => $status];
        if ($status === 'online') {
            $updateData['last_heartbeat_at'] = now();

            // 自動消警：當機台恢復連線時，自動標記之前的「斷線 (LWT)」警告為已解決
            $machine->logs()
                ->where('type', 'status')
                ->where('level', 'warning')
                ->where('message', 'Connection lost (LWT)')
                ->where('is_resolved', false)
                ->update(['is_resolved' => true]);
        }

        $machine->update($updateData);

        // If status changed, log it
        if ($oldStatus !== $status) {
            $level = 'info';
            $message = "Connection restored";

            if ($status === 'offline') {
                $level = 'warning';
                $message = "Connection lost (LWT)";
            } elseif ($status === 'restarting') {
                $level = 'info';
                $message = "System restarting";
            }

            ProcessStateLog::dispatch($machine->id, $machine->company_id, $message, $level);
            
            Log::info("ProcessStatus: Machine [{$machine->serial_no}] status changed to [{$status}]");
        }
    }
}
