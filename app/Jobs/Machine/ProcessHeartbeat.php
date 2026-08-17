<?php

namespace App\Jobs\Machine;

use App\Models\Machine\Machine;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessHeartbeat implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $serialNo;
    protected $payload;

    /**
     * Create a new job instance.
     */
    public function __construct(string $serialNo, $payload)
    {
        $this->serialNo = $serialNo;
        $this->payload = (array) $payload;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $machine = Machine::withoutGlobalScopes()->where('serial_no', $this->serialNo)->first();

        if (!$machine) {
            Log::warning("MQTT Heartbeat: Machine not found", ['serial_no' => $this->serialNo]);
            return;
        }

        // 更新機台狀態
        Log::debug("ProcessHeartbeat: Attempting to set status to online for {$this->serialNo}");
        $updateData = [
            'last_heartbeat_at' => now(),
            'status' => 'online',
        ];

        // === 狀態異動觸發 (Redis 快取，讓 MQTT 也支援日誌) ===
        $cacheKey = "machine:{$this->serialNo}:state";
        $oldState = \Illuminate\Support\Facades\Cache::get($cacheKey);
        // 1. 偵測狀態由離線轉在線 (寫入日誌)
        if ($machine->status === 'offline') {
            \App\Jobs\Machine\ProcessStateLog::dispatch(
                $machine->id,
                $machine->company_id,
                "Machine is online",
                'info',
                []
            );
        }

        $newState = $oldState ?? [];

        // 2. 韌體版本比對 (有改才傳，有改才記)
        if (isset($this->payload['firmware_version'])) {
            $fv = (string) $this->payload['firmware_version'];

            // 優先從快取取舊版本，若無則從資料庫取
            $oldVersion = $oldState['firmware_version'] ?? $machine->firmware_version;

            if ((string)$oldVersion !== $fv) {
                \App\Jobs\Machine\ProcessStateLog::dispatch(
                    $machine->id,
                    $machine->company_id,
                    "Firmware updated to :version",
                    'info',
                    ['version' => $fv, 'old' => $oldVersion ?? 'Unknown']
                );
            }
            $updateData['firmware_version'] = $fv;
            $newState['firmware_version'] = $fv;
        }

        // 2. 溫度更新 (只要有變動就紀錄日誌，並進行溫度監控告警判定)
        if (isset($this->payload['temperature'])) {
            $temp = (int) $this->payload['temperature'];
            Log::debug("ProcessHeartbeat: Temperature reported for {$this->serialNo}: {$temp}");
            $updateData['temperature'] = $temp;

            // 讀取最後一次記錄日誌的溫度與時間
            $tempLogCacheKey = "machine:{$this->serialNo}:last_temp_log";
            $lastLogEntry = \Illuminate\Support\Facades\Cache::get($tempLogCacheKey);

            // 統一邏輯：優先從快取取值，若無則從資料庫取
            $oldTemp = isset($lastLogEntry['value']) ? (int)$lastLogEntry['value'] : $machine->temperature;

            if ($temp !== (int)$oldTemp) {
                Log::debug("ProcessHeartbeat: Temperature changed from {$oldTemp} to {$temp}. Triggering ordinary log.");
                \App\Jobs\Machine\ProcessStateLog::dispatch(
                    $machine->id,
                    $machine->company_id,
                    "Temperature reported: :temp°C",
                    'info',
                    ['temp' => $temp],
                    'status' // 普通狀態日誌，不發送 Discord
                );
            }

            // 無論是否有變動，都更新/延長快取壽命，確保快取資料存在
            \Illuminate\Support\Facades\Cache::put($tempLogCacheKey, [
                'value' => $temp,
                'at' => now()->toDateTimeString()
            ], 604800);

            // === 溫度自適應告警判定 ===
            $tempAlertEnabled = false;
            $machineSettings = $machine->settings ?? [];
            $company = $machine->company;
            $companySettings = $company ? ($company->settings ?? []) : [];

            // 1. 決定是否啟用溫度監控
            $enabledSetting = $machineSettings['temp_alert_enabled'] ?? 'inherit';
            if ($enabledSetting === 'enabled') {
                $tempAlertEnabled = true;
            } elseif ($enabledSetting === 'disabled') {
                $tempAlertEnabled = false;
            } else {
                // inherit 或是沒設定，繼承公司設定
                $companyEnabled = $companySettings['discord_notify_enabled'] ?? false;
                $companyTypes = $companySettings['discord_notify_types'] ?? [];
                if ($companyEnabled && in_array('temperature', $companyTypes)) {
                    $tempAlertEnabled = true;
                }
            }

            if ($tempAlertEnabled) {
                $upperLimit = null;
                $lowerLimit = null;

                // 優先度 1: 機台自訂
                if (isset($machineSettings['temp_upper_limit']) && $machineSettings['temp_upper_limit'] !== '') {
                    $upperLimit = (int)$machineSettings['temp_upper_limit'];
                }
                if (isset($machineSettings['temp_lower_limit']) && $machineSettings['temp_lower_limit'] !== '') {
                    $lowerLimit = (int)$machineSettings['temp_lower_limit'];
                }

                // 優先度 2: 型號預設
                $model = $machine->machineModel;
                if ($model) {
                    $modelSettings = $model->settings ?? [];
                    if ($upperLimit === null && isset($modelSettings['temp_upper_limit']) && $modelSettings['temp_upper_limit'] !== '') {
                        $upperLimit = (int)$modelSettings['temp_upper_limit'];
                    }
                    if ($lowerLimit === null && isset($modelSettings['temp_lower_limit']) && $modelSettings['temp_lower_limit'] !== '') {
                        $lowerLimit = (int)$modelSettings['temp_lower_limit'];
                    }
                }

                // 優先度 3: 公司預設
                if ($upperLimit === null && isset($companySettings['temp_upper_limit']) && $companySettings['temp_upper_limit'] !== '') {
                    $upperLimit = (int)$companySettings['temp_upper_limit'];
                }
                if ($lowerLimit === null && isset($companySettings['temp_lower_limit']) && $companySettings['temp_lower_limit'] !== '') {
                    $lowerLimit = (int)$companySettings['temp_lower_limit'];
                }

                // 全域預設值 fallback
                if ($upperLimit === null) {
                    $upperLimit = 40;
                }
                if ($lowerLimit === null) {
                    $lowerLimit = 0;
                }

                $activeKey = "machine:{$this->serialNo}:temp_alert_active";
                $cooldownKey = "machine:{$this->serialNo}:temp_alert_cooldown";
                $activeStatus = \Illuminate\Support\Facades\Cache::get($activeKey);

                if ($temp > $upperLimit) {
                    // 若從低溫直接跳到高溫，清除低溫告警冷卻快取，但不發送恢復通知
                    if ($activeStatus === 'low') {
                        \Illuminate\Support\Facades\Cache::forget($cooldownKey);
                    }

                    $hasCooldown = \Illuminate\Support\Facades\Cache::has($cooldownKey);
                    if ($activeStatus !== 'high' || !$hasCooldown) {
                        \App\Jobs\Machine\ProcessStateLog::dispatch(
                            $machine->id,
                            $machine->company_id,
                            "Temperature too high: :temp°C",
                            'error',
                            ['temp' => $temp, 'limit' => $upperLimit],
                            'temperature'
                        );
                        
                        \Illuminate\Support\Facades\Cache::put($cooldownKey, true, 3600);
                        \Illuminate\Support\Facades\Cache::put($activeKey, 'high', 86400);
                    }
                } elseif ($temp < $lowerLimit) {
                    // 若從高溫直接跳到低溫，清除高溫告警冷卻快取，但不發送恢復通知
                    if ($activeStatus === 'high') {
                        \Illuminate\Support\Facades\Cache::forget($cooldownKey);
                    }

                    $hasCooldown = \Illuminate\Support\Facades\Cache::has($cooldownKey);
                    if ($activeStatus !== 'low' || !$hasCooldown) {
                        \App\Jobs\Machine\ProcessStateLog::dispatch(
                            $machine->id,
                            $machine->company_id,
                            "Temperature too low: :temp°C",
                            'error',
                            ['temp' => $temp, 'limit' => $lowerLimit],
                            'temperature'
                        );

                        \Illuminate\Support\Facades\Cache::put($cooldownKey, true, 3600);
                        \Illuminate\Support\Facades\Cache::put($activeKey, 'low', 86400);
                    }
                } else {
                    // 溫度正常
                    if ($activeStatus) {
                        \App\Jobs\Machine\ProcessStateLog::dispatch(
                            $machine->id,
                            $machine->company_id,
                            "Temperature restored: :temp°C",
                            'info',
                            ['temp' => $temp],
                            'temperature'
                        );
                        \Illuminate\Support\Facades\Cache::forget($activeKey);
                        \Illuminate\Support\Facades\Cache::forget($cooldownKey);
                    }
                }
            }
        }

        // 更新快取
        \Illuminate\Support\Facades\Cache::put($cacheKey, $newState, 86400);

        $machine->update($updateData);
    }
}
