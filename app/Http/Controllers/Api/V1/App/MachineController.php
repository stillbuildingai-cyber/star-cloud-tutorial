<?php

namespace App\Http\Controllers\Api\V1\App;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Machine\Machine;
use App\Models\System\User;
use App\Jobs\Machine\ProcessHeartbeat;
use App\Jobs\Machine\ProcessCoinInventory;
use App\Jobs\Machine\ProcessMachineError;
use App\Jobs\Machine\ProcessStateLog;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Cache;
use App\Models\Machine\MachineStockMovement;
use App\Models\Transaction\PickupCode;
use App\Models\Transaction\PickupCodeLog;
use App\Models\Transaction\PassCode;
use App\Models\StaffCard;
use App\Models\StaffCardLog;
use App\Models\Transaction\PassCodeLog;
use App\Models\Transaction\WelcomeGift;
use App\Models\Transaction\WelcomeGiftLog;
use App\Models\System\SystemOperationLog;
use App\Services\Machine\MachineService;
use App\Services\Product\ProductCatalogService;

class MachineController extends Controller
{

    /**
     * B055: Get Remote Dispense Queue (POST/GET)
     * 用於獲取雲端下發的遠端出貨指令詳情。
     */
    public function getDispenseQueue(Request $request)
    {
        $machine = $request->get('machine');

        // 查找該機台狀態為「已發送 (sent)」且類型為「出貨 (dispense)」的最新指令
        $command = \App\Models\Machine\RemoteCommand::where('machine_id', $machine->id)
            ->where('command_type', 'dispense')
            ->where('status', 'sent')
            ->latest()
            ->first();

        if (!$command) {
            return response()->json([
                'success' => true,
                'code' => 200,
                'data' => []
            ]);
        }

        // 映射 Java APP 預期格式: res1 = ID, res2 = SlotNo
        return response()->json([
            'success' => true,
            'code' => 200,
            'data' => [
                [
                    'res1' => (string) $command->id,
                    'res2' => (string) ($command->payload['slot_no'] ?? ''),
                ]
            ]
        ]);
    }

    /**
     * B055: Report Remote Dispense Result (PUT)
     * 用於機台回報遠端出貨執行的最終結果。
     */
    public function reportDispenseResult(Request $request, \App\Services\Machine\MachineService $machineService)
    {
        $commandId = $request->input('id');
        $type = $request->input('type'); // 通常為 0
        $stockReported = $request->input('stock'); // 機台回報的剩餘庫存

        $command = \App\Models\Machine\RemoteCommand::find($commandId);

        if (!$command) {
            return response()->json([
                'success' => false,
                'code' => 404,
                'message' => __('Command not found')
            ], 404);
        }

        // 更新指令狀態 (0 代表成功)
        $status = ($type == '0') ? 'success' : 'failed';

        $payloadUpdates = [
            'reported_stock' => $stockReported,
            'report_type' => $type
        ];

        // 若成功，連動更新貨道庫存
        if ($status === 'success' && isset($command->payload['slot_no'])) {
            $machine = $command->machine;
            $slotNo = $command->payload['slot_no'];

            $slot = $machine->slots()->where('slot_no', $slotNo)->first();
            if ($slot) {
                $oldStock = $slot->stock;
                // 若 APP 回傳的庫存大於 0 則使用回傳值，否則執行扣減
                $newStock = (int) ($stockReported ?? 0);
                if ($newStock <= 0 && $oldStock > 0) {
                    $newStock = $oldStock - 1;
                }

                $finalStock = max(0, $newStock);
                $slot->update(['stock' => $finalStock]);

                // 紀錄庫存變化供前端顯示
                $payloadUpdates['old_stock'] = $oldStock;
                $payloadUpdates['new_stock'] = $finalStock;

                ProcessStateLog::dispatch(
                    $machine->id,
                    $machine->company_id,
                    "Remote dispense successful for slot :slot",
                    'info',
                    ['slot' => $slotNo]
                );
            }
        }

        $command->update([
            'status' => $status,
            'executed_at' => now(),
            'payload' => array_merge($command->payload, $payloadUpdates)
        ]);

        return response()->json([
            'success' => true,
            'code' => 200,
            'message' => __('Result reported')
        ]);
    }

