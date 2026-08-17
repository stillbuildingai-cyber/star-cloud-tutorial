<?php

namespace App\Models\Warehouse;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockInOrderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'stock_in_order_id',
        'product_id',
        'quantity',
        'batch_no',
        'expiry_date',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'expiry_date' => 'date:Y-m-d',
    ];

    /**
     * 所屬入庫單
     */
    public function stockInOrder()
    {
        return $this->belongsTo(StockInOrder::class);
    }

    /**
     * 對應商品
     */
    public function product()
    {
        return $this->belongsTo(\App\Models\Product\Product::class);
    }
}
