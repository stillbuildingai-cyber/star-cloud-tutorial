<?php

namespace App\Models\System;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompanyContract extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'type',
        'start_date',
        'end_date',
        'warranty_start_date',
        'warranty_end_date',
        'software_start_date',
        'software_end_date',
        'note',
        'creator_id',
    ];

    protected $casts = [
        'start_date' => 'date:Y-m-d',
        'end_date' => 'date:Y-m-d',
        'warranty_start_date' => 'date:Y-m-d',
        'warranty_end_date' => 'date:Y-m-d',
        'software_start_date' => 'date:Y-m-d',
        'software_end_date' => 'date:Y-m-d',
    ];

    /**
     * Get the company that owns the contract.
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the user who created the record.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_id');
    }
}
