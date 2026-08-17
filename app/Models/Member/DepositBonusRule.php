<?php

namespace App\Models\Member;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DepositBonusRule extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'min_amount',
        'bonus_type',
        'bonus_value',
        'is_active',
        'start_at',
        'end_at',
    ];

    protected $casts = [
        'min_amount' => 'decimal:2',
        'bonus_value' => 'decimal:2',
        'is_active' => 'boolean',
        'start_at' => 'datetime',
        'end_at' => 'datetime',
    ];

    /**
     * 取得目前有效的規則
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('start_at')->orWhere('start_at', '<=', now());
            })
            ->where(function ($q) {
                $q->whereNull('end_at')->orWhere('end_at', '>=', now());
            });
    }

    /**
     * 計算回饋金額
     */
    public function calculateBonus(float $depositAmount): float
    {
        if ($depositAmount < $this->min_amount) {
            return 0;
        }

        if ($this->bonus_type === 'fixed') {
            return $this->bonus_value;
        }

        // percentage
        return $depositAmount * ($this->bonus_value / 100);
    }
}
