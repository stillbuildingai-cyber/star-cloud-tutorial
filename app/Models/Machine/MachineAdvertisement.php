<?php

namespace App\Models\Machine;

use App\Models\System\Advertisement;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MachineAdvertisement extends Model
{
    use HasFactory;

    protected $fillable = [
        'machine_id',
        'advertisement_id',
        'position',
        'sort_order',
        'start_at',
        'end_at',
    ];

    protected $casts = [
        'sort_order' => 'integer',
        'start_at' => 'datetime',
        'end_at' => 'datetime',
    ];

    /**
     * Get the advertisement associated with this assignment.
     */
    public function advertisement()
    {
        return $this->belongsTo(Advertisement::class);
    }

    /**
     * Get the machine associated with this assignment.
     */
    public function machine()
    {
        return $this->belongsTo(Machine::class);
    }
}
