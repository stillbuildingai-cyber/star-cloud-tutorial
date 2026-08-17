<?php

namespace App\Traits;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

trait ImageHandler
{
    /**
     * 將圖片轉換為 WebP 並儲存 (支援自動裁切與縮放)
     *
     * @param UploadedFile $file 原始檔案
     * @param string $directory 儲存目錄 (不含 disk 名稱)
     * @param int $quality 壓縮品質 (0-100)
     * @param int|null $targetWidth 目標寬度
     * @param int|null $targetHeight 目標高度
     * @return string 儲存的路徑
     */
    protected function storeAsWebp(UploadedFile $file, string $directory, int $quality = 80, ?int $targetWidth = null, ?int $targetHeight = null): string
    {
        $filename = Str::random(40) . '.webp';
        $path = "{$directory}/{$filename}";

        // 讀取原始圖片資訊
        $imageInfo = getimagesize($file->getRealPath());
        if (!$imageInfo) {
            return $file->store($directory, 'public');
        }

        $origWidth = $imageInfo[0];
        $origHeight = $imageInfo[1];
        $mime = $imageInfo['mime'];
        $source = null;

        switch ($mime) {
            case 'image/jpeg':
                $source = imagecreatefromjpeg($file->getRealPath());
                break;
            case 'image/png':
                $source = imagecreatefrompng($file->getRealPath());
                break;
            case 'image/gif':
                $source = imagecreatefromgif($file->getRealPath());
                break;
            case 'image/webp':
                $source = imagecreatefromwebp($file->getRealPath());
                break;
            default:
                // 不支援的格式直接存
                return $file->store($directory, 'public');
        }

        if (!$source) {
            return $file->store($directory, 'public');
        }

        // 處理裁切與縮放
        if ($targetWidth && $targetHeight) {
            $targetImage = imagecreatetruecolor($targetWidth, $targetHeight);
            
            // 處理透明背景 (如果是 PNG/WebP)
            if ($mime == 'image/png' || $mime == 'image/webp') {
                imagealphablending($targetImage, false);
                imagesavealpha($targetImage, true);
                $transparent = imagecolorallocatealpha($targetImage, 255, 255, 255, 127);
                imagefilledrectangle($targetImage, 0, 0, $targetWidth, $targetHeight, $transparent);
            }

            // 居中裁切計算法
            $sourceAspect = $origWidth / $origHeight;
            $targetAspect = $targetWidth / $targetHeight;

            if ($sourceAspect > $targetAspect) {
                // 原圖太寬，裁切兩邊
                $tempHeight = $origHeight;
                $tempWidth = (int)($origHeight * $targetAspect);
                $srcX = (int)(($origWidth - $tempWidth) / 2);
                $srcY = 0;
            } else {
                // 原圖太高，裁切上下
                $tempWidth = $origWidth;
                $tempHeight = (int)($origWidth / $targetAspect);
                $srcX = 0;
                $srcY = (int)(($origHeight - $tempHeight) / 2);
            }

            imagecopyresampled(
                $targetImage, $source,
                0, 0, $srcX, $srcY,
                $targetWidth, $targetHeight, $tempWidth, $tempHeight
            );
            
            imagedestroy($source);
            $source = $targetImage;
        } else {
            // 確保支援真彩色 (解決 palette image 問題)
            if (!imageistruecolor($source)) {
                imagepalettetotruecolor($source);
            }
        }

        // 確保目錄存在
        Storage::disk('public')->makeDirectory($directory);
        $fullPath = Storage::disk('public')->path($path);

        // 轉換並儲存
        imagewebp($source, $fullPath, $quality);
        imagedestroy($source);

        return $path;
    }
}
