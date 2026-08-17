<?php

namespace App\Models\Member;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MembershipTier extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'annual_fee',
        'discount_rate',
        'point_multiplier',
        'description',
        'is_default',
        'sort_order',
    ];

    protected $casts = [
        'annual_fee' => 'decimal:2',
        'discount_rate' => 'decimal:2',
        'point_multiplier' => 'decimal:2',
        'is_default' => 'boolean',
        'sort_order' => 'integer',
    ];

    /**
     * 此等級的會員紀錄
     */
    public function memberships(): HasMany
    {
        return $this->hasMany(MemberMembership::class, 'tier_id');
    }

    /**
     * 此等級的禮品定義
     */
    public function giftDefinitions(): HasMany
    {
        return $this->hasMany(GiftDefinition::class, 'tier_id');
    }

    /**
     * 取得預設等級
     */
    public static function getDefault(): ?self
    {
        return static::where('is_default', true)->first();
    }

    /**
     * 是否為免費等級
     */
    public function getIsFreeAttribute(): bool
    {
        return $this->annual_fee <= 0;
    }
}
