<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product\ProductCategory;
use App\Models\System\Translation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Models\System\SystemOperationLog;

class ProductCategoryController extends Controller
{
    /**
     * 顯示商品分類清單 (主要用於 AJAX 或內嵌在商品管理頁面)
     */
    public function index(Request $request)
    {
        $user = auth()->user();
        $query = ProductCategory::with(['translations']);

        if ($user->isSystemAdmin() && $request->filled('company_id')) {
            $query->where('company_id', $request->company_id);
        }

        $categories = $query->latest()->get();

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'data' => $categories
            ]);
        }

        return redirect()->route('admin.data-config.products.index', ['tab' => 'categories']);
    }

    /**
     * 儲存新分類
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'names.zh_TW' => 'required|string|max:255',
            'names.en' => 'nullable|string|max:255',
            'names.ja' => 'nullable|string|max:255',
            'company_id' => 'nullable|exists:companies,id',
        ]);

        try {
            DB::beginTransaction();

            $dictKey = Str::uuid()->toString();
            $company_id = (auth()->user()->isSystemAdmin() && $request->filled('company_id')) 
                ? $request->company_id 
                : auth()->user()->company_id;

            // 儲存多語系翻譯
            foreach ($request->names as $locale => $value) {
                if (empty($value)) continue;
                Translation::withoutGlobalScopes()->create([
                    'group' => 'category',
                    'key' => $dictKey,
                    'locale' => $locale,
                    'value' => $value,
                    'company_id' => $company_id,
                ]);
            }

            $category = ProductCategory::create([
                'company_id' => $company_id,
                'name' => $request->names['zh_TW'] ?? (collect($request->names)->first() ?? 'Untitled'),
                'name_dictionary_key' => $dictKey,
            ]);

            SystemOperationLog::create([
                'company_id' => $category->company_id,
                'user_id' => auth()->id(),
                'module' => 'category',
                'action' => 'create',
                'target_id' => $category->id,
                'target_type' => ProductCategory::class,
                'note' => 'category_created',
                'new_values' => $category->toArray(),
            ]);

            DB::commit();

            return redirect()->back()->with('success', __('Category created successfully'));

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', $e->getMessage())->withInput();
        }
    }

    /**
     * 更新分類
     */
    public function update(Request $request, $id)
    {
        $category = ProductCategory::findOrFail($id);

        $validated = $request->validate([
            'names.zh_TW' => 'required|string|max:255',
            'names.en' => 'nullable|string|max:255',
            'names.ja' => 'nullable|string|max:255',
        ]);

        try {
            DB::beginTransaction();

            $dictKey = $category->name_dictionary_key ?: Str::uuid()->toString();
            $company_id = $category->company_id;

            foreach ($request->names as $locale => $value) {
                if (empty($value)) {
                    Translation::withoutGlobalScopes()->where([
                        'group' => 'category',
                        'key' => $dictKey,
                        'locale' => $locale
                    ])->delete();
                    continue;
                }

                Translation::withoutGlobalScopes()->updateOrCreate(
                    [
                        'group' => 'category',
                        'key' => $dictKey,
                        'locale' => $locale,
                    ],
                    [
                        'value' => $value,
                        'company_id' => $company_id,
                    ]
                );
            }

            $oldValues = $category->toArray();
            $category->update([
                'name' => $request->names['zh_TW'] ?? $category->name,
                'name_dictionary_key' => $dictKey,
            ]);
            $newValues = $category->fresh()->toArray();

            SystemOperationLog::create([
                'company_id' => $category->company_id,
                'user_id' => auth()->id(),
                'module' => 'category',
                'action' => 'update',
                'target_id' => $category->id,
                'target_type' => ProductCategory::class,
                'note' => 'category_updated',
                'old_values' => $oldValues,
                'new_values' => $newValues,
            ]);

            DB::commit();

            return redirect()->back()->with('success', __('Category updated successfully'));

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', $e->getMessage())->withInput();
        }
    }

    /**
     * 刪除分類
     */
    public function destroy($id)
    {
        try {
            $category = ProductCategory::findOrFail($id);

            // 檢查是否已有商品使用此分類
            if ($category->products()->count() > 0) {
                $errorMsg = __('Cannot delete category that has products. Please move products first.');
                if (request()->ajax()) {
                    return response()->json(['success' => false, 'message' => $errorMsg], 422);
                }
                return redirect()->back()->with('error', $errorMsg);
            }

            $oldValues = $category->toArray();
            $categoryId = $category->id;
            $companyId = $category->company_id;
            $category->delete();

            SystemOperationLog::create([
                'company_id' => $companyId,
                'user_id' => auth()->id(),
                'module' => 'category',
                'action' => 'delete',
                'target_id' => $categoryId,
                'target_type' => ProductCategory::class,
                'note' => 'category_deleted',
                'old_values' => $oldValues,
            ]);

            if (request()->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => __('Category deleted successfully')
                ]);
            }

            return redirect()->back()->with('success', __('Category deleted successfully'));
        } catch (\Exception $e) {
            if (request()->ajax()) {
                return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
            }
            return redirect()->back()->with('error', $e->getMessage());
        }
    }
}
