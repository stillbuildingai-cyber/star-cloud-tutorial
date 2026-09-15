<?php

namespace App\Http\Controllers\Admin;

use App\Models\Machine\Machine;
use App\Models\System\Company;
use App\Services\Machine\MachineService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MachineController extends AdminController
{
    public function __construct(protected MachineService $machineService)
    {
    }

    public function index(Request $request): View
    {
        $per_page = $request->input('per_page', 10);
        $companyId = trim((string) $request->input('company_id', ''));

        $query = Machine::query();
        $currentUser = auth()->user();

        // 搜尋：名稱或序號
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('serial_no', 'like', "%{$search}%");
            });
        }

        if ($currentUser->isSystemAdmin() && $companyId !== '') {
            $query->where('company_id', $companyId);
        }

        // 刷卡機狀態：以彙總/相關子查詢預載，避免列表逐列 N+1
        $query->select('machines.*')
            ->withCount([
                'logs as card_terminal_error_count' => fn($q) => $q->where('type', 'card_terminal')->where('level', 'error')->where('is_resolved', false),
                'logs as card_terminal_warning_count' => fn($q) => $q->where('type', 'card_terminal')->where('level', 'warning')->where('is_resolved', false),
            ])
            ->addSelect(['latest_card_terminal_log_at' => \App\Models\Machine\MachineLog::select('created_at')
                ->whereColumn('machine_id', 'machines.id')
                ->where('type', 'card_terminal')
                ->whereIn('level', ['error', 'warning'])
                ->where('is_resolved', false)
                ->latest()
                ->limit(1)
            ]);

        // 預加載統計資料
        $machines = $query->orderBy("last_heartbeat_at", "desc")
            ->orderBy("id", "desc")
            ->paginate($per_page)
            ->withQueryString();

        $companies = $currentUser->isSystemAdmin()
            ? Company::select('id', 'name', 'code')->orderBy('name')->get()
            : collect();

        return view('admin.machines.index', compact('machines', 'companies'));
    }

    /**
     * 更新機台基本資訊 (目前僅名稱)
     */
    public function update(Request $request, Machine $machine)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'ambient_temp_setting' => 'nullable|numeric',
        ]);

        $machine->update($validated);

        if ($machine->wasChanged('ambient_temp_setting')) {
            $newSetting = $machine->ambient_temp_setting;

            // 將先前未執行的同類型指令標記為 superseded (被取代)
            \App\Models\Machine\RemoteCommand::where('machine_id', $machine->id)
                ->where('command_type', 'ambient_temp_limit')
                ->where('status', 'pending')
                ->update([
                    'status' => 'superseded',
                    'remark' => __('Superseded by new command'),
                    'executed_at' => now(),
                ]);

            // 建立新指令
            $command = \App\Models\Machine\RemoteCommand::create([
                'machine_id' => $machine->id,
                'user_id' => auth()->id() ?: 1,
                'command_type' => 'ambient_temp_limit',
                'payload' => [
                    'temperature' => $newSetting !== null ? (float)$newSetting : null
                ],
                'status' => 'pending',
                'remark' => $newSetting !== null 
                    ? __('Temperature reaches :temp degrees, the fan will turn on', ['temp' => $newSetting]) 
                    : __('Disabled ambient temperature monitoring')
            ]);

            // 發送 MQTT 指令
            $mqttService = app(\App\Services\Machine\MqttService::class);
            $mqttService->pushCommand(
                $machine->serial_no,
                $command->command_type,
                $command->payload,
                (string)$command->id
            );
        }

        return redirect()->route('admin.machines.index')
            ->with('success', __('Machine updated successfully.'));
    }

    /**
     * 顯示特定機台的日誌與詳細資訊
     */
    public function show(int $id): View
    {
        $machine = Machine::with([
            'logs' => function ($query) {
                $query->latest()->limit(50);
            }
        ])->findOrFail($id);

        return view('admin.machines.show', compact('machine'));
    }


    /**
     * AJAX: 取得機台抽屜面板所需的歷程日誌
     */
    public function logsAjax(Request $request, Machine $machine)
    {
        $per_page = $request->input('per_page', 20);

        $startDate = $request->get('start_date');
        $endDate = $request->get('end_date');

        $logs = $machine->logs()
            ->when($request->level, function ($query, $level) {
                return $query->where('level', $level);
            })
            ->when($startDate, function ($query, $start) {
                return $query->where('created_at', '>=', str_replace('T', ' ', $start));
            })
            ->when($endDate, function ($query, $end) {
                return $query->where('created_at', '<=', str_replace('T', ' ', $end));
            })
            ->when($request->type, function ($query, $type) {
                return $query->where('type', $type);
            })
            ->latest()
            ->paginate($per_page);

        return response()->json([
            'success' => true,
            'data' => $logs->items(),
            'pagination' => [
                'total' => $logs->total(),
                'current_page' => $logs->currentPage(),
                'last_page' => $logs->lastPage(),
            ]
        ]);
    }


    /**
     * AJAX: 清除機台所有未解決的警告與異常 (手動排除)
     */
    public function resolveLogs(Machine $machine)
    {
        $machine->logs()
            ->where('is_resolved', false)
            ->whereIn('level', ['error', 'warning'])
            ->update(['is_resolved' => true]);

        return response()->json([
            'success' => true,
            'message' => __('All issues marked as resolved.')
        ]);
    }

    /**
     * AJAX: 取得機台溫度歷史紀錄 (供圖表使用)
     */
    public function temperatureAjax(Request $request, Machine $machine)
    {
        $startDate = $request->get('start_date');
        $endDate = $request->get('end_date');

        $logs = $machine->logs()
            ->where('message', 'Temperature reported: :temp°C')
            ->when($startDate, function ($query, $start) {
                return $query->where('created_at', '>=', str_replace('T', ' ', $start));
            })
            ->when($endDate, function ($query, $end) {
                return $query->where('created_at', '<=', str_replace('T', ' ', $end));
            })
            ->oldest()
            ->get();

        $chartData = $logs->map(fn($log) => [
            'x' => $log->created_at->getTimestamp() * 1000,
            'y' => (float) ($log->context['temp'] ?? 0),
        ])->values()->toArray();

        return response()->json([
            'success' => true,
            'data' => $chartData
        ]);
    }

    /**
     * AJAX: 取得機台環境溫度歷史紀錄 (供圖表使用)
     */
    public function ambientTemperatureAjax(Request $request, Machine $machine)
    {
        $startDate = $request->get('start_date');
        $endDate = $request->get('end_date');

        $logs = $machine->logs()
            ->where('type', 'ambient_temp')
            ->where('message', 'Ambient temperature reported: :temp°C')
            ->when($startDate, function ($query, $start) {
                return $query->where('created_at', '>=', str_replace('T', ' ', $start));
            })
            ->when($endDate, function ($query, $end) {
                return $query->where('created_at', '<=', str_replace('T', ' ', $end));
            })
            ->oldest()
            ->get();

        $chartData = $logs->map(fn($log) => [
            'x' => $log->created_at->getTimestamp() * 1000,
            'y' => (float) ($log->context['temp'] ?? 0),
        ])->values()->toArray();

        return response()->json([
            'success' => true,
            'data' => $chartData
        ]);
    }
}
