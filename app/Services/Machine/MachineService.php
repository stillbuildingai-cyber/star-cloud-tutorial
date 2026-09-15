<?php

namespace App\Services\Machine;

use App\Models\Machine\Machine;
use App\Models\Machine\MachineLog;
use Illuminate\Support\Facades\DB;

class MachineService
{
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

}
