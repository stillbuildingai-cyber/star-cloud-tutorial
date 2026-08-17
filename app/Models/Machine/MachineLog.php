<?php

namespace App\Models\Machine;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MachineLog extends Model
{
    use HasFactory;

    const UPDATED_AT = null;

    protected static function booted()
    {
        static::created(function (MachineLog $log) {
            // 1. 取得機台與公司，若無公司則直接 return
            $machine = $log->machine;
            if (!$machine || !$machine->company) {
                return;
            }

            $company = $machine->company;
            $companySettings = $company->settings ?? [];
            
            // 必須啟用 Discord 通知功能
            if (!($companySettings['discord_notify_enabled'] ?? false)) {
                return;
            }

            // 優先讀取機台專屬 Webhook，無則繼承公司全域 Webhook
            $machineSettings = $machine->settings ?? [];
            $webhookUrl = $machineSettings['discord_webhook_url'] ?? $companySettings['discord_webhook_url'] ?? null;
            if (empty($webhookUrl)) {
                return;
            }

            // 2. 判定是否符合發送條件
            $shouldNotify = false;

            if ($log->type === 'temperature') {
                $shouldNotify = true;
            } elseif ($log->type === 'status') {
                if ($log->message === 'Connection lost (LWT)') {
                    $shouldNotify = true;
                } elseif ($log->message === 'Connection restored') {
                    // 尋找此機台前一筆狀態日誌 (ID 比當前小，type 為 status，倒序第一筆)
                    $previousStatus = self::withoutGlobalScopes()
                        ->where('machine_id', $log->machine_id)
                        ->where('type', 'status')
                        ->where('id', '<', $log->id)
                        ->orderBy('id', 'desc')
                        ->first();

                    if ($previousStatus && $previousStatus->message === 'Connection lost (LWT)' && $previousStatus->is_resolved) {
                        // 只有當前一筆是真實 LWT 斷線，且已被消警 (is_resolved = true) 時，才發送恢復通知
                        $shouldNotify = true;
                    }
                }
            } elseif ($log->type === 'submachine') {
                // 下位機日誌僅在 warning 或 error 級別時發送 Discord 告警，info 狀態日誌不發送
                if (in_array($log->level, ['warning', 'error'])) {
                    $shouldNotify = true;
                }
            } elseif ($log->level === 'error') {
                $shouldNotify = true;
            }

            // 3. 若符合發送條件，調度非同步任務
            if ($shouldNotify) {
                \App\Jobs\Notification\SendDiscordNotificationJob::dispatch($log->id);
            }
        });
    }

    protected $fillable = [
        'company_id',
        'machine_id',
        'level',
        'type',
        'message',
        'context',
        'is_resolved',
    ];

    protected $casts = [
        'context' => 'array',
        'is_resolved' => 'boolean',
    ];

    protected $appends = [
        'translated_message',
    ];

    /**
     * 動態重組翻譯後的訊息
     */
    public function getTranslatedMessageAttribute(): string
    {
        $context = $this->context;

        // 刷卡機回應碼：用目前代碼表重組失敗原因，支援多語系顯示。
        if (isset($context['card_code'])) {
            $code = $context['card_code'];
            $reason = \App\Services\Machine\MachineService::CARD_TERMINAL_CODE_MAP[$code] ?? 'Transaction failed';
            return __('Card payment failed') . '：' . __($reason) . " (Code: {$code})";
        }

        // B013 封裝：優先用目前代碼表重組，讓舊日誌也能吃到最新文案。
        if (isset($context['translated_label'])) {
            $code = $context['raw_code'] ?? $context['error_code'] ?? '0000';
            $mapping = \App\Services\Machine\MachineService::ERROR_CODE_MAP[$code] ?? null;
            $label = __($mapping['label'] ?? $context['translated_label']);
            $tid = $context['tid'] ?? null;

            if ($tid) {
                return __('Slot') . " {$tid}: {$label} (Code: {$code})";
            }
            return "{$label} (Code: {$code})";
        }

        // 預設退回原始 message (支援歷史資料的翻譯判定與佔位符替換)
        return __($this->message, $context ?? []);
    }

    public function machine()
    {
        return $this->belongsTo(Machine::class);
    }
}
