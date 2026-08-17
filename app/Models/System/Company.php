<?php

namespace App\Models\System;

use App\Models\Machine\Machine;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Permission\Models\Role;

class Company extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'code',
        'original_type',
        'current_type',
        'tax_id',
        'contact_name',
        'contact_phone',
        'contact_email',
        'status',
        'start_date',
        'end_date',
        'warranty_start_date',
        'warranty_end_date',
        'software_start_date',
        'software_end_date',
        'note',
        'settings',
    ];

    protected $casts = [
        'start_date' => 'date:Y-m-d',
        'end_date' => 'date:Y-m-d',
        'warranty_start_date' => 'date:Y-m-d',
        'warranty_end_date' => 'date:Y-m-d',
        'software_start_date' => 'date:Y-m-d',
        'software_end_date' => 'date:Y-m-d',
        'status' => 'integer',
        'settings' => 'array',
    ];

    /**
     * Get the contract history for the company.
     */
    public function contracts(): HasMany
    {
        return $this->hasMany(CompanyContract::class)->latest();
    }

    /**
     * Get the users for the company.
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * Get the machines for the company.
     */
    public function machines(): HasMany
    {
        return $this->hasMany(Machine::class);
    }

    /**
     * Get the products for the company.
     */
    public function products(): HasMany
    {
        return $this->hasMany(\App\Models\Product\Product::class);
    }

    /**
     * Scope：僅篩選啟用的公司
     */
    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }

    /**
     * 獲取公司自訂 Logo 的完整 URL (Accessor)
     */
    public function getLogoUrlAttribute(): ?string
    {
        $settings = $this->settings ?? [];
        $logoPath = $settings['logo_path'] ?? null;
        
        // 只有在啟用品牌自訂功能且上傳了 Logo 時才使用自訂 Logo
        if ($this->hasCustomBrandingEnabled() && $logoPath) {
            return \Illuminate\Support\Facades\Storage::disk('public')->url($logoPath);
        }

        return asset('starcloud_icon.png');
    }

    /**
     * 檢查此公司是否啟用且配置了品牌自訂功能
     */
    public function hasCustomBrandingEnabled(): bool
    {
        $settings = $this->settings ?? [];
        $enableCustomBranding = filter_var($settings['enable_custom_branding'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $logoPath = $settings['logo_path'] ?? null;

        return $enableCustomBranding && !empty($logoPath);
    }

    /**
     * 取得指定公司「所有機台已開語系的聯集」(商品翻譯實際要維護的語系)。
     *
     * - 來源：各機台 settings['languages'] 去重後聯集。
     * - 一律保證含 fallback 語系 (zh_TW)，且依系統白名單定義順序排列（穩定、不隨機台增減跳動）。
     * - 僅回傳仍在白名單內的語系（機台殘留的失效語系自動濾除）。
     * - 公司沒有任何機台、或機台皆未設語系時：退化為僅 [fallback]。
     *
     * 結果快取於 'company_active_locales:{companyId}'，於機台語系異動時失效
     * (見 forgetActiveLocales())。
     *
     * @param int|null $companyId  null 代表系統層級（無租戶機台），直接回 [fallback]。
     * @return array<int,string>
     */
    public static function activeLocalesFor(?int $companyId): array
    {
        $fallback  = config('locales.fallback', 'zh_TW');
        $whitelist = array_keys(config('locales.supported', [$fallback => $fallback]));

        if ($companyId === null) {
            return [$fallback];
        }

        return \Illuminate\Support\Facades\Cache::rememberForever(
            "company_active_locales:{$companyId}",
            function () use ($companyId, $fallback, $whitelist) {
                // 繞過全域範圍：本計算需涵蓋公司全部機台，與登入者可見範圍無關。
                $langSets = Machine::withoutGlobalScopes()
                    ->where('company_id', $companyId)
                    ->pluck('settings');

                $union = [];
                foreach ($langSets as $settings) {
                    foreach ((array) ($settings['languages'] ?? []) as $locale) {
                        $union[$locale] = true;
                    }
                }
                // 保證含 fallback，並只保留白名單內語系、依白名單順序輸出。
                $union[$fallback] = true;

                return array_values(array_filter(
                    $whitelist,
                    fn ($locale) => isset($union[$locale])
                ));
            }
        );
    }

    /**
     * 失效指定公司的語系聯集快取（機台 settings.languages 異動後呼叫）。
     */
    public static function forgetActiveLocales(?int $companyId): void
    {
        if ($companyId !== null) {
            \Illuminate\Support\Facades\Cache::forget("company_active_locales:{$companyId}");
        }
    }

    /**
     * 實例代理：當前公司的語系聯集。
     *
     * @return array<int,string>
     */
    public function activeLocales(): array
    {
        return static::activeLocalesFor($this->id);
    }
}

