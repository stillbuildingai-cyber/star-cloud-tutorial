<?php

namespace App\Models\Warehouse;

use Illuminate\Database\Eloquent\Model;
use App\Traits\TenantScoped;

class StockMovement extends Model
{
    use TenantScoped;

    /**
     * 此表為唯讀日誌，僅有 created_at
     */
    public $timestamps = false;

    protected $fillable = [
        'company_id',
        'warehouse_id',
        'product_id',
        'type',
        'quantity',
        'before_qty',
        'after_qty',
        'reference_type',
        'reference_id',
        'note',
        'created_by',
        'created_at',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'before_qty' => 'integer',
        'after_qty' => 'integer',
        'created_at' => 'datetime',
    ];

    /**
     * 異動類型對應中文
     */
    public const TYPE_LABELS = [
        'in' => 'Stock In',
        'out' => 'Stock Out',
        'adjust' => 'Adjustment',
        'damage' => 'Damage',
        'transfer_in' => 'Transfer In',
        'transfer_out' => 'Transfer Out',
    ];

    /**
     * 所屬倉庫
     */
    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    /**
     * 對應商品
     */
    public function product()
    {
        return $this->belongsTo(\App\Models\Product\Product::class);
    }

    /**
     * 操作人
     */
    public function creator()
    {
        return $this->belongsTo(\App\Models\System\User::class, 'created_by');
    }

    /**
     * 關聯單據（多態）
     */
    public function reference()
    {
        return $this->morphTo();
    }
}
