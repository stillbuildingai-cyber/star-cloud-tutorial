<?php

namespace App\Services;

use App\Models\Product\Product;
use App\Models\Product\ProductCategory;
use App\Models\System\SystemOperationLog;
use App\Models\System\Translation;
use App\Traits\ImageHandler;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use OpenSpout\Reader\XLSX\Reader;
use OpenSpout\Writer\XLSX\Writer;
use OpenSpout\Common\Entity\Row;

class ProductImportService
{
    use ImageHandler;

    /**
     * @param string|null $imageDir 已解壓的圖片資料夾路徑 (Excel「圖片檔名」欄對應此資料夾內檔案)
     * @param bool $deferImages true 時不在此同步轉檔，改將 (product_id, filename) 收集到 results['pending_images']
     *                          供呼叫端丟背景 Job 處理，避免大量圖片同步轉檔逾時。
     */
    public function import(string $filePath, ?int $companyId = null, ?int $userId = null, ?string $imageDir = null, bool $deferImages = false): array
    {
        $reader = new Reader();
        $reader->open($filePath);

        $results = [
            'success_count' => 0,
            'created_count' => 0,
            'updated_count' => 0,
            'error_count' => 0,
            'errors' => [],
            'company_ids' => [],
            'image_success' => 0,
            'image_missing' => [],
            'pending_images' => [],
        ];

        $rowCount = 0;

        foreach ($reader->getSheetIterator() as $sheet) {
            foreach ($sheet->getRowIterator() as $row) {
                $rowCount++;

                if ($rowCount === 1) {
                    continue;
                }

                $cells = $row->toArray();
                $data = $this->normalizeRow($cells, $companyId);

                if ($this->isEmptyRow($data)) {
                    continue;
                }

                $validator = Validator::make($data, [
                    'company_id' => 'nullable|exists:companies,id',
                    'category' => 'nullable|string|max:255',
                    'name_zh_TW' => 'required|string|max:255',
                    'name_en' => 'nullable|string|max:255',
                    'name_ja' => 'nullable|string|max:255',
                    'barcode' => 'nullable|string|max:100',
                    'spec' => 'nullable|string|max:255',
                    'manufacturer' => 'nullable|string|max:255',
                    'price' => 'required|numeric|min:0',
                    'member_price' => 'nullable|numeric|min:0',
                    'cost' => 'required|numeric|min:0',
                    'track_limit' => 'nullable|integer|min:0',
                    'spring_limit' => 'nullable|integer|min:0',
                    'material_code' => 'nullable|string|max:255',
                    'points_full' => 'nullable|integer|min:0',
                    'points_half' => 'nullable|integer|min:0',
                    'points_half_amount' => 'nullable|integer|min:0',
                    'is_active' => 'nullable|boolean',
                    'image_filename' => 'nullable|string|max:255',
                ]);

                if ($validator->fails()) {
                    $this->appendError($results, $rowCount, $validator->errors()->all());
                    continue;
                }

                try {
                    DB::transaction(function () use ($data, $rowCount, $userId, $imageDir, $deferImages, &$results) {
                        $categoryId = $this->resolveCategoryId($data['category'], $data['company_id']);
                        $lookup = $this->buildProductLookup($data);
                        $product = Product::withoutGlobalScopes()->where($lookup)->first();
                        $oldValues = $product?->toArray();
                        $dictKey = $product?->name_dictionary_key ?: Str::uuid()->toString();

                        $this->syncTranslations($dictKey, $data, $data['company_id']);

                        $payload = [
                            'company_id' => $data['company_id'],
                            'category_id' => $categoryId,
                            'name' => $data['name_zh_TW'],
                            'name_dictionary_key' => $dictKey,
                            'barcode' => $data['barcode'],
                            'spec' => $data['spec'],
                            'manufacturer' => $data['manufacturer'],
                            'track_limit' => $data['track_limit'],
                            'spring_limit' => $data['spring_limit'],
                            'price' => $data['price'],
                            'cost' => $data['cost'],
                            'member_price' => $data['member_price'],
                            'metadata' => [
                                'material_code' => $data['material_code'],
                                'points_full' => $data['points_full'],
                                'points_half' => $data['points_half'],
                                'points_half_amount' => $data['points_half_amount'],
                            ],
                            'is_active' => $data['is_active'],
                        ];

                        if ($product) {
                            $product->update($payload);
                            $results['updated_count']++;
                            $action = 'update';
                            $note = 'product_import_updated';
                        } else {
                            $product = Product::withoutGlobalScopes()->create($payload);
                            $results['created_count']++;
                            $action = 'create';
                            $note = 'product_import_created';
                        }

                        if ($deferImages) {
                            if ($imageDir && $data['image_filename']) {
                                $results['pending_images'][] = [
                                    'product_id' => $product->id,
                                    'filename' => $data['image_filename'],
                                    'row' => $rowCount,
                                ];
                            }
                        } else {
                            $this->attachImage($product, $data['image_filename'], $imageDir, $rowCount, $results);
                        }

                        $results['success_count']++;
                        if ($product->company_id) {
                            $results['company_ids'][$product->company_id] = $product->company_id;
                        }

                        SystemOperationLog::create([
                            'company_id' => $product->company_id,
                            'user_id' => $userId,
                            'module' => 'product',
                            'action' => $action,
                            'target_id' => $product->id,
                            'target_type' => Product::class,
                            'note' => $note,
                            'old_values' => $oldValues,
                            'new_values' => array_merge($product->fresh()->toArray(), ['import_row' => $rowCount]),
                        ]);
                    });
                } catch (\Exception $e) {
                    $this->appendError($results, $rowCount, [$e->getMessage()]);
                }
            }
        }

        $reader->close();
        $results['company_ids'] = array_values($results['company_ids']);

        return $results;
    }

