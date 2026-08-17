<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Transaction\Order;
use App\Models\Transaction\Invoice;
use App\Models\Transaction\DispenseRecord;
use App\Models\Transaction\PaymentType;
use App\Models\Transaction\OrderItem;
use App\Models\Transaction\PickupCode;
use App\Models\Transaction\PickupCodeLog;
use App\Models\Transaction\PassCode;
use App\Models\Transaction\PassCodeLog;
use App\Models\Transaction\WelcomeGift;
use App\Models\Transaction\WelcomeGiftLog;
use App\Models\Machine\Machine;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

use App\Models\System\SystemOperationLog;

class SalesController extends Controller
{
    // 銷售中心 (銷售&金流紀錄)
    public function index(Request $request)
    {
        $tab = $request->input('tab', 'orders');
        $isAjax = $request->ajax();
        
        // 取得篩選參數
        $search = $request->input('search');
        $machineId = $request->input('machine_id');
        $paymentType = $request->input('payment_type');
        // 付款狀態預設只看「已完成」：避免客戶把 failed(付款失敗/逾時) 誤會成刷卡失敗。
        // 「所有付款狀態」為明確選項 status=all（非空值，AJAX 才不會當空值丟掉）。
        $status = $request->input('status', 'completed');
        $deliveryStatus = $request->input('delivery_status');
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        // 判斷是否為隱含篩選 (未選日期時預設抓6個月)
        $isImplicitFilter = false;
        if (!$startDate || !$endDate) {
            $isImplicitFilter = true;
            $startDate = now()->subMonths(6)->format('Y-m-d H:i');
            $endDate = now()->format('Y-m-d H:i');
        }

        $data = [
            'title' => '銷售&金流紀錄',
            'description' => '銷售交易與金流明細查詢',
            'tab' => $tab,
            'filters' => [
                'search' => $search,
                'machine_id' => $machineId,
                'payment_type' => $paymentType,
                'status' => $status,
                'delivery_status' => $deliveryStatus,
                'start_date' => $request->input('start_date'), // 保留原始輸入以顯示於 UI (如果是隱含的則 UI 顯示空)
                'end_date' => $request->input('end_date'),
            ],
            'isImplicitFilter' => $isImplicitFilter,
            'machines' => Machine::select('id', 'name', 'serial_no')->get(),
            'paymentTypes' => Order::getPaymentTypeLabels(),
        ];

        // 1. 建立基本查詢 (套用共用過濾器：機台、日期)
        $ordersQuery = Order::with([
            'machine:id,name,serial_no', 
            'invoice:id,order_id,invoice_no,status',
            'items',
            'staffCardLog:id,order_id,staff_card_id',
            'staffCardLog.staffCard:id,employee_id,name,card_uid'
        ]);
        $invoicesQuery = Invoice::with(['machine:id,name,serial_no', 'order:id,order_no,flow_id,payment_type']);
        $dispenseQuery = DispenseRecord::with(['order:id,order_no,flow_id', 'machine:id,name,serial_no', 'product:id,name']);

        // 共用過濾器：日期
        $start = Carbon::parse($startDate);
        $end = Carbon::parse($endDate)->endOfMinute();
        
        $ordersQuery->whereBetween('created_at', [$start, $end]);
        // 以 created_at 篩（而非 invoice_date），pending/failed 發票尚無 invoice_date 也能顯示，才看得到缺漏
        $invoicesQuery->whereBetween('created_at', [$start, $end]);
        $dispenseQuery->whereBetween('machine_time', [$start, $end]);

        // 機台權限隔離：非系統管理員只能看到「自己可存取機台」的交易。沿用 Machine 的 machine_access 全域 scope
        // 取得可存取機台集合（與上方機台下拉清單一致），修正訂單/發票/出貨清單先前未依機台授權過濾、
        // 導致無該機台權限的帳號仍可見其交易紀錄的資料隔離漏洞。
        if (!auth()->user()->isSystemAdmin()) {
            $accessibleMachineIds = Machine::pluck('id');
            $ordersQuery->whereIn('machine_id', $accessibleMachineIds);
            $invoicesQuery->whereIn('machine_id', $accessibleMachineIds);
            $dispenseQuery->whereIn('machine_id', $accessibleMachineIds);
        }

        // 共用過濾器：機台
        if ($machineId) {
            $ordersQuery->where('machine_id', $machineId);
            $invoicesQuery->where('machine_id', $machineId);
            $dispenseQuery->where('machine_id', $machineId);
        }

        // 2. 應用獨立過濾器：搜尋 (僅對當前 Tab 應用)
        if ($search) {
            if ($tab === 'orders') {
                $ordersQuery->where(function($q) use ($search) {
                    $q->where('order_no', 'like', "%{$search}%")
                      ->orWhere('flow_id', 'like', "%{$search}%")
                      ->orWhere('invoice_info', 'like', "%{$search}%")
                      ->orWhere('member_barcode', 'like', "%{$search}%")
                      ->orWhereHas('staffCardLog.staffCard', function($sq) use ($search) {
                          $sq->where('name', 'like', "%{$search}%")
                            ->orWhere('employee_id', 'like', "%{$search}%")
                            ->orWhere('card_uid', 'like', "%{$search}%");
                      });
                });
            } elseif ($tab === 'invoices') {
                $invoicesQuery->where(function($q) use ($search) {
                    $q->where('invoice_no', 'like', "%{$search}%")
                      ->orWhere('flow_id', 'like', "%{$search}%");
                });
            } elseif ($tab === 'dispense') {
                $dispenseQuery->where(function($q) use ($search) {
                    $q->where('slot_no', 'like', "%{$search}%")
                      ->orWhereHas('product', function($pq) use ($search) {
                          $pq->where('name', 'like', "%{$search}%");
                      })
                      ->orWhereHas('order', function($oq) use ($search) {
                          $oq->where('order_no', 'like', "%{$search}%");
                      });
                });
            }
        }

        // 訂單專用過濾器
        if ($tab === 'orders') {
            if ($paymentType) $ordersQuery->where('payment_type', $paymentType);
            if ($status && $status !== 'all') $ordersQuery->where('status', $status);
            // 出貨狀態：唯有付款成功(completed/paid)的單才有出貨結果，需與列表顯示邏輯 (hasDeliveryOutcome) 一致
            if ($deliveryStatus !== null && $deliveryStatus !== '') {
                $ordersQuery->whereIn('status', [Order::STATUS_COMPLETED, 'paid'])
                            ->where('delivery_status', $deliveryStatus);
            }
        }

        // 發票專用過濾器：狀態（pending/issued/failed/void）
        if ($tab === 'invoices') {
            $invoiceStatus = $request->input('invoice_status');
            if ($invoiceStatus) $invoicesQuery->where('status', $invoiceStatus);
        }

        // 匯出功能攔截
        if ($request->filled('export')) {
            $exportType = $request->input('export');
            return $this->handleExport($tab, $exportType, $ordersQuery, $invoicesQuery, $dispenseQuery);
        }

        // 3. 執行分頁 (使用獨立的 pageName)
        $perPage = $request->input('per_page', 10);
        $data['orders'] = $ordersQuery->latest()->paginate($perPage, ['*'], 'orders_page')->withQueryString();
        $data['invoices'] = $invoicesQuery->latest()->paginate($perPage, ['*'], 'invoices_page')->withQueryString();
        $data['dispenseLogs'] = $dispenseQuery->latest()->paginate($perPage, ['*'], 'dispense_page')->withQueryString();

        // 出貨失敗補償取貨碼：僅掛載既有碼供顯示；產碼由 + 按鈕手動觸發 generateOrderPickupCode()
        if ($tab === 'orders') {
            $this->attachOrderPickupCodes($data['orders']->getCollection());
        }

        if ($isAjax) {
            return response()->json([
                'success' => true,
                'tab' => $tab,
                'html' => view('admin.sales.partials.tab-' . $tab, $data)->render()
            ]);
        }

        return view('admin.sales.index', $data);
    }

    /**
     * 掛載訂單的補償取貨碼(batch_no='COMP{id}')供銷售頁顯示。只讀不產碼。
     */
    private function attachOrderPickupCodes($orders): void
    {
        if ($orders->isEmpty()) {
            return;
        }
        $batchNos = $orders->map(fn ($o) => 'COMP' . $o->id)->all();
        $codesByBatch = PickupCode::whereIn('batch_no', $batchNos)->get()->groupBy('batch_no');
        foreach ($orders as $order) {
            $order->setRelation('compensationCodes', $codesByBatch->get('COMP' . $order->id, collect()));
        }
    }