    /**
     * B018: Record Machine Restock/Setup Report (Asynchronous)
     */
    public function recordRestock(Request $request)
    {
        $machine = $request->get('machine');
        $data = $request->except(['machine', 'key']); // 排除 Middleware 注入物件
        $data['serial_no'] = $machine->serial_no;

        \App\Jobs\Machine\ProcessRestockReport::dispatch($data);

        return response()->json([
            'success' => true,
            'code' => 200,
            'message' => __('Restock report accepted'),
            'status' => '49'
        ], 202);
    }

    /**
     * B017: Get Slot Info & Stock (Synchronous)
     */
    /**
     * B017: Get Slot Info & Stock (Synchronous - Full Sync)
     */
    public function getSlots(Request $request)
    {
        $machine = $request->get('machine');

        // 依貨道編號排序 (Sorted by slot_no as requested)
        $slots = $machine->slots()->with('product')->orderBy('slot_no')->get();

        // 自動轉 Success: 若機台來撈 B017，代表之前的 reload_stock 指令已成功被機台響應
        // 同時處理 sent 與 pending 狀態，確保狀態機正確關閉
        \App\Models\Machine\RemoteCommand::where('machine_id', $machine->id)
            ->where('command_type', 'reload_stock')
            ->whereIn('status', ['pending', 'sent'])
            ->update([
                'status' => 'success',
                'executed_at' => now(),
                'note' => __('Inventory synced with machine')
            ]);

        return response()->json([
            'success' => true,
            'code' => 200,
            'data' => $slots->map(function ($slot) {
                return [
                    'tid' => $slot->slot_no,
                    'num' => (int) $slot->stock,
                    'expiry_date' => $slot->expiry_date ? $slot->expiry_date->format('Y-m-d') : null,
                    'batch_no' => $slot->batch_no,
                    // 保留原始欄位以供除錯或未來擴充
                    'product_id' => $slot->product_id,
                    'capacity' => $slot->max_stock,
                    'status' => $slot->is_active ? '1' : '0',
                ];
            })
        ]);
    }



    /**
     * B220: Sync Coin Inventory (Asynchronous)
     */
    public function syncCoinInventory(Request $request)
    {
        $machine = $request->get('machine');
        $data = $request->except(['machine', 'key']); // 排除 Middleware 注入物件

        ProcessCoinInventory::dispatch($machine->serial_no, $data);

        return response()->json(['success' => true], 202);
    }

    /**
     * B650: Verify Member Code/Barcode (Synchronous)
     */
    public function verifyMember(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'code' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Invalid code'], 400);
        }

        $code = $request->input('code');

        // 搜尋會員 (barcode 或特定驗證碼)
        $member = \App\Models\Member\Member::where('barcode', $code)
            ->orWhere('id', $code) // 暫時支援 ID
            ->first();

        if (!$member) {
            return response()->json([
                'success' => false,
                'code' => 404,
                'message' => 'Member not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'code' => 200,
            'data' => [
                'member_id' => $member->id,
                'name' => $member->name,
                'points' => $member->points,
                'wallet_balance' => $member->wallet_balance ?? 0,
            ]
        ]);
    }

