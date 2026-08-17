<?php

namespace App\Services\Machine;

use App\Models\Machine\Machine;
use App\Models\Machine\MachineLog;
use App\Models\Machine\MachineSlot;
use App\Models\Machine\MachineStockMovement;
use App\Models\Machine\RemoteCommand;
use App\Models\Product\Product;
use App\Models\Transaction\Order;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class MachineService
{
    private const SPRING_SLOT_FLAGS = [
        [1, 10, 'is_spring_slot_1_10'],
        [11, 20, 'is_spring_slot_11_20'],
        [21, 30, 'is_spring_slot_21_30'],
        [31, 40, 'is_spring_slot_31_40'],
        [41, 50, 'is_spring_slot_41_50'],
        [51, 60, 'is_spring_slot_51_60'],
    ];

    /**
     * B013: 硬體狀態代碼對照表 (Hardware Status Code Mapping)
     *
     * 代碼格式：Command 0x04 + Status Byte，例如 0x04+0x03 = '0403'
     * 出貨結果判定：僅 0402 (出貨成功) 與 0424 (商品取出成功) 視為成功，
     *               其餘出貨結果碼均應觸發退款。
     *
     * 參考文件：廠商硬體通訊協議 Command(0x04) 狀態定義
     */
    public const ERROR_CODE_MAP = [
        // ═══════════════════════════════════════════════════════════════
        // 出貨與物理動作類 (Command 0x04 - BUY_STATUS)
        // ═══════════════════════════════════════════════════════════════

        // --- 基本出貨流程 ---
        '0401' => ['label' => 'Dispensing in progress', 'level' => 'info'],           // 正在出貨
        '0402' => ['label' => 'Dispense successful', 'level' => 'info'],              // ✅ 出貨成功
        '0403' => ['label' => 'Slot jammed', 'level' => 'error'],                     // 貨道卡貨 (重大異常)
        '0404' => ['label' => 'Motor not stopped', 'level' => 'warning'],             // 電機未正常停止
        '0405' => ['label' => 'Motor not found', 'level' => 'error'],                 // 電機不存在
        '0406' => ['label' => 'Motor not found (0406)', 'level' => 'error'],          // 電機不存在 (同 0405)
        '0407' => ['label' => 'Elevator failure', 'level' => 'error'],                // 昇降機故障

        // --- 昇降機系統 ---
        '040A' => ['label' => 'Elevator belt self-check error', 'level' => 'error'],  // 昇降機皮帶自檢錯誤
        '0410' => ['label' => 'Elevator rising', 'level' => 'info'],                  // 昇降機正在上升
        '0411' => ['label' => 'Elevator descending', 'level' => 'info'],              // 昇降機正在下降
        '0412' => ['label' => 'Elevator rise error', 'level' => 'error'],             // 昇降機上升錯誤
        '0413' => ['label' => 'Elevator descent error', 'level' => 'error'],          // 昇降機下降錯誤
        '0425' => ['label' => 'Elevator support rod return error', 'level' => 'error'],  // 昇降台撐桿回位錯誤
        '0428' => ['label' => 'Elevator support rod push error', 'level' => 'error'],    // 昇降台撐桿推出錯誤
        '0429' => ['label' => 'Elevator enter microwave error', 'level' => 'error'],     // 昇降台進微波爐錯誤
        '0430' => ['label' => 'Elevator exit microwave error', 'level' => 'error'],      // 昇降台出微波爐錯誤

        // --- 微波爐取貨口門 ---
        '0414' => ['label' => 'Microwave pickup door closing', 'level' => 'info'],       // 正在關閉微波爐取貨口門
        '0415' => ['label' => 'Microwave pickup door close error', 'level' => 'error'],  // 微波爐取貨口門關閉錯誤
        '0426' => ['label' => 'Microwave pickup door opening', 'level' => 'info'],       // 正在打開微波爐取貨口門
        '0427' => ['label' => 'Microwave pickup door open error', 'level' => 'error'],   // 微波爐取貨口門打開錯誤

        // --- 微波爐進貨口門 ---
        '0416' => ['label' => 'Microwave delivery door opening', 'level' => 'info'],       // 正在打開微波爐進貨口門
        '0417' => ['label' => 'Microwave delivery door open error', 'level' => 'error'],    // 微波爐進貨口門打開錯誤
        '0418' => ['label' => 'Pushing product into microwave', 'level' => 'info'],          // 正在將商品推入微波爐
        '0419' => ['label' => 'Microwave delivery door closing', 'level' => 'info'],        // 正在關閉微波爐進貨口門
        '0420' => ['label' => 'Microwave delivery door close error', 'level' => 'error'],   // 微波爐進貨口門關閉錯誤

        // --- 微波爐內部 ---
        '0421' => ['label' => 'Product not detected in microwave', 'level' => 'error'],      // 微波爐內未檢測到商品
        '0422' => ['label' => 'Product heating', 'level' => 'info'],                         // 商品正在加熱
        '0423' => ['label' => 'Product heating remaining time', 'level' => 'info'],           // 商品加熱剩餘時間 (貨道號=秒數)
        '0424' => ['label' => 'Purchase successful', 'level' => 'info'],                     // ✅ 購買成功
        '0431' => ['label' => 'Microwave push rod push error', 'level' => 'error'],          // 微波爐內推桿推出錯誤
        '0432' => ['label' => 'Microwave push rod retract error', 'level' => 'error'],       // 微波爐內推桿收回錯誤

        // --- 鮮奶機專用 ---
        '040B' => ['label' => 'Milk dispense timeout', 'level' => 'error'],             // 按鍵出奶超時 (鮮奶機)
        '040C' => ['label' => 'Cup dropper failure', 'level' => 'error'],                // 漏杯器漏杯失敗 (鮮奶機)
        '040D' => ['label' => 'Cup drop not detected', 'level' => 'error'],              // 未檢測到杯子掉落 (鮮奶機)

        // --- 手環機專用 ---
        '040E' => ['label' => 'Bracelet scan failed', 'level' => 'error'],               // 沒掃碼到手環
        '040F' => ['label' => 'Bracelet drop not detected', 'level' => 'error'],         // 沒檢測到手環掉入取貨斗

        // --- 取貨口門 (購物車模式) ---
        '0433' => ['label' => 'Pickup door opened (cart mode)', 'level' => 'info'],      // 取貨口門打開成功 (購物車購買)
        '0434' => ['label' => 'Pickup door closed (cart mode)', 'level' => 'info'],      // 取貨口門關閉成功 (購物車購買)
        '0441' => ['label' => 'Pickup door unlocked', 'level' => 'info'],                // 取貨口門開鎖上報

        // --- 液體機專用 ---
        '0435' => ['label' => 'Liquid dispense paused', 'level' => 'warning'],           // 暫停出液

        // --- 終止 ---
        '04FF' => ['label' => 'Purchase terminated', 'level' => 'error'],                // 購買終止

        // ═══════════════════════════════════════════════════════════════
        // 貨道與馬達狀態類 (Prefix: 02 - SLOT_STATUS)
        // ═══════════════════════════════════════════════════════════════
        '0201' => ['label' => 'Slot normal', 'level' => 'info'],                              // 貨道正常
        '0202' => ['label' => 'Slot out of stock', 'level' => 'warning'],                     // 貨道缺貨
        '0203' => ['label' => 'Slot does not exist', 'level' => 'warning'],                   // 貨道不存在
        '0204' => ['label' => 'Slot suspended', 'level' => 'warning'],                        // 貨道暫停使用
        '0205' => ['label' => 'Elevator contains items', 'level' => 'warning'],               // 升降台裡面有物品
        '0206' => ['label' => 'Pickup door not closed', 'level' => 'warning'],                 // 取貨口門沒關上
        '0207' => ['label' => 'Elevator failure (0207)', 'level' => 'error'],                 // 升降機故障
        '0208' => ['label' => 'Elevator self-check error', 'level' => 'error'],               // 升降機自檢錯誤
        '0209' => ['label' => 'Microwave pickup door close error (0209)', 'level' => 'error'], // 微波爐取貨口門關閉錯誤
        '020B' => ['label' => 'Machine temperature exceeds limit', 'level' => 'warning'],      // 機器超過設定溫度，暫停購買
        '020C' => ['label' => 'Bento machine cooling water tank empty', 'level' => 'error'],  // 盒飯機冷卻水箱缺水
        '020D' => ['label' => 'Age verification required', 'level' => 'warning'],             // 貨道需要年齡驗證
        '020E' => ['label' => 'Water level too low', 'level' => 'warning'],                    // 水量過低
        '020F' => ['label' => 'Weight sensor communication error', 'level' => 'error'],       // 重量傳感器通訊錯誤
        '0210' => ['label' => 'Microwave delivery door open error (0210)', 'level' => 'error'],// 微波爐進貨口門打開錯誤
        '0211' => ['label' => 'Microwave delivery door close error (0211)', 'level' => 'error'],// 微波爐進貨口門關閉錯誤
        '0212' => ['label' => 'Bento not detected', 'level' => 'warning'],                     // 沒檢測到盒飯
        '0213' => ['label' => 'Bento heating', 'level' => 'info'],                             // 盒飯正在加熱
        '0214' => ['label' => 'Microwave pickup door open error (0214)', 'level' => 'error'],  // 微波爐取貨口門打開錯誤
        '0215' => ['label' => 'Please remove bento from microwave', 'level' => 'warning'],     // 請取出微波爐內盒飯
        '0216' => ['label' => 'Support rod return error', 'level' => 'error'],                 // 撐桿回位錯誤
        '0217' => ['label' => 'Main motor failure', 'level' => 'error'],                      // 主電機故障
        '0218' => ['label' => 'Translation motor failure', 'level' => 'error'],                 // 平移電機故障
        '0219' => ['label' => 'Support rod push error', 'level' => 'error'],                  // 撐桿推出錯誤
        '0220' => ['label' => 'Elevator enter microwave error (0220)', 'level' => 'error'],    // 升降台進去微波爐錯誤
        '0221' => ['label' => 'Elevator exit microwave error (0221)', 'level' => 'error'],     // 升降台出去微波爐錯誤
        '0222' => ['label' => 'Microwave push rod push error (0222)', 'level' => 'error'],     // 微波爐內推桿推出錯誤
        '0223' => ['label' => 'Microwave push rod retract error (0223)', 'level' => 'error'],  // 微波爐內推桿收回錯誤

        // ═══════════════════════════════════════════════════════════════
        // 機台整體狀態類 (Prefix: 54 - MACHINE_STATUS)
        // ═══════════════════════════════════════════════════════════════
        '5400' => ['label' => 'Machine normal', 'level' => 'info'],
        '5401' => ['label' => 'Elevator sensor error', 'level' => 'error'],
        '5402' => ['label' => 'Pickup door not closed', 'level' => 'warning'],
        '5403' => ['label' => 'Elevator failure', 'level' => 'error'],
    ];

    /**
     * 出貨成功結果碼 (Dispense Success Codes)
     *
     * 僅以下代碼視為出貨成功，其餘出貨結果碼均應觸發退款。
     * - 0402: 一般出貨成功
     * - 0424: 商品取出成功 (微波爐機型)
     */
    public const DISPENSE_SUCCESS_CODES = ['0402', '0424'];

    /**
     * 刷卡機 (Nexsys 終端機 / NCCC 標準) 交易回應碼對照表。
     * 來源：聯合信用卡處理中心終端機回應碼規格。
     * 用於把 order.payment_response 內的 "| code=XXXX" 解析成可讀(可多語)的失敗原因，
     * 寫入 type='card_terminal' 機台日誌。信用卡(1)/電子票證(2)/手機支付(10) 共用同一台實體刷卡機。
     * 值為英文翻譯鍵，實際顯示由 lang/*.json 多語系翻譯。
     */
    public const CARD_TERMINAL_CODE_MAP = [
        '0000' => 'Transaction approved',
        '0001' => 'Transaction failed (issuer/card declined)',
        '0002' => 'Please contact bank / e-wallet provider',
        '0003' => 'Transaction timeout',
        '0004' => 'Operation error',
        '0005' => 'Communication failure',
        '0006' => 'User terminated transaction',
        '0009' => 'Non-participating institution card',
        '0010' => 'Message format error',
        '0011' => 'Not a co-branded card',
        '0012' => 'Stored-value amount over limit',
        '0013' => 'Card locked',
        '0014' => 'Cannot match original stored-value transaction',
        '0015' => 'Card auto-load not enabled',
        '0016' => 'Insufficient stored-value / e-wallet balance',
        '0017' => 'Multiple cards detected',
        '0018' => 'Please re-tap card / re-scan / retry',
        '0019' => 'Auto-load succeeded but purchase failed',
        '1001' => 'Credit card and stored-value settlement both failed',
        '1002' => 'Credit card settled, stored-value settlement failed',
        '1003' => 'Credit card settlement failed, stored-value settled',
    ];

    /**
     * Update machine heartbeat and status.
     * 
     * @param string $serialNo
     * @param array $data
     * @return Machine
     */
    public function updateHeartbeat(string $serialNo, array $data): Machine
    {
        return DB::transaction(function () use ($serialNo, $data) {
            $machine = Machine::where('serial_no', $serialNo)->firstOrFail();

            // 採用現代化語意命名 (Modern semantic naming)
            $temperature = $data['temperature'] ?? $machine->temperature;
            $currentPage = $data['current_page'] ?? $machine->current_page;
            $doorStatus = $data['door_status'] ?? $machine->door_status;
            $firmwareVersion = $data['firmware_version'] ?? $machine->firmware_version;
            $model = $data['model'] ?? $machine->model;

            $updateData = [
                'temperature' => $temperature,
                'current_page' => $currentPage,
                'door_status' => $doorStatus,
                'firmware_version' => $firmwareVersion,
                'model' => $model,
                'last_heartbeat_at' => now(),
            ];

            $machine->update($updateData);

            // Record log if provided
            if (!empty($data['log'])) {
                $machine->logs()->create([
                    'company_id' => $machine->company_id,
                    'type' => 'status',
                    'level' => $data['log_level'] ?? 'info',
                    'message' => $data['log'],
                    'context' => $data['log_payload'] ?? null,
                ]);
            }

            return $machine;
        });
    }

    /**
     * Sync machine API token to Redis for MQTT authentication.
     * 
     * @param Machine $machine
     */
    public function syncMqttAuth(Machine $machine): void
    {
        if (empty($machine->api_token)) {
            return;
        }

        // MQTT 連線認證：Username = serial_no, Password = hash(api_token)
        // 遵循 framework.md 4.5 規範
        $redisKey = "machine_auth:{$machine->serial_no}";
        
        // 這裡採用 SHA256 雜湊，與 EMQX 設定對應
        $hashedToken = hash('sha256', $machine->api_token);
        
        \Illuminate\Support\Facades\Redis::hSet($redisKey, 'password', $hashedToken);
        
        \Illuminate\Support\Facades\Log::info("MQTT Auth synced to Redis", [
            'serial_no' => $machine->serial_no,
            'key' => $redisKey
        ]);
    }

    /**
     * 統一寫入機台庫存異動紀錄
     *
     * @param MachineSlot $slot       貨道 Model（已含 machine_id, product_id, stock）
     * @param int         $delta      異動數量（正=增加，負=減少）
     * @param string      $type       異動類型（MachineStockMovement::TYPE_*）
     * @param mixed       $reference  關聯單據 Model（nullable）
     * @param string|null $note       備註說明
     */
    public function recordStockMovement(
        MachineSlot $slot,
        int $delta,
        string $type,
        mixed $reference = null,
        ?string $note = null,
        array $context = []
    ): MachineStockMovement {
        $before = $slot->stock - $delta; // 因為已更新 slot，反推異動前的值

        return MachineStockMovement::create([
            'company_id'     => $slot->machine->company_id ?? null,
            'machine_id'     => $slot->machine_id,
            'product_id'     => $slot->product_id,
            'slot_no'        => $slot->slot_no,
            'type'           => $type,
            'quantity'       => $delta,
            'before_qty'     => $before,
            'after_qty'      => $slot->stock,
            'reference_type' => $reference ? get_class($reference) : null,
            'reference_id'   => $reference?->id,
            'note'           => $note,
            'context'        => $context,
            'created_by'     => Auth::id(),
        ]);
    }

    /**
     * Sync machine slots based on replenishment report.
     * 
     * @param Machine $machine
     * @param array $slotsData
     */
    public function syncSlots(Machine $machine, array $slotsData): void
    {
        DB::transaction(function () use ($machine, $slotsData) {
            // 蒐集所有傳入的商品 ID (可能是 SKU 或 實際 ID)
            $productCodes = collect($slotsData)->pluck('product_id')->filter()->unique()->toArray();
            
            // 優先以 ID 查詢商品，若 ID 不存在則嘗試 Barcode (Prioritize ID lookup, fallback to Barcode)
            $products = \App\Models\Product\Product::whereIn('id', $productCodes)
                ->orWhereIn('barcode', $productCodes)
                ->get();

            foreach ($slotsData as $slotData) {
                $slotNo = $slotData['slot_no'] ?? null;
                if (!$slotNo) continue;

                $existingSlot = $machine->slots()->where('slot_no', $slotNo)->first();
                
                // 查找對應的實體 ID (支援 ID 與 Barcode 比對)
                $productCode = $slotData['product_id'] ?? null;
                $actualProductId = null;
                if ($productCode) {
                    $actualProductId = $products->first(function ($p) use ($productCode) {
                        return (string)$p->id === (string)$productCode || $p->barcode === (string)$productCode;
                    })?->id;
                }

                // 根據機台貨道機制設定決定上限與貨道類型 (1: 履帶, 2: 彈簧)
                $slotType = $this->resolveSlotType($machine, $slotNo);
                if ($actualProductId) {
                    $product = $products->find($actualProductId);
                    if ($product) {
                        $slotData['capacity'] = $this->calculateSlotMaxStock($product, $machine, $slotNo);
                    }
                }

                $newStock = (int) ($slotData['stock'] ?? 0);

                $updateData = [
                    'product_id' => $actualProductId,
                    'type' => $slotType,
                    'stock' => $newStock,
                    'max_stock' => $slotData['capacity'] ?? ($existingSlot->max_stock ?? 10),
                    'is_active' => true,
                ];

                // ── 雙向 LWW：鎖定(lock) 與 效期/批號(expiry/batch)，各自獨立 rev ──
                $updateData = array_merge(
                    $updateData,
                    $this->resolveSlotLockLww($existingSlot, $slotData),
                    $this->resolveSlotExpiryLww($existingSlot, $slotData, $actualProductId)
                );

                if ($existingSlot) {
                    $oldStock = (int) $existingSlot->stock;
                    $oldProductId = $existingSlot->product_id;

                    // 檢查是否有實質變動 (Check for actual changes)
                    $isStockChanged = ($oldStock !== $newStock);
                    $isProductChanged = ($oldProductId != $actualProductId); // 這裡用鬆散比對以處理 null/0 的情況
                    $isSlotConfigChanged = (string) $existingSlot->type !== (string) $updateData['type']
                        || (int) $existingSlot->max_stock !== (int) $updateData['max_stock'];
                    // lock/expiry/rev 也算實質變動，確保 LWW 結果會被寫回（即使 stock/product 沒變）
                    $curExpiryStr = $existingSlot->expiry_date ? $existingSlot->expiry_date->format('Y-m-d') : null;
                    $isLwwChanged = ((bool) $existingSlot->is_locked !== (bool) $updateData['is_locked'])
                        || ((int) $existingSlot->last_app_lock_rev !== (int) $updateData['last_app_lock_rev'])
                        || ((int) $existingSlot->last_app_expiry_rev !== (int) $updateData['last_app_expiry_rev'])
                        || ($curExpiryStr !== $updateData['expiry_date'])
                        || ($existingSlot->batch_no !== $updateData['batch_no']);

                    if ($isStockChanged || $isProductChanged || $isSlotConfigChanged || $isLwwChanged) {
                        $existingSlot->update($updateData);
                        $existingSlot->refresh();

                        if ($isStockChanged || $isProductChanged) {
                            $delta = $newStock - $oldStock;
                            $note = $isProductChanged ? "movement.note.product_changed_and_adjusted" : "movement.note.replenishment_correction";

                            $this->recordStockMovement(
                                $existingSlot,
                                $delta,
                                MachineStockMovement::TYPE_ADJUSTMENT,
                                null,
                                $note,
                                ['slot_no' => $existingSlot->slot_no, 'old' => $oldStock, 'new' => $newStock, 'old_product_id' => $oldProductId, 'new_product_id' => $actualProductId]
                            );
                        }
                    }
                } else {
                    $newSlot = $machine->slots()->create(array_merge($updateData, ['slot_no' => $slotNo]));
                    
                    // 新建貨道若有初始庫存，也紀錄流水帳
                    if ($newStock > 0) {
                        $this->recordStockMovement(
                            $newSlot,
                            $newStock,
                            MachineStockMovement::TYPE_ADJUSTMENT,
                            null,
                            "movement.note.initial_sync_report",
                            ['slot_no' => $slotNo, 'old' => 0, 'new' => $newStock]
                        );
                    }
                }
            }

            // ─── B009 全量同步：移除不在清單中的貨道 ───
            // 取得本次上報的所有貨道編號
            $reportedSlotNos = collect($slotsData)
                ->pluck('slot_no')
                ->filter()
                ->map(fn($s) => (string) $s)
                ->values()
                ->toArray();

            // 防呆：上報清單為空時不執行全量刪除（避免機台/APP 誤報空清單而把所有貨道清光）。
            $orphanedSlots = empty($reportedSlotNos)
                ? collect()
                : $machine->slots()
                    ->whereNotIn('slot_no', $reportedSlotNos)
                    ->get();

            foreach ($orphanedSlots as $orphan) {
                $oldStock = (int) $orphan->stock;

                if ($oldStock > 0) {
                    // 先將 stock 設為 0 以便 recordStockMovement 正確計算 before/after
                    $orphan->stock = 0;
                    
                    $this->recordStockMovement(
                        $orphan,
                        -$oldStock,
                        MachineStockMovement::TYPE_DECOMMISSION,
                        null,
                        "movement.note.slot_decommissioned_by_b009",
                        ['old' => $oldStock, 'new' => 0, 'slot_no' => $orphan->slot_no]
                    );
                }

                // 更新貨道狀態為非使用中或直接刪除 (依據需求決定，目前採刪除以維持 B009 全量同步語意)
                $orphan->delete();
            }
        });
    }

    /**
     * 空字串/"null" 一律歸一化為 null（避免 MySQL date 欄位寫入空字串解析爆）。
     */
    private function blankToNull($value)
    {
        if (is_string($value)) {
            $value = trim($value);
        }
        if ($value === '' || $value === null || (is_string($value) && strtolower($value) === 'null')) {
            return null;
        }
        return $value;
    }

    /**
     * 取日期字串的 yyyy-MM-dd 部分（容錯後台/機台帶了時間），空值回 null。
     */
    private function normalizeDate($value)
    {
        $value = $this->blankToNull($value);
        if ($value === null) {
            return null;
        }
        return substr((string) $value, 0, 10);
    }

    /**
     * 貨道鎖定(lock)的雙向 LWW 解析，回傳要寫入的 is_locked / last_app_lock_rev。
     * - 新貨道 → 採 App 現場值。
     * - 既有貨道 → 常態 LWW：App rev 較大才採用，否則保留後台值。
     *   （既有機台升級時，App 端 onUpgrade 已把現場既有鎖定的 lock_rev 設為 1，故能 1>0 自動遷移上雲。）
     */
    private function resolveSlotLockLww(?MachineSlot $existingSlot, array $slotData): array
    {
        $appLocked = (bool) ($slotData['is_locked'] ?? false);
        $appRev = (int) ($slotData['lock_rev'] ?? 0);

        if (!$existingSlot) {
            $newRev = $appRev > 0 ? $appRev : ($appLocked ? 1 : 0);
            return ['is_locked' => $appLocked, 'last_app_lock_rev' => $newRev];
        }
        $curLocked = (bool) $existingSlot->is_locked;
        $curRev = (int) $existingSlot->last_app_lock_rev;
        if ($appRev > $curRev) {
            return ['is_locked' => $appLocked, 'last_app_lock_rev' => $appRev];
        }
        return ['is_locked' => $curLocked, 'last_app_lock_rev' => $curRev];
    }

    /**
     * 效期/批號(expiry/batch)的雙向 LWW 解析，回傳要寫入的 expiry_date / batch_no / last_app_expiry_rev。
     * 換商品(product_id 變更)時預設清空舊效期（安全網），除非 App 同筆帶了較新的新效期。
     */
    private function resolveSlotExpiryLww(?MachineSlot $existingSlot, array $slotData, $actualProductId): array
    {
        $appExpiry = $this->normalizeDate($slotData['expiry_date'] ?? null);
        $appBatch = $this->blankToNull($slotData['batch_no'] ?? null);
        $appRev = (int) ($slotData['expiry_rev'] ?? 0);

        if (!$existingSlot) {
            $newRev = $appRev > 0 ? $appRev : ($appExpiry !== null ? 1 : 0);
            return ['expiry_date' => $appExpiry, 'batch_no' => $appBatch, 'last_app_expiry_rev' => $newRev];
        }
        $curExpiry = $existingSlot->expiry_date ? $existingSlot->expiry_date->format('Y-m-d') : null;
        $curBatch = $this->blankToNull($existingSlot->batch_no);
        $curRev = (int) $existingSlot->last_app_expiry_rev;

        // 換商品安全網：預設清空，除非 App 同筆帶了較新的新效期
        $isProductChanged = ((string) ($existingSlot->product_id) !== (string) $actualProductId);
        if ($isProductChanged) {
            if ($appExpiry !== null && $appRev > $curRev) {
                return ['expiry_date' => $appExpiry, 'batch_no' => $appBatch, 'last_app_expiry_rev' => $appRev];
            }
            return ['expiry_date' => null, 'batch_no' => null, 'last_app_expiry_rev' => max($curRev, $appRev)];
        }

        if ($appRev > $curRev) {
            return ['expiry_date' => $appExpiry, 'batch_no' => $appBatch, 'last_app_expiry_rev' => $appRev];
        }
        return ['expiry_date' => $curExpiry, 'batch_no' => $curBatch, 'last_app_expiry_rev' => $curRev];
    }

    public function syncProductSlotMaxStock(Product $product): void
    {
        MachineSlot::query()
            ->where('product_id', $product->id)
            ->with(['machine' => fn($query) => $query->withoutGlobalScopes()])
            ->chunkById(500, function ($slots) use ($product) {
                foreach ($slots as $slot) {
                    if (!$slot->machine) {
                        continue;
                    }

                    $slot->update([
                        'type' => $this->resolveSlotType($slot->machine, $slot->slot_no),
                        'max_stock' => $this->calculateSlotMaxStock($product, $slot->machine, $slot->slot_no),
                    ]);
                }
            });
    }

    public function syncMachineSlotMaxStock(Machine $machine): void
    {
        $machine->slots()
            ->with(['product' => fn($query) => $query->withoutGlobalScopes()])
            ->chunkById(500, function ($slots) use ($machine) {
                foreach ($slots as $slot) {
                    if (!$slot->product) {
                        continue;
                    }

                    $slot->update([
                        'type' => $this->resolveSlotType($machine, $slot->slot_no),
                        'max_stock' => $this->calculateSlotMaxStock($slot->product, $machine, $slot->slot_no),
                    ]);
                }
            });
    }

    public function calculateSlotMaxStock(Product $product, Machine $machine, string|int|null $slotNo): int
    {
        return $this->isSpringSlot($machine, $slotNo)
            ? (int) $product->spring_limit
            : (int) $product->track_limit;
    }

    public function resolveSlotType(Machine $machine, string|int|null $slotNo): string
    {
        return $this->isSpringSlot($machine, $slotNo) ? '2' : '1';
    }

    public function isSpringSlot(Machine $machine, string|int|null $slotNo): bool
    {
        $slotNumber = (int) $slotNo;

        foreach (self::SPRING_SLOT_FLAGS as [$start, $end, $field]) {
            if ($slotNumber >= $start && $slotNumber <= $end) {
                return (bool) $machine->{$field};
            }
        }

        return false;
    }

    /**
     * Update machine slot stock, expiry, and batch.
     * 
     * @param Machine $machine
     * @param array $data
     * @param int|null $userId
     * @return void
     */
    public function updateSlot(Machine $machine, array $data, ?int $userId = null): void
    {
        DB::transaction(function () use ($machine, $data, $userId) {
            $slotNo = $data['slot_no'];
            $stock = $data['stock'] ?? null;
            $expiryDate = $data['expiry_date'] ?? null;
            $batchNo = $data['batch_no'] ?? null;
            
            $slot = $machine->slots()->where('slot_no', $slotNo)->lockForUpdate()->firstOrFail();

            $pendingCommand = RemoteCommand::where('machine_id', $machine->id)
                ->where('command_type', 'reload_stock')
                ->where('status', 'pending')
                ->whereJsonContains('payload->slot_no', (string)$slotNo)
                ->latest()
                ->first();

            if ($pendingCommand) {
                // 重新下發時覆蓋前一筆尚未執行的指令。
                // 若舊指令已超過 1 分鐘未收到機台回傳 (ACK)，視為逾時 (timeout)；
                // 否則為使用者主動覆蓋 (superseded)。與 dispense 逾時邏輯一致。
                $isTimedOut = $pendingCommand->created_at->lt(now()->subMinute());
                $pendingCommand->update([
                    'status' => $isTimedOut ? 'timeout' : 'superseded',
                    'note'   => $isTimedOut
                        ? __('Superseded by new command (Timeout)')
                        : __('Superseded by new command'),
                ]);
            }

            // 紀錄舊數據以供回滾使用
            $oldExpiry = $slot->expiry_date ? Carbon::parse($slot->expiry_date)->toDateString() : null;
            $oldData = [
                'stock' => $slot->stock,
                'expiry_date' => $oldExpiry,
                'batch_no' => $slot->batch_no,
                'is_locked' => (bool) $slot->is_locked,
            ];

            // 2. 執行樂觀更新 (Optimistic Update)：只更新「明確帶上的欄位」，避免鎖定切換誤清效期
            $updateData = [];
            $normExpiry = $oldExpiry;
            if (array_key_exists('expiry_date', $data)) {
                $normExpiry = $this->normalizeDate($expiryDate);
                $updateData['expiry_date'] = $normExpiry;
            }
            $normBatch = $slot->batch_no;
            if (array_key_exists('batch_no', $data)) {
                $normBatch = $this->blankToNull($batchNo);
                $updateData['batch_no'] = $normBatch;
            }
            if ($stock !== null) $updateData['stock'] = (int) $stock;
            if (array_key_exists('is_locked', $data)) {
                $updateData['is_locked'] = (bool) $data['is_locked'];
            }

            // 後台編輯效期/批號或鎖定 → 遞增對應 rev（成為權威值，下行帶給 App 對齊 synced_rev）
            $expiryEdited = ($normExpiry !== $oldExpiry) || ($normBatch !== $slot->batch_no);
            if ($expiryEdited) {
                $updateData['last_app_expiry_rev'] = (int) $slot->last_app_expiry_rev + 1;
            }
            $lockEdited = array_key_exists('is_locked', $data) && ((bool) $data['is_locked'] !== (bool) $slot->is_locked);
            if ($lockEdited) {
                $updateData['last_app_lock_rev'] = (int) $slot->last_app_lock_rev + 1;
            }

            if (!empty($updateData)) $slot->update($updateData);

            // 3. 若庫存數值有異動，記錄 adjustment 流水帳
            if ($stock !== null && (int)$stock !== $oldData['stock']) {
                $delta = (int)$stock - $oldData['stock'];
                $slot->refresh();
                $this->recordStockMovement(
                    $slot,
                    $delta,
                    MachineStockMovement::TYPE_ADJUSTMENT,
                    null,
                    "movement.note.manual_adjustment",
                    ['slot_no' => $slot->slot_no, 'old' => $oldData['stock'], 'new' => $stock]
                );
            }

            $slot->refresh();

            // 3. 建立遠端指令紀錄
            $command = RemoteCommand::create([
                'machine_id' => $machine->id,
                'user_id' => $userId,
                'command_type' => 'reload_stock',
                'status' => 'pending',
                'payload' => [
                    'slot_no' => (string)$slotNo,
                    'old' => $oldData,
                    'new' => [
                        'stock' => (int) $slot->stock,
                        'expiry_date' => $slot->expiry_date ? $slot->expiry_date->format('Y-m-d') : null,
                        'batch_no' => $slot->batch_no,
                        'is_locked' => (bool) $slot->is_locked,
                    ]
                ]
            ]);

            // 4. 推播 MQTT（一律帶上目前權威的 is_locked 與雙 rev，讓 App 端對齊 synced_rev）
            $mqttPayload = [
                'slot_no' => (string) $slotNo,
                'stock' => (int) $slot->stock,
                'expiry_date' => $slot->expiry_date ? $slot->expiry_date->format('Y-m-d') : null,
                'batch_no' => $slot->batch_no,
                'is_locked' => (bool) $slot->is_locked,
                'lock_rev' => (int) $slot->last_app_lock_rev,
                'expiry_rev' => (int) $slot->last_app_expiry_rev,
            ];

            app(\App\Services\Machine\MqttService::class)->pushCommand(
                $machine->serial_no,
                'update_inventory',
                $mqttPayload,
                (string) $command->id
            );
        });
    }

    /**
     * 遠端出貨指令下發 (指令模式，不再預扣庫存)
     */
    public function dispatchDispense(Machine $machine, string $slotNo, ?int $userId = null, ?string $remark = null): RemoteCommand
    {
        return DB::transaction(function () use ($machine, $slotNo, $userId, $remark) {
            $slot = $machine->slots()->where('slot_no', $slotNo)->lockForUpdate()->firstOrFail();

            // 併行檢查：若有 pending 指令，超過 1 分鐘視為逾時可覆蓋
            $pendingCommand = RemoteCommand::where('machine_id', $machine->id)
                ->where('command_type', 'dispense')
                ->where('status', 'pending')
                ->whereJsonContains('payload->slot_no', (string)$slotNo)
                ->latest()
                ->first();

            if ($pendingCommand) {
                $isTimedOut = $pendingCommand->created_at->lt(now()->subMinute());

                if (!$isTimedOut) {
                    throw new \Exception(__('This slot has a pending command. Please wait.'));
                }

                // 超過 1 分鐘：視為逾時，自動標記舊指令
                $pendingCommand->update([
                    'status' => 'timeout',
                    'note'   => __('Superseded by new command (Timeout)'),
                ]);
            }

            if ($slot->stock <= 0) {
                throw new \Exception(__('Out of stock.'));
            }

            // 1. 建立指令紀錄 (不在此處扣庫存，等候交易結案上報)
            $command = RemoteCommand::create([
                'machine_id' => $machine->id,
                'user_id' => $userId,
                'command_type' => 'dispense',
                'status' => 'pending',
                'remark' => $remark,
                'payload' => [
                    'slot_no' => (string)$slotNo,
                    'current_stock' => $slot->stock,
                ]
            ]);

            // 2. 推播 MQTT
            app(\App\Services\Machine\MqttService::class)->pushCommand(
                $machine->serial_no,
                'dispense',
                $command->payload,
                (string) $command->id
            );

            return $command;
        });
    }


    /**
     * B013: Record machine hardware error/status log with auto-translation.
     * 
     * @param Machine $machine
     * @param array $data
     * @return MachineLog
     */
    public function recordErrorLog(Machine $machine, array $data): MachineLog
    {
        $errorCode = $data['error_code'] ?? '0000';
        $mapping = self::ERROR_CODE_MAP[$errorCode] ?? ['label' => 'Unknown Status', 'level' => 'error'];
        
        $slotNo = $data['tid'] ?? null;
        $label = $mapping['label'];
        
        // 儲存原始英文格式作為 DB 備用，前端顯示會優先使用 model accessor 的動態翻譯內容
        $message = $slotNo ? "Slot {$slotNo}: {$label} (Code: {$errorCode})" : "{$label} (Code: {$errorCode})";

        return $machine->logs()->create([
            'company_id' => $machine->company_id,
            'type' => 'submachine',
            'level' => $mapping['level'],
            'message' => $message,
            'context' => array_merge($data, [
                'translated_label' => $label,
                'raw_code' => $errorCode
            ]),
        ]);
    }

    /**
     * Update machine slot stock (single slot).
     * Legacy support for recordLog (Existing code).
     */
    public function recordLog(int $machineId, array $data): MachineLog
    {
        $machine = Machine::findOrFail($machineId);

        return $machine->logs()->create([
            'level' => $data['level'] ?? 'info',
            'message' => $data['message'],
            'context' => $data['context'] ?? null,
        ]);
    }

    /**
     * 取得艦隊整體統計（批次查詢，無 N+1）
     */
    public function getFleetStats(string $date): array
    {
        $start = Carbon::parse($date)->startOfDay();
        $end   = Carbon::parse($date)->endOfDay();

        $machines   = Machine::all(); // TenantScoped 已過濾
        $machineIds = $machines->pluck('id')->toArray();
        $totalMachines = $machines->count();
        $onlineCount   = Machine::online()->count();

        // 今日成功訂單：批次查詢，非逐台
        $ordersData = Order::whereIn('machine_id', $machineIds)
            ->where('payment_status', 1)
            ->whereBetween('created_at', [$start, $end])
            ->selectRaw('COUNT(*) as total_count, COALESCE(SUM(pay_amount), 0) as total_revenue')
            ->first();

        // 今日異常次數
        $alertCount = MachineLog::whereIn('machine_id', $machineIds)
            ->where('level', 'error')
            ->whereBetween('created_at', [$start, $end])
            ->count();

        return [
            'onlineCount'   => $onlineCount,
            'totalMachines' => $totalMachines,
            'totalOrders'   => (int)($ordersData->total_count ?? 0),
            'totalRevenue'  => (float)($ordersData->total_revenue ?? 0),
            'alertCount'    => $alertCount,
            // OEE / avgUptime 已移除，online/offline 直接從 status 讀取
        ];
    }

    /**
     * 取得單台機台詳細統計（接入真實 orders 資料）
     */
    public function getUtilizationStats(Machine $machine, string $date): array
    {
        $start = Carbon::parse($date)->startOfDay();
        $end   = Carbon::parse($date)->endOfDay();

        // 1. 今日成功訂單：筆數 + 金額
        $ordersAgg = $machine->orders()
            ->where('payment_status', 1)
            ->whereBetween('created_at', [$start, $end])
            ->selectRaw('COUNT(*) as cnt, COALESCE(SUM(pay_amount), 0) as rev')
            ->first();

        $salesCount = (int)($ordersAgg->cnt ?? 0);
        $revenue    = (float)($ordersAgg->rev ?? 0);

        // 2. 今日異常次數（dispense_records 失敗 或 machine_logs error）
        $errorCount = $machine->logs()
            ->where('level', 'error')
            ->whereBetween('created_at', [$start, $end])
            ->count();

        // 3. 逐小時銷售量（Bar Chart 用）
        $hourlySales = $machine->orders()
            ->where('payment_status', 1)
            ->whereBetween('created_at', [$start, $end])
            ->selectRaw('HOUR(created_at) as hour, COUNT(*) as count')
            ->groupBy('hour')
            ->pluck('count', 'hour');

        $labels = [];
        $values = [];
        for ($h = 0; $h < 24; $h++) {
            $labels[] = sprintf('%02d:00', $h);
            $values[] = (int)($hourlySales[$h] ?? 0);
        }

        // Flatten 格式，前端直接 spread 使用
        return [
            'output_count' => $salesCount,
            'revenue'      => round($revenue, 0),
            'error_count'  => $errorCount,
            'chart_data'   => [
                'labels' => $labels,
                'values' => $values,
            ],
        ];
    }
}
