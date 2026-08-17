<?php

// 載入 Laravel Bootstrap
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Machine\Machine;
use App\Models\Machine\MachineModel;
use App\Models\System\Company;

// 輔助函數：模擬 ProcessHeartbeat 中獲取最終溫度限制的邏輯
function getTempLimits($machine) {
    $mSettings = $machine->settings ?? [];
    $mTempAlert = $mSettings['temp_alert_enabled'] ?? 'inherit';

    // 1. 機台個別設定
    $mUpper = $mSettings['temp_upper_limit'] ?? null;
    $mLower = $mSettings['temp_lower_limit'] ?? null;

    // 2. 型號設定
    $modelSettings = $machine->machineModel->settings ?? [];
    $modelUpper = $modelSettings['temp_upper_limit'] ?? null;
    $modelLower = $modelSettings['temp_lower_limit'] ?? null;

    // 3. 系統安全預設
    $sysUpper = 40.0;
    $sysLower = 0.0;

    $finalUpper = $sysUpper;
    $finalLower = $sysLower;
    $source = 'System Default';
    $isEnabled = true;

    if ($mTempAlert === 'disabled') {
        $isEnabled = false;
        $source = 'Muted';
    } elseif ($mTempAlert === 'enabled' && ($mUpper !== null || $mLower !== null)) {
        $finalUpper = $mUpper !== null ? $mUpper : $sysUpper;
        $finalLower = $mLower !== null ? $mLower : $sysLower;
        $source = 'Custom Machine Settings';
    } elseif ($modelUpper !== null || $modelLower !== null) {
        $finalUpper = $modelUpper !== null ? $modelUpper : $sysUpper;
        $finalLower = $modelLower !== null ? $modelLower : $sysLower;
        $source = 'Model Settings';
    } else {
        $source = 'System Default (No custom/model limits)';
    }

    return [
        'enabled' => $isEnabled,
        'upper' => $finalUpper,
        'lower' => $finalLower,
        'source' => $source
    ];
}

echo "🧪 溫度告警「三層繼承鏈」邏輯單元測試\n\n";

// 準備資料
$oldMachine = Machine::where('serial_no', 'T1001')->first();
if ($oldMachine) {
    $oldMachine->delete();
}

$oldModel = MachineModel::where('name', '溫控測試型號')->first();
if ($oldModel) {
    $oldModel->delete();
}

$oldCompany = Company::withTrashed()->where('code', 'TEMP_CHAIN_COMP')->first();
if ($oldCompany) {
    $oldCompany->forceDelete();
}

$company = new Company();
$company->code = 'TEMP_CHAIN_COMP';
$company->name = '溫控繼承公司';
$company->save();

$model = new MachineModel();
$model->name = '溫控測試型號';
$model->company_id = $company->id;
$model->save();

$machine = new Machine();
$machine->serial_no = 'T1001';
$machine->name = '溫控繼承機台';
$machine->company_id = $company->id;
$machine->machine_model_id = $model->id;
$machine->settings = null; // 初始化空
$machine->save();

// --- 測試 1: 預設狀態 (機台 inherit，型號無設定) ---
$res = getTempLimits($machine);
echo "1. 預設狀態測試 (機台: inherit, 型號: 無設定)\n";
echo "   -> 告警狀態: " . ($res['enabled'] ? '✅ 啟用' : '❌ 禁用') . "\n";
echo "   -> 溫度限制: {$res['lower']}°C ~ {$res['upper']}°C\n";
echo "   -> 設定來源: {$res['source']}\n";
assert($res['enabled'] === true, '預設應啟用');
assert($res['upper'] === 40.0, '上限應為 40');
assert($res['lower'] === 0.0, '下限應為 0');
echo "   🎉 測試 1 通過！\n\n";

// --- 測試 2: 型號有設定 (機台 inherit，型號設定 5°C ~ 35°C) ---
$model->settings = [
    'temp_upper_limit' => 35.0,
    'temp_lower_limit' => 5.0
];
$model->save();

// 重新載入關係
$machine->load('machineModel');
$res = getTempLimits($machine);
echo "2. 型號有設定測試 (機台: inherit, 型號: 5°C ~ 35°C)\n";
echo "   -> 告警狀態: " . ($res['enabled'] ? '✅ 啟用' : '❌ 禁用') . "\n";
echo "   -> 溫度限制: {$res['lower']}°C ~ {$res['upper']}°C\n";
echo "   -> 設定來源: {$res['source']}\n";
assert($res['enabled'] === true, '應啟用');
assert($res['upper'] === 35.0, '上限應為 35.0');
assert($res['lower'] === 5.0, '下限應為 5.0');
echo "   🎉 測試 2 通過！\n\n";

// --- 測試 3: 機台自訂設定 (機台: enabled 且設為 10°C ~ 30°C) ---
$machine->settings = [
    'temp_alert_enabled' => 'enabled',
    'temp_upper_limit' => 30.0,
    'temp_lower_limit' => 10.0
];
$machine->save();

$res = getTempLimits($machine);
echo "3. 機台自訂設定測試 (機台: enabled 10°C ~ 30°C, 型號: 5°C ~ 35°C)\n";
echo "   -> 告警狀態: " . ($res['enabled'] ? '✅ 啟用' : '❌ 禁用') . "\n";
echo "   -> 溫度限制: {$res['lower']}°C ~ {$res['upper']}°C\n";
echo "   -> 設定來源: {$res['source']}\n";
assert($res['enabled'] === true, '應啟用');
assert($res['upper'] === 30.0, '上限應為 30.0');
assert($res['lower'] === 10.0, '下限應為 10.0');
echo "   🎉 測試 3 通過！\n\n";

// --- 測試 4: 機台禁用告警 (機台: disabled) ---
$machine->settings = [
    'temp_alert_enabled' => 'disabled',
    'temp_upper_limit' => 30.0, // 即使有數字也應禁用
    'temp_lower_limit' => 10.0
];
$machine->save();

$res = getTempLimits($machine);
echo "4. 機台禁用告警測試 (機台: disabled)\n";
echo "   -> 告警狀態: " . ($res['enabled'] ? '✅ 啟用' : '❌ 禁用') . "\n";
echo "   -> 設定來源: {$res['source']}\n";
assert($res['enabled'] === false, '應禁用');
echo "   🎉 測試 4 通過！\n\n";

// 清理測試數據
$machine->delete();
$model->delete();
$company->forceDelete();

echo "🏆 所有「三層繼承鏈」測試圓滿成功！\n";
