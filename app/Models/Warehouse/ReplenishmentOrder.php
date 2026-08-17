<?php

namespace App\Models\Warehouse;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\TenantScoped;

class ReplenishmentOrder extends Model
{
    use HasFactory, SoftDeletes, TenantScoped;

    protected $fillable = [
        'company_id',
        'order_no',
        'warehouse_id',
        'machine_id',
        'status',
        'assigned_to',
        'note',
        'created_by',
        'completed_at',
    ];

    protected $casts = [
        'completed_at' => 'datetime',
    ];

    /**
     * 狀態常數
     */
    public const STATUS_PENDING = 'pending';
    public const STATUS_PREPARED = 'prepared';
    public const STATUS_DELIVERING = 'delivering';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_CANCELLED = 'cancelled';

    /**
     * 合法狀態轉換定義
     * key = 當前狀態, value = 可轉換至的狀態陣列
     */
    public const TRANSITIONS = [
        self::STATUS_PENDING    => [self::STATUS_PREPARED, self::STATUS_CANCELLED],
        self::STATUS_PREPARED   => [self::STATUS_DELIVERING, self::STATUS_CANCELLED],
        self::STATUS_DELIVERING => [self::STATUS_COMPLETED, self::STATUS_CANCELLED],
        self::STATUS_COMPLETED  => [],
        self::STATUS_CANCELLED  => [],
    ];

    /**
     * 檢查是否可轉換至指定狀態
     */
    public function canTransitionTo(string $status): bool
    {
        return in_array($status, self::TRANSITIONS[$this->status] ?? []);
    }

    /**
     * 排除已取消的訂單
     */
    public function scopeActive($query)
    {
        return $query->where('status', '!=', self::STATUS_CANCELLED);
    }

    /**
     * 補貨總數量
     */
    public function getTotalQuantityAttribute(): int
    {
        return $this->items->sum('quantity');
    }

    /**
     * 出貨倉庫
     */
    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    /**
     * 目標機台
     */
    public function machine()
    {
        return $this->belongsTo(\App\Models\Machine\Machine::class);
    }

    /**
     * 補貨單明細
     */
    public function items()
    {
        return $this->hasMany(ReplenishmentOrderItem::class);
    }

    /**
     * 指派人員
     */
    public function assignee()
    {
        return $this->belongsTo(\App\Models\System\User::class, 'assigned_to');
    }

    /**
     * 建立人
     */
    public function creator()
    {
        return $this->belongsTo(\App\Models\System\User::class, 'created_by');
    }
}
