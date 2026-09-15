<?php

namespace App\Console\Commands\Machine;

use App\Models\Machine\MachineSchedule;
use App\Models\Machine\RemoteCommand;
use App\Services\Machine\MqttService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class DispatchSchedules extends Command
{
    /**
     * @var string
     */
    protected $signature = 'schedules:dispatch';

    /**
     * @var string
     */
    protected $description = '檢查目前時間符合哪些機台排程，把對應的開機/關機指令推進既有的下指令佇列';

    public function handle(MqttService $mqttService): int
    {
        $now = Carbon::now();
        // Carbon::dayOfWeekIso：1=一...7=日，跟 machine_schedules.days_of_week 存的值對齊
        $today = $now->dayOfWeekIso;
        $currentTime = $now->format('H:i');

        $dueSchedules = MachineSchedule::query()
            ->where('is_active', true)
            ->where('time', $currentTime)
            ->with('machine')
            ->get()
            ->filter(fn (MachineSchedule $schedule) => $schedule->runsOnDay($today));

        if ($dueSchedules->isEmpty()) {
            return self::SUCCESS;
        }

        foreach ($dueSchedules as $schedule) {
            $machine = $schedule->machine;

            if (! $machine) {
                continue;
            }

            // 指令去重：跟 RemoteController::storeCommand 同一套邏輯，
            // 避免同一台機台、同一種指令同時有好幾筆 pending 卡住。
            RemoteCommand::where('machine_id', $machine->id)
                ->where('command_type', $schedule->action)
                ->where('status', 'pending')
                ->update([
                    'status' => 'superseded',
                    'note' => '已被排程指令取代',
                    'executed_at' => $now,
                ]);

            $command = RemoteCommand::create([
                'machine_id' => $machine->id,
                'user_id' => null, // 系統排程觸發，不是特定使用者手動下的指令
                'command_type' => $schedule->action,
                'payload' => [],
                'status' => 'pending',
                'remark' => "排程觸發（#{$schedule->id}）",
            ]);

            $mqttService->pushCommand(
                $machine->serial_no,
                $command->command_type,
                $command->payload,
                (string) $command->id
            );

            $this->info("已派送排程指令：機台 {$machine->serial_no}，動作 {$schedule->action}");
        }

        return self::SUCCESS;
    }
}