    /**
     * B005: Download Machine Advertisements (Synchronous)
     */
    public function getAdvertisements(Request $request)
    {
        $machine = $request->get('machine');

        $advertisements = \App\Models\Machine\MachineAdvertisement::where('machine_id', $machine->id)
            ->with([
                'advertisement' => function ($query) {
                    $query->playing();
                }
            ])
            ->get()
            ->filter(fn($ma) => $ma->advertisement !== null)
            ->map(function ($ma) {
                // 定義顯示順序權重 (待機 > 購物 > 成功禮)
                $posWeight = [
                    'standby' => 1,
                    'vending' => 2,
                    'visit_gift' => 3
                ];

                // 為了相容現有機台 App 邏輯：
                // App 讀取 t070v03 作為位置標籤 (flag)：
                // 1. HomeActivity (待機) 讀取 "3"
                // 2. FontendActivity (販賣頁) 讀取 "1"
                $posIdMap = [
                    'standby' => '3',
                    'vending' => '1',
                    'visit_gift' => '2'
                ];

                return [
                    't070v01' => $ma->advertisement->name,
                    't070v02' => (int) ($ma->advertisement->duration ?? 15),
                    't070v03' => (int) ($posIdMap[$ma->position] ?? 1),
                    't070v04' => $ma->advertisement->url ? (str_starts_with($ma->advertisement->url, 'http') ? $ma->advertisement->url : asset($ma->advertisement->url)) : '',
                    't070v05' => (int) $ma->sort_order,
                    'raw_pos_weight' => $posWeight[$ma->position] ?? 99,
                    'raw_sort' => (int) $ma->sort_order
                ];
            })
            ->sortBy([
                ['raw_pos_weight', 'asc'],
                ['raw_sort', 'asc']
            ])
            ->values()
            ->map(function ($item) {
                unset($item['raw_pos_weight'], $item['raw_sort']);
                return $item;
            });

        return response()->json([
            'success' => true,
            'code' => 200,
            'data' => $advertisements->values()
        ]);
    }

