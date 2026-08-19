<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Machine\Machine;
use App\Models\Machine\RemoteCommand;
use App\Services\Machine\MqttService;
use App\Services\Machine\MachineService;
use Illuminate\Support\Facades\Auth;

class RemoteController extends Controller
{
    protected $mqttService;
    protected $machineService;

    public function __construct(MqttService $mqttService, MachineService $machineService)
    {
        $this->mqttService = $mqttService;
        $this->machineService = $machineService;
    }

    /**
     * 遠端管理指揮中心
     */
    public function index(Request $request)
    {
        $selectedMachine = null;

        // --- 1. 機台列表處理 (New Command Tab) ---
        $machineQuery = Machine::withCount(['slots'])->orderBy('last_heartbeat_at', 'desc')->orderBy('id', 'desc');

        if ($request->filled('search') && $request->input('tab') === 'list') {
            $search = $request->input('search');
            $machineQuery->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('serial_no', 'like', "%{$search}%");
            });
        }

        $machines = $machineQuery->paginate($request->input('per_page', 10), ['*'], 'machine_page');

        // --- 2. 歷史紀錄處理 (Operation Records Tab) ---
        // whereHas('machine') 會套用 Machine 的 machine_access 全域作用域，
        // 確保租戶僅能看到自己被授權機台的指令紀錄（系統管理員不受作用域限制，仍可看全部）。
        $historyQuery = RemoteCommand::where('command_type', '!=', 'reload_stock')
            ->whereHas('machine')
            ->with(['machine', 'user']);

        if ($request->filled('search') && ($request->input('tab') === 'history' || !$request->has('tab'))) {
            $search = $request->input('search');
            $historyQuery->where(function ($q) use ($search) {
                $q->whereHas('machine', function ($mq) use ($search) {
                    $mq->where('name', 'like', "%{$search}%")
                        ->orWhere('serial_no', 'like', "%{$search}%");
                })->orWhereHas('user', function ($uq) use ($search) {
                    $uq->where('name', 'like', "%{$search}%");
                })->orWhere('remark', 'like', "%{$search}%")
                  ->orWhere('note', 'like', "%{$search}%");
            });
        }

        // 時間區間過濾 (created_at)
        if ($request->filled('start_date') || $request->filled('end_date')) {
            try {
                if ($request->filled('start_date')) {
                    $start = \Illuminate\Support\Carbon::parse($request->input('start_date'));
                    $historyQuery->where('created_at', '>=', $start);
                }
                if ($request->filled('end_date')) {
                    $end = \Illuminate\Support\Carbon::parse($request->input('end_date'));
                    $historyQuery->where('created_at', '<=', $end);
                }
            } catch (\Exception $e) {
                // 忽略解析錯誤
            }
        } else {
            // 預設過濾當天紀錄
            $historyQuery->whereDate('created_at', now()->toDateString());
        }

        // 指令類型過濾
        if ($request->filled('command_type')) {
            $historyQuery->where('command_type', $request->input('command_type'));
        }

        // 狀態過濾
        if ($request->filled('status')) {
            $historyQuery->where('status', $request->input('status'));
        }

        $history = $historyQuery->latest()->paginate($request->input('per_page', 10), ['*'], 'history_page');

        // --- 3. 特定機台詳情處理 ---
        if ($request->has('machine_id')) {
            $selectedMachine = Machine::with([
                'slots.product',
                'commands' => function ($query) {
                    $query->where('command_type', '!=', 'reload_stock')
                        ->latest()
                        ->limit(5);
                }
            ])->find($request->machine_id);
        }

        // --- 4. AJAX 回應處理 ---
        if ($request->ajax()) {
            if ($request->has('tab')) {
                $tab = $request->input('tab');
                $viewPath = $tab === 'list' ? 'admin.remote.partials.tab-machines-index' : 'admin.remote.partials.tab-history-index';

                return response()->json([
                    'success' => true,
                    'html' => view($viewPath, [
                        'machines' => $machines,
                        'history' => $history,
                    ])->render()
                ]);
            }

            return response()->json([
                'success' => true,
                'machine' => $selectedMachine,
                'commands' => $selectedMachine ? $selectedMachine->commands : []
            ]);
        }

        return view('admin.remote.index', [
            'machines' => $machines,
            'selectedMachine' => $selectedMachine,
            'history' => $history,
            'title' => __('Remote Command Center'),
            'subtitle' => __('Execute maintenance and operational commands remotely')
        ]);
    }

    /**
     * 儲存遠端指令
     */
    public function storeCommand(Request $request)
    {
        $validated = $request->validate([
            'machine_id' => 'required|exists:machines,id',
            'command_type' => 'required|string|in:reboot,reboot_force,reboot_card,checkout,lock,unlock,change,dispense,fanon,fanoff,fanauto,power_on,power_off,power_toggle,power_status',
            'amount' => 'nullable|integer|min:0',
            'slot_no' => 'nullable|string',
            'note' => 'nullable|string|max:255',
        ]);

        if ($validated['command_type'] === 'dispense') {
            $this->machineService->dispatchDispense(
                Machine::findOrFail($validated['machine_id']),
                $validated['slot_no'],
                auth()->id(),
                $validated['note'] ?? null
            );
        } else {
            $payload = [];
            if ($validated['command_type'] === 'change') {
                $payload['amount'] = $validated['amount'];
            }

            // 指令去重：將同機台、同類期的 pending 指令標記為「已取代」
            RemoteCommand::where('machine_id', $validated['machine_id'])
                ->where('command_type', $validated['command_type'])
                ->where('status', 'pending')
                ->update([
                    'status' => 'superseded',
                    'note' => __('Superseded by new command'),
                    'executed_at' => now(),
                ]);

            $command = RemoteCommand::create([
                'machine_id' => $validated['machine_id'],
                'user_id' => auth()->id(),
                'command_type' => $validated['command_type'],
                'payload' => $payload,
                'status' => 'pending',
                'remark' => $validated['note'] ?? null,
            ]);

            // 推播 MQTT 指令
            $machine = Machine::find($validated['machine_id']);
            if ($machine) {
                $this->mqttService->pushCommand(
                    $machine->serial_no,
                    $command->command_type,
                    $command->payload,
                    (string) $command->id
                );
            }
        }

        session()->flash('success', __('Command has been queued successfully.'));

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => __('Command has been queued successfully.')
            ]);
        }

        return redirect()->back();
    }
}
