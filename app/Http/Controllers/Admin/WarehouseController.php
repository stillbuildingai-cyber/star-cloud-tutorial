<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Machine\Machine;
use App\Models\Machine\MachineSlot;
use App\Models\Machine\MachineStockMovement;
use App\Models\Product\Product;
use App\Models\Warehouse\ReplenishmentOrder;
use App\Models\Warehouse\ReplenishmentOrderItem;
use App\Models\Warehouse\StockInOrder;
use App\Models\Warehouse\StockInOrderItem;
use App\Models\Warehouse\StockMovement;
use App\Models\Warehouse\TransferOrder;
use App\Models\Warehouse\TransferOrderItem;
use App\Models\Warehouse\Warehouse;
use App\Models\Warehouse\WarehouseStock;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class WarehouseController extends Controller
{
    // ─── 模組 1：倉庫總覽 ───

    public function index(Request $request)
    {
        $currentUser = Auth::user();
        $companyId = trim((string) $request->input('company_id', ''));
        $search = $request->input('search');
        $type = $request->input('type');

        $applyWarehouseFilters = function ($query) use ($currentUser, $companyId, $search, $type) {
            if ($currentUser->isSystemAdmin() && $companyId !== '') {
                $query->where('company_id', $companyId);
            }

            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('address', 'like', "%{$search}%");
                });
            }

            if ($type) {
                $query->where('type', $type);
            }

            return $query;
        };

        $query = Warehouse::query()
            ->with(['company'])
            ->withCount('stocks as products_count')
            ->withSum('stocks as total_stock', 'quantity');

        $applyWarehouseFilters($query);

        $warehouses = $query->orderBy('type')->orderBy('name')
            ->paginate($request->input('per_page', 10))
            ->withQueryString();

        $statsWarehouseQuery = fn() => $applyWarehouseFilters(Warehouse::query());

        $stats = [
            'total' => $statsWarehouseQuery()->count(),
            'main' => $statsWarehouseQuery()->main()->count(),
            'branch' => $statsWarehouseQuery()->branch()->count(),
            'low_stock' => WarehouseStock::lowStock()
                ->whereHas('warehouse', fn($q) => $applyWarehouseFilters($q))
                ->count(),
        ];

        $companies = collect();
        if ($currentUser->isSystemAdmin()) {
            $companies = \App\Models\System\Company::active()->orderBy('name')->get();
        }

        return view('admin.warehouses.index', compact('warehouses', 'stats', 'companies'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'company_id' => Auth::user()->isSystemAdmin() ? 'nullable|exists:companies,id' : 'nullable',
            'name' => 'required|string|max:255',
            'type' => 'required|in:main,branch',
            'address' => 'nullable|string|max:500',
        ], [], [
            'name' => __('Warehouse Name'),
            'type' => __('Warehouse Type'),
            'company_id' => __('Company Name'),
        ]);

        if (!Auth::user()->isSystemAdmin()) {
            $validated['company_id'] = Auth::user()->company_id;
        }

        Warehouse::create($validated);

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => __('Warehouse created successfully'),
            ]);
        }

        return redirect()->route('admin.warehouses.index')
            ->with('success', __('Warehouse created successfully'));
    }

    public function update(Request $request, Warehouse $warehouse)
    {
        $rules = [
            'name' => 'required|string|max:255',
            'type' => 'required|in:main,branch',
            'address' => 'nullable|string|max:500',
            'is_active' => 'required|boolean',
        ];

        if (Auth::user()->isSystemAdmin()) {
            $rules['company_id'] = 'nullable|exists:companies,id';
        }

        $validated = $request->validate($rules, [], [
            'name' => __('Warehouse Name'),
            'type' => __('Warehouse Type'),
            'company_id' => __('Company Name'),
        ]);
        $warehouse->update($validated);

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => __('Warehouse updated successfully'),
            ]);
        }

        return redirect()->route('admin.warehouses.index')
            ->with('success', __('Warehouse updated successfully'));
    }

    public function destroy(Warehouse $warehouse)
    {
        if ($warehouse->stocks()->where('quantity', '>', 0)->exists()) {
            return redirect()->route('admin.warehouses.index')
                ->with('error', __('Cannot delete warehouse with existing stock'));
        }
        $warehouse->delete();

        return redirect()->route('admin.warehouses.index')
            ->with('success', __('Warehouse deleted successfully'));
    }

    public function toggleStatus(Warehouse $warehouse)
    {
        $warehouse->update(['is_active' => !$warehouse->is_active]);
        $status = $warehouse->is_active ? __('enabled') : __('disabled');

        return redirect()->route('admin.warehouses.index')
            ->with('success', __('Warehouse :status successfully', ['status' => $status]));
    }

    // ─── 模組 2：庫存管理 ───

    public function inventory(Request $request)
    {
        $tab = $request->input('tab', 'stock');
        $currentUser = Auth::user();
        $companyId = trim((string) $request->input('company_id', ''));
        $applyCompanyFilter = fn($query) => $query->when(
            $currentUser->isSystemAdmin() && $companyId !== '',
            fn($q) => $q->where('company_id', $companyId)
        );

        $warehouses = $applyCompanyFilter(Warehouse::active())
            ->orderBy('name')
            ->get();
        $companies = $currentUser->isSystemAdmin()
            ? \App\Models\System\Company::active()->orderBy('name')->get()
            : collect();
        $isAjax = $request->ajax();

        $data = compact('warehouses', 'companies', 'tab');

        // 1. Current Stocks (stock)
        if (!$isAjax || $tab === 'stock') {
            $warehouse_ids = $request->input('warehouse_ids');
            $inventory_warehouses = $applyCompanyFilter(Warehouse::active())
                ->when($warehouse_ids, fn($q) => $q->whereIn('id', (array)$warehouse_ids))
                ->orderBy('name')
                ->get(['id', 'name', 'type', 'company_id']);
            
            $query = Product::with(['stocks' => function($q) {
                $q->select('id', 'product_id', 'warehouse_id', 'quantity', 'safety_stock');
            }, 'translations']);

            if ($currentUser->isSystemAdmin() && $companyId !== '') {
                $query->where('company_id', $companyId);
            }

            if ($warehouse_ids) {
                $query->whereHas('stocks', fn($q) => $q->whereIn('warehouse_id', (array)$warehouse_ids));
            }
            if ($search = $request->input('search')) {
                $query->where('name', 'like', "%{$search}%");
            }

            $data['inventory_warehouses'] = $inventory_warehouses;
            $data['products'] = $query->orderBy('name')
                ->paginate($request->input('per_page', 15), ['*'], 'stock_page')
                ->withQueryString();
        }

        // 2. Stock-In Orders (stock-in)
        if (!$isAjax || $tab === 'stock-in') {
            $query = StockInOrder::with(['warehouse:id,name', 'creator:id,name'])
                ->withCount('items')
                ->latest();

            if ($currentUser->isSystemAdmin() && $companyId !== '') {
                $query->where('company_id', $companyId);
            }

            if ($request->filled('status')) {
                $query->where('status', $request->input('status'));
            }
            if ($search = $request->input('search_order')) {
                $query->where('order_no', 'like', "%{$search}%");
            }
            if ($request->filled('start_date') || $request->filled('end_date')) {
                if ($request->filled('start_date')) {
                    $query->where('created_at', '>=', \Illuminate\Support\Carbon::parse($request->start_date));
                }
                if ($request->filled('end_date')) {
                    $query->where('created_at', '<=', \Illuminate\Support\Carbon::parse($request->end_date)->endOfMinute());
                }
            } else {
                // 隱性限制：預設只抓 6 個月內的單據以保護效能，但 UI 保持空白
                $query->where('created_at', '>=', now()->subMonths(6)->startOfDay());
                
                $data['stockInDefaultStart'] = '';
                $data['stockInDefaultEnd'] = '';
            }
            $data['orders'] = $query->paginate($request->input('per_page', 10), ['*'], 'order_page')->withQueryString();
            $data['stock_in_products'] = Product::with('translations')
                ->when($currentUser->isSystemAdmin() && $companyId !== '', fn($q) => $q->where('company_id', $companyId))
                ->orderBy('name')
                ->get(['id', 'name', 'image_url', 'name_dictionary_key']);
        }

        // 3. Movement History (movements)
        if (!$isAjax || $tab === 'movements') {
            $query = StockMovement::with(['warehouse:id,name', 'product.translations', 'creator:id,name'])
                ->orderBy('created_at', 'desc')
                ->orderBy('id', 'desc');

            if ($currentUser->isSystemAdmin() && $companyId !== '') {
                $query->where('company_id', $companyId);
            }

            if ($request->filled('warehouse_id')) {
                $query->where('warehouse_id', $request->input('warehouse_id'));
            }
            if ($request->filled('type')) {
                $query->where('type', $request->input('type'));
            }
            if ($request->filled('start_date') || $request->filled('end_date')) {
                if ($request->filled('start_date')) {
                    $query->where('created_at', '>=', \Illuminate\Support\Carbon::parse($request->start_date));
                }
                if ($request->filled('end_date')) {
                    $query->where('created_at', '<=', \Illuminate\Support\Carbon::parse($request->end_date)->endOfMinute());
                }
            } else {
                // 預設當天
                $start = now()->startOfDay();
                $end = now()->endOfDay();
                $query->whereBetween('created_at', [$start, $end]);

                $data['movementDefaultStart'] = $start->format('Y-m-d H:i');
                $data['movementDefaultEnd'] = $end->format('Y-m-d H:i');

                // 只有在當前分頁是 movements 時才合併到 request
                if ($tab === 'movements') {
                    $request->merge([
                        'start_date' => $data['movementDefaultStart'],
                        'end_date' => $data['movementDefaultEnd']
                    ]);
                }
            }
            $data['movements'] = $query->paginate($request->input('per_page', 15), ['*'], 'movement_page')->withQueryString();
        }

        if ($isAjax) {
            return response()->json([
                'success' => true,
                'tab' => $tab,
                'html' => view('admin.warehouses.partials.tab-' . $tab, $data)->render()
            ]);
        }

        return view('admin.warehouses.inventory', $data);
    }

    /**
     * 建立入庫單
     */
    public function storeStockIn(Request $request)
    {
        $validated = $request->validate([
            'warehouse_id' => 'required|exists:warehouses,id',
            'note' => 'nullable|string|max:500',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
        ], [
            'warehouse_id.required' => __('Please select target warehouse'),
            'items.required' => __('Please add at least one item'),
            'items.*.product_id.required' => __('Please select product for item :num', ['num' => ':position']),
            'items.*.quantity.min' => __('Please enter valid quantity for item :num', ['num' => ':position']),
        ]);

        DB::transaction(function () use ($validated) {
            $prefix = 'SI-' . now()->format('Ymd') . '-';
            $lastOrder = StockInOrder::withoutGlobalScopes()
                ->withTrashed()
                ->where('order_no', 'like', $prefix . '%')
                ->orderBy('order_no', 'desc')
                ->first();

            $nextNumber = $lastOrder ? (int)substr($lastOrder->order_no, -4) + 1 : 1;
            $order_no = $prefix . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);

            $order = StockInOrder::create([
                'company_id' => Auth::user()->company_id,
                'warehouse_id' => $validated['warehouse_id'],
                'order_no' => $order_no,
                'status' => 'draft',
                'note' => $validated['note'] ?? null,
                'created_by' => Auth::id(),
            ]);

            foreach ($validated['items'] as $item) {
                $order->items()->create($item);
            }
        });

        if ($request->ajax()) {
            session()->flash('success', __('Stock-in order created successfully'));
            return response()->json([
                'success' => true,
                'message' => __('Stock-in order created successfully')
            ]);
        }

        return redirect()->route('admin.warehouses.inventory', ['tab' => 'stock-in'])
            ->with('success', __('Stock-in order created successfully'));
    }

    /**
     * 確認入庫單（草稿 → 完成，同時增加庫存）
     */
    public function confirmStockIn(StockInOrder $stockInOrder)
    {
        if ($stockInOrder->status !== StockInOrder::STATUS_DRAFT) {
            if (request()->ajax()) {
                return response()->json(['success' => false, 'message' => __('Order already processed')], 422);
            }
            return back()->with('error', __('Order already processed'));
        }

        DB::transaction(function () use ($stockInOrder) {
            $stockInOrder->update([
                'status' => StockInOrder::STATUS_COMPLETED,
                'completed_at' => now(),
            ]);

            foreach ($stockInOrder->items as $item) {
                // 更新或建立倉庫庫存
                $stock = WarehouseStock::firstOrCreate(
                    ['warehouse_id' => $stockInOrder->warehouse_id, 'product_id' => $item->product_id],
                    ['quantity' => 0, 'safety_stock' => 0]
                );

                $before = $stock->quantity;
                $stock->increment('quantity', $item->quantity);

                // 記錄異動
                StockMovement::create([
                    'company_id' => $stockInOrder->company_id,
                    'warehouse_id' => $stockInOrder->warehouse_id,
                    'product_id' => $item->product_id,
                    'type' => 'in',
                    'quantity' => $item->quantity,
                    'before_qty' => $before,
                    'after_qty' => $before + $item->quantity,
                    'reference_type' => StockInOrder::class,
                    'reference_id' => $stockInOrder->id,
                    'note' => __('Stock-in order') . ' #' . $stockInOrder->order_no,
                    'created_by' => Auth::id(),
                ]);
            }
        });

        if (request()->ajax()) {
            return response()->json([
                'success' => true,
                'message' => __('Stock-in order confirmed and inventory updated')
            ]);
        }

        return back()->with('success', __('Stock-in order confirmed and inventory updated'));
    }

    /**
     * 刪除入庫單（僅限草稿）
     */
    public function destroyStockIn(StockInOrder $stockInOrder)
    {
        if ($stockInOrder->status !== StockInOrder::STATUS_DRAFT) {
            if (request()->ajax()) {
                return response()->json(['success' => false, 'message' => __('Only draft orders can be deleted')], 422);
            }
            return back()->with('error', __('Only draft orders can be deleted'));
        }

        $stockInOrder->delete(); // Soft delete

        if (request()->ajax()) {
            return response()->json([
                'success' => true,
                'message' => __('Stock-in order deleted successfully')
            ]);
        }

        return back()->with('success', __('Stock-in order deleted successfully'));
    }

    // ─── 模組 3：調撥單 ───

    public function transfers(Request $request)
    {
        $currentUser = Auth::user();
        $companyId = trim((string) $request->input('company_id', ''));
        $isSystemAdmin = $currentUser->isSystemAdmin();

        $query = TransferOrder::with(['fromWarehouse:id,name', 'toWarehouse:id,name', 'fromMachine:id,name,serial_no', 'creator:id,name'])
            ->withCount('items')
            ->latest();

        if ($isSystemAdmin && $companyId !== '') {
            $query->where('company_id', $companyId);
        }
        if ($request->filled('type')) {
            $query->where('type', $request->input('type'));
        }
        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }
        if ($search = $request->input('search_order')) {
            $query->where('order_no', 'like', "%{$search}%");
        }
        if ($request->filled('start_date') || $request->filled('end_date')) {
            if ($request->filled('start_date')) {
                $query->where('created_at', '>=', \Illuminate\Support\Carbon::parse($request->start_date));
            }
            if ($request->filled('end_date')) {
                $query->where('created_at', '<=', \Illuminate\Support\Carbon::parse($request->end_date)->endOfMinute());
            }
        } else {
            // 隱性限制：預設只抓 6 個月內的單據以保護效能，但 UI 保持空白
            $query->where('created_at', '>=', now()->subMonths(6)->startOfDay());
        }

        $orders = $query->paginate($request->input('per_page', 15))->withQueryString();

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'html' => view('admin.warehouses.partials.table-transfers', compact('orders'))->render()
            ]);
        }

        $companies = $isSystemAdmin
            ? \App\Models\System\Company::active()->orderBy('name')->get()
            : collect();
        $applyCompanyFilter = fn($query) => $query->when(
            $isSystemAdmin && $companyId !== '',
            fn($q) => $q->where('company_id', $companyId)
        );

        $warehouses = $applyCompanyFilter(Warehouse::active())->orderBy('name')->get(['id', 'name']);
        $machines = $applyCompanyFilter(Machine::query())->orderBy('name')->get(['id', 'name', 'serial_no']);
        $products = $applyCompanyFilter(Product::with('translations'))->orderBy('name')->get(['id', 'name', 'image_url', 'name_dictionary_key']);
        
        $defaultStart = '';
        $defaultEnd = '';

        return view('admin.warehouses.transfers', compact('orders', 'warehouses', 'machines', 'products', 'companies', 'defaultStart', 'defaultEnd'));
    }

    /**
     * 建立調撥單（W2W 或 M2W）
     */
    public function storeTransfer(Request $request)
    {
        $validated = $request->validate([
            'type' => 'required|in:warehouse_to_warehouse,machine_to_warehouse',
            'from_warehouse_id' => 'required_if:type,warehouse_to_warehouse|nullable|exists:warehouses,id',
            'from_machine_id' => 'required_if:type,machine_to_warehouse|nullable|exists:machines,id',
            'to_warehouse_id' => 'required|exists:warehouses,id',
            'note' => 'nullable|string|max:500',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
        ], [
            'to_warehouse_id.required' => __('Please select target warehouse'),
            'from_warehouse_id.required_if' => __('Please select source warehouse'),
            'from_machine_id.required_if' => __('Please select source machine'),
            'items.required' => __('Please add at least one item'),
            'items.*.product_id.required' => __('Please select product for item :num', ['num' => ':position']),
            'items.*.quantity.min' => __('Please enter valid quantity for item :num', ['num' => ':position']),
        ]);

        DB::transaction(function () use ($validated) {
            $prefix = 'TR-' . now()->format('Ymd') . '-';
            $lastOrder = TransferOrder::withoutGlobalScopes()
                ->withTrashed()
                ->where('order_no', 'like', $prefix . '%')
                ->orderBy('order_no', 'desc')
                ->first();

            $nextNumber = $lastOrder ? (int)substr($lastOrder->order_no, -4) + 1 : 1;
            $order_no = $prefix . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);

            $order = TransferOrder::create([
                'company_id' => Auth::user()->company_id,
                'order_no' => $order_no,
                'type' => $validated['type'],
                'from_warehouse_id' => $validated['from_warehouse_id'] ?? null,
                'from_machine_id' => $validated['from_machine_id'] ?? null,
                'to_warehouse_id' => $validated['to_warehouse_id'],
                'status' => TransferOrder::STATUS_DRAFT,
                'note' => $validated['note'] ?? null,
                'created_by' => Auth::id(),
            ]);

            // 預先取得商品資訊以建立快照
            $productIds = collect($validated['items'])->pluck('product_id')->unique()->toArray();
            $products = \App\Models\Product\Product::whereIn('id', $productIds)->get()->keyBy('id');

            foreach ($validated['items'] as $item) {
                $product = $products->get($item['product_id']);
                if ($product) {
                    $item['snapshot_product_name'] = $product->name;
                    $item['snapshot_product_name_key'] = $product->name_dictionary_key;
                    $item['snapshot_product_barcode'] = $product->barcode;
                    $item['snapshot_product_image_url'] = $product->image_url;
                }
                $order->items()->create($item);
            }
        });

        if ($request->ajax()) {
            session()->flash('success', __('Transfer order created successfully'));
            return response()->json([
                'success' => true,
                'message' => __('Transfer order created successfully')
            ]);
        }

        return redirect()->route('admin.warehouses.transfers')
            ->with('success', __('Transfer order created successfully'));
    }

    /**
     * 確認調撥（扣除來源庫存 + 增加目標庫存）
     */
    public function confirmTransfer(TransferOrder $transferOrder)
    {
        if ($transferOrder->status !== TransferOrder::STATUS_DRAFT) {
            if (request()->ajax()) {
                return response()->json(['success' => false, 'message' => __('Order already processed')], 422);
            }
            return back()->with('error', __('Order already processed'));
        }

        DB::transaction(function () use ($transferOrder) {
            $transferOrder->loadMissing('items');

            foreach ($transferOrder->items as $item) {
                // 扣除來源庫存（僅 W2W）
                if ($transferOrder->type === TransferOrder::TYPE_W2W && $transferOrder->from_warehouse_id) {
                    $fromStock = WarehouseStock::where('warehouse_id', $transferOrder->from_warehouse_id)
                        ->where('product_id', $item->product_id)->first();

                    if (!$fromStock || $fromStock->quantity < $item->quantity) {
                        throw new \Exception(__('Insufficient stock for transfer'));
                    }

                    $beforeFrom = $fromStock->quantity;
                    $fromStock->decrement('quantity', $item->quantity);

                    StockMovement::create([
                        'company_id' => $transferOrder->company_id,
                        'warehouse_id' => $transferOrder->from_warehouse_id,
                        'product_id' => $item->product_id,
                        'type' => 'transfer_out',
                        'quantity' => $item->quantity,
                        'before_qty' => $beforeFrom,
                        'after_qty' => $beforeFrom - $item->quantity,
                        'reference_type' => TransferOrder::class,
                        'reference_id' => $transferOrder->id,
                        'note' => __('Transfer out') . ' #' . $transferOrder->order_no,
                        'created_by' => Auth::id(),
                    ]);
                }

                // 增加目標倉庫庫存
                $toStock = WarehouseStock::firstOrCreate(
                    ['warehouse_id' => $transferOrder->to_warehouse_id, 'product_id' => $item->product_id],
                    ['quantity' => 0, 'safety_stock' => 0]
                );
                $beforeTo = $toStock->quantity;
                $toStock->increment('quantity', $item->quantity);

                StockMovement::create([
                    'company_id' => $transferOrder->company_id,
                    'warehouse_id' => $transferOrder->to_warehouse_id,
                    'product_id' => $item->product_id,
                    'type' => 'transfer_in',
                    'quantity' => $item->quantity,
                    'before_qty' => $beforeTo,
                    'after_qty' => $beforeTo + $item->quantity,
                    'reference_type' => TransferOrder::class,
                    'reference_id' => $transferOrder->id,
                    'note' => __('Transfer in') . ' #' . $transferOrder->order_no,
                    'created_by' => Auth::id(),
                ]);
            }

            $transferOrder->update([
                'status' => TransferOrder::STATUS_COMPLETED,
                'completed_at' => now(),
            ]);
        });

        if (request()->ajax()) {
            return response()->json([
                'success' => true,
                'message' => __('Transfer order confirmed and inventory updated')
            ]);
        }

        return back()->with('success', __('Transfer order confirmed and inventory updated'));
    }

    /**
     * 刪除調撥單（僅限草稿）
     */
    public function destroyTransfer(TransferOrder $transferOrder)
    {
        if ($transferOrder->status !== TransferOrder::STATUS_DRAFT) {
            if (request()->ajax()) {
                return response()->json(['success' => false, 'message' => __('Only draft orders can be deleted')], 422);
            }
            return back()->with('error', __('Only draft orders can be deleted'));
        }

        $transferOrder->delete(); // Soft delete

        if (request()->ajax()) {
            return response()->json([
                'success' => true,
                'message' => __('Transfer order deleted successfully')
            ]);
        }

        return back()->with('success', __('Transfer order deleted successfully'));
    }

    // ─── 模組 4：機台庫存總覽 ───

    public function machineInventory(Request $request)
    {
        $currentUser = Auth::user();
        $companyId = trim((string) $request->input('company_id', ''));
        $isSystemAdmin = $currentUser->isSystemAdmin();

        $query = Machine::with(['slots' => fn($q) => $q->with('product:id,name,image_url')->orderByRaw('CAST(slot_no AS UNSIGNED) ASC')])
            ->withCount('slots as total_slots')
            ->withSum('slots as total_stock', 'stock')
            ->withSum('slots as total_capacity', 'max_stock');

        if ($isSystemAdmin && $companyId !== '') {
            $query->where('company_id', $companyId);
        }

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('serial_no', 'like', "%{$search}%");
            });
        }

        // 預設按庫存率由低到高排序 (補貨優先)，在庫存率相同時再按名稱排序
        $machines = $query->orderByRaw('CASE WHEN total_capacity > 0 THEN (total_stock / total_capacity) ELSE 1 END ASC')
            ->orderBy('name')
            ->paginate($request->input('per_page', 10))
            ->withQueryString();

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'html' => view('admin.warehouses.partials.list-machines', compact('machines'))->render()
            ]);
        }

        $companies = $isSystemAdmin
            ? \App\Models\System\Company::active()->orderBy('name')->get()
            : collect();

        // 供批次匯出 modal 勾選用的完整機台清單（全域 scope 已限可存取機台）
        $exportMachines = Machine::select('id', 'name', 'serial_no')->orderBy('name')->get();

        return view('admin.warehouses.machine-inventory', compact('machines', 'companies', 'exportMachines'));
    }


    /**
     * 匯出單一機台的「每個貨道」明細（CSV / Excel）——供機台詳細視圖(矩陣/表格)旁的匯出鈕使用。
     * 參考 ProductController::export / SalesController::handleExport 的串流下載模式。
     */
    public function machineInventoryExport(Request $request, Machine $machine)
    {
        $type = $request->input('export', 'csv');
        $isExcel = ($type === 'excel');
        $ext = $isExcel ? 'xls' : 'csv';
        $contentType = $isExcel ? 'application/vnd.ms-excel; charset=utf-8' : 'text/csv; charset=utf-8';

        $slots = $machine->slots()
            ->with('product:id,name,barcode')
            ->orderByRaw('CAST(slot_no AS UNSIGNED) ASC')
            ->get();

        $headers = ['貨道號', '商品ID', '商品名稱', '商品條碼', '類型', '目前庫存', '滿庫容量', '庫存率(%)', '效期', '批號', '啟用', '鎖定'];

        $safeSerial = preg_replace('/[^A-Za-z0-9_-]/', '', (string) $machine->serial_no);
        $filename = '貨道明細_' . ($safeSerial ?: $machine->id) . '_' . now()->format('YmdHis') . '.' . $ext;

        $callback = function () use ($slots, $headers, $isExcel) {
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
                fwrite($file, "\xEF\xBB\xBF"); // UTF-8 BOM
                fputcsv($file, $headers);
            }

            foreach ($slots as $slot) {
                $stock = (int) $slot->stock;
                $capacity = (int) $slot->max_stock;
                $rate = $capacity > 0 ? round($stock / $capacity * 100) : 0;

                $row = [
                    $slot->slot_no,
                    $slot->product_id ?? '',
                    $slot->product->name ?? '',
                    $slot->product->barcode ?? '',
                    $slot->type,
                    $stock,
                    $capacity,
                    $rate,
                    $slot->expiry_date ? $slot->expiry_date->format('Y-m-d') : '',
                    $slot->batch_no,
                    $slot->is_active ? '是' : '否',
                    $slot->is_locked ? '是' : '否',
                ];

                if ($isExcel) {
                    fwrite($file, '<tr>');
                    foreach ($row as $cell) {
                        fwrite($file, '<td>' . htmlspecialchars((string) $cell) . '</td>');
                    }
                    fwrite($file, '</tr>');
                } else {
                    fputcsv($file, $row);
                }
            }

            if ($isExcel) {
                fwrite($file, '</tbody></table></body></html>');
            }
            fclose($file);
        };

        return response()->streamDownload($callback, $filename, ['Content-Type' => $contentType]);
    }

    /**
     * 批次匯出「機台庫存概覽」——每台機台一個分頁(Excel sheet)/一個檔(CSV)，內容為該機台每貨道明細。
     * Excel = 多分頁 xlsx(OpenSpout)；CSV 無分頁概念，故每台一個 .csv 打包成 ZIP。
     * ids：逗號字串或陣列；空則匯出全部可存取機台。全域 scope 確保只含可存取機台。
     */
    public function machineInventoryExportBatch(Request $request)
    {
        $isExcel = ($request->input('export', 'csv') === 'excel');

        $ids = $request->input('ids');
        if (is_string($ids)) {
            $ids = array_filter(array_map('intval', explode(',', $ids)));
        } elseif (is_array($ids)) {
            $ids = array_filter(array_map('intval', $ids));
        } else {
            $ids = [];
        }

        $query = Machine::with(['slots' => fn ($q) => $q->with('product:id,name,barcode')->orderByRaw('CAST(slot_no AS UNSIGNED) ASC')])
            ->orderBy('name');
        if (!empty($ids)) {
            $query->whereIn('id', $ids);
        }
        $machines = $query->get();

        $headers = ['貨道號', '商品ID', '商品名稱', '商品條碼', '類型', '目前庫存', '滿庫容量', '庫存率(%)', '效期', '批號', '啟用', '鎖定'];
        $ts = now()->format('YmdHis');

        // 單台的資料列
        $rowsOf = function ($machine) {
            $rows = [];
            foreach ($machine->slots as $slot) {
                $stock = (int) $slot->stock;
                $capacity = (int) $slot->max_stock;
                $rate = $capacity > 0 ? round($stock / $capacity * 100) : 0;
                $rows[] = [
                    (string) $slot->slot_no,
                    $slot->product_id ?? '',
                    $slot->product->name ?? '',
                    $slot->product->barcode ?? '',
                    (string) $slot->type,
                    $stock,
                    $capacity,
                    $rate,
                    $slot->expiry_date ? $slot->expiry_date->format('Y-m-d') : '',
                    (string) $slot->batch_no,
                    $slot->is_active ? '是' : '否',
                    $slot->is_locked ? '是' : '否',
                ];
            }
            return $rows;
        };

        // 分頁/檔名（機台名稱_序號，去非法字元、限長、去重；Excel sheet 名上限 31 字）
        $labelOf = function ($machine, array &$used) {
            $b = trim(($machine->name ?? '機台') . '_' . ($machine->serial_no ?? $machine->id));
            $b = preg_replace('/[\\\\\\/\\?\\*\\[\\]:]/u', '_', $b);
            $b = mb_substr($b, 0, 28);
            $name = $b;
            $i = 1;
            while (in_array($name, $used, true)) {
                $name = mb_substr($b, 0, 24) . '_' . (++$i);
            }
            $used[] = $name;
            return $name;
        };

        if ($isExcel) {
            $tmp = tempnam(sys_get_temp_dir(), 'invxlsx');
            $writer = new \OpenSpout\Writer\XLSX\Writer();
            $writer->openToFile($tmp);
            $used = [];
            $first = true;
            foreach ($machines as $machine) {
                $sheet = $first ? $writer->getCurrentSheet() : $writer->addNewSheetAndMakeItCurrent();
                $first = false;
                $sheet->setName($labelOf($machine, $used));
                $writer->addRow(\OpenSpout\Common\Entity\Row::fromValues($headers));
                foreach ($rowsOf($machine) as $row) {
                    $writer->addRow(\OpenSpout\Common\Entity\Row::fromValues($row));
                }
            }
            if ($first) {
                $writer->getCurrentSheet()->setName('機台庫存');
                $writer->addRow(\OpenSpout\Common\Entity\Row::fromValues($headers));
            }
            $writer->close();

            return response()->download($tmp, "機台庫存_{$ts}.xlsx", [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ])->deleteFileAfterSend(true);
        }

        // CSV：每台一個 .csv 打包成 ZIP
        $tmp = tempnam(sys_get_temp_dir(), 'invzip');
        $zip = new \ZipArchive();
        $zip->open($tmp, \ZipArchive::OVERWRITE);
        $used = [];
        foreach ($machines as $machine) {
            $fh = fopen('php://temp', 'r+');
            fputcsv($fh, $headers);
            foreach ($rowsOf($machine) as $row) {
                fputcsv($fh, $row);
            }
            rewind($fh);
            $content = "\xEF\xBB\xBF" . stream_get_contents($fh);
            fclose($fh);
            $zip->addFromString($labelOf($machine, $used) . '.csv', $content);
        }
        if ($machines->isEmpty()) {
            $zip->addFromString('機台庫存.csv', "\xEF\xBB\xBF" . implode(',', $headers) . "\n");
        }
        $zip->close();

        return response()->download($tmp, "機台庫存_{$ts}.zip")->deleteFileAfterSend(true);
    }

    /**
     * AJAX：取得單台機台貨道詳情
     */
    public function machineSlots(Machine $machine)
    {
        $slots = $machine->slots()
            ->with(['product:id,name,image_url,barcode,price'])
            ->orderByRaw('CAST(slot_no AS UNSIGNED) ASC')
            ->get();

        // 取得流水帳中出現過的所有貨道編號 (包含已移除的)
        $historicalSlotNos = MachineStockMovement::where('machine_id', $machine->id)
            ->distinct()
            ->pluck('slot_no')
            ->map(fn($val) => (string)$val)
            ->toArray();

        return response()->json([
            'success' => true, 
            'machine' => $machine,
            'slots' => $slots,
            'historical_slot_nos' => $historicalSlotNos
        ]);
    }

    /**
     * 機台庫存異動紀錄 (AJAX) — 供 Slide-out Panel 使用
     */
    public function machineStockMovements(Request $request, Machine $machine)
    {
        $query = $machine->stockMovements()
            ->with(['product:id,name', 'creator:id,name'])
            ->latest();

        if ($request->filled('type')) {
            $query->where('type', $request->input('type'));
        }
        if ($request->filled('slot_no')) {
            $query->where('slot_no', $request->input('slot_no'));
        }
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->input('date_from'));
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->input('date_to'));
        }

        $movements = $query->paginate($request->input('per_page', 20))->withQueryString();

        return response()->json([
            'success'   => true,
            'movements' => $movements->items(),
            'meta'      => [
                'current_page' => $movements->currentPage(),
                'last_page'    => $movements->lastPage(),
                'total'        => $movements->total(),
            ],
        ]);
    }

    // ─── 模組 5：機台補貨 ───

    public function replenishments(Request $request)
    {
        $currentUser = Auth::user();
        $companyId = trim((string) $request->input('company_id', ''));
        $isSystemAdmin = $currentUser->isSystemAdmin();

        $query = ReplenishmentOrder::with(['warehouse:id,name', 'machine:id,name,serial_no', 'assignee:id,name', 'creator:id,name'])
            ->withCount('items')
            ->latest();

        if ($isSystemAdmin && $companyId !== '') {
            $query->where('company_id', $companyId);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }
        if ($search = $request->input('search_order')) {
            $query->where('order_no', 'like', "%{$search}%");
        }
        if ($request->filled('start_date') || $request->filled('end_date')) {
            if ($request->filled('start_date')) {
                $query->where('created_at', '>=', \Illuminate\Support\Carbon::parse($request->start_date));
            }
            if ($request->filled('end_date')) {
                $query->where('created_at', '<=', \Illuminate\Support\Carbon::parse($request->end_date)->endOfMinute());
            }
        } else {
            // 隱性限制：預設只抓 6 個月內的單據以保護效能，但 UI 保持空白
            $query->where('created_at', '>=', now()->subMonths(6)->startOfDay());
        }

        $orders = $query->paginate($request->input('per_page', 10))->withQueryString();

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'html' => view('admin.warehouses.partials.table-replenishments', compact('orders'))->render()
            ]);
        }

        $companies = $isSystemAdmin
            ? \App\Models\System\Company::active()->orderBy('name')->get()
            : collect();
        $applyCompanyFilter = fn($query) => $query->when(
            $isSystemAdmin && $companyId !== '',
            fn($q) => $q->where('company_id', $companyId)
        );

        $warehouses = $applyCompanyFilter(Warehouse::active())->orderBy('name')->get(['id', 'name']);
        $machines = $applyCompanyFilter(Machine::query())->orderBy('name')->get(['id', 'name', 'serial_no']);
        $products = $applyCompanyFilter(Product::with('translations'))->orderBy('name')->get(['id', 'name', 'image_url', 'name_dictionary_key']);

        // 可見範圍：系統管理員看全部；主帳號看同公司全部；子帳號僅看自己直接建立的下層（避免洩漏旁線帳號）。
        $userQuery = \App\Models\System\User::where('status', 1)->orderBy('name')->visibleTo($currentUser);
        if ($isSystemAdmin && $companyId !== '') {
            $userQuery->where('company_id', $companyId);
        }
        $users = $userQuery->get(['id', 'name']);

        $defaultStart = '';
        $defaultEnd = '';

        return view('admin.warehouses.replenishments', compact('orders', 'warehouses', 'machines', 'products', 'users', 'companies', 'defaultStart', 'defaultEnd'));
    }

    /**
     * 建立補貨單（含 current_stock/max_stock 快照）
     */
    public function storeReplenishment(Request $request)
    {
        $validated = $request->validate([
            'warehouse_id' => 'required|exists:warehouses,id',
            'machine_id' => 'required|exists:machines,id',
            'note' => 'nullable|string|max:500',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.slot_no' => 'required|integer',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.current_stock' => 'nullable|integer|min:0',
            'items.*.max_stock' => 'nullable|integer|min:0',
        ], [
            'warehouse_id.required' => __('Please select source warehouse'),
            'machine_id.required' => __('Please select target machine'),
            'items.required' => __('Please add at least one item'),
            'items.*.product_id.required' => __('Please select product for item :num', ['num' => ':position']),
            'items.*.quantity.min' => __('Please enter valid quantity for item :num', ['num' => ':position']),
        ]);

        // 補貨針對機台，單據歸屬以「機台所綁公司」為準（系統管理員可為他公司機台建單）
        $machine = Machine::findOrFail($validated['machine_id']);

        DB::transaction(function () use ($validated, $machine) {
            $prefix = 'RP-' . now()->format('Ymd') . '-';
            $lastOrder = ReplenishmentOrder::withoutGlobalScopes()
                ->withTrashed()
                ->where('order_no', 'like', $prefix . '%')
                ->orderBy('order_no', 'desc')
                ->first();

            $nextNumber = $lastOrder ? (int)substr($lastOrder->order_no, -4) + 1 : 1;
            $order_no = $prefix . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);

            $order = ReplenishmentOrder::create([
                'company_id' => $machine->company_id,
                'order_no' => $order_no,
                'warehouse_id' => $validated['warehouse_id'],
                'machine_id' => $machine->id,
                'status' => ReplenishmentOrder::STATUS_PENDING,
                'note' => $validated['note'] ?? null,
                'created_by' => Auth::id(),
            ]);

            // 預先取得商品資訊以建立快照
            $productIds = collect($validated['items'])->pluck('product_id')->unique()->toArray();
            $products = \App\Models\Product\Product::whereIn('id', $productIds)->get()->keyBy('id');

            // 批量寫入明細（遵循 DB Best Practice: 不在迴圈中逐筆 create）
            $itemsData = [];
            foreach ($validated['items'] as $item) {
                $product = $products->get($item['product_id']);
                $itemsData[] = [
                    'replenishment_order_id' => $order->id,
                    'product_id' => $item['product_id'],
                    'slot_no' => $item['slot_no'],
                    'quantity' => $item['quantity'],
                    'current_stock' => $item['current_stock'] ?? 0,
                    'max_stock' => $item['max_stock'] ?? 0,
                    'snapshot_product_name' => $product ? $product->name : null,
                    'snapshot_product_name_key' => $product ? $product->name_dictionary_key : null,
                    'snapshot_product_barcode' => $product ? $product->barcode : null,
                    'snapshot_product_image_url' => $product ? $product->image_url : null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
            ReplenishmentOrderItem::insert($itemsData);
        });

        session()->flash('success', __('Replenishment order created successfully'));

        return redirect()->route('admin.warehouses.replenishments');
    }

    /**
     * 自動補貨單 — 自動讀取機台所有貨道，計算 max_stock - stock
     */
    public function autoReplenishment(Request $request)
    {
        $validated = $request->validate([
            'warehouse_id' => 'required|exists:warehouses,id',
            'machine_id' => 'required|exists:machines,id',
            'note' => 'nullable|string|max:500',
            'items' => 'nullable|array',
            'items.*.slot_no' => 'required_with:items|integer',
            'items.*.product_id' => 'required_with:items|exists:products,id',
            'items.*.quantity' => 'required_with:items|integer|min:1',
            'items.*.current_stock' => 'nullable|integer|min:0',
            'items.*.max_stock' => 'nullable|integer|min:0',
        ]);

        $machine = Machine::findOrFail($validated['machine_id']);

        // 如果前端未提供 items（首次請求），自動計算補貨清單
        if (empty($validated['items'])) {
            $slots = MachineSlot::with('product:id,name,image_url,name_dictionary_key')
                ->where('machine_id', $machine->id)
                ->whereNotNull('product_id')
                ->whereColumn('stock', '<', 'max_stock')
                ->orderByRaw('CAST(slot_no AS UNSIGNED) ASC')
                ->get();

            if ($slots->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => __('All slots are fully stocked')
                ], 422);
            }

            // 檢查倉庫庫存
            $warehouse = Warehouse::findOrFail($validated['warehouse_id']);
            $warehouseStocks = WarehouseStock::where('warehouse_id', $warehouse->id)
                ->pluck('quantity', 'product_id');

            // 按商品彙總需求量
            $demandByProduct = [];
            foreach ($slots as $slot) {
                $pid = $slot->product_id;
                $need = $slot->max_stock - $slot->stock;
                $demandByProduct[$pid] = ($demandByProduct[$pid] ?? 0) + $need;
            }

            // 檢查是否有庫存不足的商品
            $insufficientProducts = [];
            foreach ($demandByProduct as $pid => $totalNeed) {
                $available = $warehouseStocks[$pid] ?? 0;
                if ($available < $totalNeed) {
                    $insufficientProducts[$pid] = [
                        'available' => $available,
                        'needed' => $totalNeed,
                    ];
                }
            }

            $slotData = $slots->map(fn($s) => [
                'slot_no' => $s->slot_no,
                'product_id' => $s->product_id,
                'product_name' => $s->product?->localized_name ?? 'Unknown',
                'image_url' => $s->product?->image_url,
                'current_stock' => $s->stock,
                'max_stock' => $s->max_stock,
                'quantity' => $s->max_stock - $s->stock,
            ]);

            return response()->json([
                'success' => true,
                'preview' => true,
                'machine_name' => $machine->name,
                'slots' => $slotData,
                'insufficient' => $insufficientProducts,
                'warehouse_stocks' => $warehouseStocks,
                'has_warning' => !empty($insufficientProducts),
            ]);
        }

        // 有 items = 確認建單
        DB::transaction(function () use ($validated, $machine) {
            $prefix = 'RP-' . now()->format('Ymd') . '-';
            $lastOrder = ReplenishmentOrder::withoutGlobalScopes()
                ->withTrashed()
                ->where('order_no', 'like', $prefix . '%')
                ->orderBy('order_no', 'desc')
                ->first();

            $nextNumber = $lastOrder ? (int)substr($lastOrder->order_no, -4) + 1 : 1;
            $order_no = $prefix . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);

            $order = ReplenishmentOrder::create([
                'company_id' => $machine->company_id,
                'order_no' => $order_no,
                'warehouse_id' => $validated['warehouse_id'],
                'machine_id' => $machine->id,
                'status' => ReplenishmentOrder::STATUS_PENDING,
                'note' => $validated['note'] ?? __('Auto Replenishment'),
                'created_by' => Auth::id(),
            ]);

            // 預先取得商品資訊以建立快照
            $productIds = collect($validated['items'])->pluck('product_id')->unique()->toArray();
            $products = \App\Models\Product\Product::whereIn('id', $productIds)->get()->keyBy('id');

            $itemsData = [];
            foreach ($validated['items'] as $item) {
                $product = $products->get($item['product_id']);
                $itemsData[] = [
                    'replenishment_order_id' => $order->id,
                    'product_id' => $item['product_id'],
                    'slot_no' => $item['slot_no'],
                    'quantity' => $item['quantity'],
                    'current_stock' => $item['current_stock'] ?? 0,
                    'max_stock' => $item['max_stock'] ?? 0,
                    'snapshot_product_name' => $product ? $product->name : null,
                    'snapshot_product_name_key' => $product ? $product->name_dictionary_key : null,
                    'snapshot_product_barcode' => $product ? $product->barcode : null,
                    'snapshot_product_image_url' => $product ? $product->image_url : null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
            ReplenishmentOrderItem::insert($itemsData);
        });

        session()->flash('success', __('Replenishment order created successfully'));
        return response()->json([
            'success' => true,
            'message' => __('Replenishment order created successfully')
        ]);
    }

    /**
     * 狀態推進 (pending → prepared → delivering → completed)
     */
    public function updateReplenishmentStatus(Request $request, ReplenishmentOrder $replenishmentOrder)
    {
        $validated = $request->validate([
            'status' => 'required|in:prepared,delivering,completed',
            'assigned_to' => 'nullable|exists:users,id',
        ]);

        $newStatus = $validated['status'];

        if (!$replenishmentOrder->canTransitionTo($newStatus)) {
            return response()->json([
                'success' => false,
                'message' => __('Invalid status transition')
            ], 422);
        }

        DB::transaction(function () use ($replenishmentOrder, $newStatus, $validated) {
            $replenishmentOrder->loadMissing('items');

            // 更新指派人員 (如果有傳遞的話)
            if (array_key_exists('assigned_to', $validated)) {
                $replenishmentOrder->assigned_to = $validated['assigned_to'];
            }

            // pending → prepared：扣減倉庫庫存
            if ($newStatus === ReplenishmentOrder::STATUS_PREPARED) {
                foreach ($replenishmentOrder->items as $item) {
                    $stock = WarehouseStock::firstOrCreate(
                        [
                            'warehouse_id' => $replenishmentOrder->warehouse_id,
                            'product_id' => $item->product_id,
                        ],
                        [
                            'company_id' => $replenishmentOrder->company_id,
                            'quantity' => 0,
                        ]
                    );

                    $stock->lockForUpdate();

                    $before = $stock->quantity;
                    $stock->decrement('quantity', $item->quantity);

                    StockMovement::create([
                        'company_id' => $replenishmentOrder->company_id,
                        'warehouse_id' => $replenishmentOrder->warehouse_id,
                        'product_id' => $item->product_id,
                        'type' => 'out',
                        'quantity' => $item->quantity,
                        'before_qty' => $before,
                        'after_qty' => $before - $item->quantity,
                        'reference_type' => ReplenishmentOrder::class,
                        'reference_id' => $replenishmentOrder->id,
                        'note' => __('Replenishment prepare') . ' #' . $replenishmentOrder->order_no,
                        'created_by' => Auth::id(),
                    ]);
                }
            }

            // delivering → completed：更新完成時間（去耦機台庫存累加，由機台端上報決定）
            if ($newStatus === ReplenishmentOrder::STATUS_COMPLETED) {
                $replenishmentOrder->completed_at = now();
            }

            $replenishmentOrder->status = $newStatus;
            $replenishmentOrder->save();
        });

        $statusLabels = [
            'prepared' => __('Confirm Prepare'),
            'delivering' => __('Start Delivery'),
            'completed' => __('Confirm Complete'),
        ];

        $statusMessages = [
            'prepared' => __('Order prepared successfully, stock deducted'),
            'delivering' => __('Order is now in delivery'),
            'completed' => __('Order completed and stock updated'),
        ];

        session()->flash('success', $statusMessages[$newStatus] ?? __('Status updated successfully'));

        return response()->json([
            'success' => true,
            'message' => __('Status updated successfully')
        ]);
    }

    /**
     * 取消補貨單 — 若已備貨/配送中則退回倉庫庫存
     */
    public function cancelReplenishment(ReplenishmentOrder $replenishmentOrder)
    {
        if (!$replenishmentOrder->canTransitionTo(ReplenishmentOrder::STATUS_CANCELLED)) {
            return response()->json([
                'success' => false,
                'message' => __('Cannot cancel this order')
            ], 422);
        }

        DB::transaction(function () use ($replenishmentOrder) {
            // 若已備貨(prepared)或配送中(delivering)，需退回倉庫庫存
            if (in_array($replenishmentOrder->status, [ReplenishmentOrder::STATUS_PREPARED, ReplenishmentOrder::STATUS_DELIVERING])) {
                $replenishmentOrder->loadMissing('items');

                foreach ($replenishmentOrder->items as $item) {
                    $stock = WarehouseStock::firstOrCreate(
                        [
                            'warehouse_id' => $replenishmentOrder->warehouse_id,
                            'product_id' => $item->product_id,
                        ],
                        [
                            'company_id' => $replenishmentOrder->company_id,
                            'quantity' => 0,
                        ]
                    );

                    $stock->lockForUpdate();

                    $before = $stock->quantity;
                    $stock->increment('quantity', $item->quantity);

                    StockMovement::create([
                        'company_id' => $replenishmentOrder->company_id,
                        'warehouse_id' => $replenishmentOrder->warehouse_id,
                        'product_id' => $item->product_id,
                        'type' => 'in',
                        'quantity' => $item->quantity,
                        'before_qty' => $before,
                        'after_qty' => $before + $item->quantity,
                        'reference_type' => ReplenishmentOrder::class,
                        'reference_id' => $replenishmentOrder->id,
                        'note' => __('Replenishment cancelled, stock returned') . ' #' . $replenishmentOrder->order_no,
                        'created_by' => Auth::id(),
                    ]);
                }
            }

            $replenishmentOrder->update([
                'status' => ReplenishmentOrder::STATUS_CANCELLED,
            ]);
        });

        $msg = __('Order has been cancelled');
        if (in_array($replenishmentOrder->getOriginal('status'), [ReplenishmentOrder::STATUS_PREPARED, ReplenishmentOrder::STATUS_DELIVERING])) {
            $msg .= ' — ' . __('Stock returned to warehouse');
        }

        session()->flash('success', $msg);

        return response()->json([
            'success' => true,
            'message' => $msg
        ]);
    }

    /**
     * 指派補貨人員（僅限同租戶帳號）
     */
    public function assignReplenishment(Request $request, ReplenishmentOrder $replenishmentOrder)
    {
        $validated = $request->validate([
            'assigned_to' => 'nullable|exists:users,id',
        ]);

        if ($validated['assigned_to']) {
            // 確保指派人員屬於同租戶
            $user = \App\Models\System\User::where('id', $validated['assigned_to'])
                ->where('company_id', Auth::user()->company_id)
                ->first();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => __('Invalid personnel selection')
                ], 422);
            }
            $replenishmentOrder->assigned_to = $user->id;
        } else {
            $replenishmentOrder->assigned_to = null;
        }

        session()->flash('success', __('Personnel assigned successfully'));

        return response()->json([
            'success' => true,
            'message' => __('Personnel assigned successfully')
        ]);
    }

    /**
     * AJAX：取得特定機台的貨道資訊供補貨選擇
     */
    public function getMachineSlotsForReplenishment(Machine $machine)
    {
        $slots = $machine->slots()
            ->with(['product' => fn($q) => $q->select('id', 'name', 'image_url', 'name_dictionary_key')->with('translations')])
            ->orderByRaw('CAST(slot_no AS UNSIGNED) ASC')
            ->get();

        return response()->json([
            'success' => true,
            'machine' => [
                'id' => $machine->id,
                'name' => $machine->name,
                'serial_no' => $machine->serial_no,
            ],
            'slots' => $slots->map(fn($s) => [
                'slot_no' => $s->slot_no,
                'product_id' => $s->product_id,
                'product_name' => $s->product?->localized_name ?? null,
                'image_url' => $s->product?->image_url,
                'stock' => $s->stock,
                'max_stock' => $s->max_stock,
                'needs_replenishment' => $s->product_id && $s->stock < $s->max_stock,
            ]),
        ]);
    }

    /**
     * AJAX：取得補貨單詳情
     */
    public function replenishmentOrderDetails(Request $request, $id)
    {
        $order = ReplenishmentOrder::with(['machine', 'warehouse', 'creator', 'assignee'])
            ->where('id', $id)
            ->firstOrFail();

        $items = ReplenishmentOrderItem::with(['translations', 'product.translations'])
            ->where('replenishment_order_id', $id)
            ->get()
            ->map(function ($item) {
                return [
                    'product_id' => $item->product_id,
                    'product_name' => $item->localized_name ?: __('Unknown'),
                    'image_url' => $item->image_url,
                    'quantity' => $item->quantity,
                    'slot_no' => $item->slot_no,
                    'current_stock' => $item->current_stock,
                    'max_stock' => $item->max_stock,
                ];
            });

        return response()->json([
            'success' => true,
            'order' => [
                'id' => $order->id,
                'order_no' => $order->order_no,
                'status' => $order->status,
                'machine_name' => $order->machine?->name,
                'machine_serial' => $order->machine?->serial_no,
                'warehouse_name' => $order->warehouse?->name,
                'creator_name' => $order->creator?->name,
                'assignee_name' => $order->assignee?->name,
                'note' => $order->note,
                'created_at' => $order->created_at->format('Y-m-d H:i:s'),
                'completed_at' => $order->completed_at?->format('Y-m-d H:i:s'),
            ],
            'items' => $items
        ]);
    }

    /**
     * 舊版 confirmReplenishment 已重構為 updateReplenishmentStatus
     * 保留向下相容
     */
    public function confirmReplenishment(ReplenishmentOrder $replenishmentOrder)
    {
        // 模擬直接完成流程：pending → completed
        request()->merge(['status' => 'completed']);
        return $this->updateReplenishmentStatus(request(), $replenishmentOrder);
    }

    /**
     * AJAX：取得來源庫存（倉庫或機台）
     */
    public function getStockAjax(Request $request)
    {
        $warehouseId = $request->input('warehouse_id');
        $machineId = $request->input('machine_id');

        try {
            if ($warehouseId) {
                // Warehouse uses TenantScoped, findOrFail will respect it
                $warehouse = Warehouse::findOrFail($warehouseId);
                
                // 獲取公司所有啟動中的商品
                $products = Product::where('company_id', $warehouse->company_id)
                    ->active()
                    ->select('id', 'name', 'image_url', 'name_dictionary_key')
                    ->with('translations')
                    ->get();
                
                // 獲取目前的庫存量（用於顯示，不限制選擇）
                $stocks = WarehouseStock::where('warehouse_id', $warehouse->id)
                    ->pluck('quantity', 'product_id');

                $data = $products->map(fn($p) => [
                    'id' => $p->id,
                    'name' => $p->localized_name ?? 'Unknown',
                    'quantity' => $stocks[$p->id] ?? 0,
                    'image' => $p->image_url
                ]);
            } elseif ($machineId) {
                // Machine uses TenantScoped and machine_access scope
                $machine = Machine::findOrFail($machineId);
                $slots = MachineSlot::with(['product' => fn($q) => $q->select('id', 'name', 'image_url', 'name_dictionary_key')->with('translations')])
                    ->where('machine_id', $machine->id)
                    ->where('stock', '>', 0)
                    ->get()
                    ->groupBy('product_id');

                $data = $slots->map(function($group) {
                    $p = $group->first()->product;
                    return [
                        'id' => $group->first()->product_id,
                        'name' => $p?->localized_name ?? 'Unknown',
                        'quantity' => $group->sum('stock'),
                        'image' => $p?->image_url
                    ];
                })->values();
            } else {
                return response()->json(['success' => false, 'message' => __('No source provided')]);
            }
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => __('Unauthorized or not found')]);
        }

        return response()->json(['success' => true, 'data' => $data]);
    }

    /**
     * AJAX：取得倉庫商品庫存詳情 (用於滑出面板)
     */
    public function warehouseStocks(Warehouse $warehouse, Request $request)
    {
        $query = $warehouse->stocks()->with(['product' => fn($q) => $q->select('id', 'name', 'image_url', 'name_dictionary_key')->with('translations')]);

        if ($search = $request->input('search')) {
            $query->whereHas('product', fn($q) => $q->where('name', 'like', "%{$search}%"));
        }

        $stocks = $query->get();

        return response()->json([
            'success' => true,
            'warehouse' => [
                'id' => $warehouse->id,
                'name' => $warehouse->name,
                'type' => $warehouse->type,
            ],
            'stocks' => $stocks->map(fn($s) => [
                'id' => $s->id,
                'product_id' => $s->product_id,
                'product_name' => $s->product?->localized_name ?? 'Unknown',
                'image_url' => $s->product?->image_url,
                'quantity' => (int)$s->quantity,
                'safety_stock' => (int)$s->safety_stock,
            ])
        ]);
    }

    /**
     * AJAX：取得進貨單詳情
     */
    public function stockInOrderDetails(StockInOrder $order)
    {
        $order->load(['warehouse', 'creator', 'items.product.translations']);

        return response()->json([
            'success' => true,
            'order' => [
                'id' => $order->id,
                'order_no' => $order->order_no,
                'status' => $order->status,
                'warehouse_name' => $order->warehouse?->name,
                'creator_name' => $order->creator?->name,
                'created_at' => $order->created_at?->format('Y-m-d H:i:s'),
                'completed_at' => $order->completed_at?->format('Y-m-d H:i:s'),
                'note' => $order->note,
            ],
            'items' => $order->items->map(fn($item) => [
                'product_name' => $item->product?->localized_name ?? 'Unknown',
                'image_url' => $item->product?->image_url,
                'quantity' => $item->quantity,
            ])
        ]);
    }


    /**
     * Get transfer order details for AJAX.
     */
    public function transferOrderDetails(Request $request, $id)
    {
        $order = TransferOrder::with(['fromWarehouse', 'toWarehouse', 'fromMachine', 'creator'])
            ->where('id', $id)
            ->firstOrFail();

        $items = \App\Models\Warehouse\TransferOrderItem::with(['translations', 'product.translations'])
            ->where('transfer_order_id', $id)
            ->get()
            ->map(function($item) {
                return [
                    'product_id' => $item->product_id,
                    'product_name' => $item->localized_name ?: __('Unknown'),
                    'image_url' => $item->image_url,
                    'quantity' => (int)$item->quantity,
                ];
            });

        return response()->json([
            'success' => true,
            'order' => [
                'id' => $order->id,
                'order_no' => $order->order_no,
                'type' => $order->type,
                'from_name' => $order->type === 'warehouse_to_warehouse' 
                    ? ($order->fromWarehouse?->name ?? __('Unknown'))
                    : ($order->fromMachine?->name ?? __('Unknown')),
                'to_name' => $order->toWarehouse?->name ?? __('Unknown'),
                'status' => $order->status,
                'creator_name' => $order->creator?->name ?? __('System'),
                'note' => $order->note,
                'created_at' => $order->created_at->format('Y-m-d H:i:s'),
                'completed_at' => $order->completed_at?->format('Y-m-d H:i:s'),
            ],
            'items' => $items
        ]);
    }

    /**
     * 列印調撥單
     */
    public function printTransfer($id)
    {
        $order = TransferOrder::with(['fromWarehouse', 'toWarehouse', 'fromMachine', 'creator'])
            ->findOrFail($id);

        // 雙重防護：驗證租戶資料隔離安全
        $user = auth()->user();
        if ($user && $user->company_id && $order->company_id !== $user->company_id) {
            abort(404);
        }

        $items = \App\Models\Warehouse\TransferOrderItem::with(['product' => fn($q) => $q->with('translations')])
            ->where('transfer_order_id', $id)
            ->get();

        return view('admin.warehouses.print.transfer', compact('order', 'items'));
    }

    /**
     * 列印機台補貨單
     */
    public function printReplenishment($id)
    {
        $order = ReplenishmentOrder::with(['machine', 'warehouse', 'creator', 'assignee'])
            ->findOrFail($id);

        // 雙重防護：驗證租戶資料隔離安全
        $user = auth()->user();
        if ($user && $user->company_id && $order->company_id !== $user->company_id) {
            abort(404);
        }

        $items = ReplenishmentOrderItem::with(['product' => fn($q) => $q->with('translations')])
            ->where('replenishment_order_id', $id)
            ->get();

        return view('admin.warehouses.print.replenishment', compact('order', 'items'));
    }
}