    /**
     * 手動產生「出貨失敗補償取貨碼」：由銷售頁 + 按鈕、前端確認後 POST 觸發。
     * 僅限「付款成功(completed/paid)但出貨失敗/部分(delivery 0/2)」的訂單；依每商品缺量各產一碼
     * (綁機台+商品、7 天有效、usage_limit=缺量)；冪等(batch_no='COMP{id}')。
     * 不設 order_id——取貨碼核銷時 order_id 會被回填為「新出貨單」。
     */
    public function generateOrderPickupCode(Request $request, Order $order)
    {
        if (!Auth::user()->isSystemAdmin() && $order->machine_id && !Machine::whereKey($order->machine_id)->exists()) {
            abort(403);
        }

        $isPaid = in_array($order->status, [Order::STATUS_COMPLETED, 'paid'], true);
        $failedDelivery = in_array((int) $order->delivery_status, [Order::DELIVERY_STATUS_FAILED, Order::DELIVERY_STATUS_PARTIAL], true);
        if (!$isPaid || !$failedDelivery || !$order->machine_id) {
            return response()->json(['success' => false, 'message' => __('This order is not eligible for a pickup code.')], 422);
        }

        $batchNo = 'COMP' . $order->id;
        $existing = PickupCode::where('batch_no', $batchNo)->get();
        if ($existing->isNotEmpty()) {
            return response()->json(['success' => true, 'message' => __('Pickup code already generated.'), 'codes' => $existing->pluck('code')]);
        }

        $machine = $order->machine ?: Machine::find($order->machine_id);
        if (!$machine) {
            return response()->json(['success' => false, 'message' => __('Machine not found.')], 422);
        }

        $ordered = $order->items->groupBy('product_id')->map(fn ($g) => (int) $g->sum('quantity'));
        $dispensedOk = DispenseRecord::where('order_id', $order->id)
            ->where('dispense_status', 1)
            ->get()->groupBy('product_id')->map(fn ($g) => (int) $g->sum('amount'));

        $created = [];
        foreach ($ordered as $productId => $orderedQty) {
            if (!$productId) {
                continue;
            }
            $shortfall = $orderedQty - (int) ($dispensedOk[$productId] ?? 0);
            if ($shortfall <= 0) {
                continue;
            }

            $code = PickupCode::create([
                'company_id' => $order->company_id ?? $machine->company_id,
                'machine_id' => $order->machine_id,
                'product_id' => $productId,
                'code' => PickupCode::generateUniqueCode($order->machine_id),
                'slug' => Str::random(16),
                'batch_no' => $batchNo,
                'status' => 'active',
                'usage_limit' => $shortfall,
                'usage_count' => 0,
                'expires_at' => now()->addDays(7),
                'created_by' => Auth::id(),
            ]);
            $created[] = $code->code;

            SystemOperationLog::create([
                'company_id' => $machine->company_id,
                'user_id' => Auth::id(),
                'module' => 'pickup_code',
                'action' => 'create',
                'target_id' => $code->id,
                'target_type' => PickupCode::class,
                'new_values' => $code->toArray() + ['source' => 'failed_delivery_compensation', 'source_order_id' => $order->id],
            ]);
        }

        if (empty($created)) {
            return response()->json(['success' => false, 'message' => __('No undelivered items to compensate.')], 422);
        }

        return response()->json(['success' => true, 'message' => __('Pickup code generated.'), 'codes' => $created]);
    }

    /**
     * 取得單筆交易詳情 (用於 Slide-over)
     */
    public function show(Order $order)
    {
        $order->load([
            'machine', 
            'invoice', 
            'items', 
            'dispenseRecords.product', 
            'staffCardLog:id,order_id,staff_card_id',
            'staffCardLog.staffCard:id,employee_id,name,card_uid'
        ]);
        
        return response()->json([
            'success' => true,
            'html' => view('admin.sales.partials.order-detail-panel', [
                'order' => $order,
                'paymentTypes' => Order::getPaymentTypeLabels()
            ])->render()
        ]);
    }

    /**
     * 手動補單：取得指定機台的貨道（含商品）與電子發票開關，供補單彈窗動態載入。
     * 僅限系統管理員（整個補單功能限系統管理員）。
     */
    public function manualMachineSlots(Request $request)
    {
        if (!Auth::user()->isSystemAdmin()) {
            abort(403);
        }

        $machine = Machine::with(['slots.product:id,name,price'])
            ->findOrFail($request->input('machine_id'));

        $slots = $machine->slots
            ->sortBy(fn ($s) => str_pad(ltrim($s->slot_no, '0') ?: '0', 6, '0', STR_PAD_LEFT))
            ->values()
            ->map(fn ($slot) => [
                'slot_no'      => $slot->slot_no,
                'product_id'   => $slot->product_id,
                'product_name' => $slot->product?->localized_name ?? __('Unknown Product'),
                'price'        => $slot->product?->price !== null ? (float) $slot->product->price : 0,
                'stock'        => (int) $slot->stock,
            ]);

        return response()->json([
            'success' => true,
            'tax_invoice_enabled' => (bool) $machine->tax_invoice_enabled,
            'slots' => $slots,
        ]);
    }

    /**
     * 手動補單：機台斷線／漏報時人工補登銷售紀錄。
     *
     * 僅限系統管理員。複用 TransactionService::createManualOrder（與機台上報共用扣庫存／開發票邏輯），
     * 扣庫存與開立電子發票皆為選配；開發票受機台「電子發票開關」閘門限制（後端再驗一次）。
     */
    public function storeManualOrder(Request $request, \App\Services\Transaction\TransactionService $service)
    {
        // 權限閘門：補單僅限平台系統管理員（前端僅隱藏入口，真正把關在後端，防偽造 POST）。
        if (!Auth::user()->isSystemAdmin()) {
            abort(403, __('Only system administrators can create manual orders'));
        }

        $validated = $request->validate([
            'machine_id'         => ['required', 'exists:machines,id'],
            'payment_type'       => ['required', 'integer', 'in:' . implode(',', array_keys(Order::getPaymentTypeLabels()))],
            'occurred_at'        => ['nullable', 'date'],
            'deduct_stock'       => ['nullable', 'boolean'],
            'remark'             => ['nullable', 'string', 'max:1000'],
            'items'              => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'items.*.slot_no'    => ['nullable', 'string', 'max:20'],
            'items.*.price'      => ['required', 'numeric', 'min:0'],
            'items.*.quantity'   => ['required', 'integer', 'min:1', 'max:999'],
            'issue_invoice'      => ['nullable', 'boolean'],
            'invoice_type'       => ['nullable', 'required_if:issue_invoice,1', 'in:b2c,taxid,mobile,citizen,donation'],
            'invoice_value'      => ['nullable', 'string', 'max:64'],
        ]);

        $machine = Machine::findOrFail($validated['machine_id']);

        // 組裝發票輸入（僅在勾選開立、且機台開關開啟時）。後端閘門：機台關閉電子發票一律不開。
        $invoice = null;
        if (!empty($validated['issue_invoice'])) {
            if (!$machine->tax_invoice_enabled) {
                return $this->manualOrderResponse($request, false, __('This machine has e-invoice disabled; cannot issue'));
            }
            $invoice = $this->buildManualInvoiceInput($validated['invoice_type'] ?? 'b2c', trim((string) ($validated['invoice_value'] ?? '')));
            if (isset($invoice['error'])) {
                return $this->manualOrderResponse($request, false, $invoice['error']);
            }
        }

        try {
            $order = $service->createManualOrder([
                'serial_no'    => $machine->serial_no,
                'created_by'   => Auth::id(),
                'payment_type' => (int) $validated['payment_type'],
                'occurred_at'  => $validated['occurred_at'] ?? null,
                'deduct_stock' => !empty($validated['deduct_stock']),
                'remark'       => $validated['remark'] ?? null,
                'items'        => array_map(fn ($i) => [
                    'product_id' => (int) $i['product_id'],
                    'slot_no'    => $i['slot_no'] ?? null,
                    'price'      => (float) $i['price'],
                    'quantity'   => (int) $i['quantity'],
                ], $validated['items']),
                'invoice'      => $invoice,
            ]);
        } catch (\Throwable $e) {
            \Log::error('Manual order creation failed: ' . $e->getMessage(), [
                'machine_id' => $machine->id,
                'user_id'    => Auth::id(),
            ]);
            return $this->manualOrderResponse($request, false, __('Failed to create manual order'));
        }

        // 審計日誌：留痕「誰／何時／對哪台機台補了什麼、是否開發票/扣庫存」。
        SystemOperationLog::create([
            'company_id'  => $machine->company_id,
            'user_id'     => Auth::id(),
            'module'      => 'manual_order',
            'action'      => 'create',
            'target_id'   => $order->id,
            'target_type' => Order::class,
            'new_values'  => [
                'order_no'      => $order->order_no,
                'flow_id'       => $order->flow_id,
                'machine_id'    => $machine->id,
                'total_amount'  => $order->total_amount,
                'payment_type'  => $order->payment_type,
                'deduct_stock'  => !empty($validated['deduct_stock']),
                'issue_invoice' => $invoice !== null,
                'occurred_at'   => optional($order->machine_time)->toDateTimeString(),
            ],
        ]);

        return $this->manualOrderResponse($request, true, __('Manual order created: :no', ['no' => $order->order_no]));
    }

