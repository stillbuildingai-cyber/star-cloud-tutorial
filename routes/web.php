<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

// Multi-language switch
Route::get('lang/{locale}', [App\Http\Controllers\System\LanguageController::class, 'switch'])->name('lang.switch');

Route::get('/', function () {
    return redirect()->route('login');
});

// 客戶專屬首頁重導向與預覽路由
Route::get('/c/{company_code}', [App\Http\Controllers\Auth\TenantLoginController::class, 'indexRedirect'])->name('tenant.index');
Route::get('/c/{company_code}/preview', [App\Http\Controllers\Auth\TenantLoginController::class, 'preview'])->name('tenant.login.preview');

// 客戶專屬登入頁面（公開，需 guest 中間件）
Route::middleware('guest')->group(function () {
    Route::get('/c/{company_code}/login', [App\Http\Controllers\Auth\TenantLoginController::class, 'showLoginForm'])->name('tenant.login');
    Route::post('/c/{company_code}/login', [App\Http\Controllers\Auth\TenantLoginController::class, 'login'])->name('tenant.login.post');
});


// 公開機台分布地圖 (無需登入)
Route::get('/machines/distribution', [App\Http\Controllers\Admin\BasicSettings\MachineSettingController::class, 'distribution'])->name('machines.distribution');

// 公開取貨憑證頁面 (無需登入)
Route::get('/p/{slug}', [App\Http\Controllers\Guest\PickupController::class, 'show'])->name('pickup.ticket')->middleware('throttle:60,1');
Route::get('/t/{slug}', [App\Http\Controllers\Guest\PassCodeController::class, 'show'])->name('pass-code.ticket')->middleware('throttle:60,1');
Route::get('/g/{slug}', [App\Http\Controllers\Guest\WelcomeGiftController::class, 'show'])->name('welcome-gift.ticket')->middleware('throttle:60,1');

// 公開 QR 產生 (供訪客取貨/通行/好禮頁渲染，無需登入)
Route::get('/qr-code', [App\Http\Controllers\Admin\QrCodeController::class, 'generate'])->name('qr-code.public')->middleware('throttle:120,1');

Route::get('/dashboard', function () {
    return redirect()->route('admin.dashboard');
})->middleware(['auth', 'auth.session', 'verified'])->name('dashboard');

