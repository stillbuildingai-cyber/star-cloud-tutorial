<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

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
    });

    /*
    |--------------------------------------------------------------------------
    | 專屬 App API Routes (Customer)
    |--------------------------------------------------------------------------
    | 給終端使用者的手機 App 用，跟上面 app/* 那組「機台本機認證」用途不同。
    */
    Route::prefix('app/customer')->group(function () {
        Route::post('login', [\App\Http\Controllers\Api\V1\App\CustomerAuthController::class, 'login'])->middleware('throttle:10,1');

        Route::middleware(['auth:sanctum', 'throttle:100,1'])->group(function () {
            Route::post('logout', [\App\Http\Controllers\Api\V1\App\CustomerAuthController::class, 'logout']);

            Route::get('machines', [\App\Http\Controllers\Api\V1\App\CustomerMachineController::class, 'index']);
            Route::get('machines/{machine}', [\App\Http\Controllers\Api\V1\App\CustomerMachineController::class, 'show']);
            Route::post('machines/{machine}/commands', [\App\Http\Controllers\Api\V1\App\CustomerMachineController::class, 'storeCommand']);

            Route::get('machines/{machine}/schedules', [\App\Http\Controllers\Api\V1\App\CustomerScheduleController::class, 'index']);
            Route::post('machines/{machine}/schedules', [\App\Http\Controllers\Api\V1\App\CustomerScheduleController::class, 'store']);
            Route::patch('machines/{machine}/schedules/{schedule}/toggle', [\App\Http\Controllers\Api\V1\App\CustomerScheduleController::class, 'toggle']);
            Route::delete('machines/{machine}/schedules/{schedule}', [\App\Http\Controllers\Api\V1\App\CustomerScheduleController::class, 'destroy']);
        });
    });

});

use App\Http\Controllers\MachineController;
Route::post("/v1/telemetry", [MachineController::class, "receiveTelemetry"]);