    /**
     * 將補單表單的發票類型/輸入值轉成 recordInvoice 可用的欄位。
     * reissue() 依 business_tax_id / carrier_id / love_code 自動判別綠界載具類型與 Print 旗標。
     */
    private function buildManualInvoiceInput(string $type, string $value): array
    {
        switch ($type) {
            case 'b2c': // 一般 B2C（綠界會員載具，Print=0，不可列印）
                return [];
            case 'taxid': // 統一編號（Print=1，可列印）
                if (!preg_match('/^\d{8}$/', $value)) {
                    return ['error' => __('Tax ID must be 8 digits')];
                }
                return ['business_tax_id' => $value];
            case 'mobile': // 手機條碼載具（8 碼，/ 開頭）
                if (!preg_match('#^/[0-9A-Z.\-+]{7}$#', $value)) {
                    return ['error' => __('Invalid mobile barcode carrier')];
                }
                return ['carrier_id' => $value];
            case 'citizen': // 自然人憑證載具（16 碼，2 英文 + 14 數字）
                if (!preg_match('/^[A-Z]{2}\d{14}$/', $value)) {
                    return ['error' => __('Invalid citizen digital certificate carrier')];
                }
                return ['carrier_id' => $value];
            case 'donation': // 捐贈（愛心碼 3~7 碼數字）
                if (!preg_match('/^\d{3,7}$/', $value)) {
                    return ['error' => __('Donation love code must be 3 to 7 digits')];
                }
                return ['love_code' => $value];
            default:
                return ['error' => __('Invalid invoice type')];
        }
    }

    /** 補單回應：AJAX 回 JSON、一般表單回 back()。 */
    private function manualOrderResponse(Request $request, bool $success, string $message)
    {
        if ($request->expectsJson()) {
            return response()->json(['success' => $success, 'message' => $message], $success ? 200 : 422);
        }
        return back()->with($success ? 'success' : 'error', $message);
    }

    // 取貨碼設定
    public function pickupCodes(Request $request)
    {
        $tab = $request->input('tab', 'list');
        $isAjax = $request->ajax();

        $data = [
            'title' => '取貨碼設定',
            'description' => '產生與管理商品取貨驗證碼',
            'tab' => $tab,
        ];

        // 批次產生數量上限/快捷選項依角色分級：平台管理員 1000（10/50/100/1000）、客戶端 20（10/20）
        $isSystemAdmin = Auth::user()->isSystemAdmin();
        $data['maxBatchQuantity'] = $isSystemAdmin ? 1000 : 20;
        $data['batchQuickOptions'] = $isSystemAdmin ? [10, 50, 100, 1000] : [10, 20];

        // 1. 取貨碼列表 (list)
        if (!$isAjax || $tab === 'list') {
            // 僅顯示目前帳號可存取機台的取貨碼（機台授權以 machine_user 為準，whereHas 會套用 Machine 的 machine_access 全域 scope）
            $query = PickupCode::with(['machine.slots.product', 'product', 'creator', 'order'])
                ->whereHas('machine')
                ->latest();

            if ($request->search) {
                $query->where(function ($q) use ($request) {
                    $q->where('code', 'like', "%{$request->search}%")
                        ->orWhereHas('machine', function ($mq) use ($request) {
                            $mq->where('name', 'like', "%{$request->search}%")
                                ->orWhere('serial_no', 'like', "%{$request->search}%");
                        });
                });
            }

            if ($request->status && trim($request->status) !== '') {
                $query->where('status', trim($request->status));
            }

            $per_page = $request->input('per_page', 10);
            $data['pickupCodes'] = $query->paginate($per_page, ['*'], 'list_page')->withQueryString();
            
            // 供新增彈窗使用的機台清單
            $data['machines'] = Machine::all();
        }

        // 2. 操作紀錄 (logs)
        if (!$isAjax || $tab === 'logs') {
            // 同上：操作紀錄只顯示可存取機台的資料（machine_id 為 null 的非機台紀錄仍保留）
            $logQuery = PickupCodeLog::with(['user:id,name', 'machine:id,name,serial_no', 'pickupCode', 'order'])
                ->where(function ($q) {
                    $q->whereNull('machine_id')
                        ->orWhereHas('machine');
                });

            if ($request->filled('search_log')) {
                $search = $request->input('search_log');
                $logQuery->where(function ($q) use ($search) {
                    $q->where('remark', 'like', "%{$search}%")
                      ->orWhereHas('pickupCode', function($pq) use ($search) {
                          $pq->where('code', 'like', "%{$search}%");
                      });
                });
            }

            // 新增：類型篩選
            if ($request->filled('action')) {
                $logQuery->where('action', $request->action);
            }

            // 新增：日期區間篩選 (隱含 6 個月限制)
            $startDate = $request->input('start_date');
            $endDate = $request->input('end_date');

            if (!$startDate && !$endDate) {
                $logQuery->where('created_at', '>=', now()->subMonths(6));
            } else {
                try {
                    if ($startDate) {
                        $logQuery->where('created_at', '>=', \Carbon\Carbon::parse($startDate)->startOfMinute());
                    }
                    if ($endDate) {
                        $logQuery->where('created_at', '<=', \Carbon\Carbon::parse($endDate)->endOfMinute());
                    }
                } catch (\Exception $e) { }
            }

            $data['logs'] = $logQuery->latest()
                ->paginate($request->input('per_page', 10), ['*'], 'log_page')
                ->withQueryString();

            // 定義可用動作
            $data['actions'] = [
                'create' => __('create'),
                'update' => __('update'),
                'cancel' => __('cancel'),
                'used' => __('used'),
                'consume' => __('consume'),
                'consume_failed' => __('consume_failed'),
                'verify_success' => __('verify_success'),
            ];
        }

        if ($isAjax) {
            return response()->json([
                'success' => true,
                'tab' => $tab,
                'html' => view('admin.sales.pickup-codes.partials.tab-' . $tab, $data)->render()
            ]);
        }

        return view('admin.sales.pickup-codes.index', $data);
    }

    /**
     * 產生取貨碼
     */
    public function storePickupCode(Request $request)
    {
        // 批次數量上限依角色分級：平台管理員最多 1000 筆；客戶端（租戶）最多 20 筆。
        // 後端強制把關，避免前端被繞過送出大量產碼請求。
        $maxQuantity = Auth::user()->isSystemAdmin() ? 1000 : 20;

        $validated = $request->validate([
            'machine_id' => 'required|exists:machines,id',
            // 取貨碼改綁「商品」：新流程傳 product_id；舊的綁「貨道」流程仍可傳 slot_no（向後相容）。
            'product_id' => 'required_without:slot_no|nullable|exists:products,id',
            'slot_no' => 'required_without:product_id|nullable|string',
            'usage_limit' => 'nullable|integer|min:1|max:20',
            'expires_hours' => 'nullable|integer|min:1|max:720',
            'expires_at' => 'nullable|date|after:now',
            'custom_code' => 'nullable|string|min:4|max:12',
            'quantity' => 'nullable|integer|min:1|max:' . $maxQuantity,
        ]);

        $machine = Machine::findOrFail($validated['machine_id']);
        $quantity = (int) ($validated['quantity'] ?? 1);

        // 綁商品時驗證該商品屬於此機台所屬公司（不要求機台當下有貨道；機台未上架仍可產碼，
        // 由前端提示、刷碼時若無貨道再由 B660 擋下）。
        if (!empty($validated['product_id'])) {
            $belongsToCompany = \App\Models\Product\Product::where('id', $validated['product_id'])
                ->where('company_id', $machine->company_id)
                ->exists();
            if (!$belongsToCompany) {
                return back()->withErrors(['product_id' => __('The selected product does not belong to this machine\'s company.')]);
            }
        }

        // 處理過期時間：優先使用直接傳入的日期，否則使用時數計算
        $expiresAt = $request->filled('expires_at')
            ? Carbon::parse($validated['expires_at'])
            : now()->addHours((int) ($request->expires_hours ?? 24));
        $usageLimit = $validated['usage_limit'] ?? 1;

        // 批次產生（>1 筆）：忽略自訂碼，系統自動產生隨機唯一碼，並以 batch_no 標記同一批。
        // 沿用單筆的綁定方式（優先 product_id，相容舊的 slot_no）。
        if ($quantity > 1) {
            $batchNo = 'PUB' . now()->format('YmdHis') . strtoupper(Str::random(4));
            $codes = $this->generateUniqueCodeBatch(PickupCode::class, $machine->id, $quantity);
            $nowTs = now();

            $rows = [];
            foreach ($codes as $code) {
                $rows[] = [
                    'company_id' => $machine->company_id,
                    'machine_id' => $machine->id,
                    'product_id' => $validated['product_id'] ?? null,
                    'slot_no' => $validated['slot_no'] ?? null,
                    'code' => $code,
                    'slug' => Str::random(16),
                    'batch_no' => $batchNo,
                    'status' => 'active',
                    'usage_limit' => $usageLimit,
                    'usage_count' => 0,
                    'expires_at' => $expiresAt,
                    'created_by' => Auth::id(),
                    'created_at' => $nowTs,
                    'updated_at' => $nowTs,
                ];
            }

            DB::transaction(function () use ($rows) {
                foreach (array_chunk($rows, 500) as $chunk) {
                    PickupCode::insert($chunk);
                }
            });

            SystemOperationLog::create([
                'company_id' => $machine->company_id,
                'user_id' => Auth::id(),
                'module' => 'pickup_code',
                'action' => 'batch_create',
                'target_id' => null,
                'target_type' => PickupCode::class,
                'new_values' => [
                    'batch_no' => $batchNo,
                    'count' => count($rows),
                    'machine_id' => $machine->id,
                    'product_id' => $validated['product_id'] ?? null,
                    'slot_no' => $validated['slot_no'] ?? null,
                ],
            ]);

            return back()
                ->with('success', __(':count pickup codes generated', ['count' => count($rows)]))
                ->with('code_batch', [
                    'type' => 'pickup',
                    'batch_no' => $batchNo,
                    'count' => count($rows),
                    'download_url' => route('admin.sales.pickup-codes.batch-download', $batchNo),
                ]);
        }

        $pickupCode = PickupCode::create([
            'machine_id' => $validated['machine_id'],
            'product_id' => $validated['product_id'] ?? null,
            'slot_no' => $validated['slot_no'] ?? null,
            'code' => $request->custom_code ?? PickupCode::generateUniqueCode($validated['machine_id']),
            'usage_limit' => $validated['usage_limit'] ?? 1,
            'usage_count' => 0,
            'expires_at' => $expiresAt,
            'status' => 'active',
            'company_id' => $machine->company_id,
            'created_by' => Auth::id(),
        ]);

        SystemOperationLog::create([
            'company_id' => $machine->company_id,
            'user_id' => Auth::id(),
            'module' => 'pickup_code',
            'action' => 'create',
            'target_id' => $pickupCode->id,
            'target_type' => PickupCode::class,
            'new_values' => $pickupCode->toArray(),
        ]);

        return back()->with('success', __('Pickup code generated: :code', ['code' => $pickupCode->code]));
    }