    public function downloadTemplate()
    {
        $writer = new Writer();
        $writer->openToBrowser('products_template.xlsx');

        $writer->addRow(Row::fromValues([
            '分類 ID 或名稱（選填）',
            '商品名稱 zh_TW（必填）',
            '商品名稱 en（選填）',
            '商品名稱 ja（選填）',
            '商品條碼（選填，用於更新）',
            '規格（選填）',
            '生產公司（選填）',
            '售價（必填）',
            '會員價（選填）',
            '成本（必填）',
            '履帶貨道上限（選填，預設 15）',
            '彈簧貨道上限（選填，預設 15）',
            '物料代碼（選填）',
            '全額點數（選填）',
            '半額點數（選填）',
            '半額點數折抵金額（選填）',
            '是否啟用（1/0，選填）',
            '圖片檔名（選填，需一併上傳圖片壓縮檔）',
        ]));

        $writer->addRow(Row::fromValues([
            '飲料',
            '經典拿鐵',
            'Classic Latte',
            'クラシックラテ',
            '471000000001',
            '350ml',
            'Star Cloud',
            80,
            75,
            45,
            15,
            15,
            'MAT-LATTE-001',
            800,
            400,
            40,
            1,
            'latte.png',
        ]));

        $writer->close();
    }

    private function normalizeRow(array $cells, ?int $companyId): array
    {
        return [
            'company_id' => $companyId,
            'category' => $this->stringValue($cells[0] ?? null),
            'name_zh_TW' => $this->stringValue($cells[1] ?? null),
            'name_en' => $this->stringValue($cells[2] ?? null),
            'name_ja' => $this->stringValue($cells[3] ?? null),
            'barcode' => $this->stringValue($cells[4] ?? null),
            'spec' => $this->stringValue($cells[5] ?? null),
            'manufacturer' => $this->stringValue($cells[6] ?? null),
            'price' => $this->numericValue($cells[7] ?? null, null),
            'member_price' => $this->numericValue($cells[8] ?? null, 0),
            'cost' => $this->numericValue($cells[9] ?? null, null),
            'track_limit' => (int) $this->numericValue($cells[10] ?? null, 15),
            'spring_limit' => (int) $this->numericValue($cells[11] ?? null, 15),
            'material_code' => $this->stringValue($cells[12] ?? null),
            'points_full' => (int) $this->numericValue($cells[13] ?? null, 0),
            'points_half' => (int) $this->numericValue($cells[14] ?? null, 0),
            'points_half_amount' => (int) $this->numericValue($cells[15] ?? null, 0),
            'is_active' => $this->booleanValue($cells[16] ?? null),
            'image_filename' => $this->stringValue($cells[17] ?? null),
        ];
    }

