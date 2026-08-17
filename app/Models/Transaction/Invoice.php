<?php

namespace App\Models\Transaction;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\TenantScoped;
use App\Traits\HasDisplayFlowId;

class Invoice extends Model
{
    // TenantScoped：自動以登入者 company_id 過濾（含 route-model binding 與列表查詢），
    // 防止跨公司讀取/操作他人發票。console（佇列 Job / reconcile 命令）會自動跳過 scope，
    // 系統身分仍可跨公司開立/對帳。
    use HasFactory, SoftDeletes, TenantScoped, HasDisplayFlowId;

    protected $fillable = [
        'company_id',
        'order_id',
        'machine_id',
        'flow_id',
        'invoice_no',
        'status',
        'relate_number',
        'amount',
        'carrier_id',
        'invoice_date',
        'machine_time',
        'last_checked_at',
        'retry_count',
        'voided_at',
        'void_reason',
        'remark',
        'random_number',
        'love_code',
        'rtn_code',
        'rtn_msg',
        'metadata',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'invoice_date' => 'date',
        'machine_time' => 'datetime',
        'last_checked_at' => 'datetime',
        'voided_at' => 'datetime',
        'retry_count' => 'integer',
        'metadata' => 'array',
    ];

    // 發票狀態常數
    public const STATUS_PENDING = 'pending';   // 已送出綠界、尚未確認
    public const STATUS_ISSUED = 'issued';     // 已開立成功
    public const STATUS_FAILED = 'failed';     // 綠界回失敗，待補開
    public const STATUS_VOID = 'void';         // 已作廢

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function machine()
    {
        return $this->belongsTo(\App\Models\Machine\Machine::class);
    }
}
