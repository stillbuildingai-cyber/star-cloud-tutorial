<?php

namespace App\Models\System;

use Spatie\Permission\Models\Role as SpatieRole;

class Role extends SpatieRole
{
    protected $fillable = [
        'name',
        'guard_name',
        'company_id',
        'created_by',
        'is_system',
        'is_company_admin',
    ];

    protected $casts = [
        'is_system' => 'boolean',
        'is_company_admin' => 'boolean',
    ];

    /**
     * Get the company that owns the role.
     */
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * 建立者帳號。
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Scope a query to only include roles for a specific company or system roles.
     */
    public function scopeForCompany($query, $company_id)
    {
        return $query->where(function($q) use ($company_id) {
            $q->where('company_id', $company_id)
              ->orWhereNull('company_id');
        });
    }

    /**
     * 查詢範圍：限制為指定使用者「可見」的角色（鏡像 User::scopeVisibleTo）。
     * - 系統管理員：全部
     * - 主帳號：同公司全部
     * - 子帳號：僅自己建立的角色
     */
    public function scopeVisibleTo($query, User $viewer)
    {
        if ($viewer->isSystemAdmin()) {
            return $query;
        }
        $query->where($this->getTable() . '.company_id', $viewer->company_id);
        if (!$viewer->is_admin) {
            $query->where($this->getTable() . '.created_by', $viewer->id);
        }
        return $query;
    }

    /**
     * 操作者是否有權管理（編輯/刪除）此角色。
     * - 系統管理員：全部
     * - 跨公司：禁止
     * - 主帳號：同公司全部
     * - 子帳號：僅自己建立的角色
     */
    public function canBeManagedBy(User $user): bool
    {
        if ($user->isSystemAdmin()) {
            return true;
        }
        if ($this->company_id !== $user->company_id) {
            return false;
        }
        if ($user->is_admin) {
            return true;
        }
        return $this->created_by === $user->id;
    }
}
