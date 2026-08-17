<?php

namespace App\Models\System;

use App\Models\Machine\MachineAdvertisement;
use App\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Advertisement extends Model
{
    use HasFactory, TenantScoped;

    protected $fillable = [
        'company_id',
        'name',
        'type',
        'duration',
        'url',
        'is_active',
        'start_at',
        'end_at',
    ];

    protected $casts = [
        'duration' => 'integer',
        'is_active' => 'boolean',
        'start_at' => 'datetime',
        'end_at' => 'datetime',
    ];

    /**
     * Get the machine assignments for this advertisement.
     */
    public function machineAdvertisements()
    {
        return $this->hasMany(MachineAdvertisement::class);
    }

    /**
     * Get the company that owns the advertisement.
     */
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Scope a query to only include active advertisements.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to only include advertisements that should be playing now.
     */
    public function scopePlaying($query)
    {
        $now = now();
        return $query->where('is_active', true)
            ->where(function ($q) use ($now) {
                $q->whereNull('start_at')
                    ->orWhere('start_at', '<=', $now);
            })
            ->where(function ($q) use ($now) {
                $q->whereNull('end_at')
                    ->orWhere('end_at', '>=', $now);
            });
    }
}
