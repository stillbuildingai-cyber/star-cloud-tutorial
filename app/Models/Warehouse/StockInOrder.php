<?php

namespace App\Models\Warehouse;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\TenantScoped;

class StockInOrder extends Model
{
    use HasFactory, SoftDeletes, TenantScoped;

    protected $fillable = [
        'company_id',
        'warehouse_id',
        'order_no',
        'status',
        'note',
        'created_by',
        'completed_at',
    ];

    public const STATUS_DRAFT = 'draft';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_CANCELLED = 'cancelled';

    protected $casts = [
        'completed_at' => 'datetime',
    ];

    /**
     * 所屬倉庫
     */
    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    /**
     * 入庫單明細
     */
    public function items()
    {
        return $this->hasMany(StockInOrderItem::class);
    }

    /**
     * 建立人
     */
    public function creator()
    {
        return $this->belongsTo(\App\Models\System\User::class, 'created_by');
    }
}
