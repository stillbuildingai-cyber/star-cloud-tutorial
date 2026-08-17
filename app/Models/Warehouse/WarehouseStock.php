<?php

namespace App\Models\Warehouse;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WarehouseStock extends Model
{
    use HasFactory;

    protected $fillable = [
        'warehouse_id',
        'product_id',
        'quantity',
        'safety_stock',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'safety_stock' => 'integer',
    ];

    /**
     * Scope：低於安全庫存
     */
    public function scopeLowStock($query)
    {
        return $query->whereColumn('quantity', '<=', 'safety_stock')
            ->where('safety_stock', '>', 0);
    }

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
}
