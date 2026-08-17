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

    // 5. 倉庫管理
    Route::prefix('warehouses')->name('warehouses.')->group(function () {
        // 模組 1：倉庫總覽
        Route::get('/', [App\Http\Controllers\Admin\WarehouseController::class, 'index'])->name('index');
        Route::post('/', [App\Http\Controllers\Admin\WarehouseController::class, 'store'])->name('store');
        Route::put('/{warehouse}', [App\Http\Controllers\Admin\WarehouseController::class, 'update'])->name('update');
        Route::delete('/{warehouse}', [App\Http\Controllers\Admin\WarehouseController::class, 'destroy'])->name('destroy');
        Route::patch('/{warehouse}/toggle-status', [App\Http\Controllers\Admin\WarehouseController::class, 'toggleStatus'])->name('toggle-status');

        // 模組 2：庫存管理
        Route::get('/inventory', [App\Http\Controllers\Admin\WarehouseController::class, 'inventory'])->name('inventory');
        Route::post('/inventory/stock-in', [App\Http\Controllers\Admin\WarehouseController::class, 'storeStockIn'])->name('inventory.stock-in.store');
        Route::patch('/inventory/stock-in/{stockInOrder}/confirm', [App\Http\Controllers\Admin\WarehouseController::class, 'confirmStockIn'])->name('inventory.stock-in.confirm');
        Route::delete('/inventory/stock-in/{stockInOrder}', [App\Http\Controllers\Admin\WarehouseController::class, 'destroyStockIn'])->name('inventory.stock-in.destroy');
        Route::get('/inventory/stock-in/{order}/details', [App\Http\Controllers\Admin\WarehouseController::class, 'stockInOrderDetails'])->name('inventory.stock-in.details');

        // 模組 3：調撥單
        Route::get('/transfers', [App\Http\Controllers\Admin\WarehouseController::class, 'transfers'])->name('transfers');
        Route::post('/transfers', [App\Http\Controllers\Admin\WarehouseController::class, 'storeTransfer'])->name('transfers.store');
        Route::patch('/transfers/{transferOrder}/confirm', [App\Http\Controllers\Admin\WarehouseController::class, 'confirmTransfer'])->name('transfers.confirm');
        Route::delete('/transfers/{transferOrder}', [App\Http\Controllers\Admin\WarehouseController::class, 'destroyTransfer'])->name('transfers.destroy');
        Route::get('/transfers/{id}/details', [App\Http\Controllers\Admin\WarehouseController::class, 'transferOrderDetails'])->name('transfers.details');
        Route::get('/transfers/{id}/print', [App\Http\Controllers\Admin\WarehouseController::class, 'printTransfer'])->name('transfers.print');

        // 模組 4：機台庫存總覽 (Force Route Refresh)
        Route::get('/machine-inventory', [App\Http\Controllers\Admin\WarehouseController::class, 'machineInventory'])->name('machine-inventory');
        Route::get('/machine-inventory/export', [App\Http\Controllers\Admin\WarehouseController::class, 'machineInventoryExportBatch'])->name('machine-inventory.export-batch');
        Route::get('/machine-inventory/{machine}/slots', [App\Http\Controllers\Admin\WarehouseController::class, 'machineSlots'])->name('machine-inventory.slots');
        Route::get('/machine-inventory/{machine}/export', [App\Http\Controllers\Admin\WarehouseController::class, 'machineInventoryExport'])->name('machine-inventory.export');
        Route::get('/machine-inventory/{machine}/movements', [App\Http\Controllers\Admin\WarehouseController::class, 'machineStockMovements'])->name('machine-inventory.movements');

        // 模組 5：機台補貨
        Route::get('/replenishments', [App\Http\Controllers\Admin\WarehouseController::class, 'replenishments'])->name('replenishments');
        Route::post('/replenishments', [App\Http\Controllers\Admin\WarehouseController::class, 'storeReplenishment'])->name('replenishments.store');
        Route::post('/replenishments/auto', [App\Http\Controllers\Admin\WarehouseController::class, 'autoReplenishment'])->name('replenishments.auto');
        Route::patch('/replenishments/{replenishmentOrder}/status', [App\Http\Controllers\Admin\WarehouseController::class, 'updateReplenishmentStatus'])->name('replenishments.status');
        Route::patch('/replenishments/{replenishmentOrder}/cancel', [App\Http\Controllers\Admin\WarehouseController::class, 'cancelReplenishment'])->name('replenishments.cancel');
        Route::patch('/replenishments/{replenishmentOrder}/assign', [App\Http\Controllers\Admin\WarehouseController::class, 'assignReplenishment'])->name('replenishments.assign');
        Route::get('/replenishments/machine-slots/{machine}', [App\Http\Controllers\Admin\WarehouseController::class, 'getMachineSlotsForReplenishment'])->name('replenishments.machine-slots');
        Route::get('/replenishments/{order}/details', [App\Http\Controllers\Admin\WarehouseController::class, 'replenishmentOrderDetails'])->name('replenishments.details');
        Route::get('/replenishments/{id}/print', [App\Http\Controllers\Admin\WarehouseController::class, 'printReplenishment'])->name('replenishments.print');

        // AJAX 庫存查詢
        Route::get('/ajax/stock', [App\Http\Controllers\Admin\WarehouseController::class, 'getStockAjax'])->name('ajax.stock');
        Route::get('/{warehouse}/inventory-ajax', [App\Http\Controllers\Admin\WarehouseController::class, 'warehouseStocks'])->name('inventory-ajax');
    });

    // 6. 銷售管理
    Route::prefix('sales')->name('sales.')->group(function () {
        Route::get('/', [App\Http\Controllers\Admin\SalesController::class, 'index'])->name('index');

        // 手動補單（機台斷線/漏報時人工補登銷售紀錄）— 限系統管理員（Controller 內把關）
        Route::get('/manual/machine-slots', [App\Http\Controllers\Admin\SalesController::class, 'manualMachineSlots'])->name('manual.machine-slots');
        Route::post('/manual', [App\Http\Controllers\Admin\SalesController::class, 'storeManualOrder'])->name('manual.store');

        // 電子發票對帳/補開/作廢/列印
        Route::post('/invoices/{invoice}/print', [App\Http\Controllers\Admin\SalesController::class, 'printInvoice'])->name('invoices.print');
        Route::post('/invoices/{invoice}/reconcile', [App\Http\Controllers\Admin\SalesController::class, 'reconcileInvoice'])->name('invoices.reconcile');
        Route::post('/invoices/{invoice}/reissue', [App\Http\Controllers\Admin\SalesController::class, 'reissueInvoice'])->name('invoices.reissue');
        Route::post('/invoices/{invoice}/void', [App\Http\Controllers\Admin\SalesController::class, 'voidInvoice'])->name('invoices.void');

        // 管理者備註（訂單／發票各自獨立）
        Route::patch('/orders/{order}/remark', [App\Http\Controllers\Admin\SalesController::class, 'updateOrderRemark'])->name('orders.remark');
        Route::post('/orders/{order}/pickup-code', [App\Http\Controllers\Admin\SalesController::class, 'generateOrderPickupCode'])->name('orders.pickup-code');
        Route::patch('/invoices/{invoice}/remark', [App\Http\Controllers\Admin\SalesController::class, 'updateInvoiceRemark'])->name('invoices.remark');

        // 取貨碼
        Route::get('/pickup-codes', [App\Http\Controllers\Admin\SalesController::class, 'pickupCodes'])->name('pickup-codes');
        // 產碼用：回傳機台所屬公司的商品清單 + 機台目前已上架的 product_id（供前端標示「機台未上架」提示）
        Route::get('/pickup-codes/company-products/{machine}', [App\Http\Controllers\Admin\SalesController::class, 'pickupCompanyProducts'])->name('pickup-codes.company-products');
        Route::post('/pickup-codes', [App\Http\Controllers\Admin\SalesController::class, 'storePickupCode'])->name('pickup-codes.store');
        Route::patch('/pickup-codes/{pickupCode}', [App\Http\Controllers\Admin\SalesController::class, 'updatePickupCode'])->name('pickup-codes.update');
        Route::delete('/pickup-codes/{pickupCode}', [App\Http\Controllers\Admin\SalesController::class, 'destroyPickupCode'])->name('pickup-codes.destroy');
        Route::get('/pickup-codes/batch/{batchNo}/download', [App\Http\Controllers\Admin\SalesController::class, 'downloadPickupCodeBatch'])->name('pickup-codes.batch-download');

        // 通行碼
        Route::get('/pass-codes', [App\Http\Controllers\Admin\SalesController::class, 'passCodes'])->name('pass-codes');
        Route::post('/pass-codes', [App\Http\Controllers\Admin\SalesController::class, 'storePassCode'])->name('pass-codes.store');
        Route::patch('/pass-codes/{passCode}', [App\Http\Controllers\Admin\SalesController::class, 'updatePassCode'])->name('pass-codes.update');
        Route::delete('/pass-codes/{passCode}', [App\Http\Controllers\Admin\SalesController::class, 'destroyPassCode'])->name('pass-codes.destroy');
        Route::get('/pass-codes/batch/{batchNo}/download', [App\Http\Controllers\Admin\SalesController::class, 'downloadPassCodeBatch'])->name('pass-codes.batch-download');

        Route::get('/orders', [App\Http\Controllers\Admin\SalesController::class, 'orders'])->name('orders');
        Route::get('/promotions', [App\Http\Controllers\Admin\SalesController::class, 'promotions'])->name('promotions');
        Route::get('/store-gifts', [App\Http\Controllers\Admin\SalesController::class, 'storeGifts'])->name('store-gifts');
        Route::post('/store-gifts', [App\Http\Controllers\Admin\SalesController::class, 'storeWelcomeGift'])->name('store-gifts.store');
        Route::patch('/store-gifts/{welcomeGift}', [App\Http\Controllers\Admin\SalesController::class, 'updateWelcomeGift'])->name('store-gifts.update');
        Route::delete('/store-gifts/{welcomeGift}', [App\Http\Controllers\Admin\SalesController::class, 'destroyWelcomeGift'])->name('store-gifts.destroy');

        // 領藥單（取物單模式 / 領藥模組）— 受權限 menu.sales.pharmacy-pickup 控管
        Route::get('/pharmacy-pickup', [App\Http\Controllers\Admin\PharmacyPickupController::class, 'index'])->name('pharmacy-pickup')->middleware('can:menu.sales.pharmacy-pickup');
        Route::post('/pharmacy-pickup', [App\Http\Controllers\Admin\PharmacyPickupController::class, 'store'])->name('pharmacy-pickup.store')->middleware('can:menu.sales.pharmacy-pickup');
        Route::get('/pharmacy-pickup/{machine}/products-ajax', [App\Http\Controllers\Admin\PharmacyPickupController::class, 'productsAjax'])->name('pharmacy-pickup.products-ajax')->middleware('can:menu.sales.pharmacy-pickup');
        Route::get('/pharmacy-pickup/{order}/print', [App\Http\Controllers\Admin\PharmacyPickupController::class, 'print'])->name('pharmacy-pickup.print')->middleware('can:menu.sales.pharmacy-pickup');
        Route::post('/pharmacy-pickup/{order}/cancel', [App\Http\Controllers\Admin\PharmacyPickupController::class, 'cancel'])->name('pharmacy-pickup.cancel')->middleware('can:menu.sales.pharmacy-pickup');

        // 詳情路由必須放在最後，避免攔截其他具體路由
        Route::get('/{order}', [App\Http\Controllers\Admin\SalesController::class, 'show'])->name('show');
    });

    // 7. 分析管理
    Route::prefix('analysis')->name('analysis.')->group(function () {
        Route::get('/change-stock', [App\Http\Controllers\Admin\AnalysisController::class, 'changeStock'])->name('change-stock')->middleware('can:menu.analysis.change-stock');
        Route::get('/machine-reports', [App\Http\Controllers\Admin\AnalysisController::class, 'machineReports'])->name('machine-reports')->middleware('can:menu.analysis.machine-reports');
        Route::get('/product-reports', [App\Http\Controllers\Admin\AnalysisController::class, 'productReports'])->name('product-reports')->middleware('can:menu.analysis.product-reports');
        Route::get('/survey-analysis', [App\Http\Controllers\Admin\AnalysisController::class, 'surveyAnalysis'])->name('survey-analysis')->middleware('can:menu.analysis.survey-analysis');
    });

    // 8. 稽核管理
    Route::prefix('audit')->name('audit.')->group(function () {
        Route::get('/purchases', [App\Http\Controllers\Admin\AuditController::class, 'purchases'])->name('purchases');
        Route::get('/transfers', [App\Http\Controllers\Admin\AuditController::class, 'transfers'])->name('transfers');
        Route::get('/replenishments', [App\Http\Controllers\Admin\AuditController::class, 'replenishments'])->name('replenishments');
    });

    // 9. 資料設定
    Route::prefix('data-config')->name('data-config.')->group(function () {
        Route::middleware('can:menu.data-config.products')->group(function () {
            Route::get('/products/template', [App\Http\Controllers\Admin\ProductController::class, 'downloadTemplate'])->name('products.template');
            Route::get('/products/export', [App\Http\Controllers\Admin\ProductController::class, 'export'])->name('products.export');
            Route::post('/products/import', [App\Http\Controllers\Admin\ProductController::class, 'import'])->name('products.import');
            Route::resource('products', App\Http\Controllers\Admin\ProductController::class)->except(['show']);
            Route::patch('/products/{id}/toggle-status', [App\Http\Controllers\Admin\ProductController::class, 'toggleStatus'])->name('products.status.toggle');
            Route::post('/products/sync-all', [App\Http\Controllers\Admin\ProductController::class, 'syncToAllMachines'])->name('products.sync-all');
            Route::resource('product-categories', App\Http\Controllers\Admin\ProductCategoryController::class)->except(['show', 'create', 'edit']);
        });

        // 廣告管理 (Advertisement Management)
        Route::middleware('can:menu.data-config.advertisements')->group(function () {
            Route::resource('advertisements', App\Http\Controllers\Admin\AdvertisementController::class)->except(['show', 'create', 'edit']);
            Route::patch('/advertisements/{id}/toggle-status', [App\Http\Controllers\Admin\AdvertisementController::class, 'toggleStatus'])->name('advertisements.status.toggle');
            Route::get('/advertisements/machine/{machine}', [App\Http\Controllers\Admin\AdvertisementController::class, 'getMachineAds'])->name('advertisements.machine.get');
            Route::post('/advertisements/assign', [App\Http\Controllers\Admin\AdvertisementController::class, 'assign'])->name('advertisements.assign');
            Route::post('/advertisements/assignments/reorder', [App\Http\Controllers\Admin\AdvertisementController::class, 'reorderAssignments'])->name('advertisements.assignments.reorder');
            Route::delete('/advertisements/assignment/{id}', [App\Http\Controllers\Admin\AdvertisementController::class, 'removeAssignment'])->name('advertisements.assignment.remove');
            Route::post('/advertisements/machine/{machine}/sync', [App\Http\Controllers\Admin\AdvertisementController::class, 'syncToMachine'])->name('advertisements.machine.sync');
        });

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
        Route::get('staff-cards/template', [App\Http\Controllers\Admin\StaffCardController::class, 'downloadTemplate'])->name('staff-cards.template');
        Route::post('staff-cards/import', [App\Http\Controllers\Admin\StaffCardController::class, 'import'])->name('staff-cards.import');
        Route::resource('staff-cards', App\Http\Controllers\Admin\StaffCardController::class);
        Route::patch('staff-cards/{staffCard}/toggle-status', [App\Http\Controllers\Admin\StaffCardController::class, 'toggleStatus'])->name('staff-cards.status.toggle');
    });

    // 10. 遠端管理
    Route::prefix('remote')->name('remote.')->group(function () {
        Route::get('/', [App\Http\Controllers\Admin\RemoteController::class, 'index'])->name('index');
        Route::post('/command', [App\Http\Controllers\Admin\RemoteController::class, 'storeCommand'])->name('store-command');
        Route::get('/stock', [App\Http\Controllers\Admin\RemoteController::class, 'stock'])->name('stock');
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
        // APK 版本管理 (OTA)
        Route::prefix('apk-versions')
            ->name('apk-versions.')
            ->middleware('can:menu.basic.apk-versions')
            ->group(function () {
                Route::get('/', [App\Http\Controllers\Admin\BasicSettings\ApkVersionController::class, 'index'])->name('index');
                Route::get('/create', [App\Http\Controllers\Admin\BasicSettings\ApkVersionController::class, 'create'])->name('create');
                Route::post('/', [App\Http\Controllers\Admin\BasicSettings\ApkVersionController::class, 'store'])->name('store');
                Route::delete('/{apkVersion}', [App\Http\Controllers\Admin\BasicSettings\ApkVersionController::class, 'destroy'])->name('destroy');
                Route::post('/{apkVersion}/push', [App\Http\Controllers\Admin\BasicSettings\ApkVersionController::class, 'push'])->name('push');
            });

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

        // 客戶金流設定
        Route::resource('payment-configs', App\Http\Controllers\Admin\BasicSettings\PaymentConfigController::class)->except(['show'])->middleware('can:menu.basic.payment-configs');

        // 機台型號設定
        Route::resource('machine-models', App\Http\Controllers\Admin\BasicSettings\MachineModelController::class)->except(['show']);

        // QR Code 生成
        Route::get('qr-code', [App\Http\Controllers\Admin\QrCodeController::class, 'generate'])->name('qr-code');

        // Discord 告警通知設定
        Route::prefix('discord-notifications')
            ->name('discord-notifications.')
            ->middleware('can:menu.basic.discord-notifications')
            ->group(function () {
                Route::get('/', [App\Http\Controllers\Admin\BasicSettings\DiscordNotificationController::class, 'index'])->name('index');
                Route::put('/company/{id}', [App\Http\Controllers\Admin\BasicSettings\DiscordNotificationController::class, 'updateCompany'])->name('update-company');
                Route::put('/machine/{id}', [App\Http\Controllers\Admin\BasicSettings\DiscordNotificationController::class, 'updateMachine'])->name('update-machine');
                Route::post('/test', [App\Http\Controllers\Admin\BasicSettings\DiscordNotificationController::class, 'test'])->name('test');
            });
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
