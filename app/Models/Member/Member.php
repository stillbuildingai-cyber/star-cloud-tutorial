<?php

namespace App\Models\Member;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Support\Str;

class Member extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * 資料表名稱
     */
    protected $table = 'members';

    /**
     * 可批量賦值的屬性
     */
    protected $fillable = [
        'uuid',
        'name',
        'email',
        'phone',
        'password',
        'birthday',
        'gender',
        'avatar',
        'is_active',
        'email_verified_at',
        'company_id',
        'barcode',
        'points',
        'wallet_balance',
    ];

    /**
     * 隱藏的屬性
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * 屬性轉換
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'birthday' => 'date',
        'is_active' => 'boolean',
        'password' => 'hashed',
        'points' => 'integer',
        'wallet_balance' => 'decimal:2',
    ];

    /**
     * 建立時自動產生 UUID
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
        });
    }

    /**
     * 關聯：社群帳號
     */
    public function socialAccounts()
    {
        return $this->hasMany(SocialAccount::class);
    }

    /**
     * 關聯：錢包
     */
    public function wallet()
    {
        return $this->hasOne(MemberWallet::class);
    }

    /**
     * 關聯：點數帳戶
     */
    public function points()
    {
        return $this->hasOne(MemberPoint::class);
    }

    /**
     * 關聯：會員資格紀錄
     */
    public function memberships()
    {
        return $this->hasMany(MemberMembership::class);
    }

    /**
     * 關聯：禮品紀錄
     */
    public function gifts()
    {
        return $this->hasMany(MemberGift::class);
    }

    /**
     * 取得目前有效的會員資格
     */
    public function activeMembership()
    {
        return $this->hasOne(MemberMembership::class)->active()->latest('starts_at');
    }

    /**
     * 檢查是否已綁定指定社群
     */
    public function hasSocialAccount(string $provider): bool
    {
        return $this->socialAccounts()->where('provider', $provider)->exists();
    }

    /**
     * 取得或建立錢包
     */
    public function getOrCreateWallet(): MemberWallet
    {
        return $this->wallet ?? $this->wallet()->create([
            'balance' => 0,
            'bonus_balance' => 0,
        ]);
    }

    /**
     * 取得或建立點數帳戶
     */
    public function getOrCreatePoints(): MemberPoint
    {
        return $this->points ?? $this->points()->create([
            'available_points' => 0,
            'pending_points' => 0,
            'expired_points' => 0,
            'used_points' => 0,
        ]);
    }
}