    /**
     * B009: Report Machine Slot List / Supplementary (Synchronous)
     */
    public function reportSlotList(Request $request, \App\Services\Machine\MachineService $machineService)
    {
        $machine = $request->get('machine');
        $payload = $request->all();
        $account = $payload['account'] ?? null;

        // 1. 驗證帳號是否存在 (驗證執行補貨的人員身分)
        $user = User::where('username', $account)
            ->orWhere('email', $account)
            ->first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'code' => 403,
                'message' => __('Unauthorized: Account not found'),
                'status' => ''
            ], 403);
        }

        // 2. 階層式權限驗證 (遵循 RBAC 多租戶規範)
        $isAuthorized = false;
        if ($user->isSystemAdmin()) {
            // [系統層]：系統管理員可異動所有機台
            $isAuthorized = true;
        } elseif ($user->is_admin) {
            // [公司層]：公司管理員需驗證此機台是否隸屬於該公司
            $isAuthorized = ($machine->company_id === $user->company_id);
        } else {
            // [人員層]：一般人員需檢查 machine_user 授權表
            $isAuthorized = $user->machines()->where('machine_id', $machine->id)->exists();
        }

        if (!$isAuthorized) {
            return response()->json([
                'success' => false,
                'code' => 403,
                'message' => __('Unauthorized: Account not authorized for this machine'),
                'status' => ''
            ], 403);
        }

        // 3. 映射舊版機台回傳格式 (Map legacy machine format)
        // 支持單個物件 data: {} 或 陣列 data: [{}] (Handle both single object and array)
        $legacyData = $payload['data'] ?? [];
        if (Arr::isAssoc($legacyData)) {
            $legacyData = [$legacyData];
        }

        $mappedSlots = array_map(function ($item) {
            return [
                'slot_no' => isset($item['tid']) ? (string) $item['tid'] : null,
                'product_id' => $item['t060v00'] ?? null,
                'stock' => isset($item['num']) ? (int) $item['num'] : 0,
                'type' => isset($item['type']) ? (int) $item['type'] : null,
                // 雙向 LWW：鎖定與效期/批號 + 各自的編輯序號（App 端 store 編輯才遞增）
                'is_locked' => isset($item['is_locked']) ? (bool) $item['is_locked'] : false,
                'lock_rev' => isset($item['lock_rev']) ? (int) $item['lock_rev'] : 0,
                'expiry_date' => $item['expiry_date'] ?? null,
                'batch_no' => $item['batch_no'] ?? null,
                'expiry_rev' => isset($item['expiry_rev']) ? (int) $item['expiry_rev'] : 0,
            ];
        }, $legacyData);

        // 過濾無效資料 (Filter invalid entries)
        $mappedSlots = array_filter($mappedSlots, fn($s) => $s['slot_no'] !== null);

        // 同步處理更新庫存 (直接更新不進隊列)
        $machineService->syncSlots($machine, $mappedSlots);

        return response()->json([
            'success' => true,
            'code' => 200,
            'message' => __('Slot report synchronized success'),
        ]);
    }

    /**
     * B012_new: Download Product Catalog (Synchronous)
     */
    public function getProducts(Request $request, ProductCatalogService $catalogService)
    {
        $machine = $request->get('machine');

        // 公司基底目錄（快取） + 該機台專屬定價覆蓋（即時套用，見 getMachinePayload）
        $payload = $catalogService->getMachinePayload($machine);

        return response()->json($payload);
    }


    /**
     * B014: Download Machine Settings & Config (Synchronous)
     * 用於機台引導階段，同步金流、發票與機台專屬 API Token。
     */
    public function getSettings(Request $request)
    {
        $serialNo = $request->input('machine');

        // 1. 查找機台 (忽略全局範圍以進行認領)
        $machine = Machine::withoutGlobalScopes()
            ->with(['paymentConfig', 'company'])
            ->where('serial_no', $serialNo)
            ->first();

        if (!$machine) {
            return response()->json([
                'success' => false,
                'code' => 404,
                'message' => __('Machine not found')
            ], 404);
        }

        // 2. 獲取金流配置 (依據使用者要求，跳過權限校驗，只要有序號即可獲取)
        //    來源統一為 payment_configs.settings 的巢狀結構 (對齊「編輯金流配置」表單)
        $paymentSettings = $machine->paymentConfig->settings ?? [];

        // 4. 映射 App 預期欄位 (輸出鍵維持 HttpAPI.java 結構，僅改值來源為巢狀)
        $data = [
            't050v01' => $machine->serial_no,
            'api_token' => $machine->api_token, // 向 App 核發正式通訊 Token

            // 玉山掃碼 (esun_scan)
            't050v41' => data_get($paymentSettings, 'esun_scan.store_id') ?? '',
            't050v42' => data_get($paymentSettings, 'esun_scan.term_id') ?? '',
            't050v43' => data_get($paymentSettings, 'esun_scan.key') ?? '',

            // 綠界電子發票 (ecpay_invoice) — 改讀金流配置，不再讀 company.settings
            't050v34' => data_get($paymentSettings, 'ecpay_invoice.store_id') ?? '',
            't050v35' => data_get($paymentSettings, 'ecpay_invoice.hash_key') ?? '',
            't050v36' => data_get($paymentSettings, 'ecpay_invoice.hash_iv') ?? '',
            't050v38' => data_get($paymentSettings, 'ecpay_invoice.email') ?? '',

            // TapPay / 趨勢支付 (tappay)
            'TP_APP_ID' => data_get($paymentSettings, 'tappay.app_id') ?? '',
            'TP_APP_KEY' => data_get($paymentSettings, 'tappay.app_key') ?? '',
            'TP_PARTNER_KEY' => data_get($paymentSettings, 'tappay.partner_key') ?? '',

            // 各類行動支付特店 ID (tappay.*_merchant_id)
            'TP_LINE_MERCHANT_ID' => data_get($paymentSettings, 'tappay.line_merchant_id') ?? '',
            'TP_PS_MERCHANT_ID' => data_get($paymentSettings, 'tappay.ps_merchant_id') ?? '',
            'TP_EASY_MERCHANT_ID' => data_get($paymentSettings, 'tappay.easy_merchant_id') ?? '',
            'TP_PI_MERCHANT_ID' => data_get($paymentSettings, 'tappay.pi_merchant_id') ?? '',
            'TP_JKO_MERCHANT_ID' => data_get($paymentSettings, 'tappay.jko_merchant_id') ?? '',
        ];

        // 5. 機台系統設定 (machines.settings) → 全部以機台端 *Set + PascalCase 風格下發。
        //    註：機台 App 端目前讀本地設定，需另案修改 App 才會實際消費此處下發值。
        $s = $machine->settings ?? [];

        // 5-1 DevSet：支付旗標（命名沿用機台 DevSetStructure；DevCreditCard/DevMobilePay/
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

        // 5-2 CashSet：現金面額（對齊機台 CashSetStructure）
        $data['CashSet'] = [
            'BillF1000' => (bool) ($s['cash_bill_1000'] ?? false),
            'BillE500'  => (bool) ($s['cash_bill_500']  ?? false),
            'BillD100'  => (bool) ($s['cash_bill_100']  ?? false),
            'CoinF50'   => (bool) ($s['cash_coin_50']   ?? false),
            'CoinE10'   => (bool) ($s['cash_coin_10']   ?? false),
            'CoinD5'    => (bool) ($s['cash_coin_5']    ?? false),
            'CoinC1'    => (bool) ($s['cash_coin_1']    ?? false),
        ];

        // 5-3 FunctionSet：非支付功能模組旗標（我方新定義，機台端待實作對應結構）
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

        // 5-4 ShoppingMode：購物方式（頂層字串）
        $data['ShoppingMode'] = (string) ($s['shopping_mode'] ?? 'basic'); // basic / employee_card / pickup_sheet

        // 5-4b LangSet：機台顯示語系（後台勾選，最多 N 種）。機台據此渲染語系切換 UI，
        //      Default 為開機/idle 預設語系（清單第一個）。未設定時退化為 fallback 單語。
        $langs = array_values(array_filter((array) ($s['languages'] ?? [])));
        if (empty($langs)) {
            $langs = [config('locales.fallback', 'zh_TW')];
        }
        $data['LangSet'] = [
            'Languages' => $langs,        // 有序清單，順序即切換順序
            'Default'   => $langs[0],     // 預設語系（清單第一個）
        ];

        // 5-5 OperationSet：運作參數（machines 實體欄位）
        $data['OperationSet'] = [
            'CardReaderSeconds'    => (int) ($machine->card_reader_seconds ?? 0),       // 刷卡機秒數
            'PaymentBufferSeconds' => (int) ($machine->payment_buffer_seconds ?? 0),    // 金流緩衝秒數
            'CheckoutTime1'        => (string) ($machine->card_reader_checkout_time_1 ?? ''), // 結帳時間 1
            'CheckoutTime2'        => (string) ($machine->card_reader_checkout_time_2 ?? ''), // 結帳時間 2
            'HeatingStartTime'     => (string) ($machine->heating_start_time ?? ''),    // 加熱開始時間
            'HeatingEndTime'       => (string) ($machine->heating_end_time ?? ''),      // 加熱結束時間
        ];

        // 5-6 HardwareSet：硬體與貨道（machines 實體欄位；SpringSlot* true=彈簧 / false=履帶）
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

    /**
     * B660: Verify/Consume Pickup Code
     * POST: 驗證 8 位數取貨碼 (與原邏輯一致，但加入日誌)
     * PUT: 消耗取貨碼 (扣庫存)
     */
    public function verifyPickupCode(Request $request)
    {
        return $this->handlePickupVerify($request, $request->get('machine'));
    }

    private function handlePickupVerify(Request $request, $machine)
    {
        $lockoutKey = "pickup_lockout:{$machine->id}";
        if (Cache::has($lockoutKey)) {
            return response()->json(['success' => false, 'message' => 'Too many failed attempts.', 'code' => 429], 429);
        }

        $validator = Validator::make($request->all(), ['code' => 'required|string|size:8']);
        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Invalid format', 'code' => 400], 400);
        }

        $code = $request->input('code');
        $pickupCode = PickupCode::where('machine_id', $machine->id)
            ->where('code', $code)
            ->where('status', 'active')
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->first();

        if (!$pickupCode) {
            $fails = Cache::increment("pickup_fails:{$machine->id}");
            if ($fails === 1)
                Cache::put("pickup_fails:{$machine->id}", 1, 600);
            if ($fails >= 5) {
                Cache::put($lockoutKey, true, 60);
                Cache::forget("pickup_fails:{$machine->id}");
            }

            ProcessStateLog::dispatch($machine->id, $machine->company_id, "[PickupCode] Verification failed: :code", 'info', ['code' => $code]);

            return response()->json(['success' => false, 'message' => 'Invalid code', 'code' => 404, 'remaining_attempts' => 5 - $fails], 404);
        }

        Cache::forget("pickup_fails:{$machine->id}");

        // 建立取貨碼專屬日誌 (驗證成功)
        PickupCodeLog::create([
            'company_id' => $machine->company_id,
            'machine_id' => $machine->id,
            'pickup_code_id' => $pickupCode->id,
            'action' => 'verify_success',
            'remark' => "log.pickup.verify_success",
            'raw_data' => [
                'machine_sn' => $machine->serial_no,
                'code' => $pickupCode->code,
                'slot' => $pickupCode->slot_no
            ]
        ]);

        // 移除 StaffCardLog 重複紀錄，因為取貨碼本身有 status 和 order_id 可以追蹤
        // 一般取貨碼的「核銷」仍由 MQTT finalizeTransaction 流程處理（行為不變）。

        $data = [
            'slot_no' => $pickupCode->slot_no,        // 保留：既有綁貨道碼讀此欄（綁商品碼/領藥單為 null）
            'product_id' => $pickupCode->product_id,  // 綁商品碼：App 據此自挑可出貨道（綁貨道碼為 null）
            'pickup_code_id' => $pickupCode->id,
            'code_id' => $pickupCode->id,             // 統一回傳 code_id
            'status' => $pickupCode->status,
        ];

        // === 領藥單(pharmacy_pickup)擴充：回傳多貨道清單 + 標記碼已領（僅領藥單，不影響既有單貨道流程） ===
        $order = $pickupCode->order;
        $isPharmacy = $order && $order->order_type === \App\Models\Transaction\Order::TYPE_PHARMACY_PICKUP;

        if ($isPharmacy) {
            // Gate：機台須仍為「取物單模式 + 領藥單開關開啟」才放行領藥碼
            $settings = $machine->settings ?? [];
            $enabled = (($settings['shopping_mode'] ?? null) === 'pickup_sheet')
                && (bool) ($settings['pharmacy_pickup_enabled'] ?? false);

            if (!$enabled) {
                return response()->json(['success' => false, 'message' => 'Pharmacy pickup not enabled on this machine', 'code' => 403], 403);
            }

            $pharmacyService = app(\App\Services\Transaction\PharmacyPickupService::class);

            // 嚴格擋關：任一商品的貨道可出量 < 領藥單需求量 → 整單擋下、不出貨、不消耗領藥碼，回明確提示
            $shortage = $pharmacyService->dispenseShortage($order);
            if (!empty($shortage)) {
                $detail = collect($shortage)
                    ->map(fn ($s) => "{$s['product_name']}（需 {$s['required']}、可出 {$s['available']}）")
                    ->implode('、');

                PickupCodeLog::create([
                    'company_id' => $machine->company_id,
                    'machine_id' => $machine->id,
                    'pickup_code_id' => $pickupCode->id,
                    'order_id' => $order->id,
                    'action' => 'verify_failed',
                    'remark' => 'log.pickup.pharmacy_insufficient_stock',
                    'raw_data' => ['order_no' => $order->order_no, 'shortage' => $shortage],
                ]);

                return response()->json([
                    'success' => false,
                    'code' => 409,
                    'message' => '因貨道庫存不足，無法出貨：' . $detail,
                ], 409);
            }

            // 依目前貨道庫存解析多貨道出貨清單（一商品可跨多貨道湊量）
            $items = $pharmacyService->resolveDispenseItems($order);

            // 解析不到可出貨貨道（機台無對應貨道/庫存不足）→ 不消耗領藥碼，回明確錯誤讓藥師處理
            if (empty($items)) {
                PickupCodeLog::create([
                    'company_id' => $machine->company_id,
                    'machine_id' => $machine->id,
                    'pickup_code_id' => $pickupCode->id,
                    'order_id' => $order->id,
                    'action' => 'verify_failed',
                    'remark' => 'log.pickup.pharmacy_no_slot',
                    'raw_data' => ['order_no' => $order->order_no, 'reason' => 'no available slot/stock'],
                ]);
                return response()->json([
                    'success' => false,
                    'code' => 409,
                    'message' => '此領藥單在本機台無可出貨貨道（請確認貨道對應與庫存）',
                ], 409);
            }

            // 不在「驗證/掃碼」當下核銷領藥碼。核銷統一改由機台實際出貨後的
            // MQTT finalize（TransactionService::finalizePharmacyDispense）標記為 used，
            // 這樣顧客掃碼後若未按「確定取貨」（逾時/取消、未出貨），領藥碼仍維持 active 可再次掃碼領取。
            // （原本在此處 usage_count++/轉 used 會在尚未取貨時就把碼鎖死，導致再掃顯示「已領」。）

            $data['order_type'] = \App\Models\Transaction\Order::TYPE_PHARMACY_PICKUP;
            $data['order_no'] = $order->order_no;
            $data['pricing_slip_no'] = $order->pricing_slip_no ?? ''; // 批價單號（藥師批價依據）；機台「確認領藥」畫面備註欄顯示
            $data['items'] = $items;          // [{slot_no, product_id, product_name, qty}, ...]
            $data['status'] = $pickupCode->status;
        }

        // === 綁商品取貨碼（非領藥單）擋關：機台須仍有此商品貨道，否則 App 必然挑不到貨道 ===
        // 即時庫存/鎖定/效期由 App 刷碼當下自行判斷；此處只擋「機台根本沒上這商品」的明確錯誤。
        if (!$isPharmacy && $pickupCode->product_id) {
            $hasProduct = $machine->slots()->where('product_id', $pickupCode->product_id)->exists();
            if (!$hasProduct) {
                PickupCodeLog::create([
                    'company_id' => $machine->company_id,
                    'machine_id' => $machine->id,
                    'pickup_code_id' => $pickupCode->id,
                    'action' => 'verify_failed',
                    'remark' => 'log.pickup.no_slot_for_product',
                    'raw_data' => ['code' => $pickupCode->code, 'product_id' => $pickupCode->product_id],
                ]);
                return response()->json([
                    'success' => false,
                    'code' => 409,
                    'message' => '此機台目前無此商品貨道，無法取貨',
                ], 409);
            }
        }

        return response()->json([
            'success' => true,
            'code' => 200,
            'data' => $data,
        ]);
    }


    /**
     * B670: Verify Pass Code (For Technicians)
     */
    public function verifyPassCode(Request $request)
    {
        $machine = $request->get('machine');
        $lockoutKey = "passcode_lockout:{$machine->id}";

        if (Cache::has($lockoutKey)) {
            return response()->json(['success' => false, 'message' => 'Locked', 'code' => 429], 429);
        }

        $validator = Validator::make($request->all(), ['code' => 'required|string|size:8']);
        if ($validator->fails())
            return response()->json(['success' => false, 'code' => 400], 400);

        $code = $request->input('code');
        $passCode = PassCode::where('machine_id', $machine->id)
            ->where('code', $code)
            ->where('status', 'active')
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->first();

        if (!$passCode) {
            $fails = Cache::increment("passcode_fails:{$machine->id}");
            if ($fails >= 5)
                Cache::put($lockoutKey, true, 60);
            return response()->json(['success' => false, 'code' => 404], 404);
        }

        // 建立通行碼專屬日誌 (驗證成功)
        PassCodeLog::create([
            'company_id' => $machine->company_id,
            'machine_id' => $machine->id,
            'pass_code_id' => $passCode->id,
            'status' => 'verified',
            'action' => 'verify_success',
            'raw_data' => ['machine_sn' => $machine->serial_no, 'code' => $passCode->code]
        ]);

        // 單次/限次通行碼：驗證成功即計為使用一次；達上限就標記 used，
        // 下次驗證會被上方 where('status','active') 過濾擋掉，達成「只能使用一次」。
        // usage_limit 為 null（未勾單次）時不計數，維持原本無限次行為。
        if (!is_null($passCode->usage_limit)) {
            $newCount = $passCode->usage_count + 1;
            $passCode->update([
                'usage_count' => $newCount,
                'status' => $newCount >= $passCode->usage_limit ? 'used' : $passCode->status,
            ]);
        }


        return response()->json([
            'success' => true,
            'code' => 200,
            'data' => [
                'name' => $passCode->name,
                'code_id' => $passCode->id, // 統一回傳 code_id
                'status' => 'active'
            ]
        ]);
    }

    /**
     * B680: Verify Staff Card (New)
     */
    public function verifyStaffCard(Request $request)
    {
        $machine = $request->get('machine');

        $validator = Validator::make($request->all(), [
            'code' => 'required|string', // 卡片 UID
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'code' => 400], 400);
        }

        $uid = $request->input('code');

        // 搜尋該公司的有效員工卡 (遵循租戶隔離)
        $staffCard = StaffCard::where('company_id', $machine->company_id)
            ->where('card_uid', $uid)
            ->where('status', 'active')
            ->first();

        if (!$staffCard) {
            ProcessStateLog::dispatch($machine->id, $machine->company_id, "[StaffCard] Verification failed: :uid", 'info', ['uid' => $uid]);
            return response()->json(['success' => false, 'code' => 404, 'message' => 'Card not found or inactive'], 404);
        }

        // 建立刷卡日誌 (作為閉環起點)
        StaffCardLog::create([
            'company_id' => $machine->company_id,
            'staff_card_id' => $staffCard->id,
            'machine_id' => $machine->id,
            'payment_type' => 41, // Staff Card
            'code_id' => $staffCard->id,
            'action' => 'verify_success',
            'raw_data' => ['machine_sn' => $machine->serial_no, 'uid' => $uid]
        ]);

        return response()->json([
            'success' => true,
            'code' => 200,
            'data' => [
                'name' => $staffCard->name,
                'code_id' => $staffCard->id, // 統一回傳 code_id
                'status' => 'active'
            ]
        ]);
    }

    /**
     * B690: Verify Welcome Gift (New)
     */
    public function verifyWelcomeGift(Request $request)
    {
        $machine = $request->get('machine');
        $lockoutKey = "welcome_gift_lockout:{$machine->id}";

        if (Cache::has($lockoutKey)) {
            return response()->json(['success' => false, 'message' => 'Too many attempts. Locked.', 'code' => 429], 429);
        }

        $validator = Validator::make($request->all(), ['code' => 'required|string|size:8']);
        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Invalid format', 'code' => 400], 400);
        }

        $code = $request->input('code');
        $gift = WelcomeGift::where('machine_id', $machine->id)
            ->where('code', $code)
            ->where('status', 'active')
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->first();

        if (!$gift || !$gift->isValid()) {
            $fails = Cache::increment("welcome_gift_fails:{$machine->id}");
            if ($fails === 1) {
                Cache::put("welcome_gift_fails:{$machine->id}", 1, 600);
            }
            if ($fails >= 5) {
                Cache::put($lockoutKey, true, 60);
                Cache::forget("welcome_gift_fails:{$machine->id}");
            }
            return response()->json(['success' => false, 'message' => 'Invalid or expired code', 'code' => 404, 'remaining_attempts' => max(0, 5 - $fails)], 404);
        }

        Cache::forget("welcome_gift_fails:{$machine->id}");

        // 建立來店禮驗證日誌 (驗證成功)
        WelcomeGiftLog::create([
            'company_id' => $machine->company_id,
            'machine_id' => $machine->id,
            'welcome_gift_id' => $gift->id,
            'action' => 'verify_success',
            'remark' => 'log.welcome_gift.verify_success',
            'raw_data' => [
                'code' => $gift->code,
                'discount_type' => $gift->discount_type,
                'discount_value' => $gift->discount_value
            ]
        ]);

        return response()->json([
            'success' => true,
            'code' => 200,
            'data' => [
                'name' => $gift->name,
                'code_id' => $gift->id,
                'discount_type' => $gift->discount_type,
                'discount_value' => $gift->discount_value,
                'discount_label' => $gift->discount_label, // 回傳自動算好的中文折數 (如「八折」、「折50元」)
                'usage_type' => $gift->usage_type,
                'status' => 'active'
            ]
        ]);
    }
}
