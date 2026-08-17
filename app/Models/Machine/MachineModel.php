<?php

namespace App\Models\Machine;

use Illuminate\Database\Eloquent\Model;
use App\Traits\TenantScoped;

class MachineModel extends Model
{
    use TenantScoped;
    protected $fillable = [
        'name',
        'company_id',
        'creator_id',
        'updater_id',
        'settings',
    ];

    protected $casts = [
        'settings' => 'array',
    ];

    public function machines()
    {
        return $this->hasMany(Machine::class);
    }

    public function company()
    {
        return $this->belongsTo(\App\Models\System\Company::class);
    }

    public function creator()
    {
        return $this->belongsTo(\App\Models\System\User::class, 'creator_id');
    }

    public function updater()
    {
        return $this->belongsTo(\App\Models\System\User::class, 'updater_id');
    }
}
