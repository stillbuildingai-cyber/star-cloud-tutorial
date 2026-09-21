<?php

namespace App\Http\Controllers\Api\V1\App;

use App\Http\Controllers\Admin\BasicSettings\MachineScheduleController;
use App\Models\Machine\Machine;
use App\Models\Machine\MachineSchedule;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * 排程功能的邏輯完全沿用 MachineScheduleController（四支方法本來就回傳 JSON），
 * 這裡只加一層「這個 App 帳號是否真的被指派管理這台機台」的檢查，
 * 不重寫一份排程的 CRUD。
 */
class CustomerScheduleController extends MachineScheduleController
{
    public function index(Machine $machine): JsonResponse
    {
        $this->ensureAccess($machine);

        return parent::index($machine);
    }

    public function store(Request $request, Machine $machine): JsonResponse
    {
        $this->ensureAccess($machine);

        return parent::store($request, $machine);
    }

    public function toggle(Machine $machine, MachineSchedule $schedule): JsonResponse
    {
        $this->ensureAccess($machine);

        return parent::toggle($machine, $schedule);
    }

    public function destroy(Machine $machine, MachineSchedule $schedule): JsonResponse
    {
        $this->ensureAccess($machine);

        return parent::destroy($machine, $schedule);
    }

    protected function ensureAccess(Machine $machine): void
    {
        abort_unless(
            request()->user()->machines()->where('machines.id', $machine->id)->exists(),
            403
        );
    }
}
