<?php

namespace App\Jobs\Machine;

use App\Models\Machine\Machine;
use App\Models\Machine\CoinInventory;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessCoinInventory implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $serialNo;
    protected $data;

    /**
     * Create a new job instance.
     */
    public function __construct(string $serialNo, array $data)
    {
        $this->serialNo = $serialNo;
        $this->data = $data;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            $machine = Machine::where('serial_no', $this->serialNo)->firstOrFail();
            
            // Sync inventory: typically the IoT device sends the full state
            // If it sends partial, logic would differ. For now, we assume simple updateOrCreate per denomination.
            if (isset($this->data['inventories']) && is_array($this->data['inventories'])) {
                foreach ($this->data['inventories'] as $inv) {
                    CoinInventory::updateOrCreate(
                        [
                            'machine_id' => $machine->id, 
                            'denomination' => $inv['denomination'],
                            'type' => $inv['type'] ?? 'coin'
                        ],
                        ['count' => $inv['count']]
                    );
                }
            }
        } catch (\Exception $e) {
            Log::error("Failed to process coin inventory for machine {$this->serialNo}: " . $e->getMessage());
            throw $e;
        }
    }
}
