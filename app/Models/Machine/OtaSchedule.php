<?php

namespace App\Models\Machine;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OtaSchedule extends Model
{
    use HasFactory;

    protected $fillable = [
        'apk_version_id',
        'scheduled_at',
        'target_type',
        'target_value',
        'status',
        'user_id',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'target_value' => 'array',
    ];

    public function apkVersion()
    {
        return $this->belongsTo(ApkVersion::class);
    }

    public function user()
    {
        return $this->belongsTo(\App\Models\System\User::class);
    }
}
