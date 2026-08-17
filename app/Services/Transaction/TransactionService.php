<?php

namespace App\Services\Transaction;

use App\Models\Transaction\Order;
use App\Models\Transaction\OrderItem;
use App\Models\Transaction\Invoice;
use App\Models\Transaction\DispenseRecord;
use Illuminate\Support\Facades\DB;
use App\Models\Machine\Machine;
use App\Models\StaffCardLog;
use App\Models\Transaction\PickupCode;
use App\Models\Transaction\PickupCodeLog;
use App\Models\Transaction\PassCodeLog;
use App\Models\Transaction\WelcomeGift;
use App\Models\Transaction\WelcomeGiftLog;
use Illuminate\Support\Facades\Log;
use App\Services\Machine\MachineService;


class TransactionService
{
    protected $machineService;

    public function __construct(MachineService $machineService)
    {
        $this->machineService = $machineService;
    }

    /**
     * Process a new transaction (B600).
     */
    public function processTransaction(array $data): Order
    {
        $flowId = $data['flow_id'] ?? null;

        // 交易生命週期狀態：payload 沒帶就預設 completed。
        // → main(晟崴/中國醫)的成功 finalize 不帶 status，仍得到 'completed'，行為逐字不變。
        $incomingStatus = $data['status'] ?? Order::STATUS_COMPLETED;

        if ($flowId) {
            $existingOrder = Order::withoutGlobalScopes()
                ->where('flow_id', $flowId)
                ->first();
            if ($existingOrder) {
                // 鐵律二：終態(completed/failed/abandoned)一律冪等回傳，不可覆蓋。
                // main 的單永遠是 completed → 等同原本「flow_id 已存在直接 return」的行為，
                // 重送/redelivery 也不會重複建立、不會重複扣庫存。
                if (in_array($existingOrder->status, Order::TERMINAL_STATUSES, true)) {
                    return $existingOrder;
                }
                // 鐵律三：只有「既有單為 pending」才允許狀態轉移（pending → completed/failed/abandoned）。
                // 唯有「先送過 pending」的機台(本分支、flag on)會走到這；main 從不產生 pending → 不可達。
                return $this->transitionOrder($existingOrder, $data, $incomingStatus);
            }
        }

        return DB::transaction(function () use ($data, $incomingStatus) {
            $machine = Machine::where('serial_no', $data['serial_no'])->firstOrFail();

            $paymentStatus = (int) ($data['payment_status'] ?? Order::PAYMENT_STATUS_SUCCESS);

            // Create Order
            $order = Order::create([
                'company_id' => $machine->company_id,
                'flow_id' => $data['flow_id'] ?? null,
                'order_no' => $data['order_no'] ?? $this->generateOrderNo(),
                'machine_id' => $machine->id,
                'member_id' => $data['member_id'] ?? null,
                'total_amount' => $data['total_amount'],
                'discount_amount' => $data['discount_amount'] ?? 0,
                'pay_amount' => $data['pay_amount'],
                'change_amount' => $data['change_amount'] ?? 0,
                // 現金面額明細（僅現金 payment_type=9 會帶；其餘為 null）
                'cash_detail' => $data['cash_detail'] ?? null,
                'points_used' => $data['points_used'] ?? 0,
                'original_amount' => $data['original_amount'] ?? $data['total_amount'],
                'payment_type' => (int) ($data['payment_type'] ?? 0),
                'payment_status' => $paymentStatus,
                'payment_request' => $data['payment_request'] ?? null,
                'payment_response' => $data['payment_response'] ?? null,
                'member_barcode' => $data['member_barcode'] ?? null,
                'code_id' => $data['code_id'] ?? null,
                'welcome_gift_id' => $data['welcome_gift_id'] ?? null,
                'invoice_info' => $data['invoice_info'] ?? null,
                'machine_time' => $data['machine_time'] ?? now(),
                // 付款成功才有 payment_at；pending/failed 無實際付款時間。
                // main = completed + payment_status=1 → now()，與原本一致。
                'payment_at' => $paymentStatus === Order::PAYMENT_STATUS_SUCCESS ? now() : null,
                'status' => $incomingStatus,
                'metadata' => $data['metadata'] ?? null,
                'delivery_status' => (int) ($data['delivery_status'] ?? Order::DELIVERY_STATUS_SUCCESS),
            ]);

            $this->createOrderItems($order, $data['items'] ?? []);

            return $order;
        });
    }

