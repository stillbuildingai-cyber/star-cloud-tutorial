<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product\Product;
use App\Models\Product\ProductCategory;
use App\Models\System\Company;
use App\Models\System\Translation;
use App\Traits\ImageHandler;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use App\Services\Product\ProductCatalogService;
use App\Services\ProductImportService;
use App\Models\System\SystemOperationLog;

class ProductController extends Controller
{
    use \App\Traits\ImageHandler;

    public function __construct(
        private readonly ProductCatalogService $catalogService
    ) {}

    /**
     * Upsert a locale=>value map into translations for a given group/key.
     * 空值的語系會被刪除（清空翻譯）。繞過 TenantScoped。
     *
     * @param string $group  product / product_spec
     * @param string $key    對應的 dictionary key
     * @param array $values  locale => value
     * @param int|null $companyId
     */
    private function upsertTranslations(string $group, string $key, array $values, ?int $companyId): void
    {
        foreach ($values as $locale => $value) {
            if (empty($value)) {
                Translation::withoutGlobalScopes()->where([
                    'group' => $group,
                    'key' => $key,
                    'locale' => $locale,
                ])->delete();
                continue;
            }

            Translation::withoutGlobalScopes()->updateOrCreate(
                ['group' => $group, 'key' => $key, 'locale' => $locale],
                ['value' => $value, 'company_id' => $companyId]
            );
        }
    }

    /**
     * 解析規格多語系輸入：優先採 specs 物件；
     * 若僅送舊版單一 spec 欄位則轉為 ['zh_TW' => spec] 以維持相容。
     *
     * @return array locale => value
     */
    private function resolveSpecs(Request $request): array
    {
        $specs = $request->input('specs');
        if (is_array($specs) && !empty($specs)) {
            return $specs;
        }

        $spec = $request->input('spec');
        return $spec !== null && $spec !== '' ? ['zh_TW' => $spec] : [];
    }

