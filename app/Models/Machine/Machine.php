<?php

namespace App\Models\Machine;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use App\Traits\TenantScoped;

class Machine extends Model
{
    use HasFactory, TenantScoped;

    protected static function booted()
    {
        // 權限隔離：一般帳號登入時只能看到自己被分配的機台
        static::addGlobalScope('machine_access', function (\Illuminate\Database\Eloquent\Builder $builder) {
            $user = auth()->user();
            // 如果是在 Console（且非單元測試）、或是系統管理員，則不限制 (可看所有機台)
            if ((app()->runningInConsole() && !app()->runningUnitTests()) || !$user || $user->isSystemAdmin()) {
                return;
            }

            // 一般租戶帳號：限制只能看自己擁有的機台
            $builder->whereExists(function ($query) use ($user) {
                $query->select(\Illuminate\Support\Facades\DB::raw(1))
                    ->from('machine_user')
                    ->whereColumn('machine_user.machine_id', 'machines.id')
                    ->where('machine_user.user_id', $user->id);
            });
        });

        // 當 api_token 或 serial_no 發生變動時，自動同步至 Redis (供 MQTT 認證使用)
        static::saved(function ($machine) {
            if ($machine->wasChanged('api_token') || $machine->wasChanged('serial_no') || $machine->wasRecentlyCreated) {
                app(\App\Services\Machine\MachineService::class)->syncMqttAuth($machine);

                // 如果是修改序號，則刪除舊的 Redis Key 避免殘留
                if ($machine->wasChanged('serial_no') && !$machine->wasRecentlyCreated) {
                    $oldSerial = $machine->getOriginal('serial_no');
                    if ($oldSerial) {
                        \Illuminate\Support\Facades\Redis::del("machine_auth:{$oldSerial}");
                    }
                }
            }

            if ($machine->wasChanged([
                'is_spring_slot_1_10',
                'is_spring_slot_11_20',
                'is_spring_slot_21_30',
                'is_spring_slot_31_40',
                'is_spring_slot_41_50',
                'is_spring_slot_51_60',
            ])) {
                app(\App\Services\Machine\MachineService::class)->syncMachineSlotMaxStock($machine);
            }

            // 機台轉移公司：不同公司商品 ID 體系不同，舊的機台專屬定價必須清空，
            // 避免跨租戶價格錯位與資料污染。批次刪除不觸發 model 事件，故補推一次
            // B012 同步，讓機台重抓新公司目錄與（已清空的）定價。
            if ($machine->wasChanged('company_id')) {
                $machine->productPrices()->delete();
                \App\Jobs\Product\SendProductSyncCommandJob::dispatch($machine->id, __('Machine company changed'));
            }
        });
    }

    protected $fillable = [
        'company_id',
        'name',
        'serial_no',
        'model',
        'location',
        'address',
        'latitude',
        'longitude',
        'status',
        'current_page',
        'door_status',
        'temperature',
        'ambient_temperature',
        'ambient_temp_setting',
        'ambient_temp_monitoring_enabled',
        'firmware_version',
        'api_token',
        'last_heartbeat_at',
        'card_reader_seconds',
        'card_reader_checkout_time_1',
        'card_reader_checkout_time_2',
        'heating_start_time',
        'heating_end_time',
        'payment_buffer_seconds',
        'card_reader_no',
        'key_no',
        'invoice_status',
        'welcome_gift_enabled',
        'is_spring_slot_1_10',
        'is_spring_slot_11_20',
        'is_spring_slot_21_30',
        'is_spring_slot_31_40',
        'is_spring_slot_41_50',
        'is_spring_slot_51_60',
        'member_system_enabled',
        'payment_config_id',
        'machine_model_id',
        'images',
        'creator_id',
        'updater_id',
        'tax_invoice_enabled',
        'card_terminal_enabled',
        'scan_pay_esun_enabled',
        'scan_pay_linepay_enabled',
        'shopping_cart_enabled',
        'cash_module_enabled',
        'settings',
    ];

    protected $appends = ['image_urls', 'calculated_status', 'hardware_status', 'latest_status_log_time', 'latest_hardware_log_time', 'is_unstable', 'recent_disconnection_count', 'has_login_warning'];

