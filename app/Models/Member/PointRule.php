<?php

namespace App\Models\Member;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PointRule extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'trigger',
        'points_per_unit',
        'unit_amount',
        'validity_days',
        'is_active',
    ];

    protected $casts = [
        'points_per_unit' => 'integer',
        'unit_amount' => 'decimal:2',
        'validity_days' => 'integer',
        'is_active' => 'boolean',
    ];

    /**
     * 取得有效規則
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * 根據金額計算可獲得點數
     */
    public function calculatePoints(float $amount): int
    {
        if ($this->unit_amount <= 0) {
            return 0;
        }

        return (int) floor($amount / $this->unit_amount) * $this->points_per_unit;
    }
}
