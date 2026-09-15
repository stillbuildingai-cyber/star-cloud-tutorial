<?php

namespace App\Http\Controllers\Admin\BasicSettings;

use App\Http\Controllers\Admin\AdminController;
use App\Models\Machine\Machine;
use App\Models\Machine\MachineSchedule;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class MachineScheduleController extends AdminController
{
    /**
     * 列出某台機台的所有排程
     */
    public function index(Machine $machine): JsonResponse
    {
        return response()->json([
            'success' => true,
            'schedules' => $machine->schedules()->orderBy('time')->get(),
        ]);
    }

    /**
     * 新增一筆排程
     */
    public function store(Request $request, Machine $machine): JsonResponse
    {
        $validated = $request->validate([
            'action' => 'required|in:power_on,power_off',
            'days_of_week' => 'required|array|min:1',
            'days_of_week.*' => 'integer|between:1,7',
            'time' => 'required|date_format:H:i',
        ]);

        $schedule = $machine->schedules()->create([
            'action' => $validated['action'],
            'days_of_week' => implode(',', $validated['days_of_week']),
            'time' => $validated['time'],
            'is_active' => true,
        ]);

        return response()->json([
            'success' => true,
            'schedule' => $schedule,
        ]);
    }

    /**
     * 切換排程的啟用/停用狀態
     */
    public function toggle(Machine $machine, MachineSchedule $schedule): JsonResponse
    {
        abort_if($schedule->machine_id !== $machine->id, 404);

        $schedule->update(['is_active' => ! $schedule->is_active]);

        return response()->json([
            'success' => true,
            'schedule' => $schedule,
        ]);
    }

    /**
     * 刪除一筆排程
     */
    public function destroy(Machine $machine, MachineSchedule $schedule): JsonResponse
    {
        abort_if($schedule->machine_id !== $machine->id, 404);

        $schedule->delete();

        return response()->json(['success' => true]);
    }
}
