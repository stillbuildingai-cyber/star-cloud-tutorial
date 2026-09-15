<?php

namespace App\Models\Machine;

use Illuminate\Database\Eloquent\Model;

class MachineSchedule extends Model
{
    protected $fillable = [
        'machine_id',
        'action',
        'days_of_week',
        'time',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function machine()
    {
        return $this->belongsTo(Machine::class);
    }

    /**
     * 是否包含指定的星期幾（1=一...7=日，對應 Carbon::dayOfWeekIso）
     */
    public function runsOnDay(int $isoDay): bool
    {
        $days = array_map('intval', explode(',', $this->days_of_week));

        return in_array($isoDay, $days, true);
    }
}
