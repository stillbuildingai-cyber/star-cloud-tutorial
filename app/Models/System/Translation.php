<?php

namespace App\Models\System;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Translation extends Model
{
    use HasFactory, \App\Traits\TenantScoped;

    protected $fillable = [
        'company_id',
        'group',
        'key',
        'locale',
        'value',
    ];
}
