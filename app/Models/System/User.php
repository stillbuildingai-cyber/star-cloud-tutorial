<?php

namespace App\Models\System;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

use App\Traits\TenantScoped;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles, TenantScoped, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'company_id',
        'parent_id',
        'level',
        'username',
        'name',
        'email',
        'password',
        'phone',
        'avatar',
        'role',
        'status',
        'is_admin',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'is_admin' => 'boolean',
        'level' => 'integer',
    ];

    /**
     * 帳號層級上限：子帳號最多兩層（主帳號 level 0 → 子 level 1 → 孫 level 2，level 2 不可再建）。
     */
    public const MAX_LEVEL = 2;

    /**
     * Get the login logs for the user.
     */
    public function loginLogs()
    {
        return $this->hasMany(UserLoginLog::class);
    }

    /**
     * Get the company that owns the user.
     */
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the machines assigned to the user.
     */
    public function machines()
    {
        return $this->belongsToMany(\App\Models\Machine\Machine::class);
    }

    /**
     * Check if the user is a system administrator.
     */
    public function isSystemAdmin(): bool
    {
        return is_null($this->company_id);
    }

    /**
     * Check if the user belongs to a tenant.
     */
    public function isTenant(): bool
    {
        return !is_null($this->company_id);
    }

    /**
     * 上層（建立者）帳號。
     */
    public function parent()
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    /**
     * 直接建立的下層帳號。
     */
    public function children()
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    /**
     * 是否為該公司主帳號（租戶層級且 is_admin）。
     */
    public function isMainAccount(): bool
    {
        return $this->isTenant() && $this->is_admin;
    }

    /**
     * 是否為子帳號（租戶層級且非主帳號）。
     */
    public function isSubAccount(): bool
    {
        return $this->isTenant() && !$this->is_admin;
    }

    /**
     * 是否還能再建立下層子帳號（深度上限約束，系統管理員不受限）。
     */
    public function canCreateSubAccount(): bool
    {
        return $this->isSystemAdmin() || $this->level < self::MAX_LEVEL;
    }

    /**
     * 查詢範圍：限制為指定使用者「可見」的帳號。
     * - 系統管理員：全部
     * - 主帳號：同公司全部（安全網）
     * - 子帳號：僅自己直接建立的下層帳號
     */
    public function scopeVisibleTo($query, self $viewer)
    {
        if ($viewer->isSystemAdmin()) {
            return $query;
        }
        $query->where($this->getTable() . '.company_id', $viewer->company_id);
        if (!$viewer->is_admin) {
            $query->where($this->getTable() . '.parent_id', $viewer->id);
        }
        return $query;
    }

    /**
     * 操作者是否有權管理（編輯/刪除/停用）指定帳號。
     * - 系統管理員：全部
     * - 跨公司：禁止
     * - 主帳號：同公司全部
     * - 子帳號：僅自己直接建立的下層帳號
     */
    public function canManageAccount(self $target): bool
    {
        if ($this->isSystemAdmin()) {
            return true;
        }
        if ($target->company_id !== $this->company_id) {
            return false;
        }
        if ($this->is_admin) {
            return true;
        }
        return $target->parent_id === $this->id;
    }

    /**
     * Get the URL for the user's avatar.
     */
    public function getAvatarUrlAttribute(): string
    {
        if ($this->avatar) {
            return \Illuminate\Support\Facades\Storage::disk('public')->url($this->avatar);
        }

        // Return a default UI Avatar if no avatar is set
        return "https://ui-avatars.com/api/?name=" . urlencode($this->name) . "&color=7F9CF5&background=EBF4FF";
    }
}
