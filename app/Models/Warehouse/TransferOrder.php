<?php

namespace App\Models\Warehouse;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\TenantScoped;

class TransferOrder extends Model
{
    use HasFactory, SoftDeletes, TenantScoped;

    protected $fillable = [
        'company_id',
        'order_no',
        'type',
        'from_warehouse_id',
        'from_machine_id',
        'to_warehouse_id',
        'status',
        'note',
        'created_by',
        'completed_at',
    ];

    protected $casts = [
        'completed_at' => 'datetime',
    ];

    /**
     * 調撥類型常數
     */
    public const TYPE_W2W = 'warehouse_to_warehouse';
    public const TYPE_M2W = 'machine_to_warehouse';

    /**
     * 狀態常數
     */
    public const STATUS_DRAFT = 'draft';
    public const STATUS_PENDING = 'pending';
    public const STATUS_IN_TRANSIT = 'in_transit';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_CANCELLED = 'cancelled';

    /**
     * 來源倉庫
     */
    public function fromWarehouse()
    {
        return $this->belongsTo(Warehouse::class, 'from_warehouse_id');
    }

    /**
     * 來源機台（機台退回時）
     */
    public function fromMachine()
    {
        return $this->belongsTo(\App\Models\Machine\Machine::class, 'from_machine_id');
    }

    /**
     * 目標倉庫
     */
    public function toWarehouse()
    {
        return $this->belongsTo(Warehouse::class, 'to_warehouse_id');
    }

    /**
     * 調撥單明細
     */
    public function items()
    {
        return $this->hasMany(TransferOrderItem::class);
    }

    /**
     * 建立人
     */
    public function creator()
    {
        return $this->belongsTo(\App\Models\System\User::class, 'created_by');
    }
}
