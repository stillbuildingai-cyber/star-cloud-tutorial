<?php

namespace App\Models\Member;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SocialAccount extends Model
{
    use HasFactory;

    /**
     * 資料表名稱
     */
    protected $table = 'social_accounts';

    /**
     * 可批量賦值的屬性
     */
    protected $fillable = [
        'member_id',
        'provider',
        'provider_id',
        'access_token',
        'refresh_token',
        'profile_data',
        'token_expires_at',
    ];

    /**
     * 屬性轉換
     */
    protected $casts = [
        'profile_data' => 'array',
        'token_expires_at' => 'datetime',
    ];

    /**
     * 隱藏的屬性
     */
    protected $hidden = [
        'access_token',
        'refresh_token',
    ];

    /**
     * 關聯：會員
     */
    public function member()
    {
        return $this->belongsTo(Member::class);
    }
}
