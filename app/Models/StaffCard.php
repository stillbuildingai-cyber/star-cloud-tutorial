<?php

namespace App\Models;

use App\Traits\TenantScoped;
use App\Models\Machine\Machine;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StaffCard extends Model
{
    use TenantScoped;

    protected $fillable = [
        'company_id',
        'employee_id',
        'name',
        'card_uid',
        'status',
        'notes',
        'last_used_at',
        'last_machine_id',
    ];

    protected $casts = [
        'last_used_at' => 'datetime',
    ];

    /**
     * 取得此卡片的刷卡紀錄
     */
    public function logs(): HasMany
    {
        return $this->hasMany(StaffCardLog::class);
    }

    /**
     * 取得最後一次使用的機台
     */
    public function lastMachine(): BelongsTo
    {
        return $this->belongsTo(Machine::class, 'last_machine_id');
    }
}