    public function index(Request $request)
    {
        $user = auth()->user();
        $tab = $request->input('tab', 'products');
        $per_page = $request->input('per_page', 10);
        
        // Products Query
        $productQuery = Product::with(['category.translations', 'translations', 'company']);
        
        // 搜尋
        if ($request->filled('search')) {
            $search = $request->search;
            $productQuery->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('barcode', 'like', "%{$search}%")
                  ->orWhere('spec', 'like', "%{$search}%");
            });
        }

        // 分類篩選
        if ($request->filled('category_id')) {
            $productQuery->where('category_id', $request->category_id);
        }

        if ($user->isSystemAdmin()) {
            if ($request->filled('product_company_id')) {
                $productQuery->where('company_id', $request->product_company_id);
            }
        }

        $products = $productQuery->orderBy('created_at', 'desc')->orderBy('id', 'desc')->paginate($per_page, ['*'], 'product_page')->withQueryString();

        // Categories Query
        $categoryQuery = ProductCategory::with(['translations', 'company']);
        
        if ($user->isSystemAdmin() && $request->filled('category_company_id')) {
            $categoryQuery->where('company_id', $request->category_company_id);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $categoryQuery->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%");
            });
        }

        $categories = $categoryQuery->orderBy('created_at', 'desc')->orderBy('id', 'desc')->paginate($per_page, ['*'], 'category_page')->withQueryString();
        $companies = $user->isSystemAdmin() ? Company::all() : collect();
        
        // Settings for Modal (use current user company or fallback)
        $selectedCompanyId = $user->isSystemAdmin() 
            ? ($request->input('product_company_id') ?: $request->input('category_company_id')) 
            : $user->company_id;
        $selectedCompany = $selectedCompanyId ? Company::find($selectedCompanyId) : $user->company;
        $companySettings = $selectedCompany ? ($selectedCompany->settings ?? []) : [];
        
        $routeName = 'admin.data-config.products.index';

        if ($request->ajax() || $request->wantsJson()) {
            if ($tab === 'products') {
                return response()->json([
                    'success' => true,
                    'html' => view('admin.products.partials.tab-products', [
                        'products' => $products,
                        'categories' => $categories,
                        'companySettings' => $companySettings,
                        'companies' => $companies,
                        'routeName' => $routeName
                    ])->render()
                ]);
            } elseif ($tab === 'categories') {
                return response()->json([
                    'success' => true,
                    'html' => view('admin.products.partials.tab-categories', [
                        'categories' => $categories,
                        'companies' => $companies,
                        'routeName' => $routeName
                    ])->render()
                ]);
            } elseif ($tab === 'logs') {
                $logQuery = SystemOperationLog::with(['user', 'company', 'target'])
                    ->whereIn('module', ['product', 'category']);

                if ($user->isSystemAdmin()) {
                    if ($request->filled('log_company_id')) {
                        $logQuery->where('company_id', $request->log_company_id);
                    }
                }

                if ($request->filled('search_log')) {
                    $search = $request->search_log;
                    $logQuery->where(function($q) use ($search) {
                        $q->where('note', 'like', "%{$search}%")
                          ->orWhere('old_values', 'like', "%{$search}%")
                          ->orWhere('new_values', 'like', "%{$search}%");
                    });
                }

                $logDefaultStart = now()->startOfDay()->format('Y-m-d H:i');
                $logDefaultEnd = now()->endOfDay()->format('Y-m-d H:i');

                if ($request->filled('start_date') || $request->filled('end_date')) {
                    if ($request->filled('start_date')) {
                        $logQuery->where('created_at', '>=', \Illuminate\Support\Carbon::parse($request->start_date));
                    }
                    if ($request->filled('end_date')) {
                        $logQuery->where('created_at', '<=', \Illuminate\Support\Carbon::parse($request->end_date)->endOfMinute());
                    }
                } else {
                    $logQuery->whereBetween('created_at', [now()->startOfDay(), now()->endOfDay()]);
                    $request->merge([
                        'start_date' => $logDefaultStart,
                        'end_date' => $logDefaultEnd
                    ]);
                }

                $logs = $logQuery->orderBy('created_at', 'desc')->orderBy('id', 'desc')->paginate($per_page, ['*'], 'log_page')->withQueryString();

                return response()->json([
                    'success' => true,
                    'html' => view('admin.products.partials.tab-logs', [
                        'logs' => $logs,
                        'companies' => $companies,
                        'logDefaultStart' => $logDefaultStart,
                        'logDefaultEnd' => $logDefaultEnd
                    ])->render()
                ]);
            }
        }

        $logDefaultStart = now()->startOfDay()->format('Y-m-d H:i');
        $logDefaultEnd = now()->endOfDay()->format('Y-m-d H:i');

        return view('admin.products.index', [
            'products' => $products,
            'categories' => $categories,
            'companies' => $companies,
            'companySettings' => $companySettings,
            'routeName' => $routeName,
            'logDefaultStart' => $logDefaultStart,
            'logDefaultEnd' => $logDefaultEnd
        ]);
    }

    public function create(Request $request)
    {
        $user = auth()->user();
        $categories = ProductCategory::with('translations')->get();
        $companies = $user->isSystemAdmin() ? Company::all() : collect();

        // If system admin, check if company_id is provided in URL to get settings
        $companyId = $request->query('company_id') ?? $user->company_id;
        $selectedCompany = $companyId ? Company::find($companyId) : $user->company;
        $companySettings = $selectedCompany ? ($selectedCompany->settings ?? []) : [];

        return view('admin.products.create', [
            'categories' => $categories,
            'companies' => $companies,
            'companySettings' => $companySettings,
            // 商品多語系 Tab：公司機台語系聯集（見 product_multilingual_spec §4.2）
            'locales' => Company::activeLocalesFor($companyId),
            'localeLabels' => config('locales.supported', ['zh_TW' => '繁體中文']),
        ]);
    }

    public function edit($id)
    {
        $user = auth()->user();
        // 繞過 TenantScoped 載入翻譯，確保系統管理員能看到租戶公司的翻譯資料
        $product = Product::with(['company'])->findOrFail($id);
        $product->setRelation('translations',
            Translation::withoutGlobalScopes()
                ->where('group', 'product')
                ->where('key', $product->name_dictionary_key)
                ->get()
        );
        $product->setRelation('specTranslations',
            Translation::withoutGlobalScopes()
                ->where('group', 'product_spec')
                ->where('key', $product->spec_dictionary_key)
                ->get()
        );
        $categories = ProductCategory::with('translations')->get();
        $companies = $user->isSystemAdmin() ? Company::all() : collect();
        
        // Use the product's company settings for editing
        $companySettings = $product->company ? ($product->company->settings ?? []) : [];

        return view('admin.products.edit', [
            'product' => $product,
            'categories' => $categories,
            'companies' => $companies,
            'companySettings' => $companySettings,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'names' => 'required|array',
            'names.zh_TW' => 'required|string|max:255',
            'names.*' => 'nullable|string|max:255',
            'specs' => 'nullable|array',
            'specs.*' => 'nullable|string|max:2000',
            'barcode' => 'nullable|string|max:100',
            'spec' => 'nullable|string|max:2000',
            'category_id' => 'nullable|exists:product_categories,id',
            'manufacturer' => 'nullable|string|max:255',
            'track_limit' => 'required|integer|min:1',
            'spring_limit' => 'required|integer|min:1',
            'price' => 'required|numeric|min:0',
            'cost' => 'required|numeric|min:0',
            'member_price' => 'required|numeric|min:0',
            'metadata' => 'nullable|array',
            'is_active' => 'nullable|boolean',
            'company_id' => 'nullable|exists:companies,id',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:10240', // Increase to 10MB
        ]);

        try {
            DB::beginTransaction();

            $dictKey = \Illuminate\Support\Str::uuid()->toString();
            $specDictKey = \Illuminate\Support\Str::uuid()->toString();
            // Determine company_id: prioritized from request (for sys admin) then from user
            $company_id = (auth()->user()->isSystemAdmin() && $request->filled('company_id'))
                ? $request->company_id
                : auth()->user()->company_id;

            // 儲存名稱多語系翻譯（繞過 TenantScoped，避免系統管理員操作租戶資料時被過濾）
            $this->upsertTranslations('product', $dictKey, $request->input('names', []), $company_id);

            // 儲存規格多語系翻譯（與名稱同邏輯，group=product_spec）
            $specs = $this->resolveSpecs($request);
            $this->upsertTranslations('product_spec', $specDictKey, $specs, $company_id);

            $imageUrl = null;
            if ($request->hasFile('image')) {
                $path = $this->storeAsWebp($request->file('image'), 'products', 80, 320, 320);
                $imageUrl = Storage::url($path);
            }

            $product = Product::create([
                'company_id' => $company_id,
                'category_id' => $request->category_id,
                'name' => $request->names['zh_TW'] ?? (collect($request->names)->first() ?? 'Untitled'), // Fallback if zh_TW is missing
                'name_dictionary_key' => $dictKey,
                'image_url' => $imageUrl,
                'barcode' => $request->barcode,
                'spec' => $specs['zh_TW'] ?? '',
                'spec_dictionary_key' => $specDictKey,
                'manufacturer' => $request->manufacturer,
                'track_limit' => $request->track_limit,
                'spring_limit' => $request->spring_limit,
                'price' => $request->price,
                'cost' => $request->cost,
                'member_price' => $request->member_price,
                'metadata' => $request->metadata ?? [],
                'is_active' => $request->boolean('is_active', true),
            ]);

            SystemOperationLog::create([
                'company_id' => $product->company_id,
                'user_id' => auth()->id(),
                'module' => 'product',
                'action' => 'create',
                'target_id' => $product->id,
                'target_type' => Product::class,
                'note' => 'product_created',
                'new_values' => $product->toArray(),
            ]);

            DB::commit();

            // Rebuild catalog cache
            $this->catalogService->rebuildCache($product->company_id);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => __('Product created successfully'),
                    'data' => $product
                ]);
            }

            return redirect()->route('admin.data-config.products.index')->with('success', __('Product created successfully'));

        } catch (\Exception $e) {
            DB::rollBack();
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
            }
            return redirect()->back()->with('error', $e->getMessage())->withInput();
        }
    }

    public function update(Request $request, $id)
    {
        $product = Product::findOrFail($id);

        $validated = $request->validate([
            'names' => 'required|array',
            'names.zh_TW' => 'required|string|max:255',
            'names.*' => 'nullable|string|max:255',
            'specs' => 'nullable|array',
            'specs.*' => 'nullable|string|max:2000',
            'barcode' => 'nullable|string|max:100',
            'spec' => 'nullable|string|max:2000',
            'category_id' => 'nullable|exists:product_categories,id',
            'manufacturer' => 'nullable|string|max:255',
            'track_limit' => 'required|integer|min:1',
            'spring_limit' => 'required|integer|min:1',
            'price' => 'required|numeric|min:0',
            'cost' => 'required|numeric|min:0',
            'member_price' => 'required|numeric|min:0',
            'metadata' => 'nullable|array',
            'is_active' => 'nullable|boolean',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:10240', // Increase to 10MB
            'remove_image' => 'nullable|boolean',
        ]);

        try {
            DB::beginTransaction();

            $dictKey = $product->name_dictionary_key ?: \Illuminate\Support\Str::uuid()->toString();
            // 既有商品若尚無 spec 字典鍵，首次編輯時補建
            $specDictKey = $product->spec_dictionary_key ?: \Illuminate\Support\Str::uuid()->toString();
            // Determine company_id: prioritized from request (for sys admin) then from product's current company
            $company_id = (auth()->user()->isSystemAdmin() && $request->filled('company_id'))
                ? $request->company_id
                : $product->company_id;

            // 更新或建立名稱多語系翻譯（繞過 TenantScoped，避免系統管理員操作租戶資料時被過濾）
            $this->upsertTranslations('product', $dictKey, $request->input('names', []), $company_id);

            // 更新或建立規格多語系翻譯（與名稱同邏輯，group=product_spec）
            $specs = $this->resolveSpecs($request);
            $this->upsertTranslations('product_spec', $specDictKey, $specs, $company_id);

            $data = [
                'company_id' => $company_id,
                'category_id' => $request->category_id,
                'name' => $request->names['zh_TW'] ?? ($product->name ?? 'Untitled'),
                'name_dictionary_key' => $dictKey,
                'barcode' => $request->barcode,
                'spec' => $specs['zh_TW'] ?? ($product->spec ?? ''),
                'spec_dictionary_key' => $specDictKey,
                'manufacturer' => $request->manufacturer,
                'track_limit' => $request->track_limit,
                'spring_limit' => $request->spring_limit,
                'price' => $request->price,
                'cost' => $request->cost,
                'member_price' => $request->member_price,
                'metadata' => $request->metadata ?? [],
                'is_active' => $request->boolean('is_active', true),
            ];

            if ($request->hasFile('image')) {
                // Delete old image
                if ($product->image_url) {
                    $oldPath = str_replace('/storage/', '', $product->image_url);
                    Storage::disk('public')->delete($oldPath);
                }
                $path = $this->storeAsWebp($request->file('image'), 'products', 80, 320, 320);
                $data['image_url'] = Storage::url($path);
            } elseif ($request->boolean('remove_image')) {
                if ($product->image_url) {
                    $oldPath = str_replace('/storage/', '', $product->image_url);
                    Storage::disk('public')->delete($oldPath);
                }
                $data['image_url'] = null;
            }

            $oldValues = $product->toArray();
            $product->update($data);
            $newValues = $product->fresh()->toArray();

            SystemOperationLog::create([
                'company_id' => $product->company_id,
                'user_id' => auth()->id(),
                'module' => 'product',
                'action' => 'update',
                'target_id' => $product->id,
                'target_type' => Product::class,
                'note' => 'product_updated',
                'old_values' => $oldValues,
                'new_values' => $newValues,
            ]);

            DB::commit();

            // Rebuild catalog cache
            $this->catalogService->rebuildCache($product->company_id);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => __('Product updated successfully'),
                    'data' => $product
                ]);
            }

            return redirect()->route('admin.data-config.products.index')->with('success', __('Product updated successfully'));

        } catch (\Exception $e) {
            DB::rollBack();
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
            }
            return redirect()->back()->with('error', $e->getMessage())->withInput();
        }
    }

    public function toggleStatus($id)
    {
        try {
            $product = Product::findOrFail($id);
            $oldValues = $product->toArray();
            $product->is_active = !$product->is_active;
            $product->save();
            $newValues = $product->fresh()->toArray();

            SystemOperationLog::create([
                'company_id' => $product->company_id,
                'user_id' => auth()->id(),
                'module' => 'product',
                'action' => 'update',
                'target_id' => $product->id,
                'target_type' => Product::class,
                'note' => 'product_status_toggled',
                'old_values' => $oldValues,
                'new_values' => $newValues,
            ]);

            // Rebuild catalog cache
            $this->catalogService->rebuildCache($product->company_id);

            $status = $product->is_active ? __('Enabled') : __('Disabled');
            
            if (request()->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => __('Product status updated to :status', ['status' => $status]),
                    'is_active' => $product->is_active
                ]);
            }

            return redirect()->back()->with('success', __('Product status updated to :status', ['status' => $status]));
        } catch (\Exception $e) {
            if (request()->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage()
                ], 500);
            }
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    public function destroy($id)
    {
        try {
            $product = Product::findOrFail($id);
            
            // 刪除與此商品關聯的翻譯資料（名稱 + 規格，繞過 TenantScoped）
            if ($product->name_dictionary_key) {
                Translation::withoutGlobalScopes()->where('key', $product->name_dictionary_key)->delete();
            }
            if ($product->spec_dictionary_key) {
                Translation::withoutGlobalScopes()->where('key', $product->spec_dictionary_key)->delete();
            }

            // Delete image
            if ($product->image_url) {
                $oldPath = str_replace('/storage/', '', $product->image_url);
                Storage::disk('public')->delete($oldPath);
            }

            $companyId = $product->company_id;
            $oldValues = $product->toArray();
            $product->delete();

            SystemOperationLog::create([
                'company_id' => $companyId,
                'user_id' => auth()->id(),
                'module' => 'product',
                'action' => 'delete',
                'target_id' => $id,
                'target_type' => Product::class,
                'note' => 'product_deleted',
                'old_values' => $oldValues,
            ]);

            // Rebuild catalog cache
            $this->catalogService->rebuildCache($companyId);

            if (request()->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => __('Product deleted successfully')
                ]);
            }

            return redirect()->back()->with('success', __('Product deleted successfully'));
        } catch (\Exception $e) {
            if (request()->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage()
                ], 500);
            }
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * 匯出商品資料（CSV / Excel），套用與 index 相同的篩選（搜尋/分類/公司）。
     * 參考 SalesController::handleExport 的串流下載模式；欄位順序對齊匯入範本，方便編輯後回匯入。
     */
    public function export(Request $request)
    {
        $user = auth()->user();
        $type = $request->input('export', 'csv');
        $isExcel = ($type === 'excel');
        $ext = $isExcel ? 'xls' : 'csv';
        $contentType = $isExcel ? 'application/vnd.ms-excel; charset=utf-8' : 'text/csv; charset=utf-8';

        // 與 index 相同的商品查詢與篩選（租戶隔離沿用 Product 全域 scope）
        $query = Product::with(['category.translations', 'translations', 'company']);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('barcode', 'like', "%{$search}%")
                  ->orWhere('spec', 'like', "%{$search}%");
            });
        }

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($user->isSystemAdmin() && $request->filled('product_company_id')) {
            $query->where('company_id', $request->product_company_id);
        }

        // 欄位順序對齊匯入範本（ProductImportService::downloadTemplate）
        $headers = [
            '分類',
            '商品名稱(zh_TW)',
            '商品名稱(en)',
            '商品名稱(ja)',
            '條碼',
            '規格',
            '生產公司',
            '售價',
            '會員價',
            '成本',
            '履帶貨道上限',
            '彈簧貨道上限',
            '物料代碼',
            '全額點數',
            '半額點數',
            '半額折抵金額',
            '是否啟用',
            '圖片檔名',
        ];

        $filename = '商品資料_' . now()->format('YmdHis') . '.' . $ext;

        $callback = function () use ($query, $headers, $isExcel) {
            $file = fopen('php://output', 'w');
            if ($isExcel) {
                fwrite($file, '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">');
                fwrite($file, '<head><meta charset="utf-8"/><style>table { border-collapse: collapse; } th, td { border: 1px solid #cbd5e1; padding: 8px 12px; text-align: left; } th { background-color: #f1f5f9; font-weight: bold; }</style></head><body>');
                fwrite($file, '<table><thead><tr>');
                foreach ($headers as $header) {
                    fwrite($file, '<th>' . htmlspecialchars($header) . '</th>');
                }
                fwrite($file, '</tr></thead><tbody>');
            } else {
                fwrite($file, "\xEF\xBB\xBF"); // UTF-8 BOM，Excel 開啟不亂碼
                fputcsv($file, $headers);
            }

            $locale = app()->getLocale();

            $query->orderBy('id')->chunk(500, function ($products) use ($file, $isExcel, $locale) {
                foreach ($products as $p) {
                    $meta = is_array($p->metadata) ? $p->metadata : [];
                    $nameEn = optional($p->translations->firstWhere('locale', 'en'))->value;
                    $nameJa = optional($p->translations->firstWhere('locale', 'ja'))->value;

                    $categoryName = '';
                    if ($p->category) {
                        $catTrans = $p->category->translations->firstWhere('locale', $locale)
                            ?? $p->category->translations->firstWhere('locale', 'zh_TW');
                        $categoryName = $catTrans->value ?? ($p->category->name ?? '');
                    }

                    $imageFilename = $p->image_url ? basename($p->image_url) : '';

                    $row = [
                        $categoryName,
                        $p->name,
                        $nameEn,
                        $nameJa,
                        $p->barcode,
                        $p->spec,
                        $p->manufacturer,
                        $p->price,
                        $p->member_price,
                        $p->cost,
                        $p->track_limit,
                        $p->spring_limit,
                        $meta['material_code'] ?? '',
                        $meta['points_full'] ?? 0,
                        $meta['points_half'] ?? 0,
                        $meta['points_half_amount'] ?? 0,
                        $p->is_active ? 1 : 0,
                        $imageFilename,
                    ];

                    if ($isExcel) {
                        fwrite($file, '<tr>');
                        foreach ($row as $cell) {
                            fwrite($file, '<td>' . htmlspecialchars((string) $cell) . '</td>');
                        }
                        fwrite($file, '</tr>');
                    } else {
                        fputcsv($file, $row);
                    }
                }
            });

            if ($isExcel) {
                fwrite($file, '</tbody></table></body></html>');
            }
            fclose($file);
        };

        return response()->streamDownload($callback, $filename, ['Content-Type' => $contentType]);
    }

    public function downloadTemplate(ProductImportService $importService)
    {
        return $importService->downloadTemplate();
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx|max:5120',
            'company_id' => 'nullable|exists:companies,id',
            'images' => 'nullable|file|mimes:zip|max:61440', // 60MB；落在 php 預設 100M 與 Cloudflare 100M 之內，免改機器設定
        ]);

        $companyId = $request->get('company_id') ?: auth()->user()->company_id;

        if (auth()->user()->isSystemAdmin() && !$companyId) {
            return response()->json([
                'success' => false,
                'message' => __('Please select a company'),
            ], 422);
        }

        // 存檔到耐久暫存目錄並派發背景 Job，請求立即回應，避免大批匯入受 Cloudflare 100 秒等請求逾時限制
        $baseDir = storage_path('app/product-imports/' . Str::uuid()->toString());
        \Illuminate\Support\Facades\File::ensureDirectoryExists($baseDir);

        $request->file('file')->move($baseDir, 'data.xlsx');
        $xlsxPath = $baseDir . '/data.xlsx';

        $zipPath = null;
        if ($request->hasFile('images')) {
            $request->file('images')->move($baseDir, 'images.zip');
            $zipPath = $baseDir . '/images.zip';
        }

        \App\Jobs\Product\ProcessProductImportJob::dispatch(
            $baseDir,
            $xlsxPath,
            $zipPath,
            $companyId,
            auth()->id()
        );

        return response()->json([
            'success' => true,
            'message' => __('Import has been queued. Refresh the list shortly to see the imported products.'),
        ], 202);
    }

    /**
     * 同步商品目錄至全公司機台 (Phase 2)
     */
    public function syncToAllMachines(Request $request)
    {
        $user = auth()->user();
        
        // 1. 決定目標公司 ID
        $companyId = $user->isSystemAdmin() 
            ? $request->input('company_id') 
            : $user->company_id;

        if (!$companyId) {
            return response()->json([
                'success' => false,
                'message' => __('Please select a company.')
            ], 422);
        }

        // 3. 頻率限制：1 分鐘內不可重複對同一家公司發送同步指令
        $machineIds = \App\Models\Machine\Machine::where('company_id', $companyId)->pluck('id');
        $recentCommand = \App\Models\Machine\RemoteCommand::whereIn('machine_id', $machineIds)
            ->where('command_type', 'update_products')
            ->where('created_at', '>=', now()->subMinute())
            ->whereIn('status', ['pending', 'sent'])
            ->exists();

        if ($recentCommand) {
            return response()->json([
                'success' => false,
                'message' => __('A sync command was recently sent. Please wait 1 minute.')
            ], 422);
        }

        $remark = $request->input('remark') ?: __('Manual Sync Products');

        // 2. 派遣延遲廣播 Job
        \App\Jobs\Product\SyncProductsToMachinesJob::dispatch(
            (int) $companyId,
            $remark,
            $user->id
        );

        SystemOperationLog::create([
            'company_id' => $companyId,
            'user_id' => $user->id,
            'module' => 'product',
            'action' => 'sync',
            'target_id' => $companyId,
            'target_type' => Company::class,
            'note' => 'product_catalog_synced_to_all_machines',
            'new_values' => ['remark' => $remark],
        ]);

        return response()->json([
            'success' => true,
            'message' => __('Batch sync command has been queued. Machines will be updated sequentially.')
        ]);
    }
}