    /**
     * pending 單的狀態轉移（pending → completed/failed/abandoned）。
     * 只更新付款結果相關欄位；商品明細在 pending 階段已建立，不重複建立（避免品項翻倍）。
     */
    protected function transitionOrder(Order $order, array $data, string $newStatus): Order
    {
        return DB::transaction(function () use ($order, $data, $newStatus) {
            $paymentStatus = isset($data['payment_status'])
                ? (int) $data['payment_status']
                : (int) $order->payment_status;

            $order->update([
                'status' => $newStatus,
                'payment_status' => $paymentStatus,
                // payment_type 以本次上報為準：例如手機支付 pending 階段為 4(路由鍵)，
                // 成功 finalize 覆寫為 10(手機支付)，轉移時需一併更新。
                'payment_type' => isset($data['payment_type'])
                    ? (int) $data['payment_type']
                    : $order->payment_type,
                'payment_request' => $data['payment_request'] ?? $order->payment_request,
                'payment_response' => $data['payment_response'] ?? $order->payment_response,
                'pay_amount' => $data['pay_amount'] ?? $order->pay_amount,
                // 商品總額/原始金額：completed finalize 才帶正確金額(pending 階段機台送 0)，
                // 須比照 pay_amount 在轉移時一併更新，否則後台銷售紀錄金額會停在建立時的 0。
                'total_amount' => $data['total_amount'] ?? $order->total_amount,
                'original_amount' => $data['original_amount'] ?? $order->original_amount,
                'discount_amount' => $data['discount_amount'] ?? $order->discount_amount,
                'change_amount' => $data['change_amount'] ?? $order->change_amount,
                // 現金面額明細：completed finalize 才帶（pending 階段無投幣結果）
                'cash_detail' => $data['cash_detail'] ?? $order->cash_detail,
                'points_used' => $data['points_used'] ?? $order->points_used,
                'invoice_info' => $data['invoice_info'] ?? $order->invoice_info,
                'delivery_status' => isset($data['delivery_status'])
                    ? (int) $data['delivery_status']
                    : $order->delivery_status,
                'payment_at' => $paymentStatus === Order::PAYMENT_STATUS_SUCCESS
                    ? ($order->payment_at ?? now())
                    : $order->payment_at,
            ]);

            // pending 階段若未帶明細，補建一次（防禦）。
            if ($order->items()->count() === 0 && !empty($data['items'])) {
                $this->createOrderItems($order, $data['items']);
            }

            return $order->fresh();
        });
    }

    /** 建立訂單品項（沒帶名稱時回資料庫補抓）。 */
    protected function createOrderItems(Order $order, array $items): void
    {
        foreach ($items as $item) {
            $productName = $item['product_name'] ?? null;

            // 如果沒傳名稱，嘗試從資料庫抓取
            if (empty($productName)) {
                $product = \App\Models\Product\Product::find($item['product_id']);
                $productName = $product?->name ?? 'Unknown';
            }

            $order->items()->create([
                'product_id' => $item['product_id'],
                'product_name' => $productName,
                // 消費者選擇的貨道/格子號（App items[].slot_no 帶入）；不管出貨成功或失敗都記錄。
                'slot_no' => $item['slot_no'] ?? null,
                'barcode' => $item['barcode'] ?? null,
                'price' => $item['price'],
                'quantity' => $item['quantity'],
                'subtotal' => $item['price'] * $item['quantity'],
            ]);
        }
    }

    /**
     * Generate a unique order number.
     */
    protected function generateOrderNo(): string
    {
        return 'ORD-' . now()->format('YmdHis') . '-' . strtoupper(bin2hex(random_bytes(3)));
    }

