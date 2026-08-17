<?php

namespace App\Models\Product;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Services\Machine\MachineService;
use App\Traits\TenantScoped;

class Product extends Model
{
    use HasFactory, SoftDeletes, TenantScoped;

    protected static function booted()
    {
        static::updated(function (Product $product) {
            if ($product->wasChanged(['track_limit', 'spring_limit'])) {
                app(MachineService::class)->syncProductSlotMaxStock($product);
            }
        });
    }
    
    /**
     * Scope a query to only include active products.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    protected $fillable = [
        'company_id',
        'category_id',
        'name',
        'name_dictionary_key',
        'barcode',
        'spec',
        'spec_dictionary_key',
        'manufacturer',
        'description',
        'price',
        'member_price',
        'cost',
        'track_limit',
        'spring_limit',
        'type',
        'image_url',
        'status',
        'is_active',
        'metadata',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'member_price' => 'decimal:2',
        'cost' => 'decimal:2',
        'track_limit' => 'integer',
        'spring_limit' => 'integer',
        'is_active' => 'boolean',
        'metadata' => 'array',
    ];

    public function category()
    {
        return $this->belongsTo(ProductCategory::class, 'category_id');
    }

    /**
     * 自動附加到 JSON/陣列輸出的屬性（供 Alpine.js 等前端使用）
     */
    protected $appends = ['localized_name', 'localized_spec'];

    /**
     * 取得當前語系的商品名稱。
     * 回退順序：當前語系 → zh_TW → name 欄位
     */
    public function getLocalizedNameAttribute(): string
    {
        if ($this->relationLoaded('translations') && $this->translations->isNotEmpty()) {
            $locale = app()->getLocale();
            // 先找當前語系
            $translation = $this->translations->firstWhere('locale', $locale);
            if ($translation) {
                return $translation->value;
            }
            // 回退至 zh_TW
            $fallback = $this->translations->firstWhere('locale', 'zh_TW');
            if ($fallback) {
                return $fallback->value;
            }
        }
        return $this->name ?? '';
    }

    /**
     * 取得當前語系的商品規格。
     * 回退順序：當前語系 → zh_TW → spec 欄位（與名稱同邏輯）
     */
    public function getLocalizedSpecAttribute(): string
    {
        if ($this->relationLoaded('specTranslations') && $this->specTranslations->isNotEmpty()) {
            $locale = app()->getLocale();
            $translation = $this->specTranslations->firstWhere('locale', $locale);
            if ($translation) {
                return $translation->value;
            }
            $fallback = $this->specTranslations->firstWhere('locale', 'zh_TW');
            if ($fallback) {
                return $fallback->value;
            }
        }
        return $this->spec ?? '';
    }

    /**
     * Get the translations for the product name.
     */
    public function translations()
    {
        return $this->hasMany(\App\Models\System\Translation::class, 'key', 'name_dictionary_key')
            ->where('group', 'product');
    }

    /**
     * Get the translations for the product spec.
     */
    public function specTranslations()
    {
        return $this->hasMany(\App\Models\System\Translation::class, 'key', 'spec_dictionary_key')
            ->where('group', 'product_spec');
    }

    /**
     * 倉庫庫存紀錄
     */
    public function stocks()
    {
        return $this->hasMany(\App\Models\Warehouse\WarehouseStock::class);
    }
}
