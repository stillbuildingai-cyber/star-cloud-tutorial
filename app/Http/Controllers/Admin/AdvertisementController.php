<?php

namespace App\Http\Controllers\Admin;

use App\Models\Machine\Machine;
use App\Models\Machine\MachineAdvertisement;
use App\Models\System\Advertisement;
use App\Models\System\Company;
use App\Models\System\SystemOperationLog;
use App\Traits\ImageHandler;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AdvertisementController extends AdminController
{
    use ImageHandler;

    public function index(Request $request)
    {
        $user = auth()->user();
        $tab = $request->input('tab', 'list');
        $perPage = in_array((int) $request->input('per_page', 10), [10, 25, 50, 100], true)
            ? (int) $request->input('per_page', 10)
            : 10;

        // Tab 1: 廣告列表
        $query = Advertisement::with('company');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where('name', 'like', "%{$search}%");
        }

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('company_id')) {
            $query->where('company_id', $request->company_id);
        }

        $advertisements = $query->latest()->paginate($perPage)->withQueryString();
        
        // Tab 2: 機台廣告設置 (所需資料) - 隱藏已過期的廣告
        $allAds = Advertisement::playing()->get();

        // Tab 2: 機台廣告設置 (所需資料)
        // 取得使用者有權限的機台列表 (已透過 Global Scope 過濾)
        $machines = Machine::select('id', 'name', 'serial_no', 'company_id')->get();

        $companies = $user->isSystemAdmin() ? Company::orderBy('name')->get() : collect();

        return view('admin.ads.index', [
            'advertisements' => $advertisements,
            'machines' => $machines,
            'tab' => $tab,
            'allAds' => $allAds,
            'companies' => $companies,
        ]);
    }

    /**
     * 素材 CRUD: 儲存廣告
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:image,video',
            'duration' => 'required|in:15,30,60',
            'file' => [
                'required',
                'file',
                'mimes:jpeg,png,jpg,gif,webp,mp4,mov,avi',
                $request->type === 'image' ? 'max:10240' : 'max:51200', // Image 10MB, Video 50MB
            ],
            'company_id' => 'nullable|exists:companies,id',
            'start_at' => 'nullable|date',
            'end_at' => 'nullable|date|after_or_equal:start_at',
        ]);

        try {
            $user = auth()->user();
            $file = $request->file('file');
            
            if ($request->type === 'image') {
                $path = $this->storeAsWebp($file, 'ads');
            } else {
                $path = $file->store('ads', 'public');
            }

            if ($user->isSystemAdmin()) {
                $companyId = $request->filled('company_id') ? $request->company_id : null;
            } else {
                $companyId = $user->company_id;
            }

            $advertisement = Advertisement::create([
                'company_id' => $companyId,
                'name' => $request->name,
                'type' => $request->type,
                'duration' => (int) $request->duration,
                'url' => Storage::disk('public')->url($path),
                'is_active' => true,
                'start_at' => $request->start_at,
                'end_at' => $request->end_at,
            ]);

            if ($request->wantsJson()) {
                session()->flash('success', __('Advertisement created successfully.'));
                return response()->json([
                    'success' => true,
                    'message' => __('Advertisement created successfully.'),
                    'data' => $advertisement
                ]);
            }

            return redirect()->back()->with('success', __('Advertisement created successfully.'));

        } catch (\Exception $e) {
            \Log::error('Advertisement Upload Error: ' . $e->getMessage(), [
                'user_id' => auth()->id(),
                'file_name' => $request->file('file')?->getClientOriginalName(),
                'trace' => $e->getTraceAsString()
            ]);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => __('Failed to process advertisement file: ') . $e->getMessage()
                ], 500);
            }

            return redirect()->back()->with('error', __('Failed to process advertisement file.'));
        }
    }

    public function update(Request $request, Advertisement $advertisement)
    {
        $rules = [
            'name' => 'required|string|max:255',
            'type' => 'required|in:image,video',
            'duration' => 'required|in:15,30,60',
            'is_active' => 'boolean',
            'company_id' => 'nullable|exists:companies,id',
            'start_at' => 'nullable|date',
            'end_at' => 'nullable|date|after_or_equal:start_at',
        ];

        if ($request->hasFile('file')) {
            $rules['file'] = [
                'file',
                'mimes:jpeg,png,jpg,gif,webp,mp4,mov,avi',
                $request->type === 'image' ? 'max:10240' : 'max:51200',
            ];
        }

        $request->validate($rules);

        $data = $request->only(['name', 'type', 'duration', 'start_at', 'end_at']);
        $data['is_active'] = $request->has('is_active');
        
        $user = auth()->user();
        if ($user->isSystemAdmin()) {
            $data['company_id'] = $request->filled('company_id') ? $request->company_id : null;
        }

        if ($request->hasFile('file')) {
            // 刪除舊檔案
            // 處理 URL 可能包含 storage 或原始路徑的情況
            $oldPath = str_replace(Storage::disk('public')->url(''), '', $advertisement->url);
            // 去除開頭可能的斜線
            $oldPath = ltrim($oldPath, '/');
            Storage::disk('public')->delete($oldPath);

            // 存入新檔案
            $file = $request->file('file');
            if ($request->type === 'image') {
                $path = $this->storeAsWebp($file, 'ads');
            } else {
                $path = $file->store('ads', 'public');
            }
            $data['url'] = Storage::disk('public')->url($path);
        }

        $advertisement->update($data);

        if ($request->wantsJson()) {
            session()->flash('success', __('Advertisement updated successfully.'));
            return response()->json([
                'success' => true,
                'message' => __('Advertisement updated successfully.'),
                'data' => $advertisement
            ]);
        }

        return redirect()->back()->with('success', __('Advertisement updated successfully.'));
    }

    public function destroy(Request $request, Advertisement $advertisement)
    {
        // 檢查是否有機台正投放中
        if ($advertisement->machineAdvertisements()->exists()) {
            return redirect()->back()->with('error', __('Cannot delete advertisement being used by machines.'));
        }

        // 刪除實體檔案
        $path = str_replace(Storage::disk('public')->url(''), '', $advertisement->url);
        Storage::disk('public')->delete($path);

        $advertisement->delete();
        if ($request->wantsJson()) {
            session()->flash('success', __('Advertisement deleted successfully.'));
            return response()->json([
                'success' => true,
                'message' => __('Advertisement deleted successfully.')
            ]);
        }
        
        return redirect()->back()->with('success', __('Advertisement deleted successfully.'));
    }

    /**
     * AJAX: 取得特定機台的廣告投放清單
     */
    public function getMachineAds(Machine $machine)
    {
        $assignments = MachineAdvertisement::where('machine_id', $machine->id)
            ->with('advertisement')
            ->orderBy('sort_order', 'asc')
            ->get()
            ->groupBy('position');

        return response()->json([
            'success' => true,
            'data' => $assignments
        ]);
    }

    /**
     * 投放廣告至機台
     */
    public function assign(Request $request)
    {
        $request->validate([
            'machine_id' => 'required|exists:machines,id',
            'advertisement_id' => 'required|exists:advertisements,id',
            'position' => 'required|in:vending,visit_gift,standby',
            'sort_order' => 'nullable|integer',
        ]);

        // If sort_order is not provided, append to the end of the current position list
        $newSortOrder = $request->sort_order;
        if (is_null($newSortOrder)) {
            $newSortOrder = MachineAdvertisement::where('machine_id', $request->machine_id)
                ->where('position', $request->position)
                ->max('sort_order') + 1;
        }

        MachineAdvertisement::updateOrCreate(
            [
                'machine_id' => $request->machine_id,
                'position' => $request->position,
                'advertisement_id' => $request->advertisement_id,
            ],
            [
                'sort_order' => $newSortOrder,
            ]
        );

        return response()->json([
            'success' => true,
            'message' => __('Advertisement assigned successfully.')
        ]);
    }

    /**
     * 重新排序廣告播放順序
     */
    public function reorderAssignments(Request $request)
    {
        $request->validate([
            'assignment_ids' => 'required|array',
            'assignment_ids.*' => 'exists:machine_advertisements,id'
        ]);

        foreach ($request->assignment_ids as $index => $id) {
            MachineAdvertisement::where('id', $id)->update(['sort_order' => $index]);
        }

        return response()->json([
            'success' => true,
            'message' => __('Order updated successfully.')
        ]);
    }

    /**
     * 移除廣告投放
     */
    public function removeAssignment($id)
    {
        $assignment = MachineAdvertisement::findOrFail($id);
        $assignment->delete();

        return response()->json([
            'success' => true,
            'message' => __('Assignment removed successfully.')
        ]);
    }

    /**
     * 同步廣告至機台 (透過 MQTT 發送 update_ads 指令)
     */
    public function syncToMachine(Request $request, Machine $machine, \App\Services\Machine\MqttService $mqttService)
    {
        // 併行檢查：若有 pending 指令，超過 1 分鐘視為逾時可覆蓋
        $pendingCommand = \App\Models\Machine\RemoteCommand::where('machine_id', $machine->id)
            ->where('command_type', 'update_ads')
            ->where('status', 'pending')
            ->latest()
            ->first();

        if ($pendingCommand) {
            $isTimedOut = $pendingCommand->created_at->lt(now()->subMinute());

            if (!$isTimedOut) {
                return response()->json([
                    'success' => false,
                    'message' => __('This machine has a pending command. Please wait.')
                ], 422);
            }

            // 超過 1 分鐘：視為逾時，自動標記舊指令
            $pendingCommand->update([
                'status' => 'timeout',
                'note'   => __('Superseded by new command (Timeout)'),
            ]);
        }

        $command = \App\Models\Machine\RemoteCommand::create([
            'machine_id' => $machine->id,
            'user_id' => auth()->id(),
            'command_type' => 'update_ads',
            'payload' => [],
            'status' => 'pending',
            'remark' => $request->filled('note') ? $request->input('note') : __('Manual Sync Ads'),
        ]);

        $success = $mqttService->pushCommand(
            $machine->serial_no,
            'update_ads',
            [],
            (string) $command->id
        );

        if ($success) {
            return response()->json([
                'success' => true,
                'message' => __('Sync command sent successfully.')
            ]);
        }

        $command->update(['status' => 'failed', 'note' => 'MQTT push failed']);

        return response()->json([
            'success' => false,
            'message' => __('Failed to send sync command.')
        ], 500);
    }

    public function toggleStatus($id)
    {
        try {
            $advertisement = Advertisement::findOrFail($id);
            $oldValues = $advertisement->toArray();
            $advertisement->is_active = !$advertisement->is_active;
            $advertisement->save();
            $newValues = $advertisement->fresh()->toArray();

            SystemOperationLog::create([
                'company_id' => $advertisement->company_id,
                'user_id' => auth()->id(),
                'module' => 'advertisement',
                'action' => 'update',
                'target_id' => $advertisement->id,
                'target_type' => Advertisement::class,
                'note' => 'advertisement_status_toggled',
                'old_values' => $oldValues,
                'new_values' => $newValues,
            ]);

            $status = $advertisement->is_active ? __('Enabled') : __('Disabled');
            
            if (request()->ajax()) {
                session()->flash('success', __('Advertisement status updated to :status', ['status' => $status]));
                return response()->json([
                    'success' => true,
                    'message' => __('Advertisement status updated to :status', ['status' => $status]),
                    'is_active' => $advertisement->is_active
                ]);
            }

            return redirect()->back()->with('success', __('Advertisement status updated to :status', ['status' => $status]));
        } catch (\Exception $e) {
            if (request()->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage()
                ], 500);
            }
            return redirect()->back()->with('error', $e->getMessage());
        }
    }
}
