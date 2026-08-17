<?php

namespace App\Models\Product;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\TenantScoped;

class ProductCategory extends Model
{
    use HasFactory, SoftDeletes, TenantScoped;

    protected $fillable = [
        'company_id',
        'name',
        'name_dictionary_key',
    ];

    /**
     * 自動附加到 JSON/陣列輸出
     */
    protected $appends = ['localized_name'];

    public function products()
    {
        return $this->hasMany(Product::class, 'category_id');
    }

    /**
     * Get the translations for the category name.
     */
    public function translations()
    {
        return $this->hasMany(\App\Models\System\Translation::class, 'key', 'name_dictionary_key')
            ->where('group', 'category');
    }

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
}
