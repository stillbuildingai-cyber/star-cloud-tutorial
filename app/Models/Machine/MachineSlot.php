<?php

namespace App\Models\Machine;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Product\Product;

class MachineSlot extends Model
{
    use HasFactory;

    protected $fillable = [
        'machine_id',
        'product_id',
        'slot_no',
        'type',
        'max_stock',
        'stock',
        'expiry_date',
        'batch_no',
        'is_active',
        'is_locked',
        'last_app_lock_rev',
        'last_app_expiry_rev',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'last_restocked_at' => 'datetime',
        'expiry_date' => 'date:Y-m-d',
        'is_active' => 'boolean',
        'is_locked' => 'boolean',
        'last_app_lock_rev' => 'integer',
        'last_app_expiry_rev' => 'integer',
    ];

    public function machine()
    {
        return $this->belongsTo(Machine::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
