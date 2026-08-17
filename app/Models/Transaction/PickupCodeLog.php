<?php

namespace App\Models\Transaction;

use Illuminate\Database\Eloquent\Model;
use App\Models\Machine\Machine;
use App\Models\System\User;
use App\Traits\TenantScoped;

class PickupCodeLog extends Model
{
    use TenantScoped;

    protected $fillable = [
        'company_id',
        'machine_id',
        'pickup_code_id',
        'order_id',
        'action',
        'user_id', // Optional, for admin actions recorded here
        'remark',
        'raw_data'
    ];

    protected $casts = [
        'raw_data' => 'array'
    ];

    public function machine()
    {
        return $this->belongsTo(Machine::class);
    }

    public function pickupCode()
    {
        return $this->belongsTo(PickupCode::class);
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
