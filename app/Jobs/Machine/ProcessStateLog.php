<?php

namespace App\Jobs\Machine;

use App\Models\Machine\MachineLog;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessStateLog implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $machineId;
    protected $companyId;
    protected $message;
    protected $level;
    protected $type;
    protected $context;

    /**
     * Create a new job instance.
     */
    public function __construct(int $machineId, ?int $companyId, string $message, string $level = 'info', array $context = [], string $type = 'status')
    {
        $this->machineId = $machineId;
        $this->companyId = $companyId;
        $this->message = $message;
        $this->level = $level;
        $this->context = $context;
        $this->type = $type;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            MachineLog::create([
                'machine_id' => $this->machineId,
                'company_id' => $this->companyId,
                'type' => $this->type,
                'level' => $this->level,
                'message' => $this->message,
                'context' => $this->context,
            ]);
        } catch (\Exception $e) {
            Log::error("Failed to create state log for machine {$this->machineId}: " . $e->getMessage());
            throw $e;
        }
    }
}
