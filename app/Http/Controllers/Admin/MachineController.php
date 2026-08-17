<?php

namespace App\Http\Controllers\Admin;

use App\Jobs\Product\SendProductSyncCommandJob;
use App\Models\Machine\Machine;
use App\Models\Machine\MachineProductPrice;
use App\Models\Product\Product;
use App\Models\System\Company;
use App\Services\Machine\MachineService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
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
     * 機台使用率統計
     */
    public function utilization(Request $request): View
    {
        // 取得當前使用者有權限的所有機台 (已透過 Global Scope 過濾)
        $machines = Machine::all();

        $date = $request->get('date', now()->toDateString());
        $service = app(\App\Services\Machine\MachineService::class);
        $fleetStats = $service->getFleetStats($date);

        return view('admin.machines.utilization', [
            'machines' => $machines,
            'fleetStats' => $fleetStats,
            'compactMachines' => $machines->map(fn($m) => [
                'id' => $m->id,
                'name' => $m->name,
                'serial_no' => $m->serial_no,
                'status' => $m->status
            ])->values()
        ]);
    }

    /**
     * AJAX: 取得機台所有貨道資訊 (供效期管理視覺化圖表使用)
     */
    public function slotsAjax(Machine $machine)
    {
        $slots = $machine->slots()
            ->with('product:id,name,image_url')
            ->orderByRaw('CAST(slot_no AS UNSIGNED) ASC')
            ->get();

        // 取得該機台目前所有等待中的庫存更新指令 (Pending Commands)
        // 僅將「1 分鐘內」的 pending 視為鎖定中；超過 1 分鐘未收到機台回傳 (ACK)
        // 視為逾時，自動解除鎖定，讓使用者可重新編輯與重新下發 (與 dispense 逾時邏輯一致)
        $pendingSlotNos = \App\Models\Machine\RemoteCommand::where('machine_id', $machine->id)
            ->where('command_type', 'reload_stock')
            ->where('status', 'pending')
            ->where('created_at', '>=', now()->subMinute())
            ->get()
            ->pluck('payload.slot_no')
            ->flatten()
            ->toArray();

        // 將待處理狀態注入貨道物件
        $slots->each(function ($slot) use ($pendingSlotNos) {
            $slot->is_pending = in_array((int) $slot->slot_no, $pendingSlotNos);
        });

        return response()->json([
            'success' => true,
            'machine' => $machine->only(['id', 'name', 'serial_no']),
            'slots' => $slots
        ]);
    }

    /**
     * AJAX: 更新貨道資訊 (庫存、效期、批號)
     */
    public function updateSlotExpiry(Request $request, Machine $machine)
    {
        // 確保庫存數值為整數，處理如 "01" 轉為 1 的情況
        if ($request->has('stock') && !is_null($request->stock) && $request->stock !== '') {
            $request->merge(['stock' => (int) $request->stock]);
        }

        $validated = $request->validate([
            'slot_no' => 'required|integer',
            'stock' => 'nullable|integer|min:0',
            'expiry_date' => 'nullable|date',
            'batch_no' => 'nullable|string|max:50',
        ]);

        try {
            $this->machineService->updateSlot($machine, $validated, auth()->id());

            session()->flash('success', __('Slot updated successfully.'));

            return response()->json([
                'success' => true,
                'message' => __('Slot updated successfully.')
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * AJAX: 遠端鎖定/解鎖單一貨道（暫停販售）。
     * 與 updateSlotExpiry 共用 updateSlot 下發 update_inventory；只帶 is_locked，不動效期/庫存。
     */
    public function toggleSlotLock(Request $request, Machine $machine)
    {
        $validated = $request->validate([
            'slot_no' => 'required|integer',
            'is_locked' => 'required|boolean',
        ]);

        try {
            $this->machineService->updateSlot($machine, $validated, auth()->id());

            return response()->json([
                'success' => true,
                'message' => $validated['is_locked'] ? __('Slot locked.') : __('Slot unlocked.'),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * 機台專屬定價頁：列出該機台「所屬公司的完整商品目錄」（非僅貨道商品），
     * 可在上貨前先把機台價/會員價訂好。未填=沿用全域 products 價。
     */
    public function pricing(Machine $machine): View
    {
        $products = Product::where('company_id', $machine->company_id)
            ->active()
            ->orderBy('name')
            ->get(['id', 'name', 'image_url', 'barcode', 'price', 'member_price']);

        $overrides = $machine->productPrices()->get()->keyBy('product_id');

        $items = $products->map(function (Product $p) use ($overrides) {
            $ov = $overrides->get($p->id);
            return [
                'product_id'            => $p->id,
                'name'                  => $p->name,
                'image_url'             => $p->image_url,
                'barcode'               => $p->barcode,
                'global_price'          => (float) $p->price,
                'global_member_price'   => $p->member_price !== null ? (float) $p->member_price : null,
                'override_price'        => $ov && $ov->price !== null ? (float) $ov->price : null,
                'override_member_price' => $ov && $ov->member_price !== null ? (float) $ov->member_price : null,
            ];
        })->values();

        return view('admin.machines.pricing', compact('machine', 'items'));
    }

    /**
     * 批次儲存機台專屬定價。
     *
     * - price/member_price 皆空 → 刪除覆蓋，回到全域價。
     * - 任一有值 → 建立/更新覆蓋；會員價於 B012 下發時自動夾為不高於機台售價。
     * - 用 quiet 寫入避免逐筆觸發 observer 重複推送，最後統一推一次 B012。
     * - 商品須屬於該機台公司（跨租戶防護）。
     */
    public function updatePricing(Request $request, Machine $machine): \Illuminate\Http\JsonResponse
    {
        $validated = $request->validate([
            'items'                => 'required|array',
            'items.*.product_id'   => 'required|integer|exists:products,id',
            'items.*.price'        => 'nullable|numeric|min:1|max:99999999',
            'items.*.member_price' => 'nullable|numeric|min:1|max:99999999',
        ]);

        $allowedProductIds = Product::where('company_id', $machine->company_id)
            ->pluck('id')
            ->flip();

        DB::transaction(function () use ($validated, $machine, $allowedProductIds) {
            foreach ($validated['items'] as $item) {
                $productId = (int) $item['product_id'];
                if (!$allowedProductIds->has($productId)) {
                    continue;
                }

                $price = isset($item['price']) && $item['price'] !== '' ? (float) $item['price'] : null;
                $memberPrice = isset($item['member_price']) && $item['member_price'] !== '' ? (float) $item['member_price'] : null;

                if ($price === null && $memberPrice === null) {
                    $machine->productPrices()->where('product_id', $productId)->delete();
                    continue;
                }

                $row = MachineProductPrice::firstOrNew([
                    'machine_id' => $machine->id,
                    'product_id' => $productId,
                ]);
                $row->price = $price;
                $row->member_price = $memberPrice;
                $row->saveQuietly();
            }
        });

        // 統一推一次 B012 同步給該機台（覆蓋於請求當下即時套用，無需失效公司快取）
        SendProductSyncCommandJob::dispatch($machine->id, __('Machine pricing updated'), Auth::id());

        return response()->json([
            'success' => true,
            'message' => __('Machine pricing saved and sync command pushed.'),
        ]);
    }

    /**
     * 取得機台統計數據 (AJAX)
     */
    public function utilizationData(Request $request, $id = null)
    {
        $date = $request->get('date', now()->toDateString());
        $service = app(\App\Services\Machine\MachineService::class);

        if ($id) {
            $machine = Machine::findOrFail($id);
            $stats = $service->getUtilizationStats($machine, $date);
        } else {
            $stats = $service->getFleetStats($date);
        }

        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }

    /**
     * 機台維護紀錄 (開發中)
     */
    public function maintenance(Request $request): View
    {
        return view('admin.machines.index', ['machines' => Machine::paginate(1)]); // Placeholder
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