Route::middleware(['auth', 'auth.session', 'verified', 'tenant.access'])->prefix('admin')->name('admin.')->group(function () {
    // 1. 儀表板
    Route::get('/dashboard', [App\Http\Controllers\Admin\DashboardController::class, 'index'])->name('dashboard');

    // 2. 會員管理
    Route::resource('members', App\Http\Controllers\MemberController::class)->only(['index']);
    Route::resource('membership-tiers', App\Http\Controllers\Admin\MembershipTierController::class)->except(['show', 'create', 'edit']);
    Route::resource('deposit-bonus-rules', App\Http\Controllers\Admin\DepositBonusRuleController::class)->except(['show', 'create', 'edit']);
    Route::resource('point-rules', App\Http\Controllers\Admin\PointRuleController::class)->except(['show', 'create', 'edit']);
    Route::resource('gift-definitions', App\Http\Controllers\Admin\GiftDefinitionController::class)->except(['show', 'create', 'edit']);

    // 3. 機台管理
    Route::prefix('machines')->name('machines.')->group(function () {
        Route::get('/permissions', [App\Http\Controllers\Admin\Machine\MachinePermissionController::class, 'index'])->name('permissions')->middleware('can:menu.machines.permissions');
        Route::get('/permissions/accounts/{user}', [App\Http\Controllers\Admin\Machine\MachinePermissionController::class, 'getAccountMachines'])->name('permissions.accounts.get');
        Route::post('/permissions/accounts/{user}', [App\Http\Controllers\Admin\Machine\MachinePermissionController::class, 'syncAccountMachines'])->name('permissions.accounts.sync');

        Route::get('/utilization', [App\Http\Controllers\Admin\MachineController::class, 'utilization'])->name('utilization');
        Route::get('/utilization-ajax/{id?}', [App\Http\Controllers\Admin\MachineController::class, 'utilizationData'])->name('utilization-ajax');
        Route::get('/{machine}/slots-ajax', [App\Http\Controllers\Admin\MachineController::class, 'slotsAjax'])->name('slots-ajax');
        Route::post('/{machine}/slots/expiry', [App\Http\Controllers\Admin\MachineController::class, 'updateSlotExpiry'])->name('slots.expiry.update');
        Route::post('/{machine}/slots/lock', [App\Http\Controllers\Admin\MachineController::class, 'toggleSlotLock'])->name('slots.lock.toggle');
        // 機台專屬定價（獨立頁，列全公司目錄，可上貨前先定價）
        Route::get('/{machine}/pricing', [App\Http\Controllers\Admin\MachineController::class, 'pricing'])->name('pricing');
        Route::post('/{machine}/pricing', [App\Http\Controllers\Admin\MachineController::class, 'updatePricing'])->name('pricing.update');
        Route::get('/{machine}/logs-ajax', [App\Http\Controllers\Admin\MachineController::class, 'logsAjax'])->name('logs-ajax');
        Route::get('/{machine}/temperature-ajax', [App\Http\Controllers\Admin\MachineController::class, 'temperatureAjax'])->name('temperature-ajax');
        Route::get('/{machine}/ambient-temperature-ajax', [App\Http\Controllers\Admin\MachineController::class, 'ambientTemperatureAjax'])->name('ambient-temperature-ajax');
        Route::post('/{machine}/resolve-logs', [App\Http\Controllers\Admin\MachineController::class, 'resolveLogs'])->name('resolve-logs');
    });
    Route::resource('machines', App\Http\Controllers\Admin\MachineController::class);

    // 維修管理
    Route::prefix('maintenance')->name('maintenance.')->middleware('can:menu.machines.maintenance')->group(function () {
        Route::get('/', [App\Http\Controllers\Admin\MaintenanceController::class, 'index'])->name('index');
        Route::get('/create/{serial_no?}', [App\Http\Controllers\Admin\MaintenanceController::class, 'create'])->name('create');
        Route::post('/', [App\Http\Controllers\Admin\MaintenanceController::class, 'store'])->name('store');
    });

    // 4. APP管理
    Route::prefix('app')->name('app.')->group(function () {
        Route::get('/ui-elements', [App\Http\Controllers\Admin\AppConfigController::class, 'uiElements'])->name('ui-elements');
        Route::get('/helper', [App\Http\Controllers\Admin\AppConfigController::class, 'helper'])->name('helper');
        Route::get('/questionnaire', [App\Http\Controllers\Admin\AppConfigController::class, 'questionnaire'])->name('questionnaire');
        Route::get('/games', [App\Http\Controllers\Admin\AppConfigController::class, 'games'])->name('games');
        Route::get('/timer', [App\Http\Controllers\Admin\AppConfigController::class, 'timer'])->name('timer');
    });
    Route::get('/app-configs', [App\Http\Controllers\Admin\AppConfigController::class, 'index'])->name('app-configs.index');
    Route::put('/app-configs', [App\Http\Controllers\Admin\AppConfigController::class, 'update'])->name('app-configs.update');

    // 7. 分析管理
    Route::prefix('analysis')->name('analysis.')->group(function () {
        Route::get('/machine-reports', [App\Http\Controllers\Admin\AnalysisController::class, 'machineReports'])->name('machine-reports')->middleware('can:menu.analysis.machine-reports');
    });

    // 8. 稽核管理
    Route::prefix('audit')->name('audit.')->group(function () {
        Route::get('/purchases', [App\Http\Controllers\Admin\AuditController::class, 'purchases'])->name('purchases');
        Route::get('/transfers', [App\Http\Controllers\Admin\AuditController::class, 'transfers'])->name('transfers');
        Route::get('/replenishments', [App\Http\Controllers\Admin\AuditController::class, 'replenishments'])->name('replenishments');
    });

    // 9. 資料設定
    Route::prefix('data-config')->name('data-config.')->group(function () {
        Route::get('/sub-accounts', [App\Http\Controllers\Admin\PermissionController::class, 'accounts'])->name('sub-accounts')->middleware('can:menu.data-config.sub-accounts');
        Route::patch('/sub-accounts/{id}/toggle-status', [App\Http\Controllers\Admin\PermissionController::class, 'toggleAccountStatus'])->name('sub-accounts.status.toggle')->middleware('can:menu.data-config.sub-accounts');
        Route::post('/sub-accounts', [App\Http\Controllers\Admin\PermissionController::class, 'storeAccount'])->name('sub-accounts.store')->middleware('can:menu.data-config.sub-accounts');
        Route::put('/sub-accounts/{id}', [App\Http\Controllers\Admin\PermissionController::class, 'updateAccount'])->name('sub-accounts.update')->middleware('can:menu.data-config.sub-accounts');
        Route::delete('/sub-accounts/{id}', [App\Http\Controllers\Admin\PermissionController::class, 'destroyAccount'])->name('sub-accounts.destroy')->middleware('can:menu.data-config.sub-accounts');
        Route::get('/sub-account-roles', function() {
            return redirect()->route('admin.data-config.sub-accounts', ['tab' => 'roles']);
        })->name('sub-account-roles')->middleware('can:menu.data-config.sub-accounts');
        Route::get('/sub-account-roles/create', [App\Http\Controllers\Admin\PermissionController::class, 'createRole'])->name('sub-account-roles.create')->middleware('can:menu.data-config.sub-accounts');
        Route::get('/sub-account-roles/{id}/edit', [App\Http\Controllers\Admin\PermissionController::class, 'editRole'])->name('sub-account-roles.edit')->middleware('can:menu.data-config.sub-accounts');
        Route::post('/sub-account-roles', [App\Http\Controllers\Admin\PermissionController::class, 'storeRole'])->name('sub-account-roles.store')->middleware('can:menu.data-config.sub-accounts');
        Route::put('/sub-account-roles/{id}', [App\Http\Controllers\Admin\PermissionController::class, 'updateRole'])->name('sub-account-roles.update')->middleware('can:menu.data-config.sub-accounts');
        Route::delete('/sub-account-roles/{id}', [App\Http\Controllers\Admin\PermissionController::class, 'destroyRole'])->name('sub-account-roles.destroy')->middleware('can:menu.data-config.sub-accounts');
        Route::get('/points', [App\Http\Controllers\Admin\DataConfigController::class, 'points'])->name('points');
    });

    // 10. 遠端管理
    Route::prefix('remote')->name('remote.')->group(function () {
        Route::get('/', [App\Http\Controllers\Admin\RemoteController::class, 'index'])->name('index');
        Route::post('/command', [App\Http\Controllers\Admin\RemoteController::class, 'storeCommand'])->name('store-command');
    });

    // 11. Line管理
    Route::prefix('line')->name('line.')->group(function () {
        Route::get('/members', [App\Http\Controllers\Admin\LineController::class, 'members'])->name('members');
        Route::get('/machines', [App\Http\Controllers\Admin\LineController::class, 'machines'])->name('machines');
        Route::get('/products', [App\Http\Controllers\Admin\LineController::class, 'products'])->name('products');
        Route::get('/official-account', [App\Http\Controllers\Admin\LineController::class, 'officialAccount'])->name('official-account');
        Route::get('/orders', [App\Http\Controllers\Admin\LineController::class, 'orders'])->name('orders');
        Route::get('/coupons', [App\Http\Controllers\Admin\LineController::class, 'coupons'])->name('coupons');
    });

    // 12. 預約系統
    Route::prefix('reservation')->name('reservation.')->group(function () {
        Route::get('/members', [App\Http\Controllers\Admin\ReservationController::class, 'members'])->name('members');
        Route::get('/stores', [App\Http\Controllers\Admin\ReservationController::class, 'stores'])->name('stores');
        Route::get('/time-slots', [App\Http\Controllers\Admin\ReservationController::class, 'timeSlots'])->name('time-slots');
        Route::get('/venues', [App\Http\Controllers\Admin\ReservationController::class, 'venues'])->name('venues');
        Route::get('/coupons', [App\Http\Controllers\Admin\ReservationController::class, 'coupons'])->name('coupons');
        Route::get('/reservations', [App\Http\Controllers\Admin\ReservationController::class, 'reservations'])->name('reservations');
        Route::get('/orders', [App\Http\Controllers\Admin\ReservationController::class, 'orders'])->name('orders');
    });

    // 13. 特殊權限管理
    Route::prefix('special-permission')->name('special-permission.')->group(function () {
        Route::get('/clear-stock', [App\Http\Controllers\Admin\SpecialPermissionController::class, 'clearStock'])->name('clear-stock');
    });

    // 14. 基本設定
    Route::prefix('basic-settings')->name('basic-settings.')->group(function () {
        // 機台設定
        Route::prefix('machines')->name('machines.')->middleware('can:menu.basic.machines')->group(function () {
            // 機台照片獨立更新
            Route::patch('{machine}/photos', [App\Http\Controllers\Admin\BasicSettings\MachinePhotoController::class, 'update'])->name('photos.update');

            Route::get('/', [App\Http\Controllers\Admin\BasicSettings\MachineSettingController::class, 'index'])->name('index');
            Route::get('/{machine}/edit', [App\Http\Controllers\Admin\BasicSettings\MachineSettingController::class, 'edit'])->name('edit');
            Route::put('/{machine}', [App\Http\Controllers\Admin\BasicSettings\MachineSettingController::class, 'update'])->name('update');
            Route::post('/', [App\Http\Controllers\Admin\BasicSettings\MachineSettingController::class, 'store'])->name('store');
            Route::delete('/{machine}', [App\Http\Controllers\Admin\BasicSettings\MachineSettingController::class, 'destroy'])->name('destroy');
            Route::post('/{machine}/regenerate-token', [App\Http\Controllers\Admin\BasicSettings\MachineSettingController::class, 'regenerateToken'])->name('regenerate-token');
            Route::patch('/{machine}/update-system-settings', [App\Http\Controllers\Admin\BasicSettings\MachineSettingController::class, 'updateSystemSettings'])->name('update-system-settings');
            Route::post('/{machine}/sync-settings', [App\Http\Controllers\Admin\BasicSettings\MachineSettingController::class, 'syncSettings'])->name('sync-settings');

            // 地址轉座標 (Geocoding Proxy)
            Route::post('/geocode', [App\Http\Controllers\Admin\GeocodingController::class, 'resolve'])->name('geocode');
        });

        // 機台型號設定
        Route::resource('machine-models', App\Http\Controllers\Admin\BasicSettings\MachineModelController::class)->except(['show']);

        // QR Code 生成
        Route::get('qr-code', [App\Http\Controllers\Admin\QrCodeController::class, 'generate'])->name('qr-code');
    });

    // 15. 權限設定
    Route::prefix('permission')->name('permission.')->group(function () {
        Route::patch('companies/{company}/toggle-status', [App\Http\Controllers\Admin\CompanyController::class, 'toggleStatus'])->name('companies.status.toggle')->middleware('can:menu.permissions.companies');
        Route::put('companies/{company}/settings', [App\Http\Controllers\Admin\CompanyController::class, 'updateSettings'])->name('companies.settings.update')->middleware('can:menu.permissions.companies');
        Route::get('companies/{company}/branding', [App\Http\Controllers\Admin\CompanyController::class, 'editBranding'])->name('companies.branding.edit')->middleware('can:menu.permissions.companies');
        Route::post('companies/{company}/branding', [App\Http\Controllers\Admin\CompanyController::class, 'updateBranding'])->name('companies.branding.update')->middleware('can:menu.permissions.companies');
        Route::resource('companies', App\Http\Controllers\Admin\CompanyController::class)->except(['show', 'create', 'edit'])->middleware('can:menu.permissions.companies');
        Route::get('/accounts', [App\Http\Controllers\Admin\PermissionController::class, 'accounts'])->name('accounts')->middleware('can:menu.permissions.accounts');
        Route::patch('/accounts/{id}/toggle-status', [App\Http\Controllers\Admin\PermissionController::class, 'toggleAccountStatus'])->name('accounts.status.toggle')->middleware('can:menu.permissions.accounts');
        Route::post('/accounts', [App\Http\Controllers\Admin\PermissionController::class, 'storeAccount'])->name('accounts.store')->middleware('can:menu.permissions.accounts');
        Route::put('/accounts/{id}', [App\Http\Controllers\Admin\PermissionController::class, 'updateAccount'])->name('accounts.update')->middleware('can:menu.permissions.accounts');
        Route::delete('/accounts/{id}', [App\Http\Controllers\Admin\PermissionController::class, 'destroyAccount'])->name('accounts.destroy')->middleware('can:menu.permissions.accounts');
        Route::get('/roles', [App\Http\Controllers\Admin\PermissionController::class, 'roles'])->name('roles')->middleware('can:menu.permissions.roles');
        Route::get('/roles/create', [App\Http\Controllers\Admin\PermissionController::class, 'createRole'])->name('roles.create')->middleware('can:menu.permissions.roles');
        Route::get('/roles/{id}/edit', [App\Http\Controllers\Admin\PermissionController::class, 'editRole'])->name('roles.edit')->middleware('can:menu.permissions.roles');
        Route::post('/roles', [App\Http\Controllers\Admin\PermissionController::class, 'storeRole'])->name('roles.store')->middleware('can:menu.permissions.roles');
        Route::put('/roles/{id}', [App\Http\Controllers\Admin\PermissionController::class, 'updateRole'])->name('roles.update')->middleware('can:menu.permissions.roles');
        Route::delete('/roles/{id}', [App\Http\Controllers\Admin\PermissionController::class, 'destroyRole'])->name('roles.destroy');
    });

    // 主題設定
    Route::post('/theme', [App\Http\Controllers\Admin\ThemeController::class, 'update'])->name('theme.update');

    // 16. 切換帳號 (Impersonation)
    Route::post('/impersonate', [App\Http\Controllers\Admin\ImpersonateController::class, 'store'])->name('impersonate.store');
    Route::post('/impersonate/leave', [App\Http\Controllers\Admin\ImpersonateController::class, 'leave'])->name('impersonate.leave');
    Route::get('/impersonate/companies', [App\Http\Controllers\Admin\ImpersonateController::class, 'companies'])->name('impersonate.companies');
    Route::get('/impersonate/users', [App\Http\Controllers\Admin\ImpersonateController::class, 'users'])->name('impersonate.users');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::post("/profile/avatar", [ProfileController::class, "updateAvatar"])->name("profile.avatar");
});

require __DIR__ . '/auth.php';

// 測試路由 (需非正式環境或有特別權限控管)
Route::prefix('test')->name('test.')->group(function () {
    Route::get('/social-login', [App\Http\Controllers\SocialLoginTestController::class, 'index'])->name('social-login');
    Route::get('/line/callback', [App\Http\Controllers\SocialLoginTestController::class, 'lineCallback'])->name('line.callback');
});
// 公開 API 文件 (無需登入)
Route::get('/api/docs', [App\Http\Controllers\ApiDocsController::class, 'index'])->name('api.docs');
