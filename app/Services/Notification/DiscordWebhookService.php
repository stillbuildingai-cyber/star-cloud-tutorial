<?php

namespace App\Services\Notification;

use App\Models\Machine\MachineLog;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DiscordWebhookService
{
    // B013 硬體代碼翻譯統一由 MachineService::ERROR_CODE_MAP 管理 (Single Source of Truth)

    /**
     * 發送測試通知
     */
    public function sendTestAlert(string $url): bool
    {
        $payload = [
            'username' => 'Star Cloud Alert Bot',
            'embeds' => [
                [
                    'title' => '🎉 Star Cloud 測試通知',
                    'description' => "這是一則來自 Star Cloud 後台的 Discord Webhook 連線測試通知。\n如果您收到這則訊息，代表您的 Webhook 設定已順利生效並正常運作！",
                    'color' => 561586, // cyan-600 (0x0891B2)
                    'fields' => [
                        [
                            'name' => '測試時間 (台北時間)',
                            'value' => now()->timezone('Asia/Taipei')->format('Y-m-d H:i:s'),
                            'inline' => true,
                        ],
                        [
                            'name' => '發送人員',
                            'value' => auth()->user() ? auth()->user()->name : 'System',
                            'inline' => true,
                        ],
                    ],
                    'footer' => [
                        'text' => 'Star Cloud Notification Subsystem',
                    ],
                    'timestamp' => now()->toISOString(),
                ]
            ]
        ];

        return $this->postToWebhook($url, $payload);
    }

    /**
     * 發送機台日誌告警
     */
    public function sendMachineLogAlert(string $url, MachineLog $log): bool
    {
        $machine = $log->machine;
        if (!$machine) {
            return false;
        }

        // 1. 智慧語系切換與還原
        $company = $machine->company;
        $companyLocale = $company ? ($company->settings['locale'] ?? 'zh_TW') : 'zh_TW';
        $originalLocale = app()->getLocale();
        app()->setLocale($companyLocale);

        try {
            $title = '';
            $description = '';
            $color = 561586; // 預設藍色 (cyan-600)
            $fields = [];

            // 根據日誌類別與內容動態建構卡片
            if ($log->type === 'temperature') {
                $temp = $log->context['temp'] ?? 'N/A';
                $limit = $log->context['limit'] ?? 'N/A';

                if (str_contains($log->message, 'too high')) {
                    $title = '🔴 ' . $this->cleanTranslation(__('Temperature Alert (High)'), $companyLocale);
                    $description = $this->cleanTranslation(__('Current temperature (:temp°C) is above the upper limit (:limit°C).', ['temp' => $temp, 'limit' => $limit]), $companyLocale);
                    $color = 14753096; // rose-600

                    $fields[] = [
                        'name' => $this->cleanTranslation(__('Current Temperature'), $companyLocale),
                        'value' => "`{$temp}°C`",
                        'inline' => true,
                    ];
                    $fields[] = [
                        'name' => $this->cleanTranslation(__('Temperature Limit'), $companyLocale),
                        'value' => "`{$limit}°C`",
                        'inline' => true,
                    ];
                } elseif (str_contains($log->message, 'too low')) {
                    $title = '🔴 ' . $this->cleanTranslation(__('Temperature Alert (Low)'), $companyLocale);
                    $description = $this->cleanTranslation(__('Current temperature (:temp°C) is below the lower limit (:limit°C).', ['temp' => $temp, 'limit' => $limit]), $companyLocale);
                    $color = 14753096; // rose-600 (與過高告警一致使用紅色)

                    $fields[] = [
                        'name' => $this->cleanTranslation(__('Current Temperature'), $companyLocale),
                        'value' => "`{$temp}°C`",
                        'inline' => true,
                    ];
                    $fields[] = [
                        'name' => $this->cleanTranslation(__('Temperature Limit'), $companyLocale),
                        'value' => "`{$limit}°C`",
                        'inline' => true,
                    ];
                } else {
                    $title = '🟢 ' . $this->cleanTranslation(__('Temperature Restored'), $companyLocale);
                    $description = $this->cleanTranslation(__('Current temperature has returned to normal range (:temp°C).', ['temp' => $temp]), $companyLocale);
                    $color = 366185; // emerald-600

                    $fields[] = [
                        'name' => $this->cleanTranslation(__('Current Temperature'), $companyLocale),
                        'value' => "`{$temp}°C`",
                        'inline' => true,
                    ];
                }
            } elseif ($log->type === 'status') {
                if ($log->message === 'Connection lost (LWT)') {
                    $title = '🔴 ' . $this->cleanTranslation(__('Machine Connectivity Lost'), $companyLocale);
                    $description = $this->cleanTranslation(__('System detected that the machine disconnected unexpectedly.'), $companyLocale);
                    $color = 14753096; // rose-600 (0xE11D48)
                } elseif ($log->message === 'Connection restored') {
                    $title = '🟢 ' . $this->cleanTranslation(__('Machine Connectivity Restored'), $companyLocale);
                    $description = $this->cleanTranslation(__('Machine has reconnected to the server and resumed normal heartbeat.'), $companyLocale);
                    $color = 366185; // emerald-600 (0x059669)
                } else {
                    $title = 'ℹ️ ' . $this->cleanTranslation(__('Machine Status Updated'), $companyLocale);
                    $description = $this->cleanTranslation(__($log->message), $companyLocale);
                    $color = 14251782; // warning/amber
                }
            } elseif ($log->type === 'submachine') {
                // 解析 B013 Payload，可能包含 tid (貨道) 與 error_code
                $data = is_array($log->context) ? $log->context : json_decode($log->context ?? '', true);
                $errorCode = $data['error_code'] ?? $data['raw_code'] ?? 'Unknown';
                $slotNo = $data['tid'] ?? 'N/A';
                
                // 智慧異常對譯：查詢 MachineService::ERROR_CODE_MAP
                $mapping = \App\Services\Machine\MachineService::ERROR_CODE_MAP[$errorCode] ?? null;
                if ($mapping) {
                    $translatedError = __($mapping['label']);
                } else {
                    $translatedError = $data['translated_label'] ?? $errorCode;
                    $translatedError = __($translatedError);
                }
                
                $icon = ($log->level === 'error') ? '🚨 ' : '⚠️ ';
                $title = $icon . $this->cleanTranslation(__('Submachine Exception'), $companyLocale);
                $description = $this->cleanTranslation(__('Vending machine reported a submachine hardware error.'), $companyLocale);
                $color = ($log->level === 'error') ? 14753096 : 14251782;

                $fields[] = [
                    'name' => __('Error Code'),
                    'value' => "`{$errorCode}`",
                    'inline' => true,
                ];
                $fields[] = [
                    'name' => __('Error Details'),
                    'value' => $this->cleanTranslation($translatedError, $companyLocale),
                    'inline' => true,
                ];
                $fields[] = [
                    'name' => __('Affected Slot'),
                    'value' => "`{$slotNo}`",
                    'inline' => true,
                ];
            } else {
                // 其他類型的日誌
                $title = '📢 ' . $this->cleanTranslation(__('Machine Status Updated'), $companyLocale);
                $description = $this->cleanTranslation(__($log->message), $companyLocale);
                $color = ($log->level === 'error') ? 14753096 : (($log->level === 'warning') ? 14251782 : 561586);
            }

            // 加入機台基本欄位資訊
            $fields[] = [
                'name' => __('Machine Name'),
                'value' => $machine->name ?? __('Unnamed Machine'),
                'inline' => true,
            ];
            $fields[] = [
                'name' => __('Serial No'),
                'value' => "`{$machine->serial_no}`",
                'inline' => true,
            ];
            
            // 所屬公司欄位依使用者要求完全移除
            
            if ($machine->location) {
                $fields[] = [
                    'name' => __('Location'),
                    'value' => $machine->location,
                    'inline' => false,
                ];
            }

            $payload = [
                'username' => 'Star Cloud Alert Bot',
                'embeds' => [
                    [
                        'title' => $title,
                        'description' => $description,
                        'color' => $color,
                        'fields' => $fields,
                        'footer' => [
                            'text' => 'Star Cloud Telemetry Subsystem',
                        ],
                        'timestamp' => $log->created_at ? $log->created_at->toISOString() : now()->toISOString(),
                    ]
                ]
            ];

            return $this->postToWebhook($url, $payload);
        } finally {
            // 強制還原語系，防止系統語系污染
            app()->setLocale($originalLocale);
        }
    }

    /**
     * 清理翻譯字串，移除末尾或中間的英文技術括號說明（如 "(K-PDT)"、"(LWT)" 等）
     */
    protected function cleanTranslation(string $text, string $locale): string
    {
        if ($locale === 'en') {
            return $text;
        }
        // 匹配半角及全角括號，內含英數字、連字號、空格等，例如 " (K-PDT)" 或 " （LWT）"
        return preg_replace('/\s*[\(\（][A-Za-z0-9\-_\s]+[\)\）]/u', '', $text);
    }

    /**
     * HTTP POST 請求 Discord Webhook
     */
    protected function postToWebhook(string $url, array $payload): bool
    {
        try {
            $response = Http::timeout(5)->post($url, $payload);
            
            if ($response->successful()) {
                return true;
            }
            
            Log::error('Discord Webhook Failed: ' . $response->body());
            return false;
        } catch (\Exception $e) {
            Log::error('Discord Webhook Exception: ' . $e->getMessage());
            return false;
        }
    }
}