    /**
     * Record Invoice (B601).
     */
    public function recordInvoice(array $data): Invoice
    {
        return DB::transaction(function () use ($data) {
            $machine = Machine::withoutGlobalScopes()->where('serial_no', $data['serial_no'])->firstOrFail();

            $order = null;
            if (!empty($data['flow_id'])) {
                $order = Order::withoutGlobalScopes()->where('flow_id', $data['flow_id'])->first();
            }

            // 處理額外的發票資訊 (business_tax_id, carrier_type)
            $metadata = $data['metadata'] ?? [];
            if (isset($data['business_tax_id']))
                $metadata['business_tax_id'] = $data['business_tax_id'];
            if (isset($data['carrier_type']))
                $metadata['carrier_type'] = $data['carrier_type'];

            $invoiceNo = $data['invoice_no'] ?? null;
            $rtnCode = $data['rtn_code'] ?? null;

            // 狀態：優先採用 App 已判定的 status（新 finalize 流程一定帶 status=pending）；否則才推導。
            // 此推導分支目前僅 MQTT 'invoice' 指令(ProcessInvoice)會走，App 暫未使用，屬防禦性。
            // 重點：有發票號就代表已開立，除非「明確」收到失敗碼才算 failed
            //（部分上報格式只帶 invoice_no、不帶 rtn_code，不可因缺 rtn_code 就誤判失敗）。
            // 綠界 RtnCode == "1" 為成功；其餘非空值才視為明確失敗。
            $status = $data['status'] ?? null;
            if (empty($status)) {
                $explicitFailure = !empty($rtnCode) && (string) $rtnCode !== '1';
                if (!empty($invoiceNo)) {
                    $status = $explicitFailure ? 'failed' : 'issued';
                } else {
                    // 無發票號：明確失敗→failed；否則待開立/待查證→pending
                    $status = $explicitFailure ? 'failed' : 'pending';
                }
            }

            // RelateNumber 冪等鍵：App 未帶時由後台以 flow_id 推導（綠界上限 30 字、限英數）。
            // flow_id 在 finalizeTransaction 入口已前綴機台序號(serial+rawFlowId)，
            // 故此處直接取用即可，輸出與線上既有 serial+rawFlowId 逐字等價，不可再重複加 serial。
            $relateNumber = $data['relate_number'] ?? null;
            if (empty($relateNumber) && !empty($data['flow_id'])) {
                $relateNumber = substr($data['flow_id'], 0, 30);
            }

            $attributes = [
                'company_id' => $machine->company_id,
                'order_id' => $order?->id ?? ($data['order_id'] ?? null),
                'machine_id' => $machine->id,
                'invoice_no' => $invoiceNo,
                'status' => $status,
                'relate_number' => $relateNumber,
                'amount' => $data['amount'] ?? 0,
                'carrier_id' => $data['carrier_id'] ?? null,
                'invoice_date' => $data['invoice_date'] ?? null,
                'machine_time' => $data['machine_time'] ?? null,
                'random_number' => $data['random_number'] ?? ($data['random_no'] ?? null),
                'love_code' => $data['love_code'] ?? null,
                'rtn_code' => $rtnCode,
                'rtn_msg' => $data['rtn_msg'] ?? null,
                'metadata' => $metadata,
            ];

            // 去重：同一 flow_id（一張訂單一張發票）以 updateOrCreate 收斂，
            // 避免 finalize 重送 / 補開回寫產生重複發票列。
            if (!empty($data['flow_id'])) {
                // 終態守衛（防禦縱深）：已開立/已作廢的發票為終態，不得被後續寫入覆蓋，
                // 避免任何重送（含 ProcessInvoice/MQTT invoice 指令）把 issued 打回 pending、清掉發票號。
                $existing = Invoice::withoutGlobalScopes()->where('flow_id', $data['flow_id'])->first();
                if ($existing && in_array($existing->status, [Invoice::STATUS_ISSUED, Invoice::STATUS_VOID], true)) {
                    return $existing;
                }
                return Invoice::withoutGlobalScopes()->updateOrCreate(
                    ['flow_id' => $data['flow_id']],
                    $attributes
                );
            }

            return Invoice::create(array_merge($attributes, ['flow_id' => null]));
        });
    }

