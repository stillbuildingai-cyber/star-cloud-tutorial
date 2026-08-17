<?php

namespace App\Models\Member;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MemberWallet extends Model
{
    use HasFactory;

    protected $fillable = [
        'member_id',
        'balance',
        'bonus_balance',
    ];

    protected $casts = [
        'balance' => 'decimal:2',
        'bonus_balance' => 'decimal:2',
    ];

    /**
     * 所屬會員
     */
    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    /**
     * 交易紀錄
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(WalletTransaction::class, 'member_id', 'member_id');
    }

    /**
     * 總餘額 (儲值 + 回饋)
     */
    public function getTotalBalanceAttribute(): float
    {
        return $this->balance + $this->bonus_balance;
    }
}
