<?php

namespace App\Models\Machine;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RemoteCommand extends Model
{
    use HasFactory;

    protected $fillable = [
        'machine_id',
        'user_id',
        'command_type',
        'payload',
        'status',
        'ttl',
        'note',
        'remark',
        'executed_at',
    ];

    protected $casts = [
        'payload' => 'array',
        'executed_at' => 'datetime',
    ];

    public function machine()
    {
        return $this->belongsTo(Machine::class);
    }

    public function user()
    {
        return $this->belongsTo(\App\Models\System\User::class);
    }

    /**
     * Scope for pending commands
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending')->orderBy('created_at', 'asc');
    }
}
