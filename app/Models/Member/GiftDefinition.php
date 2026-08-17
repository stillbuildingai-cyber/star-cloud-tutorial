<?php

namespace App\Models\Member;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GiftDefinition extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'type',
        'value',
        'tier_id',
        'trigger',
        'validity_days',
        'is_active',
    ];

    protected $casts = [
        'value' => 'decimal:2',
        'validity_days' => 'integer',
        'is_active' => 'boolean',
    ];

    /**
     * 適用等級
     */
    public function tier(): BelongsTo
    {
        return $this->belongsTo(MembershipTier::class, 'tier_id');
    }

    /**
     * 發放紀錄
     */
    public function memberGifts(): HasMany
    {
        return $this->hasMany(MemberGift::class);
    }

    /**
     * 有效禮品
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