    /**
     * Record dispense result (B602).
     */
    public function recordDispense(array $data): DispenseRecord
    {
        return DB::transaction(function () use ($data) {
            // Use withoutGlobalScopes for background job safety
            $machine = Machine::withoutGlobalScopes()->where('serial_no', $data['serial_no'])->firstOrFail();

            $order = null;
            if (!empty($data['flow_id'])) {
                $order = Order::withoutGlobalScopes()->where('flow_id', $data['flow_id'])->first();
            }

            // Find slot with robust matching (handle "1" vs "01")
            $slotNo = $data['slot_no'] ?? null;
            $slot = null;
            if ($slotNo) {
                $slot = $machine->slots()->where('slot_no', (string) $slotNo)->first();
                if (!$slot) {
                    $normalized = ltrim((string) $slotNo, '0');
                    if ($normalized === '')
                        $normalized = '0';
                    $slot = $machine->slots()->get()->first(function ($s) use ($normalized) {
                        $sn = ltrim($s->slot_no, '0');
                        if ($sn === '')
                            $sn = '0';
                        return $sn === $normalized;
                    });
                }
            }

            $record = DispenseRecord::create([
                'company_id' => $machine->company_id,
                'order_id' => $order?->id ?? ($data['order_id'] ?? null),
                'flow_id' => $data['flow_id'] ?? null,
                'machine_id' => $machine->id,
                'slot_no' => $slotNo ?? 'unknown',
                'product_id' => ($data['product_id'] ?? 0) > 0 ? $data['product_id'] : ($slot ? $slot->product_id : null),
                'amount' => $data['amount'] ?? 0,
                'remaining_stock' => $data['remaining_stock'] ?? null,
                'dispense_status' => $data['dispense_status'] ?? 0,
                'member_barcode' => $data['member_barcode'] ?? ($order?->member_barcode ?? null),
                'machine_time' => $data['machine_time'] ?? now(),
                'points_used' => $data['points_used'] ?? 0,
            ]);

            // 執行實體庫存扣除 (真實交易化)
            if ((int) ($data['dispense_status'] ?? 0) === 1) {
                if ($slot) {
                    // 防呆：庫存已為 0（或更低）時不再扣減，避免出現負庫存(-1)。
                    // 正常情況下 B660 已嚴格擋下庫存不足的領藥單；此處為下位機與後台庫存不同步時的最後防線。
                    if ((int) $slot->stock > 0) {
                        $slot->decrement('stock');

                        // 售完（庫存歸 0）即清空效期與批號，避免舊批貨效期殘留。
                        // 場景：日後管理員只補 stock 未改 expiry_date 時，update_inventory 才不會
                        // 把過期日一起下發、把新批貨誤鎖成「暫不販售」。與機台端「賣完清效期」對稱。
                        // 純加法：僅在仍有殘留值時才寫，不影響既有扣庫存行為。
                        if ($slot->stock <= 0 && ($slot->expiry_date !== null || $slot->batch_no !== null)) {
                            $slot->update([
                                'expiry_date' => null,
                                'batch_no' => null,
                            ]);
                        }

                        $type = \App\Models\Machine\MachineStockMovement::TYPE_SALE;

                        $this->machineService->recordStockMovement(
                            $slot,
                            -1,
                            $type,
                            $order ?? $record,
                            $order ? "Sale Order: #{$order->order_no}" : "Dispense Record: #{$record->id}",
                            ['order_id' => $order?->id, 'dispense_id' => $record->id]
                        );
                    } else {
                        \Log::warning("Dispense decrement skipped: slot stock already <= 0", [
                            'machine_id' => $machine->id,
                            'slot_no' => $slotNo,
                            'current_stock' => (int) $slot->stock,
                            'flow_id' => $data['flow_id'] ?? null,
                        ]);
                    }
                } else {
                    // Log warning if slot matching fails to help diagnostic
                    \Log::warning("Dispense record created but slot not found for decrement", [
                        'machine_id' => $machine->id,
                        'slot_no' => $slotNo
                    ]);
                }
            }

            return $record;
        });
    }


