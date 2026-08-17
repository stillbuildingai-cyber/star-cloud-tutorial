<?php

namespace App\Jobs\Notification;

use App\Models\Machine\MachineLog;
use App\Services\Notification\DiscordWebhookService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendDiscordNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * 任務最大重試次數
     */
    public int $tries = 3;

    protected int $logId;

    /**
     * 建立新任務實例
     */
    public function __construct(int $logId)
    {
        $this->logId = $logId;
    }

    /**
     * 執行任務
     */
    public function handle(DiscordWebhookService $service): void
    {
        $log = MachineLog::find($this->logId);
        
        if (!$log) {
            Log::warning("SendDiscordNotificationJob: MachineLog [{$this->logId}] not found. Skipping.");
            return;
        }

        $machine = $log->machine;
        if (!$machine || !$machine->company) {
            return;
        }

        $company = $machine->company;
        $companySettings = $company->settings ?? [];

        // 驗證是否啟用 Discord 通知
        if (!($companySettings['discord_notify_enabled'] ?? false)) {
            return;
        }

        // 優先使用機台專屬 Webhook URL，其次為全域設定
        $machineSettings = $machine->settings ?? [];
        $webhookUrl = $machineSettings['discord_webhook_url'] ?? $companySettings['discord_webhook_url'] ?? null;
        if (!$webhookUrl) {
            return;
        }

        // 僅針對類型進行過濾，不再進行重要級別 (Levels) 的篩選
        $allowedTypes = $companySettings['discord_notify_types'] ?? [];

        if (!in_array($log->type, $allowedTypes)) {
            Log::info("SendDiscordNotificationJob: Log [{$this->logId}] filtered by type settings.", [
                'log_type' => $log->type,
                'allowed_types' => $allowedTypes
            ]);
            return;
        }

        // 發送告警通知
        $success = $service->sendMachineLogAlert($webhookUrl, $log);

        if (!$success) {
            // 拋出 Exception 以便 Laravel Queue 機制自動重試
            throw new \RuntimeException("Failed to deliver Discord notification for Log ID: {$log->id}");
        }
    }
}
