<?php

namespace App\Http\Controllers\Api\V1\App;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Machine\Machine;

class MachineController extends Controller
{

    /**
     * B014: Download Machine Settings & Config (Synchronous)
     * 用於機台引導階段，同步發票與機台專屬 API Token。
     */
    public function getSettings(Request $request)
    {
        $serialNo = $request->input('machine');

        // 1. 查找機台 (忽略全局範圍以進行認領)
        $machine = Machine::withoutGlobalScopes()
            ->with(['company'])
            ->where('serial_no', $serialNo)
            ->first();

        if (!$machine) {
            return response()->json([
                'success' => false,
                'code' => 404,
                'message' => __('Machine not found')
            ], 404);
        }

        // 2. 映射 App 預期欄位 (輸出鍵維持 HttpAPI.java 結構)
        // 註：原本這裡還會下發金流配置 (payment_configs / esun_scan / ecpay_invoice / tappay 等)，
        // 該金流配置 (PaymentConfig) 概念已隨販賣機金流模組整支移除，教學版智慧插座不需要。
        $data = [
            't050v01' => $machine->serial_no,
            'api_token' => $machine->api_token, // 向 App 核發正式通訊 Token
        ];

        // 3. 機台系統設定 (machines.settings) → 全部以機台端 *Set + PascalCase 風格下發。
        //    註：機台 App 端目前讀本地設定，需另案修改 App 才會實際消費此處下發值。
        $s = $machine->settings ?? [];

        // 3-1 DevSet：支付旗標（命名沿用機台 DevSetStructure；DevCreditCard/DevMobilePay/
        //     DevCardPay/DevScanPay 為我方新定義，機台端需比照新增欄位）
        $data['DevSet'] = [
            'ShoppingCar'   => (bool) ($s['shopping_cart_enabled']   ?? false), // 購物車
            'Invoice'       => (bool) ($s['tax_invoice_enabled']     ?? false), // 電子發票
            'DevNFCPay'     => (bool) ($s['card_terminal_enabled']   ?? false), // 刷卡機（總開關）
            'DevCreditCard' => (bool) ($s['credit_card_enabled']     ?? false), // 信用卡支付（新定義）
            'DevMobilePay'  => (bool) ($s['mobile_pay_enabled']      ?? false), // 手機支付（新定義）
            'DevCardPay'    => (bool) ($s['card_pay_enabled']        ?? false), // 卡片支付（新定義）
            'DevScanPay'    => (bool) ($s['scan_pay_enabled']        ?? false), // 掃碼（總開關，新定義）
            'DevEsunPay'    => (bool) ($s['scan_pay_esun_enabled']   ?? false), // 玉山掃碼
            'DevTapPay'     => (bool) ($s['scan_pay_tappay_enabled'] ?? false), // TapPay 掃碼
            'DevCash'       => (bool) ($s['cash_module_enabled']     ?? false), // 現金
            'DevLinePay'    => (bool) ($s['scan_pay_linepay_enabled'] ?? false), // LINE Pay 官方直連（Line官方支付，與 TapPay30 不同）
            'TapPay30'      => (bool) ($s['tappay_linepay']          ?? false), // TapPay 底下的 LINE Pay
            'TapPay31'      => (bool) ($s['tappay_jkopay']           ?? false), // TapPay-街口支付
            'TapPay32'      => (bool) ($s['tappay_easywallet']       ?? false), // TapPay-悠遊付
            'TapPay33'      => (bool) ($s['tappay_pipay']            ?? false), // TapPay-Pi 支付
            'TapPay34'      => (bool) ($s['tappay_pluspay']          ?? false), // TapPay-全盈+支付
            // 註：VMC/Electic 為機台硬體類型，雲端無來源，由機台本地保留
        ];

        // 3-2 CashSet：現金面額（對齊機台 CashSetStructure）
        $data['CashSet'] = [
            'BillF1000' => (bool) ($s['cash_bill_1000'] ?? false),
            'BillE500'  => (bool) ($s['cash_bill_500']  ?? false),
            'BillD100'  => (bool) ($s['cash_bill_100']  ?? false),
            'CoinF50'   => (bool) ($s['cash_coin_50']   ?? false),
            'CoinE10'   => (bool) ($s['cash_coin_10']   ?? false),
            'CoinD5'    => (bool) ($s['cash_coin_5']    ?? false),
            'CoinC1'    => (bool) ($s['cash_coin_1']    ?? false),
        ];

        // 3-3 FunctionSet：非支付功能模組旗標（我方新定義，機台端待實作對應結構）
        $data['FunctionSet'] = [
            'PickupModule' => (bool) ($s['pickup_module_enabled']          ?? false), // 取貨模組
            'PickupCode'   => (bool) ($s['pickup_code_enabled']           ?? false), // 取貨碼
            'PassCode'     => (bool) ($s['pass_code_enabled']             ?? false), // 通行碼
            'WelcomeGift'  => (bool) ($s['welcome_gift_enabled']          ?? false), // 來店禮
            'MemberSystem' => (bool) ($s['member_system_enabled']         ?? false), // 會員系統
            'AmbientTemp'  => (bool) ($s['ambient_temp_monitoring_enabled'] ?? false), // 環境溫度監控
            'PharmacyPickup' => (bool) ($s['pharmacy_pickup_enabled']     ?? false), // 領藥單(雲端建單，取物單模式下開關)
            'Subcabinet'   => (bool) ($s['subcabinet_enabled']           ?? false), // 副櫃系統(格子櫃功能，基礎版授權開關)
        ];

        // 3-4 ShoppingMode：購物方式（頂層字串）
        $data['ShoppingMode'] = (string) ($s['shopping_mode'] ?? 'basic'); // basic / employee_card / pickup_sheet

        // 3-4b LangSet：機台顯示語系（後台勾選，最多 N 種）。機台據此渲染語系切換 UI，
        //      Default 為開機/idle 預設語系（清單第一個）。未設定時退化為 fallback 單語。
        $langs = array_values(array_filter((array) ($s['languages'] ?? [])));
        if (empty($langs)) {
            $langs = [config('locales.fallback', 'zh_TW')];
        }
        $data['LangSet'] = [
            'Languages' => $langs,        // 有序清單，順序即切換順序
            'Default'   => $langs[0],     // 預設語系（清單第一個）
        ];

        // 3-5 OperationSet：運作參數（machines 實體欄位）
        $data['OperationSet'] = [
            'CardReaderSeconds'    => (int) ($machine->card_reader_seconds ?? 0),       // 刷卡機秒數
            'PaymentBufferSeconds' => (int) ($machine->payment_buffer_seconds ?? 0),    // 金流緩衝秒數
            'CheckoutTime1'        => (string) ($machine->card_reader_checkout_time_1 ?? ''), // 結帳時間 1
            'CheckoutTime2'        => (string) ($machine->card_reader_checkout_time_2 ?? ''), // 結帳時間 2
            'HeatingStartTime'     => (string) ($machine->heating_start_time ?? ''),    // 加熱開始時間
            'HeatingEndTime'       => (string) ($machine->heating_end_time ?? ''),      // 加熱結束時間
        ];

        // 3-6 HardwareSet：硬體與貨道（machines 實體欄位；SpringSlot* true=彈簧 / false=履帶）
        $data['HardwareSet'] = [
            'CardReaderNo'    => (string) ($machine->card_reader_no ?? ''),  // 刷卡機編號
            'SpringSlot1_10'  => (bool) $machine->is_spring_slot_1_10,
            'SpringSlot11_20' => (bool) $machine->is_spring_slot_11_20,
            'SpringSlot21_30' => (bool) $machine->is_spring_slot_21_30,
            'SpringSlot31_40' => (bool) $machine->is_spring_slot_31_40,
            'SpringSlot41_50' => (bool) $machine->is_spring_slot_41_50,
            'SpringSlot51_60' => (bool) $machine->is_spring_slot_51_60,
        ];

        return response()->json([
            'success' => true,
            'code' => 200,
            'data' => $data
        ]);
    }