    /**
     * 產碼用 AJAX：回傳機台所屬「公司」的商品清單，以及機台目前已上架的 product_id。
     * 商品下拉以公司商品為準（非僅機台貨道）；前端用 machine_product_ids 標示「此機台未上架」提示。
     */
    public function pickupCompanyProducts(Machine $machine)
    {
        $products = \App\Models\Product\Product::where('company_id', $machine->company_id)
            ->select('id', 'name')
            ->orderBy('name')
            ->get();

        $machineProductIds = $machine->slots()
            ->whereNotNull('product_id')
            ->pluck('product_id')
            ->unique()
            ->values();

        return response()->json([
            'success' => true,
            'products' => $products,
            'machine_product_ids' => $machineProductIds,
        ]);
    }

    /**
     * 更新取貨碼 (僅限修改時間)
     */
    public function updatePickupCode(Request $request, PickupCode $pickupCode)
    {
        $validated = $request->validate([
            'expires_at' => 'nullable|date|after:now',
            'usage_limit' => 'nullable|integer|min:1|max:20',
        ]);

        $oldValues = $pickupCode->toArray();

        $pickupCode->update(array_filter([
            'expires_at' => $validated['expires_at'] ?? null,
            'usage_limit' => $validated['usage_limit'] ?? null,
        ]));

        SystemOperationLog::create([
            'company_id' => $pickupCode->company_id,
            'user_id' => Auth::id(),
            'module' => 'pickup_code',
            'action' => 'update',
            'target_id' => $pickupCode->id,
            'target_type' => PickupCode::class,
            'old_values' => $oldValues,
            'new_values' => $pickupCode->toArray(),
        ]);

        return back()->with('success', __('Pickup code updated.'));
    }

    /**
     * 刪除/取消取貨碼
     */
    public function destroyPickupCode(PickupCode $pickupCode)
    {
        // 機台授權以 machine_user 為準：非系統管理員若無法存取該取貨碼所屬機台則拒絕
        if (!Auth::user()->isSystemAdmin() && !Machine::whereKey($pickupCode->machine_id)->exists()) {
            abort(403);
        }

        $oldValues = $pickupCode->toArray();
        $pickupCode->update(['status' => 'cancelled']);

        SystemOperationLog::create([
            'company_id' => $pickupCode->company_id,
            'user_id' => Auth::id(),
            'module' => 'pickup_code',
            'action' => 'cancel',
            'target_id' => $pickupCode->id,
            'target_type' => PickupCode::class,
            'old_values' => $oldValues,
            'new_values' => $pickupCode->toArray(),
        ]);

        return back()->with('success', __('Pickup code cancelled.'));
    }

    // 購買單
    public function orders()
    {
        return view('admin.placeholder', [
            'title' => '購買單',
            'description' => '購買訂單管理',
        ]);
    }

    // 促銷時段設定
    public function promotions()
    {
        return view('admin.placeholder', [
            'title' => '促銷時段設定',
            'description' => '促銷活動時間設定',
        ]);
    }

    // 通行碼設定
    public function passCodes(Request $request)
    {
        $tab = $request->input('tab', 'list');
        $isAjax = $request->ajax();

        $data = [
            'title' => '通行碼設定',
            'description' => '特殊通行權限碼管理 (測試/補貨用)',
            'tab' => $tab,
        ];

        // 批次產生數量上限/快捷選項依角色分級：平台管理員 1000（10/50/100/1000）、客戶端 20（10/20）
        $isSystemAdmin = Auth::user()->isSystemAdmin();
        $data['maxBatchQuantity'] = $isSystemAdmin ? 1000 : 20;
        $data['batchQuickOptions'] = $isSystemAdmin ? [10, 50, 100, 1000] : [10, 20];

        // 1. 通行碼列表 (list)
        if (!$isAjax || $tab === 'list') {
            // 僅顯示目前帳號可存取機台的通行碼（機台授權以 machine_user 為準，whereHas 會套用 Machine 的 machine_access 全域 scope）
            $query = PassCode::with(['machine', 'creator'])
                ->whereHas('machine')
                ->latest();

            if ($request->search) {
                $query->where(function ($q) use ($request) {
                    $q->where('code', 'like', "%{$request->search}%")
                        ->orWhere('name', 'like', "%{$request->search}%")
                        ->orWhereHas('machine', function ($mq) use ($request) {
                            $mq->where('name', 'like', "%{$request->search}%")
                                ->orWhere('serial_no', 'like', "%{$request->search}%");
                        });
                });
            }

            if ($request->status && trim($request->status) !== '') {
                $status = trim($request->status);
                if ($status === 'active') {
                    $query->where('status', 'active')
                        ->where(function ($q) {
                            $q->whereNull('expires_at')
                                ->orWhere('expires_at', '>', now());
                        });
                } elseif ($status === 'expired') {
                    $query->where('status', 'active')
                        ->whereNotNull('expires_at')
                        ->where('expires_at', '<=', now());
                } else {
                    $query->where('status', $status);
                }
            }

            $data['passCodes'] = $query->paginate($request->input('per_page', 10), ['*'], 'list_page')->withQueryString();
            $data['machines'] = Machine::all();
        }

        // 2. 操作紀錄 (logs)
        if (!$isAjax || $tab === 'logs') {
            // 同上：操作紀錄只顯示可存取機台的資料（pass_code_logs.machine_id 為 NOT NULL）
            $logQuery = PassCodeLog::with(['user:id,name', 'machine:id,name,serial_no', 'passCode'])
                ->whereHas('machine');

            if ($request->filled('search_log')) {
                $search = $request->input('search_log');
                $logQuery->where(function ($q) use ($search) {
                    $q->where('remark', 'like', "%{$search}%")
                      ->orWhereHas('passCode', function($pq) use ($search) {
                          $pq->where('code', 'like', "%{$search}%")
                            ->orWhere('name', 'like', "%{$search}%");
                      });
                });
            }

            // 新增：類型篩選
            if ($request->filled('action')) {
                $logQuery->where('action', $request->action);
            }

            // 新增：日期區間篩選 (隱含 6 個月限制)
            $startDate = $request->input('start_date');
            $endDate = $request->input('end_date');

            if (!$startDate && !$endDate) {
                $logQuery->where('created_at', '>=', now()->subMonths(6));
            } else {
                try {
                    if ($startDate) {
                        $logQuery->where('created_at', '>=', \Carbon\Carbon::parse($startDate)->startOfMinute());
                    }
                    if ($endDate) {
                        $logQuery->where('created_at', '<=', \Carbon\Carbon::parse($endDate)->endOfMinute());
                    }
                } catch (\Exception $e) { }
            }

            $data['logs'] = $logQuery->latest()
                ->paginate($request->input('per_page', 10), ['*'], 'log_page')
                ->withQueryString();

            // 定義可用動作
            $data['actions'] = [
                'create' => __('create'),
                'update' => __('update'),
                'cancel' => __('cancel'),
                'used' => __('used'),
                'consume' => __('consume'),
                'verify_success' => __('verify_success'),
            ];
        }

        if ($isAjax) {
            return response()->json([
                'success' => true,
                'tab' => $tab,
                'html' => view('admin.sales.pass-codes.partials.tab-' . $tab, $data)->render()
            ]);
        }

        return view('admin.sales.pass-codes.index', $data);
    }