    /**
     * 動態計算機台當前狀態 (僅限連線與系統層級)
     * 優先讀取資料庫 status 欄位
     * 若在線，則進一步檢查過去 15 分鐘是否為異常
     */
    public function getCalculatedStatusAttribute(): string
    {
        // 判定離線
        if ($this->status === 'offline') {
            return 'offline';
        }

        // 判定異常 (僅檢查 type = 'status' 的未解決日誌)
        $hasRecentErrors = $this->logs()
            ->where('type', 'status')
            ->where('level', 'error')
            ->where('is_resolved', false)
            ->exists();

        if ($hasRecentErrors) {
            return 'error';
        }

        // 判定警告 (僅檢查 type = 'status' 的未解決日誌)
        // 優化：當機台在線時，自動忽略「斷線 (LWT)」警告，因為狀態已恢復
        $hasRecentWarnings = $this->logs()
            ->where('type', 'status')
            ->where('level', 'warning')
            ->where('is_resolved', false)
            ->where('message', '!=', 'Connection lost (LWT)')
            ->exists();

        if ($hasRecentWarnings) {
            return 'warning';
        }

        return $this->status ?? 'online';
    }

    /**
     * 判定機台目前是否有未解決的登入失敗警告 (需達 3 次未解決警告才亮起)
     */
    public function getHasLoginWarningAttribute(): bool
    {
        return $this->logs()
            ->where('type', 'login')
            ->where('level', 'warning')
            ->where('is_resolved', false)
            ->count() >= 3;
    }

    /**
     * 下位機/硬體健康狀態 (基於 type = 'submachine' 日誌)
     */
    public function getHardwareStatusAttribute(): string
    {
        // 判定異常
        $hasErrors = $this->logs()
            ->where('type', 'submachine')
            ->where('level', 'error')
            ->where('is_resolved', false)
            ->exists();

        if ($hasErrors) {
            return 'error';
        }

        // 判定警告
        $hasWarnings = $this->logs()
            ->where('type', 'submachine')
            ->where('level', 'warning')
            ->where('is_resolved', false)
            ->exists();

        if ($hasWarnings) {
            return 'warning';
        }

        return 'normal';
    }

    /**
     * 取得最新一筆未解決的系統狀態日誌時間
     */
    public function getLatestStatusLogTimeAttribute()
    {
        $query = $this->logs()
            ->where('type', 'status')
            ->whereIn('level', ['error', 'warning'])
            ->where('is_resolved', false);

        // 優化：若在線，不顯示已恢復的斷線時間
        if ($this->status === 'online') {
            $query->where('message', '!=', 'Connection lost (LWT)');
        }

        return $query->latest()->first()?->created_at;
    }

    /**
     * 取得最新一筆未解決的下位機硬體日誌時間
     */
    public function getLatestHardwareLogTimeAttribute()
    {
        return $this->logs()
            ->where('type', 'submachine')
            ->whereIn('level', ['error', 'warning'])
            ->where('is_resolved', false)
            ->latest()
            ->first()?->created_at;
    }

    /**
     * 刷卡機健康狀態 (基於 type = 'card_terminal' 未解決日誌)。
     * 信用卡/電子票證/手機支付共用同一台實體刷卡機，刷卡失敗會寫此類日誌，
     * 下一次刷卡成功時由 finalize 自動消警恢復正常。
     * 列表頁優先使用 MachineController 預載的彙總欄位 (避免 N+1)，其餘情境退回即時查詢。
     */
    public function getCardTerminalStatusAttribute(): string
    {
        if (array_key_exists('card_terminal_error_count', $this->attributes)
            || array_key_exists('card_terminal_warning_count', $this->attributes)) {
            if (($this->attributes['card_terminal_error_count'] ?? 0) > 0) {
                return 'error';
            }
            if (($this->attributes['card_terminal_warning_count'] ?? 0) > 0) {
                return 'warning';
            }
            return 'normal';
        }

        if ($this->logs()->where('type', 'card_terminal')->where('level', 'error')->where('is_resolved', false)->exists()) {
            return 'error';
        }
        if ($this->logs()->where('type', 'card_terminal')->where('level', 'warning')->where('is_resolved', false)->exists()) {
            return 'warning';
        }
        return 'normal';
    }

