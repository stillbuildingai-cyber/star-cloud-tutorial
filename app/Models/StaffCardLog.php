<?php

namespace App\Models;

use App\Traits\TenantScoped;
use App\Models\Machine\Machine;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StaffCardLog extends Model
{
    use TenantScoped;

    public $timestamps = false;

    protected $fillable = [
        'company_id',
        'staff_card_id',
        'machine_id',
        'payment_type',
        'code_id',
        'order_id',
        'action',
        'remark',
        'raw_data',
    ];

    protected $casts = [
        'raw_data' => 'array',
        'created_at' => 'datetime',
    ];

    /**
     * 註冊 Model 事件以自動更新 StaffCard 的最後使用狀態
     */
    protected static function booted()
    {
        static::created(function ($log) {
            if ($log->staff_card_id) {
                // 使用 withoutGlobalScopes 確保不論在任何 Session Context 下皆能正確寫入
                $staffCard = StaffCard::withoutGlobalScopes()->find($log->staff_card_id);
                if ($staffCard) {
                    $staffCard->update([
                        'last_used_at' => $log->created_at ?? now(),
                        'last_machine_id' => $log->machine_id,
                    ]);
                }
            }
        });
    }

    /**
     * 取得關聯的員工卡片
     */
    public function staffCard(): BelongsTo
    {
        return $this->belongsTo(StaffCard::class);
    }

    /**
     * 取得刷卡的機台
     */
    public function machine(): BelongsTo
    {
        return $this->belongsTo(Machine::class);
    }

    /**
     * 取得關聯的訂單
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Transaction\Order::class);
    }
}