    /**
     * Finalize a complete transaction (Unified B600/B601/B602).
     */
    public function finalizeTransaction(array $data): Order
    {
        // 優先從 Root 讀取，再從 order 內讀取
        $rawFlowId = $data['flow_id'] ?? ($data['order']['flow_id'] ?? null);
        if (empty($rawFlowId)) {
            throw new \Exception("Flow ID is required for finalization");
        }

        // 跨機台防撞號：App 端 flow_id 僅單機唯一(YYYYMMDDHHMMSSXXXX)，加上機台序號前綴
        // 才全域唯一(orders.flow_id 是全域 unique)。無分隔符——綠界 RelateNumber 禁特殊符號，
        // 且 serial+flow_id 與線上既有 relateNumber 推導逐字等價(見 recordInvoice)。
        // ACK 仍由 ProcessTransactionFinalized 回傳『原始』flow_id，App / outbox 對帳無感。
        $flowId = $data['serial_no'] . $rawFlowId;
        $data['flow_id'] = $flowId;
        $data['order']['flow_id'] = $flowId;

        return DB::transaction(function () use ($data, $flowId) {
            $serialNo = $data['serial_no'];

            // 冪等性 + 併發防護：終態檢查移進交易內並對既有 row 加排他鎖，
            // 防止兩個 worker 同時穿透 pending 狀態而重複出貨/扣庫存/核銷。
            // main(晟崴/中國醫)的單永遠是 completed → 等同原本的 early-return，行為不變。
            // 既有單為 pending 時放行，往下交給 processTransaction 做 pending → paid/failed 轉移。
            $existingOrder = Order::withoutGlobalScopes()
                ->where('flow_id', $flowId)
                ->lockForUpdate()
                ->first();
            if ($existingOrder && in_array($existingOrder->status, Order::TERMINAL_STATUSES, true)) {
                Log::info("Transaction already finalized (terminal), returning existing order", [
                    'flow_id' => $flowId,
                    'status' => $existingOrder->status,
                ]);
                return $existingOrder;
            }

            // 領藥單(pharmacy_pickup)：機台沿用中國醫(CMUH)出貨流程，finalize 的 flow_id 是「機台本地流水號」，
            // 但 order.order_no = 後端預建的領藥單號(RX…)。故優先以 order_no 對應預建領藥單，避免被當成新銷售單建立。
            $pharmacyOrderNo = $data['order']['order_no'] ?? ($data['order_no'] ?? null);
            if ($pharmacyOrderNo) {
                $pharmacyOrder = Order::withoutGlobalScopes()
                    ->where('order_type', Order::TYPE_PHARMACY_PICKUP)
                    ->where('order_no', $pharmacyOrderNo)
                    ->first();
                if ($pharmacyOrder) {
                    if (in_array($pharmacyOrder->status, Order::TERMINAL_STATUSES, true)) {
                        Log::info("Pharmacy order already finalized (terminal)", [
                            'order_no' => $pharmacyOrderNo,
                            'status' => $pharmacyOrder->status,
                        ]);
                        return $pharmacyOrder; // 冪等
                    }
                    return $this->finalizePharmacyDispense($pharmacyOrder, $data);
                }
            }

            // 後備：若 flow_id 本身就對到領藥單（例如機台有正確帶 RX flow_id），也走領藥流程。
            if ($existingOrder && $existingOrder->order_type === Order::TYPE_PHARMACY_PICKUP) {
                return $this->finalizePharmacyDispense($existingOrder, $data);
            }

            // 1. Process Order (B600)
            $orderData = $data['order'];
            $orderData['serial_no'] = $serialNo;

            // 確保提取 payment_type 與 flow_id (可能在 root 或 order 內)
            $orderData['flow_id'] = $flowId;
            if (!isset($orderData['delivery_status']) && !empty($data['dispense'])) {
                $orderData['delivery_status'] = $this->resolveDeliveryStatusFromDispense($data['dispense']);
            }
            if (!isset($orderData['payment_type']) && isset($data['payment_type'])) {
                $orderData['payment_type'] = $data['payment_type'];
            }
            $order = $this->processTransaction($orderData);

            // 2. Record Invoice — 後台開立版：finalize 帶 invoice 輸入(status=pending)時建 pending 發票，
            //    commit 後派 IssueInvoiceJob 去綠界開立（不在交易內等 ECPay，避免長交易）。
            //    開立條件：機台「電子發票開關」開啟 AND 上報帶 invoice，兩者皆成立才開立。
            //    機台關閉電子發票時略過開立（不建發票、不送綠界），僅留 warning log 供日後追查。
            if (!empty($data['invoice']) && $order->machine?->tax_invoice_enabled) {
                $invoiceData = $data['invoice'];
                $invoiceData['serial_no'] = $serialNo;
                $invoiceData['flow_id'] = $order->flow_id;
                $invoiceData['order_id'] = $order->id;
                $invoice = $this->recordInvoice($invoiceData);

                // 待開立 → commit 後非同步開立（已開/失敗則不重複觸發）
                if ($invoice->status === Invoice::STATUS_PENDING) {
                    DB::afterCommit(function () use ($invoice) {
                        \App\Jobs\Transaction\IssueInvoiceJob::dispatch($invoice->id);
                    });
                }
            } elseif (!empty($data['invoice'])) {
                // 上報帶了 invoice，但機台「電子發票開關」為關閉 → 略過開立，留軌跡供追查。
                Log::warning('機台已關閉電子發票，略過開立', [
                    'machine_id' => $order->machine_id,
                    'flow_id' => $order->flow_id,
                    'order_id' => $order->id,
                ]);
            }

            // 3. Record Dispense Results (B602) - Optional/Multiple
            $dispensedSlots = []; // 收集實際出貨貨道/櫃號，供取貨碼核銷日誌記錄「真正取貨的貨道」
            if (!empty($data['dispense'])) {
                $dispenseList = isset($data['dispense'][0]) ? $data['dispense'] : [$data['dispense']];
                foreach ($dispenseList as $dispenseItem) {
                    $dispenseItem['serial_no'] = $serialNo;
                    $dispenseItem['flow_id'] = $order->flow_id;
                    $dispenseItem['order_id'] = $order->id;

                    // 自動繼承：如果出貨紀錄沒帶 Barcode，就用訂單的
                    if (empty($dispenseItem['member_barcode']) && !empty($order->member_barcode)) {
                        $dispenseItem['member_barcode'] = $order->member_barcode;
                    }

                    if (!empty($dispenseItem['slot_no'])) {
                        $dispensedSlots[] = (string) $dispenseItem['slot_no'];
                    }

                    $this->recordDispense($dispenseItem);
                }
            }

            // 4. 閉環勾稽 (Closed Loop Check)
            $codeId = $data['order']['code_id'] ?? ($data['code_id'] ?? null);
            $paymentType = (int) $order->payment_type;

            if ($codeId) {
                switch ($paymentType) {
                    case 41: // 員工卡 (Staff Card)
                        // 建立員工卡核銷日誌
                        StaffCardLog::create([
                            'company_id' => $order->company_id,
                            'machine_id' => $order->machine_id,
                            'staff_card_id' => $codeId,
                            'payment_type' => 41,
                            'code_id' => $codeId,
                            'order_id' => $order->id,
                            'action' => 'consume',
                            'remark' => "MQTT 交易完成自動核銷，訂單: #{$order->id}"
                        ]);
                        break;

                    case 6: // 取貨碼 (Pickup Code)
                        // 排他鎖：防並發核銷 lost-update 使 usage_count 突破 usage_limit。
                        $pickupCode = PickupCode::where('machine_id', $order->machine_id)
                            ->where('id', $codeId)
                            ->where('status', 'active')
                            ->lockForUpdate()
                            ->first();
                        if ($pickupCode) {
                            $newCount = $pickupCode->usage_count + 1;
                            $isUsedUp = $newCount >= $pickupCode->usage_limit;

                            $pickupCode->update([
                                'order_id' => $order->id,
                                'usage_count' => $newCount,
                                'status' => $isUsedUp ? 'used' : 'active',
                                'used_at' => $isUsedUp ? now() : $pickupCode->used_at
                            ]);

                            // 建立核銷日誌 (閉環最後一哩路)
                            PickupCodeLog::create([
                                'company_id' => $order->company_id,
                                'machine_id' => $order->machine_id,
                                'pickup_code_id' => $pickupCode->id,
                                'order_id' => $order->id,
                                'action' => $isUsedUp ? 'used' : 'consume',
                                'remark' => "MQTT 交易完成自動核銷 (" . ($newCount) . "/" . $pickupCode->usage_limit . ")，訂單: #{$order->id}",
                                'raw_data' => [
                                    // 取貨碼綁「商品」時 pickupCode->slot_no 為 null；
                                    // 改記「實際出貨的貨道/櫃號」(dispense)，取不到才退回取貨碼綁定貨道。
                                    'slot' => !empty($dispensedSlots)
                                        ? implode(', ', array_unique($dispensedSlots))
                                        : $pickupCode->slot_no,
                                    'code' => $pickupCode->code,
                                    'count' => $newCount,
                                    'limit' => $pickupCode->usage_limit
                                ]
                            ]);
                        }
                        break;

                    case 5: // 通行碼 (Pass Code)
                        // 注意：通行碼「用掉一次」的計數改在 B670 驗證(verifyPassCode)當下處理，
                        // 因為 App 對通行碼會略過交易結案上報，此 case 5 實務上不會被觸發。
                        // 建立通行碼核銷日誌
                        PassCodeLog::create([
                            'company_id' => $order->company_id,
                            'machine_id' => $order->machine_id,
                            'pass_code_id' => $codeId,
                            'order_id' => $order->id,
                            'action' => 'completed',
                            'remark' => "MQTT 交易完成自動紀錄取貨，訂單: #{$order->id}"
                        ]);
                        break;
                }
            }

            // 處理來店禮 (Welcome Gift) 自動核銷
            $welcomeGiftId = $data['order']['welcome_gift_id'] ?? ($data['welcome_gift_id'] ?? null);
            if ($welcomeGiftId) {
                // 排他鎖：防並發核銷 lost-update 使 usage_count 突破 usage_limit。
                $welcomeGift = WelcomeGift::where('machine_id', $order->machine_id)
                    ->where('id', $welcomeGiftId)
                    ->where('status', 'active')
                    ->lockForUpdate()
                    ->first();
                if ($welcomeGift) {
                    $newCount = $welcomeGift->usage_count + 1;
                    $isUsedUp = ($welcomeGift->usage_type === 'once' && $newCount >= ($welcomeGift->usage_limit ?? 1));

                    $welcomeGift->update([
                        'usage_count' => $newCount,
                        'status' => $isUsedUp ? 'used' : 'active',
                    ]);

                    WelcomeGiftLog::create([
                        'company_id' => $order->company_id,
                        'machine_id' => $order->machine_id,
                        'welcome_gift_id' => $welcomeGift->id,
                        'order_id' => $order->id,
                        'action' => 'consume',
                        'remark' => "MQTT 交易完成自動核銷，訂單: #{$order->id}",
                        'raw_data' => [
                            'code' => $welcomeGift->code,
                            'count' => $newCount,
                            'type' => $welcomeGift->usage_type
                        ]
                    ]);
                }
            }


            return $order;
        });
    }