    /**
     * 取得最新一筆未解決的刷卡機日誌時間 (列表頁優先用預載 subquery 欄位，避免 N+1)。
     */
    public function getLatestCardTerminalLogTimeAttribute()
    {
        if (array_key_exists('latest_card_terminal_log_at', $this->attributes)) {
            $value = $this->attributes['latest_card_terminal_log_at'];
            return $value ? \Illuminate\Support\Carbon::parse($value) : null;
        }

        return $this->logs()
            ->where('type', 'card_terminal')
            ->whereIn('level', ['error', 'warning'])
            ->where('is_resolved', false)
            ->latest()
            ->first()?->created_at;
    }

    /**
     * Scope: 判定在線 (讀取 status 欄位)
     */
    public function scopeOnline($query)
    {
        return $query->where('status', 'online');
    }

    /**
     * Scope: 判定離線 (讀取 status 欄位)
     */
    public function scopeOffline($query)
    {
        return $query->where('status', 'offline');
    }

    public function scopeHasError($query)
    {
        return $query->whereHas('logs', function ($q) {
            $q->where('is_resolved', false)
                ->where('level', 'error');
        });
    }

    /**
     * Scope: 判定是否有任何待處理的異常 (排除掉單純的連線中斷)
     * 只有當登入失敗次數達 3 次以上時，才列入待處理告警。
     */
    public function scopeHasAnyAlert($query)
    {
        return $query->where(function($outer) {
            // 1. 有任何非登入類型的待處理異常
            $outer->whereHas('logs', function ($q) {
                $q->where(function($sub) {
                    $sub->where('is_resolved', false)
                        ->orWhereNull('is_resolved');
                })
                ->whereIn('level', ['error', 'warning'])
                ->where('type', '!=', 'login')
                ->where('message', '!=', 'Connection lost (LWT)');
            })
            // 2. 或者有至少 3 筆未解決的登入異常
            ->orWhereHas('logs', function ($q) {
                $q->where('is_resolved', false)
                    ->where('type', 'login')
                    ->where('level', 'warning');
            }, '>=', 3);
        });
    }

    public function scopeHasStatusError($query)
    {
        return $query->whereHas('logs', function ($q) {
            $q->where('is_resolved', false)
                ->where('type', 'status')
                ->where('level', 'error');
        });
    }

    public function scopeHasHardwareError($query)
    {
        return $query->whereHas('logs', function ($q) {
            $q->where('is_resolved', false)
                ->where('type', 'submachine')
                ->where('level', 'error');
        });
    }

    /**
     * Scope: 判定運行中 (在線且無近期系統異常)
     */
    public function scopeRunning($query)
    {
        return $query->online()->whereDoesntHave('logs', function ($q) {
            $q->where('is_resolved', false)
                ->where('type', 'status')
                ->whereIn('level', ['error', 'warning']);
        });
    }

    /**
     * Scope: 判定異常在線 (在線且有近期異常)
     */
    public function scopeErrorOnline($query)
    {
        return $query->online()->hasError();
    }

    protected $casts = [
        'last_heartbeat_at' => 'datetime',
        'temperature' => 'integer',
        'ambient_temperature' => 'float',
        'ambient_temp_setting' => 'float',
        'ambient_temp_monitoring_enabled' => 'boolean',
        'welcome_gift_enabled' => 'boolean',
        'is_spring_slot_1_10' => 'boolean',
        'is_spring_slot_11_20' => 'boolean',
        'is_spring_slot_21_30' => 'boolean',
        'is_spring_slot_31_40' => 'boolean',
        'is_spring_slot_41_50' => 'boolean',
        'is_spring_slot_51_60' => 'boolean',
        'member_system_enabled' => 'boolean',
        'tax_invoice_enabled' => 'boolean',
        'card_terminal_enabled' => 'boolean',
        'scan_pay_esun_enabled' => 'boolean',
        'scan_pay_linepay_enabled' => 'boolean',
        'shopping_cart_enabled' => 'boolean',
        'cash_module_enabled' => 'boolean',
        'images' => 'array',
        'settings' => 'array',
    ];

