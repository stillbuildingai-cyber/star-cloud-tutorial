<?php

namespace App\Models\Warehouse;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReplenishmentOrderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'replenishment_order_id',
        'product_id',
        'slot_no',
        'quantity',
        'current_stock',
        'max_stock',
        'snapshot_product_name',
        'snapshot_product_name_key',
        'snapshot_product_barcode',
        'snapshot_product_image_url',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'current_stock' => 'integer',
        'max_stock' => 'integer',
    ];

    /**
     * 自動附加到 JSON/陣列輸出的屬性
     */
    protected $appends = ['localized_name', 'barcode', 'image_url'];

    /**
     * 所屬補貨單
     */
    public function replenishmentOrder()
    {
        return $this->belongsTo(ReplenishmentOrder::class);
    }

    /**
     * 對應商品 (包含被軟刪除的商品以相容舊資料)
     */
    public function product()
    {
        return $this->belongsTo(\App\Models\Product\Product::class)->withTrashed();
    }

    /**
     * 取得快照商品名稱的字典翻譯
     */
    public function translations()
    {
        return $this->hasMany(\App\Models\System\Translation::class, 'key', 'snapshot_product_name_key')
            ->where('group', 'product');
    }

    /**
     * 取得商品名稱。
     * 回退順序：快照字典語系翻譯 → 快照字典 zh_TW → 快照名稱 → 關聯商品名稱 → 空值
     */
    public function getLocalizedNameAttribute(): string
    {
        if ($this->snapshot_product_name_key) {
            if ($this->relationLoaded('translations') && $this->translations->isNotEmpty()) {
                $locale = app()->getLocale();
                // 優先找當前語系
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
        }

        if ($this->snapshot_product_name) {
            return $this->snapshot_product_name;
        }

        if ($this->product) {
            return $this->product->localized_name;
        }

        return '';
    }

    /**
     * 取得商品條碼。
     * 回退順序：快照條碼 → 關聯商品條碼 → 空值
     */
    public function getBarcodeAttribute(): string
    {
        if ($this->snapshot_product_barcode) {
            return $this->snapshot_product_barcode;
        }

        if ($this->product) {
            return $this->product->barcode ?? '';
        }

        return '';
    }

    /**
     * 取得商品圖片。
     * 回退順序：快照圖片 → 關聯商品圖片 → 空值
     */
    public function getImageUrlAttribute(): ?string
    {
        $url = $this->snapshot_product_image_url ?: ($this->product ? $this->product->image_url : null);

        // 防禦性過濾無效路徑或空值
        if (empty($url) || trim($url) === '' || in_array(rtrim($url, '/'), ['/storage', '/storage/products'])) {
            return null;
        }

        return $url;
    }
}