    /**
     * 產生通行碼
     */
    public function storePassCode(Request $request)
    {
        // 批次數量上限依角色分級：平台管理員最多 1000 筆；客戶端（租戶）最多 20 筆（後端強制把關）。
        $maxQuantity = Auth::user()->isSystemAdmin() ? 1000 : 20;

        $validated = $request->validate([
            'machine_id' => 'required|exists:machines,id',
            'name' => 'required|string|max:50',
            'expires_days' => 'nullable|integer|min:0',
            'custom_code' => 'nullable|string|min:4|max:12',
            'quantity' => 'nullable|integer|min:1|max:' . $maxQuantity,
        ]);

        $machine = Machine::findOrFail($validated['machine_id']);
        $quantity = (int) ($validated['quantity'] ?? 1);
        $expiresAt = $request->expires_days ? now()->addDays((int) $request->expires_days) : null;
        // 勾「只能使用一次」→ 使用上限 1；未勾 → null(無限次，維持原行為)。
        $usageLimit = $request->boolean('single_use') ? 1 : null;

        // 批次產生（>1 筆）：忽略自訂碼，系統自動產生隨機唯一碼，並以 batch_no 標記同一批。
        if ($quantity > 1) {
            $batchNo = 'PSB' . now()->format('YmdHis') . strtoupper(Str::random(4));
            $codes = $this->generateUniqueCodeBatch(PassCode::class, $machine->id, $quantity);
            $nowTs = now();

            $rows = [];
            foreach ($codes as $i => $code) {
                $rows[] = [
                    'company_id' => $machine->company_id,
                    'machine_id' => $machine->id,
                    'name' => $validated['name'] . ' #' . ($i + 1),
                    'code' => $code,
                    'slug' => Str::random(16),
                    'batch_no' => $batchNo,
                    'expires_at' => $expiresAt,
                    'status' => 'active',
                    'usage_limit' => $usageLimit,
                    'usage_count' => 0,
                    'created_by' => Auth::id(),
                    'created_at' => $nowTs,
                    'updated_at' => $nowTs,
                ];
            }

            DB::transaction(function () use ($rows) {
                foreach (array_chunk($rows, 500) as $chunk) {
                    PassCode::insert($chunk);
                }
            });

            SystemOperationLog::create([
                'company_id' => $machine->company_id,
                'user_id' => Auth::id(),
                'module' => 'pass_code',
                'action' => 'batch_create',
                'target_id' => null,
                'target_type' => PassCode::class,
                'new_values' => [
                    'batch_no' => $batchNo,
                    'count' => count($rows),
                    'machine_id' => $machine->id,
                    'name' => $validated['name'],
                ],
            ]);

            return back()
                ->with('success', __(':count pass codes created', ['count' => count($rows)]))
                ->with('code_batch', [
                    'type' => 'pass',
                    'batch_no' => $batchNo,
                    'count' => count($rows),
                    'download_url' => route('admin.sales.pass-codes.batch-download', $batchNo),
                ]);
        }

        $passCode = PassCode::create([
            'machine_id' => $validated['machine_id'],
            'name' => $validated['name'] ?? 'Manual Generate',
            'code' => $request->custom_code ?: PassCode::generateUniqueCode($validated['machine_id']),
            'expires_at' => $expiresAt,
            'status' => 'active',
            'usage_limit' => $usageLimit,
            'usage_count' => 0,
            'company_id' => $machine->company_id,
            'created_by' => Auth::id(),
        ]);

        SystemOperationLog::create([
            'company_id' => $machine->company_id,
            'user_id' => Auth::id(),
            'module' => 'pass_code',
            'action' => 'create',
            'target_id' => $passCode->id,
            'target_type' => PassCode::class,
            'new_values' => $passCode->toArray(),
        ]);

        return back()->with('success', __('Pass code created: :code', ['code' => $passCode->code]));
    }

    /**
     * 批次產生指定數量的唯一 8 位數字碼（避開該機台目前有效的碼，並去除同批重複）
     *
     * @param  class-string<\Illuminate\Database\Eloquent\Model>  $modelClass  PickupCode::class 或 PassCode::class
     * @return string[]
     */
    private function generateUniqueCodeBatch(string $modelClass, int $machineId, int $quantity): array
    {
        // 載入該機台目前仍有效的碼，避免與既有碼衝突
        $existing = array_flip(
            $modelClass::where('machine_id', $machineId)
                ->where('status', 'active')
                ->where(function ($q) {
                    $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
                })
                ->pluck('code')
                ->all()
        );

        $codes = [];
        $seen = [];
        while (count($codes) < $quantity) {
            $code = str_pad((string) random_int(0, 99999999), 8, '0', STR_PAD_LEFT);
            if (isset($existing[$code]) || isset($seen[$code])) {
                continue;
            }
            $seen[$code] = true;
            $codes[] = $code;
        }

        return $codes;
    }