    /**
     * Get machine images absolute URLs
     */
    public function getImageUrlsAttribute(): array
    {
        if (empty($this->images)) {
            return [];
        }

        return array_map(fn($path) => \Illuminate\Support\Facades\Storage::disk('public')->url($path), $this->images);
    }

    public function logs()
    {
        return $this->hasMany(MachineLog::class);
    }

    public function slots()
    {
        return $this->hasMany(MachineSlot::class);
    }

    public function productPrices()
    {
        return $this->hasMany(MachineProductPrice::class);
    }

    public function commands()
    {
        return $this->hasMany(RemoteCommand::class);
    }

    public function machineModel()
    {
        return $this->belongsTo(MachineModel::class);
    }

    public function paymentConfig()
    {
        return $this->belongsTo(\App\Models\System\PaymentConfig::class);
    }

    public function creator()
    {
        return $this->belongsTo(\App\Models\System\User::class, 'creator_id');
    }

    public function updater()
    {
        return $this->belongsTo(\App\Models\System\User::class, 'updater_id');
    }

    public const PAGE_STATUSES = [
        '0' => 'Standby',
        '1' => 'Home Page',
        '2' => 'Vending Page',
        '3' => 'Admin Page',
        '4' => 'Replenishment Page',
        '5' => 'Tutorial Page',
        '6' => 'Purchasing',
        '7' => 'Locked Page',
        '60' => 'Dispense Success',
        '61' => 'Slot Test',
        '62' => 'Payment Selection',
        '63' => 'Waiting for Payment',
        '64' => 'Dispensing',
        '65' => 'Receipt Printing',
        '66' => 'Pass Code',
        '67' => 'Pickup Code',
        '68' => 'Message Display',
        '69' => 'Cancel Purchase',
        '610' => 'Purchase Finished',
        '611' => 'Welcome Gift',
        '612' => 'Dispense Failed',
    ];

    public function getCurrentPageLabelAttribute(): string
    {
        $code = (string) $this->current_page;
        $label = self::PAGE_STATUSES[$code] ?? $code;
        return __($label);
    }

    public function getIsUnstableAttribute(): bool
    {
        // 抖動偵測：1 小時內斷線次數 >= 3
        return $this->recent_disconnection_count >= 3;
    }

    public function getRecentDisconnectionCountAttribute(): int
    {
        return $this->logs()
            ->where('type', 'status')
            ->where('message', 'Connection lost (LWT)')
            ->where('created_at', '>=', now()->subHour())
            ->count();
    }

    // === settings JSON 重構 Accessors & Mutators ===

    public function getShoppingModeAttribute(): string
    {
        return $this->settings['shopping_mode'] ?? 'basic';
    }

    public function setShoppingModeAttribute($value)
    {
        $settings = $this->settings ?? [];
        $settings['shopping_mode'] = (string) $value;
        $this->settings = $settings;
    }

    public function getTaxInvoiceEnabledAttribute(): bool
    {
        return (bool) ($this->settings['tax_invoice_enabled'] ?? ($this->attributes['tax_invoice_enabled'] ?? false));
    }

    public function setTaxInvoiceEnabledAttribute($value)
    {
        $settings = $this->settings ?? [];
        $settings['tax_invoice_enabled'] = (bool) $value;
        $this->settings = $settings;
        $this->attributes['tax_invoice_enabled'] = (bool) $value;
    }

    public function getCardTerminalEnabledAttribute(): bool
    {
        return (bool) ($this->settings['card_terminal_enabled'] ?? ($this->attributes['card_terminal_enabled'] ?? false));
    }

    public function setCardTerminalEnabledAttribute($value)
    {
        $settings = $this->settings ?? [];
        $settings['card_terminal_enabled'] = (bool) $value;
        $this->settings = $settings;
        $this->attributes['card_terminal_enabled'] = (bool) $value;
    }

    /**
     * 是否需要在後台呈現刷卡機狀態燈號與日誌分頁。
     * 僅「基礎版(shopping_mode=basic)」且「刷卡機支付已啟用」的機台才有意義；
     * 購物車模式或未啟用刷卡機的機台不顯示。
     */
    public function getShowCardTerminalAttribute(): bool
    {
        return $this->shopping_mode === 'basic' && $this->card_terminal_enabled;
    }

