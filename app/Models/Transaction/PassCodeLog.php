<?php

namespace App\Models\Transaction;

use App\Models\Machine\Machine;
use App\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PassCodeLog extends Model
{
    use TenantScoped;

    protected $fillable = [
        'company_id',
        'machine_id',
        'pass_code_id',
        'action',
        'user_id',
        'order_id',
        'raw_data',
    ];

    protected $casts = [
        'raw_data' => 'array',
    ];

    public function passCode(): BelongsTo
    {
        return $this->belongsTo(PassCode::class);
    }

    public function machine(): BelongsTo
    {
        return $this->belongsTo(Machine::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Models\System\User::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
