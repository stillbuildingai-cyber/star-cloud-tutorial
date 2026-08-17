<?php

namespace App\Services\Transaction;

use App\Models\Machine\Machine;
use App\Models\Machine\MachineSlot;
use App\Models\Product\Product;
use App\Models\System\SystemOperationLog;
use App\Models\Transaction\Order;
use App\Models\Transaction\PickupCode;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * 領藥單建立服務。
 *
 * 設計重點（對齊交叉檢查修正）：
 *  - 領藥單序號 = orders.flow_id（唯一），讓後續出貨 finalize 能套用既有終態冪等保護。
 *  - 建單時「不」預扣庫存；庫存僅在機台出貨 finalize 時扣一次（避免雙重扣減）。
 *  - 以「商品」為單位建單，貨道於 B660 掃碼時再由 product_id × machine_slots 解析（一張單可多貨道）。
 */
class PharmacyPickupService
{
    /** 領藥單支付類型碼：沿用 42 = 領藥憑證 (Pickup Voucher)。 */
    public const PAYMENT_TYPE_PHARMACY = 42;

    /**
     * 取得某機台「已生成但尚未領取(awaiting_pickup)」的領藥單預留量（依商品彙總）。
     * 用於建單時「預扣」顯示與防超領：可用 = 實體庫存 − 預留量。
     */
    public function reservedQtyMap(Machine $machine): array
    {
        $map = [];
        $orders = Order::where('machine_id', $machine->id)
            ->where('order_type', Order::TYPE_PHARMACY_PICKUP)
            ->where('status', Order::STATUS_AWAITING_PICKUP)
            ->with('items')
            ->get();

        foreach ($orders as $order) {
            foreach ($order->items as $item) {
                $map[$item->product_id] = ($map[$item->product_id] ?? 0) + (int) $item->quantity;
            }
        }

        return $map;
    }

    /**
     * 取得某機台目前「有庫存」的可領藥品（依商品彙總，跨貨道加總庫存）。
     * available 已扣除「尚未領取的領藥單預留量」（生成領藥單即預扣）。
     */
    public function availableProducts(Machine $machine): Collection
    {
        $reserved = $this->reservedQtyMap($machine);

        return MachineSlot::where('machine_id', $machine->id)
            ->where('is_active', true)
            ->where('stock', '>', 0)
            ->with('product')
            ->get()
            ->groupBy('product_id')
            ->map(function ($slots) use ($reserved) {
                $product = $slots->first()->product;
                if (!$product) {
                    return null;
                }

                $physical = (int) $slots->sum('stock');
                $reservedQty = (int) ($reserved[$product->id] ?? 0);
                $available = max(0, $physical - $reservedQty);

                return (object) [
                    'product_id' => $product->id,
                    'name'       => $product->localized_name ?? $product->name,
                    'spec'       => $product->localized_spec ?? $product->spec,
                    'barcode'    => $product->barcode,
                    'price'      => (float) ($product->price ?? 0),
                    'available'  => $available,    // 預扣後可用（= 實體 − 預留）
                    'physical'   => $physical,     // 實體庫存
                    'reserved'   => $reservedQty,  // 已生成領藥單預留量
                    'slot_count' => $slots->count(),
                    'slots'      => $slots->pluck('slot_no')->implode(', '),
                ];
            })
            ->filter()
            ->sortBy('name')
            ->values();
    }

