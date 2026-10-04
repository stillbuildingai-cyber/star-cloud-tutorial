<?php

namespace App\Jobs\Machine;

use App\Models\Machine\Machine;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessCurtainPosition implements ShouldQueue
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
     *
     * 跟 ProcessAmbientTemp 同一套骨架：裝置回報實際位置，寫進 machines 表方便即時顯示，
     * 值有變化才寫歷史紀錄（窗簾位置變動次數遠比用電量回報少，適合用去重邏輯）。
     */
    public function handle(): void
    {
        $machine = Machine::withoutGlobalScopes()->where('serial_no', $this->serialNo)->first();

        if (!$machine) {
            Log::warning("MQTT Curtain Position: Machine not found", ['serial_no' => $this->serialNo]);
            return;
        }

        if (!isset($this->payload['position'])) {
            Log::warning("MQTT Curtain Position: Missing position field in payload", [
                'serial_no' => $this->serialNo,
                'payload' => $this->payload,
            ]);
            return;
        }

        $position = max(0, min(100, (int) $this->payload['position']));
        $oldPosition = $machine->curtain_position;

        $machine->update(['curtain_position' => $position]);

        if ($oldPosition === null || (int) $oldPosition !== $position) {
            \App\Jobs\Machine\ProcessStateLog::dispatch(
                $machine->id,
                $machine->company_id,
                "Curtain position reported: :position%",
                'info',
                ['position' => $position],
                'curtain_position'
            );
        }
    }
}
