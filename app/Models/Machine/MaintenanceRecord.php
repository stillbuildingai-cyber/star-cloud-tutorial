<?php

namespace App\Models\Machine;

use Illuminate\Database\Eloquent\Model;

use App\Traits\TenantScoped;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\System\User;
use App\Models\Machine\Machine;

class MaintenanceRecord extends Model
{
    use TenantScoped, SoftDeletes;

    protected $fillable = [
        'company_id',
        'machine_id',
        'user_id',
        'category',
        'content',
        'photos',
        'maintenance_at',
        'is_confirmed',
    ];

    protected $casts = [
        'photos' => 'array',
        'maintenance_at' => 'datetime',
        'is_confirmed' => 'boolean',
    ];

    public function machine()
    {
        return $this->belongsTo(Machine::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function company()
    {
        return $this->belongsTo(\App\Models\System\Company::class);
    }
}
