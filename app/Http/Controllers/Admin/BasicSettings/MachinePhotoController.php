<?php

namespace App\Http\Controllers\Admin\BasicSettings;

use App\Http\Controllers\Controller;
use App\Models\Machine\Machine;
use App\Traits\ImageHandler;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class MachinePhotoController extends Controller
{
    use ImageHandler;

    /**
     * 更新機台照片
     */
    public function update(Request $request, Machine $machine): RedirectResponse
    {
        Log::info('Machine Photo Update Request', [
            'machine_id' => $machine->id,
            'files' => $request->allFiles()
        ]);
        
        $request->validate([
            'machine_image_0' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:10240',
            'machine_image_1' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:10240',
            'machine_image_2' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:10240',
        ]);

        try {
            $images = $machine->images ?? [];

            // 處理 3 個索引位置的圖片
            for ($i = 0; $i < 3; $i++) {
                // 先處理刪除標記
                if ($request->input("delete_photo_{$i}") === '1') {
                    if (isset($images[$i])) {
                        unset($images[$i]);
                    }
                }

                // 再處理檔案上傳（若有上傳會覆蓋掉刪除邏輯或原有的圖）
                $fieldName = "machine_image_{$i}";
                if ($request->hasFile($fieldName)) {
                    $file = $request->file($fieldName);
                    
                    // 轉為 WebP 格式與保存
                    $path = $this->storeAsWebp($file, "machines/{$machine->id}");
                    $images[$i] = $path;
                    
                    Log::info("Machine image uploaded at slot {$i}", ['path' => $path]);
                }
            }

            // 過濾掉 null 並重新整理索引，但這裡我們希望保持 3 個槽位的概念
            // 如果用戶想保持順序，我們就直接儲存
            ksort($images);

            $machine->update([
                'images' => $images,
                'updater_id' => auth()->id(),
            ]);

            return back()->with('success', __('Machine images updated successfully.'));
        } catch (\Exception $e) {
            Log::error('Machine Photo Update Failed', [
                'machine_id' => $machine->id,
                'error' => $e->getMessage()
            ]);
            return back()->with('error', __('Failed to update machine images: ') . $e->getMessage());
        }
    }
}