    /**
     * 建立一張領藥單：Order(pharmacy_pickup) + N 個 OrderItem + 1 個 PickupCode。
     *
     * @param array{machine_id:int, pricing_slip_no?:string, items:array<array{product_id:int, quantity:int}>, expires_at?:\DateTimeInterface} $data
     */
    public function create(array $data, int $userId): Order
    {
        $machine = Machine::findOrFail($data['machine_id']);
        $items = collect($data['items'] ?? [])
            ->map(fn ($i) => ['product_id' => (int) $i['product_id'], 'quantity' => (int) $i['quantity']])
            ->filter(fn ($i) => $i['quantity'] > 0)
            ->values();

        if ($items->isEmpty()) {
            throw ValidationException::withMessages([
                'items' => __('Please select at least one medicine with quantity.'),
            ]);
        }

        return DB::transaction(function () use ($machine, $items, $data, $userId) {
            $orderItemsData = [];
            $total = 0.0;

            // 既有「尚未領取領藥單」的預留量（本單尚未建立，不含本單）→ 可用 = 實體 − 預留，防超領。
            $reserved = $this->reservedQtyMap($machine);

            foreach ($items as $item) {
                $product = Product::findOrFail($item['product_id']);
                $qty = $item['quantity'];

                // 該機台此商品跨貨道可用庫存 = 實體庫存 − 已生成領藥單預留量
                $physical = (int) MachineSlot::where('machine_id', $machine->id)
                    ->where('product_id', $product->id)
                    ->where('is_active', true)
                    ->sum('stock');
                $available = $physical - (int) ($reserved[$product->id] ?? 0);

                if ($qty > $available) {
                    throw ValidationException::withMessages([
                        'items' => __('Insufficient stock for :name (requested :qty, available :available).', [
                            'name' => $product->localized_name ?? $product->name,
                            'qty' => $qty,
                            'available' => $available,
                        ]),
                    ]);
                }

                $price = (float) ($product->price ?? 0);
                $subtotal = $price * $qty;
                $total += $subtotal;

                $orderItemsData[] = [
                    'product_id'   => $product->id,
                    'product_name' => $product->localized_name ?? $product->name,
                    'barcode'      => $product->barcode,
                    'quantity'     => $qty,
                    'price'        => $price,
                    'subtotal'     => $subtotal,
                ];
            }

            $serial = $this->generateSerial();

            $order = Order::create([
                'company_id'      => $machine->company_id,
                'flow_id'         => $serial,
                'order_no'        => $serial,
                'order_type'      => Order::TYPE_PHARMACY_PICKUP,
                'pricing_slip_no' => $data['pricing_slip_no'] ?? null,
                'created_by'      => $userId,
                'machine_id'      => $machine->id,
                'payment_type'    => self::PAYMENT_TYPE_PHARMACY,
                'total_amount'    => $total,
                'discount_amount' => 0,
                'pay_amount'      => 0,
                'change_amount'   => 0,
                'points_used'     => 0,
                'payment_status'  => Order::PAYMENT_STATUS_SUCCESS,
                'status'          => Order::STATUS_AWAITING_PICKUP,
                'delivery_status' => Order::DELIVERY_STATUS_FAILED, // 0：尚未出貨
                'machine_time'    => now(),
            ]);

            foreach ($orderItemsData as $oi) {
                $order->items()->create($oi);
            }

            $pickupCode = PickupCode::create([
                'company_id'  => $machine->company_id,
                'machine_id'  => $machine->id,
                'slot_no'     => null, // 多貨道：由 B660 掃碼時解析
                'code'        => PickupCode::generateUniqueCode($machine->id),
                'status'      => 'active',
                'expires_at'  => $data['expires_at'] ?? now()->endOfDay(), // 預設當日有效
                'usage_limit' => 1,
                'usage_count' => 0,
                'created_by'  => $userId,
                'order_id'    => $order->id,
            ]);

            SystemOperationLog::create([
                'company_id'  => $machine->company_id,
                'user_id'     => $userId,
                'module'      => 'pharmacy_pickup',
                'action'      => 'create',
                'target_id'   => $order->id,
                'target_type' => Order::class,
                'new_values'  => [
                    'order_no'        => $order->order_no,
                    'pricing_slip_no' => $order->pricing_slip_no,
                    'pickup_code'     => $pickupCode->code,
                    'items'           => $orderItemsData,
                ],
            ]);

            return $order->load(['items', 'pickupCode', 'machine']);
        });
    }

    /**
     * 檢查一張領藥單在「目前機台貨道庫存」下是否有商品庫存不足（無法整單出貨）。
     * 回傳不足清單：[['product_name'=>, 'required'=>, 'available'=>], ...]；空陣列表示可整單出貨。
     * 用於 B660 掃碼出貨前的嚴格擋關：任何商品不足即整單擋下、不出貨、不消耗領藥碼。
     */
    public function dispenseShortage(Order $order): array
    {
        $short = [];

        foreach ($order->items as $orderItem) {
            $available = (int) MachineSlot::where('machine_id', $order->machine_id)
                ->where('product_id', $orderItem->product_id)
                ->where('is_active', true)
                ->where('stock', '>', 0)
                ->sum('stock');

            if ($available < (int) $orderItem->quantity) {
                $short[] = [
                    'product_name' => $orderItem->product_name,
                    'required'     => (int) $orderItem->quantity,
                    'available'    => $available,
                ];
            }
        }

        return $short;
    }

    /**
     * 解析一張領藥單在「目前機台貨道庫存」下的出貨清單（一商品可跨多貨道湊量）。
     * 回傳：[['slot_no'=>, 'product_id'=>, 'product_name'=>, 'qty'=>], ...]
     * 庫存不足時盡力分配（出可出的數量；部分/失敗由出貨回報 Phase 標記）。
     */
    public function resolveDispenseItems(Order $order): array
    {
        $result = [];

        foreach ($order->items as $orderItem) {
            $remaining = (int) $orderItem->quantity;

            $slots = MachineSlot::where('machine_id', $order->machine_id)
                ->where('product_id', $orderItem->product_id)
                ->where('is_active', true)
                ->where('stock', '>', 0)
                ->orderByDesc('stock')
                ->orderBy('slot_no')
                ->get();

            foreach ($slots as $slot) {
                if ($remaining <= 0) {
                    break;
                }
                $take = min($remaining, (int) $slot->stock);
                if ($take <= 0) {
                    continue;
                }
                $result[] = [
                    'slot_no'      => $slot->slot_no,
                    'product_id'   => $orderItem->product_id,
                    'product_name' => $orderItem->product_name,
                    'qty'          => $take,
                ];
                $remaining -= $take;
            }
            // $remaining > 0 表示目前庫存不足以完全滿足此商品，僅回傳可出的部分。
        }

        return $result;
    }

    /**
     * 產生領藥單序號：RX + 西元年月日 + 當日 4 碼流水（與 flow_id 唯一性衝突時自動遞增）。
     */
    protected function generateSerial(): string
    {
        $datePart = now()->format('Ymd');
        $base = Order::withoutGlobalScopes()
            ->where('order_type', Order::TYPE_PHARMACY_PICKUP)
            ->whereDate('created_at', now()->toDateString())
            ->count();

        $attempt = 0;
        do {
            $seq = $base + 1 + $attempt;
            $serial = 'RX' . $datePart . str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
            $attempt++;
        } while (
            Order::withoutGlobalScopes()->where('flow_id', $serial)->exists()
            && $attempt < 100
        );

        return $serial;
    }
}
