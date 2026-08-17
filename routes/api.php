<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\MemberController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| 這裡註冊所有的 API 路由，預設套用 api middleware group。
| 加入 v1 前綴與 throttle 進行速率限制防護。
|
*/

Route::prefix('v1')->middleware(['throttle:api'])->group(function () {
    
    // 基本的使用者資料查詢
    Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
        return $request->user();
    });

    /*
    |--------------------------------------------------------------------------
    | 會員 API Routes
    |--------------------------------------------------------------------------
    */
    
    // 公開路由（無需認證）
    Route::prefix('members')->group(function () {
        Route::post('/register', [MemberController::class, 'register']);
        Route::post('/login', [MemberController::class, 'login']);
        Route::post('/social-login', [MemberController::class, 'socialLogin']);
    });

    // 需認證路由
    Route::prefix('members')->middleware('auth:sanctum')->group(function () {
        Route::get('/profile', [MemberController::class, 'profile']);
        Route::put('/profile', [MemberController::class, 'updateProfile']);
        Route::post('/logout', [MemberController::class, 'logout']);
    });

    /*
    |--------------------------------------------------------------------------
    | 機台 API Routes (IoT)
    |--------------------------------------------------------------------------
    | 專門用於機台通訊，頻率較高，建議搭配異步處理。
    */

    // 機台啟動引導與參數下載 (公開存取，僅需機台序號，供機台初始化時獲取 API Token)
    Route::prefix('app')->group(function () {
        Route::get('machine/setting/B014', [App\Http\Controllers\Api\V1\App\MachineController::class, 'getSettings']);
    });

    // 機台端回寫系統設定 (B016)：僅系統方 (identity=system) 可操作，認證使用 B000 核發的使用者 Token
    Route::prefix('app')->middleware(['auth:sanctum', 'throttle:60,1'])->group(function () {
        Route::patch('machine/setting/B016', [App\Http\Controllers\Api\V1\App\MachineController::class, 'updateSettings']);
    });

    Route::prefix('app')->middleware(['iot.auth', 'throttle:100,1'])->group(function () {
        // 機台管理員 B000 登入驗證 (需帶機台 API Token)
        Route::post('admin/login/B000', [\App\Http\Controllers\Api\V1\App\MachineAuthController::class, 'loginB000'])->middleware('throttle:30,1');

        // 機台狀態與相關同步 (B017, B710, B220)

        Route::get('machine/reload_msg/B017', [App\Http\Controllers\Api\V1\App\MachineController::class, 'getSlots']);
        Route::post('machine/coins/B220', [App\Http\Controllers\Api\V1\App\MachineController::class, 'syncCoinInventory']);
        Route::post('machine/member/verify/B650', [App\Http\Controllers\Api\V1\App\MachineController::class, 'verifyMember']);
        
        // 取貨與通行驗證
        Route::post('machine/pickup/verify/B660', [App\Http\Controllers\Api\V1\App\MachineController::class, 'verifyPickupCode']);
        Route::post('machine/passcode/verify/B670', [App\Http\Controllers\Api\V1\App\MachineController::class, 'verifyPassCode']);
        Route::post('machine/staff/verify/B680', [App\Http\Controllers\Api\V1\App\MachineController::class, 'verifyStaffCard']);
        Route::post('machine/welcome-gift/verify/B690', [App\Http\Controllers\Api\V1\App\MachineController::class, 'verifyWelcomeGift']);

        // 廣告與貨道清單 (B005, B009, B012)
        Route::get('machine/ad/B005', [App\Http\Controllers\Api\V1\App\MachineController::class, 'getAdvertisements']);
        Route::put('products/supplementary/B009', [App\Http\Controllers\Api\V1\App\MachineController::class, 'reportSlotList']);
        
        // 統一商品主檔 API (B012 整合版)
        Route::get('machine/products/B012', [App\Http\Controllers\Api\V1\App\MachineController::class, 'getProducts']);


        // 遠端指令出貨控制 (B055) — PUT 回報已遷移至 MQTT command/ack Topic
        Route::get('machine/dispense/B055', [App\Http\Controllers\Api\V1\App\MachineController::class, 'getDispenseQueue']);

        // 交易 (B600) — B601/B602 已遷移至 MQTT transaction Topic
        Route::post('machine/restock/B018', [App\Http\Controllers\Api\V1\App\MachineController::class, 'recordRestock']);
    });

});

use App\Http\Controllers\MachineController;
Route::post("/v1/telemetry", [MachineController::class, "receiveTelemetry"]);
