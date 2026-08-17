<?php

namespace App\Models\Transaction;

use App\Models\Machine\Machine;
use App\Models\System\User;
use App\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WelcomeGiftLog extends Model
{
    use TenantScoped;

    protected $fillable = [
        'company_id',
        'machine_id',
        'welcome_gift_id',
        'order_id',
        'action',
        'remark',
        'raw_data',
    ];

    protected $casts = [
        'raw_data' => 'array',
    ];

    /**
     * 關聯來店禮
     */
    public function welcomeGift(): BelongsTo
    {
        return $this->belongsTo(WelcomeGift::class);
    }

    /**
     * 關聯機台
     */
    public function machine(): BelongsTo
    {
        return $this->belongsTo(Machine::class);
    }

    /**
     * 關聯訂單
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * 關聯操作人員（一般是系統透過訂單綁定或背景核銷，如有操作人可由關聯取得，通常從 Order 或由 SystemOperationLog 獲取）
     */
}
