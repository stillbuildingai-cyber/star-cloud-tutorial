<?php

namespace App\Jobs\Machine;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ProcessRestockReport implements ShouldQueue
{
    use Queueable;

    protected $data;

    /**
     * Create a new job instance.
     */
    public function __construct(array $data)
    {
        $this->data = $data;
    }

    /**
     * Execute the job.
     */
    public function handle(\App\Services\Machine\MachineService $machineService): void
    {
        $serialNo = $this->data['serial_no'] ?? null;
        $slotsData = $this->data['slots'] ?? [];

        if (!$serialNo) return;

        $machine = \App\Models\Machine\Machine::where('serial_no', $serialNo)->first();
        if ($machine) {
            $machineService->syncSlots($machine, $slotsData);
        }
    }
}
