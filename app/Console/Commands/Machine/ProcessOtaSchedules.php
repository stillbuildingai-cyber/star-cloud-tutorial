<?php

namespace App\Console\Commands\Machine;

use Illuminate\Console\Command;
use App\Models\Machine\OtaSchedule;
use App\Models\Machine\Machine;
use App\Models\Machine\RemoteCommand;
use App\Services\Machine\MqttService;
use Exception;
use Illuminate\Support\Facades\Log;

class ProcessOtaSchedules extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ota:process-schedules';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process scheduled OTA firmware updates and send MQTT commands to target machines';

    /**
     * Execute the console command.
     */
    public function handle(MqttService $mqttService)
    {
        $this->info('[' . now()->toDateTimeString() . '] Starting to process scheduled OTA updates...');

        // 撈取狀態為待發送且時間已到的排程
        $schedules = OtaSchedule::where('status', 'pending')
            ->where('scheduled_at', '<=', now())
            ->with('apkVersion')
            ->get();

        if ($schedules->isEmpty()) {
            $this->info('No pending OTA schedules to process.');
            return 0;
        }

        $this->info("Found {$schedules->count()} pending OTA schedule(s) to process.");

        foreach ($schedules as $schedule) {
            $this->info("Processing OTA Schedule ID: {$schedule->id} (APK: {$schedule->apkVersion->version_name})");
            
            // 標記為處理中，防止重複執行
            $schedule->update(['status' => 'processing']);
            $apkVersion = $schedule->apkVersion;

            try {
                // 根據 target_type 撈取目標機台
                if ($schedule->target_type === 'all') {
                    $machines = Machine::all();
                } else {
                    $machineIds = $schedule->target_value ?? [];
                    $machines = Machine::whereIn('id', $machineIds)->get();
                }

                if ($machines->isEmpty()) {
                    $this->warn("No target machines found for OTA Schedule ID: {$schedule->id}. Skipping.");
                    $schedule->update(['status' => 'completed']);
                    continue;
                }

                $successCount = 0;

                foreach ($machines as $machine) {
                    // 1. 先建立 RemoteCommand 紀錄
                    $command = RemoteCommand::create([
                        'machine_id'   => $machine->id,
                        'user_id'      => $schedule->user_id,
                        'command_type' => 'update_app',
                        'status'       => 'pending',
                        'payload'      => [
                            'url'          => $apkVersion->url,
                            'version'      => $apkVersion->version_name,
                            'version_code' => $apkVersion->version_code,
                            'is_forced'    => $apkVersion->is_forced,
                        ],
                    ]);

                    // 2. 推播 MQTT 更新指令
                    $pushed = $mqttService->pushCommand(
                        $machine->serial_no,
                        'update_app',
                        $command->payload,
                        (string) $command->id
                    );

                    if ($pushed) {
                        $successCount++;
                    } else {
                        $command->update(['status' => 'failed']);
                        $this->error("Failed to push MQTT OTA command to machine: {$machine->serial_no}");
                    }
                }

                $schedule->update(['status' => 'completed']);
                $this->info("Successfully processed OTA Schedule ID: {$schedule->id}. MQTT deployed to {$successCount}/{$machines->count()} machines.");

            } catch (Exception $e) {
                Log::error("Error processing OTA Schedule ID {$schedule->id}: " . $e->getMessage(), [
                    'exception' => $e
                ]);
                $schedule->update(['status' => 'failed']);
                $this->error("Error processing OTA Schedule ID {$schedule->id}: " . $e->getMessage());
            }
        }

        $this->info('OTA schedules processing completed.');
        return 0;
    }
}
