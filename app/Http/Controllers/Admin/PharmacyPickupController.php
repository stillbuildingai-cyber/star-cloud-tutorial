<?php

namespace App\Http\Controllers\Admin;

use App\Models\Machine\Machine;
use App\Models\System\SystemOperationLog;
use App\Models\Transaction\Order;
use App\Services\Transaction\PharmacyPickupService;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

/**
 * 領藥單管理（銷售管理 → 領藥單）。
 *
 * 授權雙閘：
 *  1. 選單/頁面：權限 menu.sales.pharmacy-pickup（角色/帳號權限可勾選）。
 *  2. 機台行為：機台系統設定 shopping_mode=pickup_sheet 且 pharmacy_pickup_enabled=true。
 */
class PharmacyPickupController extends AdminController
{
    public function __construct(private PharmacyPickupService $service)
    {
    }

    public function index(Request $request)
    {
        // 僅列出「取物單模式 + 領藥單開關開啟」的機台（受 TenantScoped 自動租戶隔離）
        $machines = Machine::query()
            ->where('settings->shopping_mode', 'pickup_sheet')
            ->where('settings->pharmacy_pickup_enabled', true)
            ->orderBy('name')
            ->get();

        $selectedMachine = null;
        $products = new Collection();

        if ($request->filled('machine_id')) {
            $selectedMachine = $machines->firstWhere('id', (int) $request->input('machine_id'));
            if ($selectedMachine) {
                $products = $this->service->availableProducts($selectedMachine);
            }
        }

        $orders = Order::pharmacyPickup()
            ->with(['machine:id,name,serial_no', 'items', 'creator:id,name', 'pickupCode'])
            ->latest()
            ->paginate($request->input('per_page', 10))
            ->withQueryString();

        return view('admin.sales.pharmacy-pickup.index', [
            'title' => __('menu.sales.pharmacy-pickup'),
            'machines' => $machines,
            'selectedMachine' => $selectedMachine,
            'products' => $products,
            'orders' => $orders,
        ]);
    }

    /**
     * AJAX：回傳某機台目前可領藥品（建立領藥單彈窗用，對齊取貨碼 slots-ajax 模式）。
     */
    public function productsAjax(Machine $machine)
    {
        $enabled = ($machine->settings['shopping_mode'] ?? null) === 'pickup_sheet'
            && (bool) ($machine->settings['pharmacy_pickup_enabled'] ?? false);
        abort_unless($enabled, 403);

        return response()->json([
            'products' => $this->service->availableProducts($machine)->values(),
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'machine_id' => 'required|exists:machines,id',
            'pricing_slip_no' => 'nullable|string|max:64',
        ]);

        // 由勾選的 products[] + qty[product_id] 組出 items
        $productIds = (array) $request->input('products', []);
        $qtyMap = (array) $request->input('qty', []);
        $items = [];
        foreach ($productIds as $pid) {
            $q = (int) ($qtyMap[$pid] ?? 0);
            if ($q > 0) {
                $items[] = ['product_id' => (int) $pid, 'quantity' => $q];
            }
        }

        try {
            $order = $this->service->create([
                'machine_id' => (int) $request->input('machine_id'),
                'pricing_slip_no' => $request->input('pricing_slip_no'),
                'items' => $items,
            ], Auth::id());
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        }

        return redirect()
            ->route('admin.sales.pharmacy-pickup', ['machine_id' => $request->input('machine_id')])
            ->with('success', __('Pharmacy pickup order created: :no', ['no' => $order->order_no]))
            ->with('created_order_id', $order->id);
    }

    /**
     * 可列印的領藥單（QR 內嵌 SVG，內容為 8 碼領藥碼，機台掃描後送 B660 驗證）。
     */
    public function print(Order $order)
    {
        abort_unless($order->order_type === Order::TYPE_PHARMACY_PICKUP, 404);

        $order->load(['items', 'machine', 'pickupCode', 'creator:id,name']);
        $code = $order->pickupCode?->code;
        $qrSvg = $code
            ? QrCode::format('svg')->size(220)->margin(1)->errorCorrection('M')->generate($code)
            : null;

        return view('admin.sales.pharmacy-pickup.print', [
            'order' => $order,
            'qrSvg' => $qrSvg,
        ]);
    }

    public function cancel(Order $order)
    {
        abort_unless($order->order_type === Order::TYPE_PHARMACY_PICKUP, 404);

        if (in_array($order->status, [Order::STATUS_COMPLETED, Order::STATUS_FAILED], true)
            || $order->delivery_status === Order::DELIVERY_STATUS_SUCCESS) {
            return back()->with('error', __('This pharmacy pickup order can no longer be cancelled.'));
        }

        $order->update(['status' => Order::STATUS_FAILED]);
        if ($order->pickupCode) {
            $order->pickupCode->update(['status' => 'cancelled']);
        }

        SystemOperationLog::create([
            'company_id' => $order->company_id,
            'user_id' => Auth::id(),
            'module' => 'pharmacy_pickup',
            'action' => 'cancel',
            'target_id' => $order->id,
            'target_type' => Order::class,
        ]);

        return back()->with('success', __('Pharmacy pickup order cancelled.'));
    }
}
