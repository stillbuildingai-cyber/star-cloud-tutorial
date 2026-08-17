<?php

namespace App\Models\Warehouse;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\TenantScoped;

class Warehouse extends Model
{
    use HasFactory, SoftDeletes, TenantScoped;

    protected $fillable = [
        'company_id',
        'name',
        'type',
        'address',
        'manager_user_id',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Scope：僅篩選啟用的倉庫
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope：僅篩選總倉
     */
    public function scopeMain($query)
    {
        return $query->where('type', 'main');
    }

    /**
     * Scope：僅篩選分倉
     */
    public function scopeBranch($query)
    {
        return $query->where('type', 'branch');
    }

    /**
     * 倉庫負責人
     */
    public function manager()
    {
        return $this->belongsTo(\App\Models\System\User::class, 'manager_user_id');
    }

    /**
     * 倉庫庫存（各商品）
     */
    public function stocks()
    {
        return $this->hasMany(WarehouseStock::class);
    }

    /**
     * 庫存異動紀錄
     */
    public function movements()
    {
        return $this->hasMany(StockMovement::class);
    }

    /**
     * 入庫單
     */
    public function stockInOrders()
    {
        return $this->hasMany(StockInOrder::class);
    }

    /**
     * 所屬公司
     */
    public function company()
    {
        return $this->belongsTo(\App\Models\System\Company::class);
    }
}