    /**
     * 依「圖片檔名」欄將已解壓資料夾中的圖片轉 WebP 入庫並寫回 image_url。
     * 失敗 (找不到檔/非圖) 只記錄至 image_missing，不影響商品本身寫入。
     */
    private function attachImage(Product $product, ?string $filename, ?string $imageDir, int $rowCount, array &$results): void
    {
        if (!$filename || !$imageDir) {
            return;
        }

        try {
            $path = rtrim($imageDir, '/') . '/' . basename($filename);

            if (!is_file($path) || getimagesize($path) === false) {
                $results['image_missing'][] = ['row' => $rowCount, 'filename' => $filename];
                return;
            }

            $oldImage = $product->image_url;

            $file = new UploadedFile($path, basename($path), null, null, true);
            $storedPath = $this->storeAsWebp($file, 'products', 80, 320, 320);

            $product->update(['image_url' => Storage::url($storedPath)]);

            if ($oldImage) {
                $oldPath = str_replace('/storage/', '', $oldImage);
                Storage::disk('public')->delete($oldPath);
            }

            $results['image_success']++;
        } catch (\Throwable $e) {
            $results['image_missing'][] = ['row' => $rowCount, 'filename' => $filename];
        }
    }

    private function isEmptyRow(array $data): bool
    {
        return empty($data['category'])
            && empty($data['name_zh_TW'])
            && empty($data['barcode'])
            && $data['price'] === null
            && $data['cost'] === null;
    }

    private function resolveCategoryId(?string $category, ?int $companyId): ?int
    {
        if (!$category) {
            return null;
        }

        $query = ProductCategory::withoutGlobalScopes()
            ->where(function ($q) use ($companyId) {
                $q->where('company_id', $companyId)->orWhereNull('company_id');
            });

        if (ctype_digit($category)) {
            $found = (clone $query)->where('id', (int) $category)->first();
            if ($found) {
                return $found->id;
            }
        }

        $found = (clone $query)
            ->where(function ($q) use ($category) {
                $q->where('name', $category)
                    ->orWhereHas('translations', function ($translationQuery) use ($category) {
                        $translationQuery->withoutGlobalScopes()->where('value', $category);
                    });
            })
            ->first();

        if (!$found) {
            throw new \InvalidArgumentException(__('Category not found: :category', ['category' => $category]));
        }

        return $found->id;
    }

    private function buildProductLookup(array $data): array
    {
        if (!empty($data['barcode'])) {
            return [
                'company_id' => $data['company_id'],
                'barcode' => $data['barcode'],
            ];
        }

        return [
            'company_id' => $data['company_id'],
            'name' => $data['name_zh_TW'],
        ];
    }

    private function syncTranslations(string $dictKey, array $data, ?int $companyId): void
    {
        $names = [
            'zh_TW' => $data['name_zh_TW'],
            'en' => $data['name_en'],
            'ja' => $data['name_ja'],
        ];

        foreach ($names as $locale => $name) {
            if (empty($name)) {
                Translation::withoutGlobalScopes()->where([
                    'group' => 'product',
                    'key' => $dictKey,
                    'locale' => $locale,
                ])->delete();
                continue;
            }

            Translation::withoutGlobalScopes()->updateOrCreate(
                [
                    'group' => 'product',
                    'key' => $dictKey,
                    'locale' => $locale,
                ],
                [
                    'value' => $name,
                    'company_id' => $companyId,
                ]
            );
        }
    }

    private function appendError(array &$results, int $rowCount, array $messages): void
    {
        $results['error_count']++;
        $results['errors'][] = [
            'row' => $rowCount,
            'messages' => $messages,
        ];
    }

    private function stringValue(mixed $value): ?string
    {
        $value = trim((string) $value);
        return $value === '' ? null : $value;
    }

    private function numericValue(mixed $value, mixed $default): mixed
    {
        if ($value === null || $value === '') {
            return $default;
        }

        return is_numeric($value) ? $value : $value;
    }

    private function booleanValue(mixed $value): bool
    {
        if ($value === null || $value === '') {
            return true;
        }

        if (is_bool($value)) {
            return $value;
        }

        $normalized = strtolower(trim((string) $value));

        return in_array($normalized, ['1', 'true', 'yes', 'y', 'active', 'enabled', '啟用', '是'], true);
    }
}
