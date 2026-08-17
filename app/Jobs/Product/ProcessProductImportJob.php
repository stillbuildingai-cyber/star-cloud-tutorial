<?php

namespace App\Jobs\Product;

use App\Models\Product\Product;
use App\Models\System\SystemOperationLog;
use App\Services\Product\ProductCatalogService;
use App\Services\ProductImportService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\File;

/**
 * 全背景處理商品匯入：解壓圖片壓縮檔、匯入商品（含圖片轉 WebP 入庫）、重建目錄快取、寫入結果摘要日誌、清除暫存。
 * Controller 僅負責存檔並派發本 Job，請求立即回應，避免大批匯入受 Cloudflare 100 秒等請求逾時限制。
 */
class ProcessProductImportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 1800;   // 30 分鐘，容納大批商品與圖片轉檔
    public int $tries = 1;        // 不重試整批，避免重複建立商品；結果以日誌呈現

    public function __construct(
        protected string $baseDir,
        protected string $xlsxPath,
        protected ?string $zipPath,
        protected ?int $companyId = null,
        protected ?int $userId = null
    ) {}

    public function handle(ProductImportService $importService, ProductCatalogService $catalogService): void
    {
        try {
            $imageDir = ($this->zipPath && is_file($this->zipPath))
                ? $this->extractImageZip($this->zipPath)
                : null;

            // 背景執行，無請求逾時壓力，圖片同步轉檔即可（deferImages = false）
            $results = $importService->import($this->xlsxPath, $this->companyId, $this->userId, $imageDir);

            foreach ($results['company_ids'] as $companyId) {
                $catalogService->rebuildCache($companyId);
            }

            SystemOperationLog::create([
                'company_id' => $this->companyId,
                'user_id' => $this->userId,
                'module' => 'product',
                'action' => 'import',
                'target_type' => Product::class,
                'note' => 'product_import_completed',
                'new_values' => [
                    'success_count' => $results['success_count'],
                    'created_count' => $results['created_count'],
                    'updated_count' => $results['updated_count'],
                    'error_count' => $results['error_count'],
                    'image_success' => $results['image_success'],
                    'image_missing' => count($results['image_missing']),
                    'errors' => array_slice($results['errors'], 0, 50),
                    'image_missing_detail' => array_slice($results['image_missing'], 0, 50),
                ],
            ]);
        } finally {
            if (is_dir($this->baseDir)) {
                File::deleteDirectory($this->baseDir);
            }
        }
    }

    /**
     * 將圖片壓縮檔安全解壓到 baseDir/images，回傳目錄路徑。
     * 防護：副檔名白名單、basename 防穿越、項目數/單檔/總量上限 (防 zip bomb)、內容驗證為圖片。
     */
    private function extractImageZip(string $zipPath): string
    {
        $allowedExt = ['jpg', 'jpeg', 'png', 'webp'];
        $maxEntries = 2000;
        $maxFileBytes = 5 * 1024 * 1024;
        $maxTotalBytes = 200 * 1024 * 1024;

        $dir = rtrim($this->baseDir, '/') . '/images';
        File::ensureDirectoryExists($dir);

        $zip = new \ZipArchive();
        if ($zip->open($zipPath) !== true) {
            throw new \RuntimeException(__('Unable to read the image archive'));
        }

        if ($zip->numFiles > $maxEntries) {
            $zip->close();
            throw new \RuntimeException(__('The image archive contains too many files'));
        }

        $totalBytes = 0;

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $stat = $zip->statIndex($i);
            $entryName = $stat['name'];

            if (str_ends_with($entryName, '/') || str_contains($entryName, '..')) {
                continue;
            }

            $base = basename($entryName);
            $ext = strtolower(pathinfo($base, PATHINFO_EXTENSION));
            if (!in_array($ext, $allowedExt, true)) {
                continue;
            }

            if ($stat['size'] > $maxFileBytes) {
                continue;
            }

            $totalBytes += $stat['size'];
            if ($totalBytes > $maxTotalBytes) {
                $zip->close();
                throw new \RuntimeException(__('The image archive is too large when extracted'));
            }

            $stream = $zip->getStream($entryName);
            if ($stream === false) {
                continue;
            }
            $contents = stream_get_contents($stream);
            fclose($stream);

            if ($contents === false || getimagesizefromstring($contents) === false) {
                continue;
            }

            file_put_contents($dir . '/' . $base, $contents);
        }

        $zip->close();

        return $dir;
    }
}