    /**
     * 下載某一批取貨碼清單 (CSV)
     */
    public function downloadPickupCodeBatch(string $batchNo)
    {
        $codes = PickupCode::with(['machine:id,name,serial_no', 'product:id,name'])
            ->where('batch_no', $batchNo)
            ->orderBy('id')
            ->get();

        abort_if($codes->isEmpty(), 404);

        $header = [
            __('Pickup Code'),
            __('Target Machine'),
            __('Select Product'),
            __('Select Slot'),
            __('Usage Limit'),
            __('Expires At'),
            __('Link'),
            __('Created At'),
        ];

        return response()->streamDownload(function () use ($codes, $header) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // UTF-8 BOM，Excel 開啟才不會亂碼
            fputcsv($out, $header);
            foreach ($codes as $c) {
                fputcsv($out, [
                    $c->code,
                    optional($c->machine)->name . ' (' . optional($c->machine)->serial_no . ')',
                    optional($c->product)->name ?? '',
                    $c->slot_no ?? '',
                    $c->usage_limit,
                    optional($c->expires_at)->format('Y-m-d H:i') ?? __('Permanent'),
                    $c->ticket_url,
                    optional($c->created_at)->format('Y-m-d H:i'),
                ]);
            }
            fclose($out);
        }, 'pickup-codes-' . $batchNo . '.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * 下載某一批通行碼清單 (CSV)
     */
    public function downloadPassCodeBatch(string $batchNo)
    {
        $codes = PassCode::with('machine:id,name,serial_no')
            ->where('batch_no', $batchNo)
            ->orderBy('id')
            ->get();

        abort_if($codes->isEmpty(), 404);

        $header = [
            __('Pass Code'),
            __('Description / Name'),
            __('Target Machine'),
            __('Expires At'),
            __('Link'),
            __('Created At'),
        ];

        return response()->streamDownload(function () use ($codes, $header) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, $header);
            foreach ($codes as $c) {
                fputcsv($out, [
                    $c->code,
                    $c->name,
                    optional($c->machine)->name . ' (' . optional($c->machine)->serial_no . ')',
                    optional($c->expires_at)->format('Y-m-d H:i') ?? __('Permanent'),
                    $c->ticket_url,
                    optional($c->created_at)->format('Y-m-d H:i'),
                ]);
            }
            fclose($out);
        }, 'pass-codes-' . $batchNo . '.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * 更新通行碼
     */
    public function updatePassCode(Request $request, PassCode $passCode)
    {
        $validated = $request->validate([
            'name' => 'nullable|string|max:50',
            'expires_at' => 'nullable|date',
            'status' => 'nullable|in:active,disabled',
        ]);

        // 確保 expires_at 為空字串時轉為 null
        if (isset($validated['expires_at']) && empty($validated['expires_at'])) {
            $validated['expires_at'] = null;
        }

        $oldValues = $passCode->toArray();

        $passCode->update(array_filter($validated, function ($value, $key) use ($request) {
            return $request->has($key);
        }, ARRAY_FILTER_USE_BOTH));

        SystemOperationLog::create([
            'company_id' => $passCode->company_id,
            'user_id' => Auth::id(),
            'module' => 'pass_code',
            'action' => 'update',
            'target_id' => $passCode->id,
            'target_type' => PassCode::class,
            'old_values' => $oldValues,
            'new_values' => $passCode->toArray(),
        ]);

        return back()->with('success', __('Pass code updated.'));
    }

    /**
     * 刪除通行碼 (改為停用)
     */
    public function destroyPassCode(PassCode $passCode)
    {
        // 機台授權以 machine_user 為準：非系統管理員若無法存取該通行碼所屬機台則拒絕
        if (!Auth::user()->isSystemAdmin() && !Machine::whereKey($passCode->machine_id)->exists()) {
            abort(403);
        }

        $oldValues = $passCode->toArray();
        $passCode->update(['status' => 'disabled']);

        SystemOperationLog::create([
            'company_id' => $passCode->company_id,
            'user_id' => Auth::id(),
            'module' => 'pass_code',
            'action' => 'cancel',
            'target_id' => $passCode->id,
            'target_type' => PassCode::class,
            'old_values' => $oldValues,
            'new_values' => $passCode->toArray(),
        ]);

        return back()->with('success', __('Pass code cancelled.'));
    }

    // 來店禮設定
    public function storeGifts(Request $request)
    {
        $tab = $request->input('tab', 'list');
        $isAjax = $request->ajax();

        $data = [
            'title' => '來店禮設定',
            'description' => '來店禮優惠碼管理 (單次與無限制來店優惠折抵)',
            'tab' => $tab,
        ];

        // 1. 來店禮列表 (list)
        if (!$isAjax || $tab === 'list') {
            // 僅顯示目前帳號可存取機台的來店禮（機台授權以 machine_user 為準，whereHas 會套用 Machine 的 machine_access 全域 scope）
            $query = WelcomeGift::with(['machine', 'creator'])
                ->whereHas('machine')
                ->latest();

            if ($request->search) {
                $query->where(function ($q) use ($request) {
                    $q->where('code', 'like', "%{$request->search}%")
                        ->orWhere('name', 'like', "%{$request->search}%")
                        ->orWhereHas('machine', function ($mq) use ($request) {
                            $mq->where('name', 'like', "%{$request->search}%")
                                ->orWhere('serial_no', 'like', "%{$request->search}%");
                        });
                });
            }

            if ($request->status && trim($request->status) !== '') {
                $status = trim($request->status);
                if ($status === 'active') {
                    $query->where('status', 'active')
                        ->where(function ($q) {
                            $q->whereNull('expires_at')
                                ->orWhere('expires_at', '>', now());
                        });
                } elseif ($status === 'expired') {
                    $query->where('status', 'active')
                        ->whereNotNull('expires_at')
                        ->where('expires_at', '<=', now());
                } else {
                    $query->where('status', $status);
                }
            }

            $data['welcomeGifts'] = $query->paginate($request->input('per_page', 10), ['*'], 'list_page')->withQueryString();
            $data['machines'] = Machine::all();
        }

        // 2. 操作紀錄 (logs)
        if (!$isAjax || $tab === 'logs') {
            // 同上：操作紀錄只顯示可存取機台的資料（welcome_gift_logs.machine_id 為 NOT NULL）
            $logQuery = WelcomeGiftLog::with(['machine:id,name,serial_no', 'welcomeGift', 'order:id,order_no'])
                ->whereHas('machine');

            if ($request->filled('search_log')) {
                $search = $request->input('search_log');
                $logQuery->where(function ($q) use ($search) {
                    $q->where('remark', 'like', "%{$search}%")
                      ->orWhereHas('welcomeGift', function($pq) use ($search) {
                          $pq->where('code', 'like', "%{$search}%")
                            ->orWhere('name', 'like', "%{$search}%");
                      });
                });
            }

            if ($request->filled('action')) {
                $logQuery->where('action', $request->action);
            }

            // 日期區間篩選 (6 個月限制)
            $startDate = $request->input('start_date');
            $endDate = $request->input('end_date');

            if (!$startDate && !$endDate) {
                $logQuery->where('created_at', '>=', now()->subMonths(6));
            } else {
                try {
                    if ($startDate) {
                        $logQuery->where('created_at', '>=', \Carbon\Carbon::parse($startDate)->startOfMinute());
                    }
                    if ($endDate) {
                        $logQuery->where('created_at', '<=', \Carbon\Carbon::parse($endDate)->endOfMinute());
                    }
                } catch (\Exception $e) { }
            }

            $data['logs'] = $logQuery->latest()
                ->paginate($request->input('per_page', 10), ['*'], 'log_page')
                ->withQueryString();

            // 定義可用動作
            $data['actions'] = [
                'verify_success' => __('verify_success'),
                'consume' => __('consume'),
                'cancel' => __('cancel'),
            ];
        }

        if ($isAjax) {
            return response()->json([
                'success' => true,
                'tab' => $tab,
                'html' => view('admin.sales.welcome-gifts.partials.tab-' . $tab, $data)->render()
            ]);
        }

        return view('admin.sales.welcome-gifts.index', $data);
    }

    /**
     * 新增來店禮
     */
    public function storeWelcomeGift(Request $request)
    {
        $validated = $request->validate([
            'machine_id' => 'required|exists:machines,id',
            'name' => 'required|string|max:100',
            'discount_type' => 'required|in:percentage,amount',
            'discount_val_input' => 'required|numeric|min:0.1', // 接受折數 (如 8.5) 或 金額 (如 50)
            'usage_type' => 'required|in:once,unlimited',
            'usage_limit' => 'nullable|integer|min:1',
            'expires_at' => 'nullable|date|after:now',
            'custom_code' => 'nullable|string|min:4|max:10',
        ]);

        $machine = Machine::findOrFail($validated['machine_id']);
        
        // 關鍵自動換算邏輯
        $discountValue = 0;
        if ($validated['discount_type'] === 'percentage') {
            // 使用者輸入折數 (如 8 代表打八折，8.5 代表打八五折)
            $fold = (float) $validated['discount_val_input'];
            // 換算為趴數，公式為 (10 - 折數) * 10 ➜ 例如 8.5折 ➜ (10 - 8.5) * 10 = 15% off
            $discountValue = (int) round((10 - $fold) * 10);
        } else {
            // 金額折抵 (如 50 代表折 50 元)
            $discountValue = (int) $validated['discount_val_input'];
        }

        $welcomeGift = WelcomeGift::create([
            'machine_id' => $validated['machine_id'],
            'name' => $validated['name'],
            'code' => $validated['custom_code'] ?? WelcomeGift::generateUniqueCode($validated['machine_id']),
            'discount_type' => $validated['discount_type'],
            'discount_value' => $discountValue,
            'usage_type' => $validated['usage_type'],
            'usage_limit' => $validated['usage_type'] === 'once' ? ($validated['usage_limit'] ?? 1) : null,
            'expires_at' => $validated['expires_at'],
            'status' => 'active',
            'company_id' => $machine->company_id,
            'created_by' => Auth::id(),
        ]);

        SystemOperationLog::create([
            'company_id' => $machine->company_id,
            'user_id' => Auth::id(),
            'module' => 'welcome_gift',
            'action' => 'create',
            'target_id' => $welcomeGift->id,
            'target_type' => WelcomeGift::class,
            'new_values' => $welcomeGift->toArray(),
        ]);

        return back()->with('success', __('Welcome Gift created successfully.'));
    }

    /**
     * 更新來店禮
     */
    public function updateWelcomeGift(Request $request, WelcomeGift $welcomeGift)
    {
        $validated = $request->validate([
            'name' => 'nullable|string|max:100',
            'discount_type' => 'nullable|in:percentage,amount',
            'discount_val_input' => 'nullable|numeric|min:0.1',
            'expires_at' => 'nullable|date',
            'status' => 'nullable|in:active,disabled',
            'usage_type' => 'nullable|in:once,unlimited',
            'usage_limit' => 'nullable|integer|min:1',
        ]);

        if (isset($validated['expires_at']) && empty($validated['expires_at'])) {
            $validated['expires_at'] = null;
        }

        $oldValues = $welcomeGift->toArray();

        // 整理要更新的資料
        $updateData = [];
        if ($request->has('name')) {
            $updateData['name'] = $validated['name'];
        }
        if ($request->has('status')) {
            $updateData['status'] = $validated['status'];
        }
        if ($request->has('expires_at')) {
            $updateData['expires_at'] = $validated['expires_at'];
        }
        if ($request->has('usage_type')) {
            $updateData['usage_type'] = $validated['usage_type'];
            if ($validated['usage_type'] === 'unlimited') {
                $updateData['usage_limit'] = null;
            } elseif ($request->has('usage_limit')) {
                $updateData['usage_limit'] = $validated['usage_limit'];
            }
        } elseif ($request->has('usage_limit') && $welcomeGift->usage_type === 'once') {
            $updateData['usage_limit'] = $validated['usage_limit'];
        }

        // 折扣折數/金額更新
        if ($request->has('discount_type') || $request->has('discount_val_input')) {
            $discountType = $validated['discount_type'] ?? $welcomeGift->discount_type;
            $discountValInput = $validated['discount_val_input'] ?? ($discountType === 'percentage' ? $welcomeGift->input_fold : $welcomeGift->discount_value);

            $updateData['discount_type'] = $discountType;
            if ($discountType === 'percentage') {
                $fold = (float) $discountValInput;
                $updateData['discount_value'] = (int) round((10 - $fold) * 10);
            } else {
                $updateData['discount_value'] = (int) $discountValInput;
            }
        }

        $welcomeGift->update($updateData);

        SystemOperationLog::create([
            'company_id' => $welcomeGift->company_id,
            'user_id' => Auth::id(),
            'module' => 'welcome_gift',
            'action' => 'update',
            'target_id' => $welcomeGift->id,
            'target_type' => WelcomeGift::class,
            'old_values' => $oldValues,
            'new_values' => $welcomeGift->toArray(),
        ]);

        return back()->with('success', __('Welcome Gift updated.'));
    }

    /**
     * 刪除來店禮 (改為停用)
     */
    public function destroyWelcomeGift(WelcomeGift $welcomeGift)
    {
        // 機台授權以 machine_user 為準：非系統管理員若無法存取該來店禮所屬機台則拒絕
        if (!Auth::user()->isSystemAdmin() && !Machine::whereKey($welcomeGift->machine_id)->exists()) {
            abort(403);
        }

        $oldValues = $welcomeGift->toArray();
        $welcomeGift->update(['status' => 'disabled']);

        SystemOperationLog::create([
            'company_id' => $welcomeGift->company_id,
            'user_id' => Auth::id(),
            'module' => 'welcome_gift',
            'action' => 'cancel',
            'target_id' => $welcomeGift->id,
            'target_type' => WelcomeGift::class,
            'old_values' => $oldValues,
            'new_values' => $welcomeGift->toArray(),
        ]);

        return back()->with('success', __('Welcome Gift cancelled.'));
    }

    /**
     * 處理銷售中心資料匯出 (CSV 串流下載)
     */
    private function handleExport($tab, $type, $ordersQuery, $invoicesQuery, $dispenseQuery)
    {
        $isExcel = ($type === 'excel');
        $ext = $isExcel ? 'xls' : 'csv';
        $contentType = $isExcel ? 'application/vnd.ms-excel; charset=utf-8' : 'text/csv; charset=utf-8';

        $filename = '';
        $headers = [];
        $callback = null;

        if ($tab === 'orders') {
            $filename = '交易紀錄_' . now()->format('YmdHis') . '.' . $ext;
            $headers = [
                __('Order Number'),
                __('Transaction Time'),
                __('Machine Name'),
                __('Machine Serial No'),
                __('Product Items'),
                __('Quantity'),
                __('Original Amount'),
                __('Discount Amount'),
                __('Payment Amount'),
                __('Payment Type'),
                __('Payment Status'),
                __('Dispense Status'),
                __('Invoice Number'),
                __('Staff Name'),
                __('Staff ID'),
                __('Card UID'),
            ];

            // 預先載入關聯以防 N+1
            $ordersQuery->with([
                'machine:id,name,serial_no',
                'invoice:id,order_id,invoice_no',
                'items:id,order_id,product_name,quantity',
                'staffCardLog:id,order_id,staff_card_id',
                'staffCardLog.staffCard:id,employee_id,name,card_uid'
            ]);

            $callback = function () use ($ordersQuery, $headers, $isExcel) {
                $file = fopen('php://output', 'w');
                if ($isExcel) {
                    fwrite($file, '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">');
                    fwrite($file, '<head><meta charset="utf-8"/><style>table { border-collapse: collapse; } th, td { border: 1px solid #cbd5e1; padding: 8px 12px; text-align: left; } th { background-color: #f1f5f9; font-weight: bold; }</style></head><body>');
                    fwrite($file, '<table><thead><tr>');
                    foreach ($headers as $header) {
                        fwrite($file, '<th>' . htmlspecialchars($header) . '</th>');
                    }
                    fwrite($file, '</tr></thead><tbody>');
                } else {
                    fwrite($file, "\xEF\xBB\xBF"); // 注入 UTF-8 BOM
                    fputcsv($file, $headers);
                }

                // 分批 (Chunk) 查詢，每次 500 筆，確保常數記憶體用量
                $ordersQuery->latest()->chunk(500, function ($orders) use ($file, $isExcel) {
                    foreach ($orders as $order) {
                        $productSummary = $order->items->map(function ($item) {
                            return $item->product_name . ' x' . number_format($item->quantity, 0);
                        })->implode(', ');

                        $totalQty = $order->items->sum('quantity');

                        // 轉換訂單狀態標籤，與前端 tab-orders.blade.php / detail 面板一致
                        $statusLabelMap = [
                            'pending' => __('Pending'),
                            'paid' => __('Paid'),
                            'completed' => __('Completed'),
                            'awaiting_pickup' => __('Awaiting Pickup'),
                            'failed' => __('Failed'),
                            'abandoned' => __('Unpaid'),
                            'cancelled' => __('Cancelled'),
                            'refunded' => __('Refunded'),
                        ];
                        $paymentStatusLabel = $statusLabelMap[$order->status] ?? $order->status;

                        $row = [
                            $order->order_no,
                            $order->created_at->format('Y-m-d H:i:s'),
                            $order->machine->name ?? 'Unknown',
                            $order->machine->serial_no ?? '---',
                            $productSummary ?: '---',
                            $totalQty,
                            number_format($order->total_amount, 2, '.', ''),
                            number_format($order->discount_amount, 2, '.', ''),
                            number_format($order->pay_amount, 2, '.', ''),
                            $order->payment_type_label,
                            $paymentStatusLabel,
                            $order->delivery_status_label,
                            $order->invoice->invoice_no ?? '---',
                            $order->staffCardLog->staffCard->name ?? '---',
                            $order->staffCardLog->staffCard->employee_id ?? '---',
                            $order->staffCardLog->staffCard->card_uid ?? '---',
                        ];

                        if ($isExcel) {
                            fwrite($file, '<tr>');
                            foreach ($row as $val) {
                                fwrite($file, '<td>' . htmlspecialchars($val) . '</td>');
                            }
                            fwrite($file, '</tr>');
                        } else {
                            fputcsv($file, $row);
                        }
                    }
                });

                if ($isExcel) {
                    fwrite($file, '</tbody></table></body></html>');
                }
                fclose($file);
            };
        } elseif ($tab === 'invoices') {
            $filename = '電子發票紀錄_' . now()->format('YmdHis') . '.' . $ext;
            $headers = [
                __('Invoice Number'),
                __('Invoice Date'),
                __('Flow ID'),
                __('Machine Name'),
                __('Machine Serial No'),
                __('Associated Order'),
                __('Amount'),
                __('Status'),
            ];

            // 預先載入關聯
            $invoicesQuery->with([
                'machine:id,name,serial_no',
                'order:id,order_no'
            ]);

            $callback = function () use ($invoicesQuery, $headers, $isExcel) {
                $file = fopen('php://output', 'w');
                if ($isExcel) {
                    fwrite($file, '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">');
                    fwrite($file, '<head><meta charset="utf-8"/><style>table { border-collapse: collapse; } th, td { border: 1px solid #cbd5e1; padding: 8px 12px; text-align: left; } th { background-color: #f1f5f9; font-weight: bold; }</style></head><body>');
                    fwrite($file, '<table><thead><tr>');
                    foreach ($headers as $header) {
                        fwrite($file, '<th>' . htmlspecialchars($header) . '</th>');
                    }
                    fwrite($file, '</tr></thead><tbody>');
                } else {
                    fwrite($file, "\xEF\xBB\xBF");
                    fputcsv($file, $headers);
                }

                $invoicesQuery->latest()->chunk(500, function ($invoices) use ($file, $isExcel) {
                    foreach ($invoices as $inv) {
                        // 狀態判斷邏輯需與 tab-invoices.blade.php 完全一致
                        $status = 'valid';
                        if (empty($inv->invoice_no)) $status = 'failed';
                        if ($inv->rtn_code === 'void') $status = 'void';

                        $statusMap = [
                            'valid' => __('Valid'),
                            'void' => __('Void'),
                            'failed' => __('Failed'),
                            'refunded' => __('Refunded'),
                        ];
                        $statusLabel = $statusMap[$status] ?? $status;

                        $row = [
                            $inv->invoice_no ?: '---',
                            $inv->invoice_date ?: '---',
                            $inv->display_flow_id ?: '---',
                            $inv->machine->name ?? ($inv->order->machine->name ?? 'Unknown'),
                            $inv->machine->serial_no ?? ($inv->order->machine->serial_no ?? '---'),
                            $inv->order->order_no ?? '---',
                            number_format($inv->amount, 2, '.', ''),
                            $statusLabel,
                        ];

                        if ($isExcel) {
                            fwrite($file, '<tr>');
                            foreach ($row as $val) {
                                fwrite($file, '<td>' . htmlspecialchars($val) . '</td>');
                            }
                            fwrite($file, '</tr>');
                        } else {
                            fputcsv($file, $row);
                        }
                    }
                });

                if ($isExcel) {
                    fwrite($file, '</tbody></table></body></html>');
                }
                fclose($file);
            };
        } elseif ($tab === 'dispense') {
            $filename = '遠端出貨紀錄_' . now()->format('YmdHis') . '.' . $ext;
            $headers = [
                __('Machine'),
                __('Machine Serial No'),
                __('Product Name'),
                __('Slot No'),
                __('Amount'),
                __('Dispense Status'),
                __('Dispense Time'),
                __('Associated Order'),
            ];

            // 預先載入關聯
            $dispenseQuery->with([
                'machine:id,name,serial_no',
                'product:id,name',
                'order:id,order_no'
            ]);

            $callback = function () use ($dispenseQuery, $headers, $isExcel) {
                $file = fopen('php://output', 'w');
                if ($isExcel) {
                    fwrite($file, '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">');
                    fwrite($file, '<head><meta charset="utf-8"/><style>table { border-collapse: collapse; } th, td { border: 1px solid #cbd5e1; padding: 8px 12px; text-align: left; } th { background-color: #f1f5f9; font-weight: bold; }</style></head><body>');
                    fwrite($file, '<table><thead><tr>');
                    foreach ($headers as $header) {
                        fwrite($file, '<th>' . htmlspecialchars($header) . '</th>');
                    }
                    fwrite($file, '</tr></thead><tbody>');
                } else {
                    fwrite($file, "\xEF\xBB\xBF");
                    fputcsv($file, $headers);
                }

                $dispenseQuery->latest()->chunk(500, function ($logs) use ($file, $isExcel) {
                    foreach ($logs as $log) {
                        // 出貨狀態對照，需與 tab-dispense.blade.php 完全一致
                        $statusMap = [
                            'success' => __('Dispense Success'),
                            'failed' => __('Dispense Failed'),
                            'pending' => __('Dispense Pending'),
                            '1' => __('Dispense Success'),
                            '0' => __('Dispense Failed'),
                        ];
                        $statusKey = (string)($log->dispense_status ?? 'pending');
                        $statusLabel = $statusMap[$statusKey] ?? $statusKey;

                        $row = [
                            $log->machine->name ?? 'Unknown',
                            $log->machine->serial_no ?? '---',
                            $log->product->localized_name ?? __('Unknown Product'),
                            $log->slot_no,
                            number_format($log->amount, 2, '.', ''),
                            $statusLabel,
                            $log->machine_time ? $log->machine_time->format('Y-m-d H:i:s') : '---',
                            $log->order->order_no ?? '---',
                        ];

                        if ($isExcel) {
                            fwrite($file, '<tr>');
                            foreach ($row as $val) {
                                fwrite($file, '<td>' . htmlspecialchars($val) . '</td>');
                            }
                            fwrite($file, '</tr>');
                        } else {
                            fputcsv($file, $row);
                        }
                    }
                });

                if ($isExcel) {
                    fwrite($file, '</tbody></table></body></html>');
                }
                fclose($file);
            };
        }

        if ($callback) {
            return response()->streamDownload($callback, $filename, [
                'Content-Type' => $contentType,
                'Cache-Control' => 'no-cache, must-revalidate',
                'Pragma' => 'no-cache',
                'Expires' => '0',
            ]);
        }

        return redirect()->back();
    }

    // ===================== 電子發票對帳/補開/作廢 =====================

    /**
     * 列印發票：向綠界取得列印頁網址（InvoicePrint）後 302 導向。
     * 該網址自綠界回應起 1 小時內有效，前端以 target="_blank" 於新分頁開啟。
     */
    public function printInvoice(Invoice $invoice, \App\Services\Invoice\EcpayInvoiceService $ecpay)
    {
        // 僅已開立且有發票號者可列印
        if ($invoice->status !== Invoice::STATUS_ISSUED || empty($invoice->invoice_no)) {
            return back()->with('error', __('Only issued invoices can be printed'));
        }
        // 僅「統編發票」可列印：本系統開立時 Print=1 僅給帶統編者（見 EcpayInvoiceService::issue 的 Print 規則）；
        // 捐贈、載具、一般無統編 B2C 皆以 Print=0 開立，綠界 InvoicePrint 會回查無資料而失敗。
        if (empty($invoice->metadata['business_tax_id'] ?? '')) {
            return back()->with('error', __('Only invoices with a tax ID can be printed'));
        }
        $machine = $invoice->machine;
        if (!$machine || !$ecpay->configForMachine($machine)) {
            return back()->with('error', __('This machine has no ECPay invoice settings'));
        }
        $date = $invoice->invoice_date
            ? Carbon::parse($invoice->invoice_date)->format('Y-m-d')
            : '';

        $result = $ecpay->invoicePrint($machine, $invoice->invoice_no, $date);
        $url = $result['InvoiceHtml'] ?? '';
        if ($result === null || (string) ($result['RtnCode'] ?? '') !== '1' || empty($url)) {
            return back()->with('error', __('Failed to get invoice print URL'));
        }

        return redirect()->away($url);
    }

    /**
     * 手動向綠界查詢單張發票真實狀態（GetIssue）。
     * pending 掉包時用此確認：已開→補登 issued；查無→標 failed 待補開。
     */
    public function reconcileInvoice(Invoice $invoice, \App\Services\Invoice\EcpayInvoiceService $ecpay)
    {
        // 伺服器端狀態守衛：僅 pending/failed 可查證（防偽造 POST 對終態發票亂操作）
        if (!in_array($invoice->status, [Invoice::STATUS_PENDING, Invoice::STATUS_FAILED], true)) {
            return back()->with('error', __('Only pending/failed invoices can be queried'));
        }
        $machine = $invoice->machine;
        if (!$machine || !$ecpay->configForMachine($machine)) {
            return back()->with('error', __('This machine has no ECPay invoice settings'));
        }
        if (empty($invoice->relate_number)) {
            return back()->with('error', __('Missing RelateNumber, cannot query'));
        }

        $result = $ecpay->getIssue($machine, $invoice->relate_number);
        if ($result === null) {
            $invoice->increment('retry_count');
            $invoice->update(['last_checked_at' => now()]);
            return back()->with('error', __('ECPay query failed'));
        }

        $rtnCode = (string) ($result['RtnCode'] ?? '');
        $invoiceNo = $result['IIS_Number'] ?? '';
        if ($rtnCode === '1' && !empty($invoiceNo)) {
            $invoice->update([
                'status' => Invoice::STATUS_ISSUED,
                'invoice_no' => $invoiceNo,
                'invoice_date' => $this->safeDate($result['IIS_Create_Date'] ?? null),
                'random_number' => $result['IIS_Random_Number'] ?? $invoice->random_number,
                'rtn_code' => $rtnCode,
                'rtn_msg' => $result['RtnMsg'] ?? null,
                'last_checked_at' => now(),
            ]);
            return back()->with('success', __('Confirmed issued: ') . $invoiceNo);
        }

        $invoice->update([
            'status' => Invoice::STATUS_FAILED,
            'rtn_code' => $rtnCode ?: $invoice->rtn_code,
            'rtn_msg' => $result['RtnMsg'] ?? $invoice->rtn_msg,
            'last_checked_at' => now(),
        ]);
        return back()->with('error', __('ECPay has no such invoice; marked as pending re-issue'));
    }

    /** 補開（Issue），沿用同一 RelateNumber 冪等。 */
    public function reissueInvoice(Invoice $invoice, \App\Services\Invoice\EcpayInvoiceService $ecpay)
    {
        // 伺服器端狀態守衛：僅 pending/failed 可補開（防把 issued/void 重開造成重複開立）
        if (!in_array($invoice->status, [Invoice::STATUS_PENDING, Invoice::STATUS_FAILED], true)) {
            return back()->with('error', __('Only pending/failed invoices can be re-issued'));
        }
        $result = $ecpay->reissue($invoice);
        if ($result === null) {
            return back()->with('error', __('Re-issue failed (no settings or connection error)'));
        }
        $rtnCode = (string) ($result['RtnCode'] ?? '');
        $invoiceNo = $result['InvoiceNo'] ?? '';
        if ($rtnCode === '1' && !empty($invoiceNo)) {
            $invoice->update([
                'status' => Invoice::STATUS_ISSUED,
                'invoice_no' => $invoiceNo,
                'invoice_date' => $this->safeDate($result['InvoiceDate'] ?? null),
                'random_number' => $result['RandomNumber'] ?? $invoice->random_number,
                'rtn_code' => $rtnCode,
                'rtn_msg' => $result['RtnMsg'] ?? null,
                'last_checked_at' => now(),
            ]);
            return back()->with('success', __('Re-issue success: ') . $invoiceNo);
        }
        $invoice->update([
            'rtn_code' => $rtnCode ?: $invoice->rtn_code,
            'rtn_msg' => $result['RtnMsg'] ?? $invoice->rtn_msg,
            'last_checked_at' => now(),
        ]);
        return back()->with('error', __('Re-issue failed: ') . ($result['RtnMsg'] ?? ''));
    }

    /** 作廢（Invalid），用於「已開票未出貨」等情況。 */
    public function voidInvoice(Request $request, Invoice $invoice, \App\Services\Invoice\EcpayInvoiceService $ecpay)
    {
        // 權限守衛：發票作廢僅限「平台系統管理員」，租戶帳號（含其管理員）一律不得作廢。
        // 真正的閘門在後端，前端僅隱藏按鈕無法防止偽造 POST。
        if (!Auth::user()->isSystemAdmin()) {
            abort(403, __('Only system administrators can void invoices'));
        }
        // 伺服器端狀態守衛：僅 issued 可作廢（防把已作廢/未開立的發票再作廢）
        if ($invoice->status !== Invoice::STATUS_ISSUED) {
            return back()->with('error', __('Only issued invoices can be voided'));
        }
        if (empty($invoice->invoice_no)) {
            return back()->with('error', __('No invoice issued, cannot void'));
        }
        // 送綠界的作廢原因維持固定值（法定欄位、上限 20 字）；管理者於視窗輸入的備註僅存內部 remark。
        $reason = '未出貨作廢';
        $remark = trim((string) $request->input('remark', ''));
        $date = $invoice->invoice_date
            ? Carbon::parse($invoice->invoice_date)->format('Y-m-d')
            : '';

        $result = $ecpay->invalid($invoice->machine, $invoice->invoice_no, $date, $reason);
        if ($result === null) {
            return back()->with('error', __('Void failed (settings/connection)'));
        }
        $rtnCode = (string) ($result['RtnCode'] ?? '');
        if ($rtnCode === '1') {
            $invoice->update([
                'status' => Invoice::STATUS_VOID,
                'voided_at' => now(),
                'void_reason' => $reason,
                'remark' => $remark !== '' ? $remark : $invoice->remark,
            ]);
            return back()->with('success', __('Void success'));
        }
        return back()->with('error', __('Void failed: ') . ($result['RtnMsg'] ?? ''));
    }

    /** 更新訂單備註（管理者內部註記）。租戶隔離由 TenantScoped + route-model binding 自動處理。 */
    public function updateOrderRemark(Request $request, Order $order)
    {
        $data = $request->validate([
            'remark' => ['nullable', 'string', 'max:1000'],
        ]);
        $order->update(['remark' => $data['remark'] ?? null]);

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'message' => __('Remark updated')]);
        }
        return back()->with('success', __('Remark updated'));
    }

    /** 更新發票備註（管理者內部註記，與送綠界的 void_reason 無關）。 */
    public function updateInvoiceRemark(Request $request, Invoice $invoice)
    {
        $data = $request->validate([
            'remark' => ['nullable', 'string', 'max:1000'],
        ]);
        $invoice->update(['remark' => $data['remark'] ?? null]);

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'message' => __('Remark updated')]);
        }
        return back()->with('success', __('Remark updated'));
    }

    private function safeDate($raw): ?string
    {
        if (empty($raw)) {
            return null;
        }
        try {
            return Carbon::parse($raw)->toDateString();
        } catch (\Throwable $e) {
            return null;
        }
    }
}