    public function getScanPayEsunEnabledAttribute(): bool
    {
        return (bool) ($this->settings['scan_pay_esun_enabled'] ?? ($this->attributes['scan_pay_esun_enabled'] ?? false));
    }

    public function setScanPayEsunEnabledAttribute($value)
    {
        $settings = $this->settings ?? [];
        $settings['scan_pay_esun_enabled'] = (bool) $value;
        $this->settings = $settings;
        $this->attributes['scan_pay_esun_enabled'] = (bool) $value;
    }

    public function getScanPayLinepayEnabledAttribute(): bool
    {
        return (bool) ($this->settings['scan_pay_linepay_enabled'] ?? ($this->attributes['scan_pay_linepay_enabled'] ?? false));
    }

    public function setScanPayLinepayEnabledAttribute($value)
    {
        $settings = $this->settings ?? [];
        $settings['scan_pay_linepay_enabled'] = (bool) $value;
        $this->settings = $settings;
        $this->attributes['scan_pay_linepay_enabled'] = (bool) $value;
    }

    public function getShoppingCartEnabledAttribute(): bool
    {
        return (bool) ($this->settings['shopping_cart_enabled'] ?? ($this->attributes['shopping_cart_enabled'] ?? false));
    }

    public function setShoppingCartEnabledAttribute($value)
    {
        $settings = $this->settings ?? [];
        $settings['shopping_cart_enabled'] = (bool) $value;
        $this->settings = $settings;
        $this->attributes['shopping_cart_enabled'] = (bool) $value;
    }

    public function getWelcomeGiftEnabledAttribute(): bool
    {
        return (bool) ($this->settings['welcome_gift_enabled'] ?? ($this->attributes['welcome_gift_enabled'] ?? false));
    }

    public function setWelcomeGiftEnabledAttribute($value)
    {
        $settings = $this->settings ?? [];
        $settings['welcome_gift_enabled'] = (bool) $value;
        $this->settings = $settings;
        $this->attributes['welcome_gift_enabled'] = (bool) $value;
    }

    public function getCashModuleEnabledAttribute(): bool
    {
        return (bool) ($this->settings['cash_module_enabled'] ?? ($this->attributes['cash_module_enabled'] ?? false));
    }

    public function setCashModuleEnabledAttribute($value)
    {
        $settings = $this->settings ?? [];
        $settings['cash_module_enabled'] = (bool) $value;
        $this->settings = $settings;
        $this->attributes['cash_module_enabled'] = (bool) $value;
    }

    public function getMemberSystemEnabledAttribute(): bool
    {
        return (bool) ($this->settings['member_system_enabled'] ?? ($this->attributes['member_system_enabled'] ?? false));
    }

    public function setMemberSystemEnabledAttribute($value)
    {
        $settings = $this->settings ?? [];
        $settings['member_system_enabled'] = (bool) $value;
        $this->settings = $settings;
        $this->attributes['member_system_enabled'] = (bool) $value;
    }

    public function getAmbientTempMonitoringEnabledAttribute(): bool
    {
        return (bool) ($this->settings['ambient_temp_monitoring_enabled'] ?? ($this->attributes['ambient_temp_monitoring_enabled'] ?? false));
    }

    public function setAmbientTempMonitoringEnabledAttribute($value)
    {
        $settings = $this->settings ?? [];
        $settings['ambient_temp_monitoring_enabled'] = (bool) $value;
        $this->settings = $settings;
        $this->attributes['ambient_temp_monitoring_enabled'] = (bool) $value;
    }

    public function users()
    {
        return $this->belongsToMany(\App\Models\System\User::class);
    }

    public function orders()
    {
        return $this->hasMany(\App\Models\Transaction\Order::class);
    }

    public function stockMovements()
    {
        return $this->hasMany(MachineStockMovement::class);
    }

    public function getDisplayTemperatureAttribute()
    {
        if ($this->ambient_temp_monitoring_enabled && $this->ambient_temperature !== null) {
            return $this->ambient_temperature;
        }
        return $this->ambient_temperature ?? $this->temperature;
    }
}