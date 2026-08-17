<?php

namespace App\Http\Controllers\Admin\BasicSettings;

use App\Http\Controllers\Admin\AdminController;
use App\Models\Machine\ApkVersion;
use App\Models\Machine\Machine;
use App\Services\Machine\MqttService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

use Illuminate\View\View;

class ApkVersionController extends AdminController
{
    public function index(): View
    {
        // 在進入 APK 版本清單時，同步觸發已到期排程的執行（確保無 cron 的開發/測試環境也能 100% 執行）
        try {
            \Illuminate\Support\Facades\Artisan::call('ota:process-schedules');
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Auto-trigger scheduled OTA failed: ' . $e->getMessage());
        }

        $versions = ApkVersion::latest()->paginate(10);
        
        // 統計並附屬機台部署狀態
        $commands = \App\Models\Machine\RemoteCommand::where('command_type', 'update_app')
            ->with('machine')
            ->get();

        // 撈取所有還沒執行的 OTA 預約排程
        $pendingSchedules = \App\Models\Machine\OtaSchedule::where('status', 'pending')->get();

        // 蒐集所有預約機台 ID，一次性撈出以避免 N+1
        $allScheduledMachineIds = [];
        foreach ($pendingSchedules as $sch) {
            if (is_array($sch->target_value)) {
                $allScheduledMachineIds = array_merge($allScheduledMachineIds, $sch->target_value);
            }
        }
        $allScheduledMachineIds = array_unique(array_filter($allScheduledMachineIds));

        $scheduledMachinesMap = [];
        if (count($allScheduledMachineIds) > 0) {
            $scheduledMachinesMap = \App\Models\Machine\Machine::whereIn('id', $allScheduledMachineIds)
                ->get()
                ->keyBy('id');
        }
        
        foreach ($versions as $version) {
            $pending = [];
            $completed = [];

            // 1. 已排入指令（已發送或執行中）的 RemoteCommand
            foreach ($commands as $cmd) {
                $cmdVersionCode = $cmd->payload['version_code'] ?? null;
                if ($cmdVersionCode == $version->version_code) {
                    if ($cmd->status === 'completed' || $cmd->status === 'success') {
                        $completed[] = $cmd->machine;
                    } elseif ($cmd->status === 'pending' || $cmd->status === 'processing' || $cmd->status === 'sent') {
                        $pending[] = $cmd->machine;
                    }
                }
            }

            // 2. 還沒有執行的預約排程中的機台
            foreach ($pendingSchedules as $sch) {
                if ($sch->apk_version_id == $version->id) {
                    $machineIds = $sch->target_value;
                    if (is_array($machineIds)) {
                        foreach ($machineIds as $mId) {
                            if (isset($scheduledMachinesMap[$mId])) {
                                $pending[] = $scheduledMachinesMap[$mId];
                            }
                        }
                    }
                }
            }

            $version->pending_machines = collect($pending)->filter()->unique('id');
            $version->completed_machines = collect($completed)->filter()->unique('id');
        }

        // 用於 OTA 下發時讓使用者選擇的機台列表
        $machines = Machine::all();
        return view('admin.basic-settings.apk-versions.index', compact('versions', 'machines'));
    }

    public function create(): View
    {
        return view('admin.basic-settings.apk-versions.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'version_name' => 'required|string|max:100',
            'version_code' => 'required|integer|unique:apk_versions,version_code',
            'flavor' => 'required|string|max:100',
            'apk_file' => 'required_without:download_url|nullable|file|max:102400', // 上傳限制 100MB
            'download_url' => 'required_without:apk_file|nullable|url|max:500',
            'release_notes' => 'nullable|string',
        ]);

        $filePath = null;
        if ($request->hasFile('apk_file')) {
            $filePath = $request->file('apk_file')->store('apks', 'public');
        }

        ApkVersion::create([
            'version_name' => $request->version_name,
            'version_code' => $request->version_code,
            'flavor' => $request->flavor,
            'file_path' => $filePath,
            'download_url' => $request->download_url,
            'release_notes' => $request->release_notes,
            'is_forced' => false,
        ]);

        return redirect()->route('admin.basic-settings.apk-versions.index')
            ->with('success', __('APK version uploaded successfully.'));
    }

    public function destroy(ApkVersion $apkVersion)
    {
        if ($apkVersion->file_path) {
            Storage::disk('public')->delete($apkVersion->file_path);
        }
        $apkVersion->delete();
        
        return redirect()->route('admin.basic-settings.apk-versions.index')
            ->with('success', __('APK version deleted successfully.'));
    }

    /**
     * 推送 OTA 更新指令給指定機台
     */
    public function push(Request $request, ApkVersion $apkVersion, MqttService $mqttService)
    {
        $request->validate([
            'machine_ids' => 'required|array',
            'machine_ids.*' => 'exists:machines,id',
            'scheduled_at' => 'nullable|date|after:now',
        ]);

        // 如果設定了預約發送時間
        if ($request->filled('scheduled_at')) {
            \App\Models\Machine\OtaSchedule::create([
                'apk_version_id' => $apkVersion->id,
                'scheduled_at'   => $request->scheduled_at,
                'target_type'    => 'custom',
                'target_value'   => $request->machine_ids,
                'status'         => 'pending',
                'user_id'        => auth()->id(),
            ]);

            return redirect()->route('admin.basic-settings.apk-versions.index')
                ->with('success', __('OTA update scheduled successfully.'));
        }

        $machines = Machine::whereIn('id', $request->machine_ids)->get();
        $successCount = 0;

        foreach ($machines as $machine) {
            // 1. 先建立 RemoteCommand 紀錄，以取得自增 ID 作為 command_id（與系統慣例一致）
            $command = \App\Models\Machine\RemoteCommand::create([
                'machine_id'   => $machine->id,
                'user_id'      => auth()->id(),
                'command_type' => 'update_app',
                'status'       => 'pending',
                'payload'      => [
                    'url'          => $apkVersion->url,
                    'version'      => $apkVersion->version_name,
                    'version_code' => $apkVersion->version_code,
                    'is_forced'    => $apkVersion->is_forced,
                ],
            ]);

            // 2. 推播 MQTT，command_id 使用資料庫自增流水號
            $pushed = $mqttService->pushCommand(
                $machine->serial_no,
                'update_app',
                $command->payload,
                (string) $command->id
            );

            if ($pushed) {
                $successCount++;
            } else {
                // 推播失敗，將紀錄標記為失敗
                $command->update(['status' => 'failed']);
            }
        }

        return redirect()->route('admin.basic-settings.apk-versions.index')
            ->with('success', sprintf(__('OTA command sent to %d devices successfully.'), $successCount));
    }
}
