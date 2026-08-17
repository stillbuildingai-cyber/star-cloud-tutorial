<?php

namespace App\Services\Product;

use App\Models\Machine\Machine;
use App\Models\Machine\MachineProductPrice;
use App\Models\Product\Product;
use App\Models\System\Company;
use Illuminate\Support\Facades\Cache;

class ProductCatalogService
{
    /**
     * Get the Redis cache key for a company's product catalog.
     *
     * @param int|null $companyId
     * @return string
     */
    public function getCacheKey(?int $companyId): string
    {
        return "products_catalog:" . ($companyId ?? 'global');
    }

    /**
     * Get the product catalog payload, using cache if available.
     *
     * @param int|null $companyId
     * @return array
     */
    public function getPayload(?int $companyId): array
    {
        $cacheKey = $this->getCacheKey($companyId);
        $cached   = Cache::get($cacheKey);

        if ($cached) {
            return $cached;
        }

        return $this->rebuildCache($companyId);
    }

    /**
     * Rebuild the product catalog cache for a company.
     *
     * @param int|null $companyId
     * @return array
     */
    public function rebuildCache(?int $companyId): array
    {
        // 公司機台語系聯集：商品 i18n map 下發的語系集合（見 product_multilingual_spec §5.1）
        $locales = Company::activeLocalesFor($companyId);

        $products = Product::where('company_id', $companyId)
            ->with(['translations', 'specTranslations'])
            ->active()
            ->get()
            ->map(fn($p) => $this->mapProduct($p, $locales));

        $payload = [
            'success' => true,
            'code'    => 200,
            'data'    => $products->toArray(),
        ];

        Cache::forever($this->getCacheKey($companyId), $payload);

        return $payload;
    }

    /**
     * Invalidate the product catalog cache for a company.
     *
     * @param int|null $companyId
     * @return void
     */
    public function invalidate(?int $companyId): void
    {
        Cache::forget($this->getCacheKey($companyId));
    }

    /**
     * 取得「該機台」的商品目錄：公司基底目錄（快取） + 機台專屬定價覆蓋。
     *
     * 設計取捨：重的 i18n 目錄留在 per-company 快取；機台覆蓋於請求當下即時套用，
     * 不另設機台快取。覆蓋只是一次 (machine_id) 的索引查詢，極輕量；且改價僅推「該台」
     * 重抓，不會造成多台群湧。如此完全避開「機台快取失效 / 公司基底變動連帶失效」的複雜度。
     *
     * @param Machine $machine
     * @return array
     */
    public function getMachinePayload(Machine $machine): array
    {
        $payload = $this->getPayload($machine->company_id);

        $overrides = MachineProductPrice::where('machine_id', $machine->id)
            ->get(['product_id', 'price', 'member_price'])
            ->keyBy('product_id');

        if ($overrides->isEmpty()) {
            return $payload;
        }

        // PHP 陣列為值複製，array_map 產生新陣列，不會污染 Cache 內的公司基底目錄。
        $payload['data'] = array_map(function ($item) use ($overrides) {
            $override = $overrides->get((int) ($item['t060v00'] ?? 0));
            if (!$override) {
                return $item;
            }

            // 機台售價（t063v03=App machinePrice）：有覆蓋價就用，否則維持全域售價基底。
            $sellingPrice = $override->price !== null
                ? (float) $override->price
                : (float) $item['t063v03'];
            $item['t063v03'] = $sellingPrice;

            // 機台會員價（t060v30）：有覆蓋就用，否則沿用全域會員價；
            // 一律夾為「不高於機台售價」，避免會員買得比一般人貴。
            $memberPrice = $override->member_price !== null
                ? (float) $override->member_price
                : (float) $item['t060v30'];
            $item['t060v30'] = min($memberPrice, $sellingPrice);

            return $item;
        }, $payload['data']);

        return $payload;
    }

    /**
     * Map a Product model to the catalog format expected by machines.
     *
     * 相容鐵則：既有欄位（t060v01 / t060v01_en / t060v01_jp / t060v03 …）一律保留，
     * 僅「新增」locale-keyed 的 t060v01_i18n（名稱）與 t060v03_i18n（規格）。
     * 線上舊 App 忽略新欄位、照舊運作；新版 App 讀 *_i18n 以支援多語系切換。
     *
     * @param Product $product
     * @param array<int,string> $locales 公司機台語系聯集（i18n map 下發的語系集合）
     * @return array
     */
    private function mapProduct($product, array $locales = ['zh_TW']): array
    {
        $nameEn = $product->translations->firstWhere('locale', 'en')?->value ?? '';
        $nameJp = $product->translations->firstWhere('locale', 'ja')?->value ?? '';

        return [
            't060v00'      => (string) $product->id,
            't060v01'      => $product->name,
            't060v01_en'   => $nameEn,
            't060v01_jp'   => $nameJp,
            't060v01_i18n' => $this->buildI18nMap($product->translations, $product->name, $locales),
            't060v03'      => $product->spec ?? '',
            't060v03_i18n' => $this->buildI18nMap($product->specTranslations, $product->spec ?? '', $locales),
            't060v06'      => $product->image_url
                                ? (str_starts_with($product->image_url, 'http') ? $product->image_url : asset($product->image_url))
                                : '',
            't060v09'      => (float) $product->price,
            't060v11'      => (int) ($product->track_limit ?? 10),
            't060v30'      => (float) ($product->member_price ?? $product->price),
            't060v40'      => $product->metadata['marketing_plan'] ?? '',
            't060v41'      => $product->metadata['material_code'] ?? $product->barcode ?? '',
            'spring_limit' => (int) ($product->spring_limit ?? 10),
            'track_limit'  => (int) ($product->track_limit ?? 10),
            't063v03'      => (float) $product->price,
        ];
    }

    /**
     * 依公司機台語系聯集，建立 locale → 文字 的 i18n map。
     *
     * - 僅輸出 $locales 內的語系。
     * - 缺翻譯時回退至 zh_TW，再回退至 $fallbackText（products.name / spec 主值），
     *   確保不下發空字串，避免機台顯示空白。
     *
     * @param \Illuminate\Support\Collection $translations 該欄位的翻譯集合
     * @param string|null $fallbackText 最終回退文字（主欄位值）
     * @param array<int,string> $locales 公司機台語系聯集
     * @return array<string,string>
     */
    private function buildI18nMap($translations, ?string $fallbackText, array $locales): array
    {
        $zhFallback = $translations->firstWhere('locale', 'zh_TW')?->value
            ?? ($fallbackText ?? '');

        $map = [];
        foreach ($locales as $locale) {
            $map[$locale] = $translations->firstWhere('locale', $locale)?->value ?: $zhFallback;
        }

        return $map;
    }
}
