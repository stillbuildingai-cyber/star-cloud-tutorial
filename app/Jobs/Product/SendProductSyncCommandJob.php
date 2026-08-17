<?php

namespace App\Jobs\Product;

use App\Models\Machine\Machine;
use App\Models\Machine\RemoteCommand;
use App\Services\Machine\MqttService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendProductSyncCommandJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @param int $machineId
     * @param string|null $remark
     * @param int|null $userId
     */
    public function __construct(
        protected int $machineId,
        protected ?string $remark = null,
        protected ?int $userId = null
    ) {}

    /**
     * Execute the job.
     *
     * @param MqttService $mqttService
     * @return void
     */
    public function handle(MqttService $mqttService): void
    {
        $machine = Machine::find($this->machineId);

        if (!$machine) {
            Log::error("SendProductSyncCommandJob: Machine not found", ['id' => $this->machineId]);
            return;
        }

        // 覆蓋任何該機台尚在「待處理」狀態的商品同步指令
        RemoteCommand::where('machine_id', $machine->id)
            ->where('command_type', 'update_products')
            ->where('status', 'pending')
            ->update([
                'status' => 'superseded',
                'note' => 'Superseded by new command'
            ]);

        $command = RemoteCommand::create([
            'machine_id'   => $machine->id,
            'user_id'      => $this->userId,
            'command_type' => 'update_products',
            'payload'      => [], // No extra payload needed for B012 trigger
            'status'       => 'pending',
            'remark'       => $this->remark ?: __('Batch Product Sync'),
        ]);

        $success = $mqttService->pushCommand(
            $machine->serial_no,
            'update_products',
            [],
            (string) $command->id
        );

        if (!$success) {
            $command->update(['status' => 'failed', 'note' => 'MQTT push failed']);
        }
    }
}
