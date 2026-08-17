<?php

namespace App\Models\Machine;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CoinInventory extends Model
{
    use HasFactory;

    protected $fillable = [
        'machine_id',
        'denomination',
        'count',
        'type',
    ];

    public function machine()
    {
        return $this->belongsTo(Machine::class);
    }
}
