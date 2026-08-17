<?php

namespace App\Models\Member;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MemberPoint extends Model
{
    use HasFactory;

    protected $fillable = [
        'member_id',
        'available_points',
        'pending_points',
        'expired_points',
        'used_points',
    ];

    protected $casts = [
        'available_points' => 'integer',
        'pending_points' => 'integer',
        'expired_points' => 'integer',
        'used_points' => 'integer',
    ];

    /**
     * 所屬會員
     */
    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    /**
     * 點數異動紀錄
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(PointTransaction::class, 'member_id', 'member_id');
    }
}