    /**
     * 後台手動補單（機台斷線／漏報時，由系統管理員人工補登銷售紀錄）。
     *
     * 與機台上報共用底層 recordDispense()（扣庫存）與 recordInvoice()+IssueInvoiceJob（後台開發票），
     * 但訂單標記 order_type=manual、created_by=操作者；flow_id 以 'MAN' 前綴與機台上報區隔，
     * 一眼可辨識並天然避開 orders.flow_id 唯一鍵衝突（長度 ≤30、純英數，亦相容綠界 RelateNumber）。
     * 扣庫存與開發票皆為選配（由呼叫端決定）；權限把關（限系統管理員）在 Controller。
     *
     * @param array $data {
     *   serial_no, created_by, payment_type, occurred_at?,
     *   items: [{product_id, slot_no?, product_name?, price, quantity}],
     *   deduct_stock?: bool, remark?,
     *   invoice?: null|{business_tax_id?, carrier_id?, love_code?}  // 給定才開立
     * }
     */
    public function createManualOrder(array $data): Order
    {
        $machine = Machine::where('serial_no', $data['serial_no'])->firstOrFail();

        $items = $data['items'] ?? [];
        if (empty($items)) {
            throw new \InvalidArgumentException('Manual order requires at least one item');
        }

        $totalAmount = collect($items)
            ->sum(fn ($i) => (float) $i['price'] * (int) $i['quantity']);

        // 補單常為事後補登：machine_time / created_at 採實際發生時間，使銷售報表落在正確日期。
        $occurredAt = !empty($data['occurred_at'])
            ? \Carbon\Carbon::parse($data['occurred_at'])
            : now();

        $flowId = $this->generateManualFlowId();

        return DB::transaction(function () use ($machine, $flowId, $items, $totalAmount, $occurredAt, $data) {
            // 1. 建立訂單（終態 completed + 付款成功）
            $order = Order::create([
                'company_id'      => $machine->company_id,
                'flow_id'         => $flowId,
                'order_no'        => $this->generateOrderNo(),
                'order_type'      => Order::TYPE_MANUAL,
                'created_by'      => $data['created_by'] ?? null,
                'machine_id'      => $machine->id,
                'payment_type'    => (int) ($data['payment_type'] ?? 0),
                'total_amount'    => $totalAmount,
                'original_amount' => $totalAmount,
                'discount_amount' => 0,
                'pay_amount'      => $totalAmount,
                'payment_status'  => Order::PAYMENT_STATUS_SUCCESS,
                'payment_at'      => $occurredAt,
                'machine_time'    => $occurredAt,
                'status'          => Order::STATUS_COMPLETED,
                'delivery_status' => Order::DELIVERY_STATUS_SUCCESS,
                'remark'          => $data['remark'] ?? null,
                'metadata'        => ['source' => 'manual'],
            ]);
            // created_at 對齊實際發生時間（補單事後補登，報表需落在真實日期而非補登當下）
            $order->forceFill(['created_at' => $occurredAt])->save();

            $this->createOrderItems($order, $items);

            // 2. 扣庫存（選配）：逐單位呼叫 recordDispense，與機台上報一致（成功才扣 1，庫存為 0 時不為負）
            if (!empty($data['deduct_stock'])) {
                foreach ($items as $item) {
                    $qty = max(1, (int) ($item['quantity'] ?? 1));
                    for ($n = 0; $n < $qty; $n++) {
                        $this->recordDispense([
                            'serial_no'       => $machine->serial_no,
                            'flow_id'         => $flowId,
                            'order_id'        => $order->id,
                            'slot_no'         => $item['slot_no'] ?? null,
                            'product_id'      => $item['product_id'] ?? null,
                            'amount'          => $item['price'],
                            'dispense_status' => 1,
                            'machine_time'    => $occurredAt,
                        ]);
                    }
                }
            }

            // 3. 開立電子發票（選配）：建 pending 發票 → commit 後派 IssueInvoiceJob 去綠界開立。
            //    閘門：唯有機台「電子發票開關」開啟才開立（防禦縱深，與 finalizeTransaction 一致；前端已隱藏選項）。
            if (!empty($data['invoice']) && $machine->tax_invoice_enabled) {
                $invoiceData = $data['invoice'];
                $invoiceData['serial_no']    = $machine->serial_no;
                $invoiceData['flow_id']      = $flowId;
                $invoiceData['order_id']     = $order->id;
                $invoiceData['amount']       = $totalAmount;
                $invoiceData['status']       = Invoice::STATUS_PENDING;
                $invoiceData['machine_time'] = $occurredAt;
                $invoice = $this->recordInvoice($invoiceData);

                if ($invoice->status === Invoice::STATUS_PENDING) {
                    DB::afterCommit(function () use ($invoice) {
                        \App\Jobs\Transaction\IssueInvoiceJob::dispatch($invoice->id);
                    });
                }
            } elseif (!empty($data['invoice'])) {
                Log::warning('手動補單要求開立發票，但機台已關閉電子發票，略過開立', [
                    'machine_id' => $machine->id,
                    'flow_id'    => $flowId,
                    'order_id'   => $order->id,
                ]);
            }

            return $order;
        });
    }

