<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\System\Company;
use App\Models\System\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class TenantLoginController extends Controller
{
    /**
     * 顯示專屬客戶的客製化登入頁面
     */
    public function showLoginForm($company_code)
    {
        // 1. 查詢啟用的公司
        $company = Company::active()->where('code', $company_code)->first();

        // 2. 安全防護：若公司不存在，或沒有啟用品牌客製化功能授權，一律自動重導向至通用登入頁
        if (!$company) {
            return redirect()->route('login');
        }

        $settings = $company->settings ?? [];
        $enableCustomBranding = filter_var($settings['enable_custom_branding'] ?? false, FILTER_VALIDATE_BOOLEAN);

        if (!$enableCustomBranding) {
            Log::warning("Tenant login attempted for unauthorized company [{$company_code}]. Redirecting to main login.");
            return redirect()->route('login');
        }

        // 3. 渲染共用的 login Blade，傳入當前公司的上下文
        return view('auth.login', compact('company'));
    }

    /**
     * 處理專屬客戶的登入請求 (安全性加強版)
     */
    public function login($company_code, Request $request)
    {
        // 1. 查詢並確認該公司
        $company = Company::active()->where('code', $company_code)->first();
        if (!$company) {
            return redirect()->route('login');
        }

        $settings = $company->settings ?? [];
        $enableCustomBranding = filter_var($settings['enable_custom_branding'] ?? false, FILTER_VALIDATE_BOOLEAN);
        if (!$enableCustomBranding) {
            return redirect()->route('login');
        }

        // 2. 驗證輸入欄位
        $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        // 3. 查詢使用者
        $user = User::where('username', $request->username)->first();

        // 4. 【關鍵安全防錯】驗證此使用者是否確實屬於該公司
        if (!$user || (int)$user->company_id !== (int)$company->id) {
            Log::warning("Tenant login security violation: User [{$request->username}] attempted login at company [{$company_code}] portal. User Company ID: " . var_export($user ? $user->company_id : null, true) . ", Target Company ID: {$company->id}");
            
            return back()->withErrors([
                'username' => __('This account does not belong to this company.'),
            ])->withInput($request->only('username', 'remember'));
        }

        // 5. 驗證帳號狀態
        if ($user->status !== 1) {
            return back()->withErrors([
                'username' => __('Your account is disabled.'),
            ])->withInput($request->only('username', 'remember'));
        }

        // 6. 驗證密碼
        if (!Hash::check($request->password, $user->password)) {
            return back()->withErrors([
                'password' => __('auth.failed'),
            ])->withInput($request->only('username', 'remember'));
        }

        // 7. 執行登入與安全 Session 重新生成
        Auth::login($user, $request->boolean('remember'));
        $request->session()->regenerate();

        Log::info("User [{$user->username}] successfully logged in via company portal [{$company_code}].");

        return redirect()->intended(route('admin.dashboard'));
    }

    /**
     * 預覽專屬客戶的客製化登入頁面 (不受 guest 中間件阻擋，供後台預覽用)
     */
    public function preview($company_code)
    {
        // 1. 查詢啟用的公司
        $company = Company::active()->where('code', $company_code)->first();

        // 2. 若公司不存在，重導向至主登入頁
        if (!$company) {
            return redirect()->route('login');
        }

        $settings = $company->settings ?? [];
        $enableCustomBranding = filter_var($settings['enable_custom_branding'] ?? false, FILTER_VALIDATE_BOOLEAN);

        if (!$enableCustomBranding) {
            return redirect()->route('login');
        }

        $isPreview = true;

        // 3. 渲染共用的 login Blade，並傳入預覽標記
        return view('auth.login', compact('company', 'isPreview'));
    }

    /**
     * 處理打 /c/{company_code} 的跳轉邏輯
     */
    public function indexRedirect($company_code)
    {
        // 1. 查詢啟用的公司
        $company = Company::active()->where('code', $company_code)->first();

        if (!$company) {
            return redirect()->route('login');
        }

        // 2. 已登入則去 dashboard，未登入則去專屬登入頁
        if (auth()->check()) {
            return redirect()->route('admin.dashboard');
        }

        return redirect()->route('tenant.login', $company_code);
    }
}