    /**
     * B016: Update Machine System Settings (Write-back from machine console)
     * 機台主控台「系統設定」由系統方 (identity=system) 編輯後回寫雲端。
     * 認證：B000 核發的使用者 Token (auth:sanctum)；僅系統管理員可操作。
     * Body: { machine: 序號, settings: { is_spring_slot_*: bool, ... } }
     * 機台端僅回寫硬體貨道類型 (is_spring_slot_*) 實體欄位；其餘支付旗標、
     * 現金面額、功能模組等開關純由後台決定，B016 不接收，未列入者一律忽略。
     */
    public function updateSettings(Request $request)
    {
        // 1. 僅系統方可回寫（雙重把關：App 端隱藏控制 + 此處伺服器驗權）
        $user = $request->user();
        if (!$user || !$user->isSystemAdmin()) {
            return response()->json([
                'success' => false,
                'code' => 403,
                'message' => __('Forbidden')
            ], 403);
        }

        // 2. 查找機台
        $serialNo = $request->input('machine');
        $machine = Machine::withoutGlobalScopes()
            ->where('serial_no', $serialNo)
            ->first();

        if (!$machine) {
            return response()->json([
                'success' => false,
                'code' => 404,
                'message' => __('Machine not found')
            ], 404);
        }

        $settings = $request->input('settings', []);
        if (!is_array($settings)) {
            return response()->json([
                'success' => false,
                'code' => 422,
                'message' => __('Invalid settings payload')
            ], 422);
        }

        // 3. 直接欄位白名單：machines 實體布林欄位（硬體貨道類型）
        // 機台端 B016 僅回寫貨道類型，其餘系統設定由後台單向決定，不在此處接收。
        $columnBoolKeys = [
            'is_spring_slot_1_10', 'is_spring_slot_11_20', 'is_spring_slot_21_30',
            'is_spring_slot_31_40', 'is_spring_slot_41_50', 'is_spring_slot_51_60',
        ];

        // 4. 整理實體欄位更新（僅貨道類型，只覆寫有送來的鍵）
        $columnData = [];
        foreach ($columnBoolKeys as $k) {
            if (array_key_exists($k, $settings)) {
                $columnData[$k] = (bool) $settings[$k];
            }
        }

        if (empty($columnData)) {
            return response()->json([
                'success' => false,
                'code' => 422,
                'message' => __('Invalid settings payload')
            ], 422);
        }

        $machine->update(array_merge($columnData, [
            'updater_id' => $user->id,
        ]));

        \Log::info('B016 Machine settings updated', [
            'machine' => $machine->serial_no,
            'user_id' => $user->id,
            'keys' => array_keys($settings),
        ]);

        return response()->json([
            'success' => true,
            'code' => 200,
            'message' => __('Settings updated successfully.')
        ]);
    }
}
