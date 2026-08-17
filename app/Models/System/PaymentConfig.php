<?php

namespace App\Models\System;

use Illuminate\Database\Eloquent\Model;

class PaymentConfig extends Model
{
    protected $fillable = [
        'company_id',
        'name',
        'settings',
        'creator_id',
        'updater_id',
    ];

    protected $casts = [
        'settings' => 'array',
    ];

    public function machines()
    {
        return $this->hasMany(\App\Models\Machine\Machine::class);
    }

    public function company()
    {
        return $this->belongsTo(\App\Models\System\Company::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updater_id');
    }
}