    /** 產生手動補單專用 flow_id（'MAN' 前綴，全域唯一、純英數、≤30 字）。 */
    protected function generateManualFlowId(): string
    {
        do {
            $flowId = 'MAN' . now()->format('YmdHis') . strtoupper(bin2hex(random_bytes(3)));
        } while (Order::withoutGlobalScopes()->where('flow_id', $flowId)->exists());

        return $flowId;
    }

    /**
     * 領藥單出貨回報（獨立於銷售流程）。
     *
     * 機台逐道出貨後，以 transaction/finalize 帶 dispense[]（每出貨一單位一筆，含 slot_no/dispense_status）回報。
     * 本方法：建立出貨紀錄（沿用 recordDispense：成功則扣 1 庫存）→ 彙整 delivery_status →
     * 將訂單轉為終態 completed（冪等：重送會在 finalizeTransaction 開頭 early-return）→ 標記領藥碼已領 + 核銷日誌。
     */
    protected function finalizePharmacyDispense(Order $order, array $data): Order
    {
        return DB::transaction(function () use ($order, $data) {
            $serialNo = $data['serial_no'] ?? $order->machine?->serial_no;

            if (!empty($data['dispense'])) {
                $dispenseList = isset($data['dispense'][0]) ? $data['dispense'] : [$data['dispense']];
                foreach ($dispenseList as $dispenseItem) {
                    $dispenseItem['serial_no'] = $serialNo;
                    $dispenseItem['flow_id'] = $order->flow_id;
                    $dispenseItem['order_id'] = $order->id;
                    $this->recordDispense($dispenseItem); // 建 DispenseRecord；dispense_status=1 時扣 1 庫存
                }
                $deliveryStatus = $this->resolveDeliveryStatusFromDispense($data['dispense']);
            } else {
                // 無出貨明細視為失敗
                $deliveryStatus = Order::DELIVERY_STATUS_FAILED;
            }

            $order->update([
                'delivery_status' => $deliveryStatus,
                'status'          => Order::STATUS_COMPLETED, // 終態 → 後續重送冪等
                'machine_time'    => $data['machine_time'] ?? $order->machine_time,
            ]);

            // 標記領藥碼已領（B660 通常已標 used；此處保險並留核銷日誌）
            $pickupCode = $order->pickupCode;
            if ($pickupCode) {
                if ($pickupCode->status !== 'used') {
                    $pickupCode->update(['status' => 'used', 'used_at' => $pickupCode->used_at ?? now()]);
                }
                PickupCodeLog::create([
                    'company_id'     => $order->company_id,
                    'machine_id'     => $order->machine_id,
                    'pickup_code_id' => $pickupCode->id,
                    'order_id'       => $order->id,
                    'action'         => 'used',
                    'remark'         => "log.pickup.pharmacy_dispensed",
                    'raw_data'       => [
                        'order_no'        => $order->order_no,
                        'delivery_status' => $deliveryStatus,
                    ],
                ]);
            }

            Log::info('Pharmacy pickup dispense finalized', [
                'flow_id' => $order->flow_id,
                'delivery_status' => $deliveryStatus,
            ]);

            return $order;
        });
    }

    /**
     * Resolve order-level delivery status from item-level dispense results.
     */
    protected function resolveDeliveryStatusFromDispense(array $dispense): int
    {
        $dispenseList = isset($dispense[0]) ? $dispense : [$dispense];
        $statuses = collect($dispenseList)
            ->filter(fn ($item) => is_array($item) && array_key_exists('dispense_status', $item))
            ->map(fn ($item) => (int) $item['dispense_status']);

        if ($statuses->isEmpty()) {
            return Order::DELIVERY_STATUS_SUCCESS;
        }

        $successCount = $statuses
            ->filter(fn ($status) => $status === Order::DELIVERY_STATUS_SUCCESS)
            ->count();

        if ($successCount === $statuses->count()) {
            return Order::DELIVERY_STATUS_SUCCESS;
        }

        if ($successCount === 0) {
            return Order::DELIVERY_STATUS_FAILED;
        }

        return Order::DELIVERY_STATUS_PARTIAL;
    }
}
