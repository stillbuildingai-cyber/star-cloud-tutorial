<?php

namespace App\Models\Transaction;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Machine\Machine;
use App\Models\Product\Product;

class DispenseRecord extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'company_id',
        'order_id',
        'flow_id',
        'machine_id',
        'product_id',
        'slot_no',
        'amount',
        'remaining_stock',
        'dispense_status',
        'member_barcode',
        'machine_time',
        'points_used',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'machine_time' => 'datetime',
        'dispense_status' => 'integer',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function machine()
    {
        return $this->belongsTo(Machine::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class)->withTrashed();
    }
}
