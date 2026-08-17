<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\System\Company;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Traits\ImageHandler;

class CompanyController extends Controller
{
    use ImageHandler;
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Company::query()->withCount(['users', 'machines'])
            ->with(['contracts.creator:id,name']);

        // 搜尋
        if ($search = $request->input('search')) {
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%");
            });
        }

        // 狀態篩選
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $per_page = $request->input('per_page', 10);
        $companies = $query->latest()->paginate($per_page)->withQueryString();

        // 取得可供選擇的客戶角色範本 (系統層級的角色，排除 super-admin)
        $template_roles = \App\Models\System\Role::whereNull('company_id')
            ->where('name', '!=', 'super-admin')
            ->get();

        // 取得發生驗證錯誤的公司資訊 (用於維持 Modal 開啟)
        $error_company = session('error_company_id') ? Company::find(session('error_company_id')) : null;

        return view('admin.companies.index', compact('companies', 'template_roles', 'error_company'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:50|unique:companies,code',
            'original_type' => 'required|string|in:buyout,lease',
            'tax_id' => 'nullable|string|max:50',
            'contact_name' => 'nullable|string|max:255',
            'contact_phone' => 'nullable|string|max:50',
            'contact_email' => 'nullable|email|max:255',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
            'warranty_start_date' => 'nullable|date',
            'warranty_end_date' => 'nullable|date',
            'software_start_date' => 'nullable|date',
            'software_end_date' => 'nullable|date',
            'status' => 'required|boolean',
            'note' => 'nullable|string',
            'settings' => 'nullable|array',
            // 帳號相關欄位 (可選)
            'admin_username' => 'nullable|string|max:255|unique:users,username',
            'admin_password' => 'nullable|string|min:8',
            'admin_name' => 'nullable|string|max:255',
            'admin_role' => 'nullable|string|exists:roles,name',
        ]);

        // 確保 settings 中的值為布林值
        if (isset($validated['settings'])) {
            $validated['settings']['enable_material_code'] = filter_var($validated['settings']['enable_material_code'] ?? false, FILTER_VALIDATE_BOOLEAN);
            $validated['settings']['enable_points'] = filter_var($validated['settings']['enable_points'] ?? false, FILTER_VALIDATE_BOOLEAN);
        }

        DB::transaction(function () use ($validated) {
            $company = Company::create([
                'name' => $validated['name'],
                'code' => $validated['code'],
                'original_type' => $validated['original_type'],
                'current_type' => $validated['original_type'], // 新增時同步
                'tax_id' => $validated['tax_id'] ?? null,
                'contact_name' => $validated['contact_name'] ?? null,
                'contact_phone' => $validated['contact_phone'] ?? null,
                'contact_email' => $validated['contact_email'] ?? null,
                'start_date' => $validated['start_date'] ?? null,
                'end_date' => $validated['end_date'] ?? null,
                'warranty_start_date' => $validated['warranty_start_date'] ?? null,
                'warranty_end_date' => $validated['warranty_end_date'] ?? null,
                'software_start_date' => $validated['software_start_date'] ?? null,
                'software_end_date' => $validated['software_end_date'] ?? null,
                'status' => $validated['status'],
                'note' => $validated['note'] ?? null,
                'settings' => $validated['settings'] ?? [],
            ]);

            // 記錄合約歷程
            $company->contracts()->create([
                'type' => $company->original_type,
                'start_date' => $company->start_date,
                'end_date' => $company->end_date,
                'warranty_start_date' => $company->warranty_start_date,
                'warranty_end_date' => $company->warranty_end_date,
                'software_start_date' => $company->software_start_date,
                'software_end_date' => $company->software_end_date,
                'note' => __('Initial contract registration'),
                'creator_id' => auth()->id(),
            ]);

            // 如果有填寫帳號資訊，則建立管理員帳號
            if (!empty($validated['admin_username']) && !empty($validated['admin_password'])) {
                $user = \App\Models\System\User::create([
                    'company_id' => $company->id,
                    'username' => $validated['admin_username'],
                    'password' => \Illuminate\Support\Facades\Hash::make($validated['admin_password']),
                    'name' => $validated['admin_name'] ?: ($validated['contact_name'] ?: $validated['name']),
                    'status' => 1,
                    'is_admin' => true,
                ]);

                // 角色初始化與克隆邏輯 (優先使用選擇的角色，否則使用預設)
                $selected_role_name = $validated['admin_role'] ?? '客戶管理員角色模板';
                $role_to_assign = null;

                $template_role = \App\Models\System\Role::where('name', $selected_role_name)
                    ->whereNull('company_id')
                    ->where('name', '!=', 'super-admin')
                    ->first();

                if ($template_role) {
                    // 克隆範本為該公司的「管理員」
                    $role_to_assign = \App\Models\System\Role::query()->create([
                        'name' => '管理員',
                        'guard_name' => 'web',
                        'company_id' => $company->id,
                        'is_system' => false,
                    ]);
                    $role_to_assign->syncPermissions($template_role->getPermissionNames());
                } else {
                    // 如果找不到選定的角色範本，退而求其次嘗試指派現有角色 (通常不應發生)
                    $role_to_assign = $selected_role_name;
                }

                $user->assignRole($role_to_assign);
            }
        });

        return redirect()->back()->with('success', __('Customer created successfully.'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Company $company)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:50|unique:companies,code,' . $company->id,
            'current_type' => 'required|string|in:buyout,lease',
            'tax_id' => 'nullable|string|max:50',
            'contact_name' => 'nullable|string|max:255',
            'contact_phone' => 'nullable|string|max:50',
            'contact_email' => 'nullable|email|max:255',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
            'warranty_start_date' => 'nullable|date',
            'warranty_end_date' => 'nullable|date',
            'software_start_date' => 'nullable|date',
            'software_end_date' => 'nullable|date',
            'status' => 'required|boolean',
            'note' => 'nullable|string',
            'settings' => 'nullable|array',
        ]);

        // 確保 settings 中的值為布林值，避免 JSON 存儲為字串導致前端判斷錯誤
        if (isset($validated['settings'])) {
            $validated['settings']['enable_material_code'] = filter_var($validated['settings']['enable_material_code'] ?? false, FILTER_VALIDATE_BOOLEAN);
            $validated['settings']['enable_points'] = filter_var($validated['settings']['enable_points'] ?? false, FILTER_VALIDATE_BOOLEAN);
        }

        DB::transaction(function () use ($validated, $company) {
            $company->update($validated);

            // 記錄合約歷程
            $company->contracts()->create([
                'type' => $company->current_type,
                'start_date' => $company->start_date,
                'end_date' => $company->end_date,
                'warranty_start_date' => $company->warranty_start_date,
                'warranty_end_date' => $company->warranty_end_date,
                'software_start_date' => $company->software_start_date,
                'software_end_date' => $company->software_end_date,
                'note' => $validated['note'] ?? __('Contract information updated'),
                'creator_id' => auth()->id(),
            ]);
        });
        
        // 分支邏輯：若停用客戶，連帶停用其所有帳號
        if ($validated['status'] == 0) {
            $company->users()->update(['status' => 0]);
        }

        return redirect()->back()->with('success', __('Customer updated successfully.'));
    }

    /**
     * 更新客戶的系統設定
     */
    public function updateSettings(Request $request, Company $company)
    {
        \Log::info('updateSettings payload:', $request->all());
        $settings = $request->input('settings', []);
        $currentSettings = $company->settings ?? [];

        // 採用合併方式，防止覆蓋品牌設定或其他未包含的欄位
        $formattedSettings = array_merge($currentSettings, [
            'enable_material_code' => filter_var($settings['enable_material_code'] ?? false, FILTER_VALIDATE_BOOLEAN),
            'enable_points' => filter_var($settings['enable_points'] ?? false, FILTER_VALIDATE_BOOLEAN),
            'enable_custom_branding' => filter_var($settings['enable_custom_branding'] ?? false, FILTER_VALIDATE_BOOLEAN),
        ]);

        // Ensure we force save the JSON cast properly
        $company->settings = $formattedSettings;
        $company->save();

        return redirect()->to(route('admin.permission.companies.index') . '?tab=settings')
            ->with('success', __('System settings updated successfully.'));
    }

    /**
     * 顯示客製化品牌樣式設定頁面
     */
    public function editBranding(Company $company)
    {
        $settings = $company->settings ?? [];
        $enableCustomBranding = filter_var($settings['enable_custom_branding'] ?? false, FILTER_VALIDATE_BOOLEAN);

        if (!$enableCustomBranding) {
            return redirect()->route('admin.permission.companies.index')
                ->with('error', __('Custom branding function is not enabled for this company.'));
        }

        return view('admin.companies.branding', compact('company'));
    }

    /**
     * 更新公司品牌客製化樣式設定 (自訂 Logo 與歡迎詞)
     */
    public function updateBranding(Request $request, Company $company)
    {
        \Log::info('updateBranding payload:', [
            'company_id' => $company->id,
            'request_all' => $request->all(),
            'files' => array_keys($request->allFiles())
        ]);

        $settings = $company->settings ?? [];

        // 1. 安全檢驗：如果未授權此功能，則拒絕操作
        $enableCustomBranding = filter_var($settings['enable_custom_branding'] ?? false, FILTER_VALIDATE_BOOLEAN);
        if (!$enableCustomBranding) {
            return redirect()->back()->with('error', __('Custom branding function is not enabled for this company.'));
        }

        // 2. 欄位驗證
        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'logo_file' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg,webp|max:10240', // 限制最大 10MB
            'login_bg_file' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg,webp|max:10240', // 限制最大 10MB
            'login_card_bg_file' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg,webp|max:10240', // 限制最大 10MB
            'login_title' => 'nullable|string|max:100',
            'login_main_title' => 'nullable|string|max:100',
            'login_sub_title' => 'nullable|string|max:100',
            'clear_logo' => 'nullable|boolean', // 用於提供清除 Logo 功能
            'clear_login_bg' => 'nullable|boolean', // 清除大背景
            'clear_login_card_bg' => 'nullable|boolean', // 清除卡片背景
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput()
                ->with('error', __('Validation failed. Please check the styling configurations.'));
        }

        $logoPath = $settings['logo_path'] ?? null;
        $loginBgPath = $settings['login_bg_path'] ?? null;
        $loginCardBgPath = $settings['login_card_bg_path'] ?? null;

        // 3. 處理清除圖片的需求
        if ($request->boolean('clear_logo')) {
            if ($logoPath) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($logoPath);
                $logoPath = null;
            }
        }
        if ($request->boolean('clear_login_bg')) {
            if ($loginBgPath) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($loginBgPath);
                $loginBgPath = null;
            }
        }
        if ($request->boolean('clear_login_card_bg')) {
            if ($loginCardBgPath) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($loginCardBgPath);
                $loginCardBgPath = null;
            }
        }

        // 4. 處理圖片檔案上傳 (轉為高品質 webp)
        if ($request->hasFile('logo_file')) {
            if ($logoPath) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($logoPath);
            }
            $logoPath = $this->storeAsWebp($request->file('logo_file'), 'company_logos', 80);
        }
        if ($request->hasFile('login_bg_file')) {
            if ($loginBgPath) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($loginBgPath);
            }
            $loginBgPath = $this->storeAsWebp($request->file('login_bg_file'), 'company_backgrounds', 85);
        }
        if ($request->hasFile('login_card_bg_file')) {
            if ($loginCardBgPath) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($loginCardBgPath);
            }
            $loginCardBgPath = $this->storeAsWebp($request->file('login_card_bg_file'), 'company_backgrounds', 85);
        }

        // 5. 合併寫入 settings JSON 中，防止覆寫其他功能開關
        $company->settings = array_merge($settings, [
            'logo_path' => $logoPath,
            'login_bg_path' => $loginBgPath,
            'login_card_bg_path' => $loginCardBgPath,
            'login_title' => $request->input('login_title'),
            'login_main_title' => $request->input('login_main_title'),
            'login_sub_title' => $request->input('login_sub_title'),
        ]);

        $company->save();

        return redirect()->route('admin.permission.companies.branding.edit', $company->id)
            ->with('success', __('Brand styling updated successfully.'));
    }


    /**
     * 切換客戶狀態
     */
    public function toggleStatus(Company $company)
    {
        $newStatus = $company->status == 1 ? 0 : 1;
        $company->update(['status' => $newStatus]);

        // 若切換為停用，同步更新所有旗下帳號
        if ($newStatus == 0) {
            $company->users()->update(['status' => 0]);
            return redirect()->back()->with('success', __('Customer and associated accounts disabled successfully.'));
        }

        return redirect()->back()->with('success', __('Customer enabled successfully.'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Company $company)
    {
        if ($company->users()->count() > 0) {
            return redirect()->back()->with('error', __('Cannot delete company with active accounts.'));
        }

        // 為了解決軟刪除導致的唯一索引佔用問題，刪除前先重新命名唯一欄位
        $timestamp = now()->getTimestamp();
        $company->code = $company->code . '.deleted.' . $timestamp;
        $company->save();

        $company->delete();

        return redirect()->back()->with('success', __('Customer deleted successfully.'));
    }
}
