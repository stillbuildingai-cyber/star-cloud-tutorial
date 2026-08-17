<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class AnalysisController extends Controller
{
    // 零錢庫存分析
    public function changeStock()
    {
        return view('admin.placeholder', [
            'title' => '零錢庫存分析',
            'description' => '機台零錢數量監測與分析',
        ]);
    }

    public function machineReports(Request $request)
    {
        $user = auth()->user();
        $companyId = $user->company_id;

        // 1. 取得篩選參數
        $selectedCompanyId = $request->input('company_id');
        $selectedMachineId = $request->input('machine_id', 'ALL');
        $selectedProductId = $request->input('product_id', 'ALL');
        $paymentTypes = $request->input('payment_types', []);
        $dateRange = $request->input('date_range');

        if (!is_array($paymentTypes)) {
            $paymentTypes = $paymentTypes ? explode(',', $paymentTypes) : [];
        }
        $paymentTypes = array_filter(array_map('intval', $paymentTypes));

        // 2. 解析日期區間
        if ($dateRange) {
            $dates = explode(' to ', $dateRange);
            if (count($dates) === 2) {
                $startDate = \Illuminate\Support\Carbon::parse($dates[0])->startOfDay();
                $endDate = \Illuminate\Support\Carbon::parse($dates[1])->endOfDay();
            } else {
                $startDate = \Illuminate\Support\Carbon::parse($dates[0])->startOfDay();
                $endDate = \Illuminate\Support\Carbon::parse($dates[0])->endOfDay();
            }
        } else {
            // 預設為最近 7 天 (含今日)
            $startDate = now()->subDays(6)->startOfDay();
            $endDate = now()->endOfDay();
            $dateRange = $startDate->format('Y-m-d') . ' to ' . $endDate->format('Y-m-d');
        }

        // 3. 租戶隔離：非系統管理員強制限制為其所屬公司
        if (!$user->isSystemAdmin()) {
            $effectiveCompanyId = $companyId;
        } else {
            $effectiveCompanyId = $selectedCompanyId;
        }

        // 取得使用者授權的機台 ID 範圍 (一般用戶)
        $allowedMachineIds = null;
        if (!$user->isSystemAdmin()) {
            $allowedMachineIds = \App\Models\Machine\Machine::pluck('id')->toArray();
        }

        // 4. 加載下拉選單選項
        // 公司選單 (僅限 Super Admin)
        $companies = [];
        if ($user->isSystemAdmin()) {
            $companies = \App\Models\System\Company::active()->get(['id', 'name']);
        }

        // 機台選單 (自動套用多租戶與授權隔離)
        $machinesQuery = \App\Models\Machine\Machine::query();
        if ($effectiveCompanyId) {
            $machinesQuery->where('company_id', $effectiveCompanyId);
        }
        $machines = $machinesQuery->get(['id', 'name', 'serial_no']);

        // 商品選單
        $productsQuery = \App\Models\Product\Product::query();
        if ($effectiveCompanyId) {
            $productsQuery->where('company_id', $effectiveCompanyId);
        }
        $products = $productsQuery->active()->get(['id', 'name', 'barcode']);

        // 5. 支付工具選項 (使用者指定之 11 種)
        $availablePaymentTypes = [
            1 => __('Credit Card'),
            2 => __('E-Ticket (EasyCard/iPass)'),
            3 => __('QR Code Payment - E.SUN'), // 掃碼支付-玉山
            4 => __('Bill Acceptor'),
            5 => __('Pass Code'),
            6 => __('Pickup Code'),
            7 => __('Welcome Gift'),
            8 => __('Questionnaire'),
            9 => __('Coin Acceptor'),
            10 => __('Mobile Pay'), // NFC 手機感應（手機支付）
            11 => __('QR Code Payment - TayPay'), // 掃碼支付-TayPay
            41 => __('Staff Card'),
            42 => __('Pickup Voucher'),
        ];

        // 子查詢：取得每個 order_id + product_id 的銷售單價
        $itemPriceQuery = \Illuminate\Support\Facades\DB::table('order_items')
            ->select([
                'order_id',
                'product_id',
                \Illuminate\Support\Facades\DB::raw('MIN(price) as unit_price')
            ])
            ->groupBy('order_id', 'product_id');

        // 6. 執行主報表統計 SQL (按機台分組)
        // 子查詢 A：營業額 (計算機台實收)
        $salesQuery = \Illuminate\Support\Facades\DB::table('orders as o')
            ->select([
                'o.machine_id',
                \Illuminate\Support\Facades\DB::raw('SUM(o.pay_amount) as total_sales')
            ])
            ->where('o.payment_status', \App\Models\Transaction\Order::PAYMENT_STATUS_SUCCESS)
            ->whereNull('o.deleted_at')
            ->whereBetween('o.created_at', [$startDate, $endDate]);

        // 套用篩選條件到營業額子查詢
        if ($effectiveCompanyId) {
            $salesQuery->where('o.company_id', $effectiveCompanyId);
        }
        if ($allowedMachineIds !== null) {
            if (empty($allowedMachineIds)) {
                $salesQuery->whereRaw('1 = 0');
            } else {
                $salesQuery->whereIn('o.machine_id', $allowedMachineIds);
            }
        }
        if ($selectedMachineId && $selectedMachineId !== 'ALL') {
            $salesQuery->where('o.machine_id', $selectedMachineId);
        }
        if (!empty($paymentTypes)) {
            $salesQuery->whereIn('o.payment_type', $paymentTypes);
        }

        // 如果篩選特定商品，營業額只計算有成功出貨的該商品金額
        if ($selectedProductId && $selectedProductId !== 'ALL') {
            $salesQuery->join('dispense_records as dr', function($join) {
                    $join->on('dr.order_id', '=', 'o.id')
                         ->where('dr.dispense_status', '=', 1);
                })
                ->leftJoinSub($itemPriceQuery, 'oi', function($join) {
                    $join->on('oi.order_id', '=', 'dr.order_id')
                         ->on('oi.product_id', '=', 'dr.product_id');
                })
                ->where('dr.product_id', $selectedProductId)
                ->select([
                    'o.machine_id',
                    \Illuminate\Support\Facades\DB::raw('SUM(COALESCE(oi.unit_price, 0)) as total_sales')
                ]);
        }
        $salesQuery->groupBy('o.machine_id');

        // 子查詢 B：實際出貨件數與成本 (僅統計出貨成功且在範圍內)
        $dispenseQuery = \Illuminate\Support\Facades\DB::table('dispense_records as dr')
            ->join('orders as o', 'o.id', '=', 'dr.order_id')
            ->leftJoin('products as p', 'p.id', '=', 'dr.product_id')
            ->select([
                'dr.machine_id',
                \Illuminate\Support\Facades\DB::raw('COUNT(dr.id) as total_quantity'),
                \Illuminate\Support\Facades\DB::raw('SUM(COALESCE(p.cost, 0)) as total_cost')
            ])
            ->where('dr.dispense_status', 1)
            ->whereNull('o.deleted_at')
            ->whereBetween('o.created_at', [$startDate, $endDate]);

        // 套用篩選條件到出貨子查詢
        if ($effectiveCompanyId) {
            $dispenseQuery->where('o.company_id', $effectiveCompanyId);
        }
        if ($allowedMachineIds !== null) {
            if (empty($allowedMachineIds)) {
                $dispenseQuery->whereRaw('1 = 0');
            } else {
                $dispenseQuery->whereIn('o.machine_id', $allowedMachineIds);
            }
        }
        if ($selectedMachineId && $selectedMachineId !== 'ALL') {
            $dispenseQuery->where('o.machine_id', $selectedMachineId);
        }
        if ($selectedProductId && $selectedProductId !== 'ALL') {
            $dispenseQuery->where('dr.product_id', $selectedProductId);
        }
        if (!empty($paymentTypes)) {
            $dispenseQuery->whereIn('o.payment_type', $paymentTypes);
        }
        $dispenseQuery->groupBy('dr.machine_id');

        // 聯結機台與子查詢，取得最終統計資料
        $mainQuery = \Illuminate\Support\Facades\DB::table('machines as m')
            ->leftJoinSub($salesQuery, 'sq', 'sq.machine_id', '=', 'm.id')
            ->leftJoinSub($dispenseQuery, 'dq', 'dq.machine_id', '=', 'm.id')
            ->select([
                'm.id as machine_id',
                \Illuminate\Support\Facades\DB::raw('COALESCE(m.serial_no, "UNKNOWN") as serial_no'),
                \Illuminate\Support\Facades\DB::raw('COALESCE(m.name, "未知機台") as name'),
                \Illuminate\Support\Facades\DB::raw('CAST(COALESCE(dq.total_quantity, 0) as SIGNED) as total_quantity'),
                \Illuminate\Support\Facades\DB::raw('CAST(COALESCE(sq.total_sales, 0) as DECIMAL(10,2)) as total_sales'),
                \Illuminate\Support\Facades\DB::raw('CAST(COALESCE(dq.total_cost, 0) as DECIMAL(10,2)) as total_cost'),
                \Illuminate\Support\Facades\DB::raw('CAST((COALESCE(sq.total_sales, 0) - COALESCE(dq.total_cost, 0)) as DECIMAL(10,2)) as total_profit')
            ])
            ->where(function($query) {
                $query->whereNotNull('sq.total_sales')
                      ->orWhereNotNull('dq.total_quantity');
            });

        if ($effectiveCompanyId) {
            $mainQuery->where('m.company_id', $effectiveCompanyId);
        }
        if ($allowedMachineIds !== null) {
            if (empty($allowedMachineIds)) {
                $mainQuery->whereRaw('1 = 0');
            } else {
                $mainQuery->whereIn('m.id', $allowedMachineIds);
            }
        }
        if ($selectedMachineId && $selectedMachineId !== 'ALL') {
            $mainQuery->where('m.id', $selectedMachineId);
        }

        $tableData = $mainQuery->get();

        // 7. 計算總計 Summary
        $totalQuantity = 0;
        $totalSales = 0.0;
        $totalCost = 0.0;
        $totalProfit = 0.0;

        foreach ($tableData as $row) {
            $row->total_quantity = (int)$row->total_quantity;
            $row->total_sales = (float)$row->total_sales;
            $row->total_cost = (float)$row->total_cost;
            $row->total_profit = (float)$row->total_profit;

            $totalQuantity += $row->total_quantity;
            $totalSales += $row->total_sales;
            $totalCost += $row->total_cost;
            $totalProfit += $row->total_profit;
        }

        $summary = [
            'total_quantity' => $totalQuantity,
            'total_sales' => round($totalSales, 2),
            'total_cost' => round($totalCost, 2),
            'total_profit' => round($totalProfit, 2),
        ];

        // 8. 圖表日走勢統計
        // 每日營業額
        $trendSalesQuery = \Illuminate\Support\Facades\DB::table('orders as o')
            ->select([
                \Illuminate\Support\Facades\DB::raw("DATE_FORMAT(o.created_at, '%Y-%m-%d') as date"),
                \Illuminate\Support\Facades\DB::raw('SUM(o.pay_amount) as total_sales')
            ])
            ->where('o.payment_status', \App\Models\Transaction\Order::PAYMENT_STATUS_SUCCESS)
            ->whereNull('o.deleted_at')
            ->whereBetween('o.created_at', [$startDate, $endDate]);

        // 套用篩選到走勢營業額
        if ($effectiveCompanyId) {
            $trendSalesQuery->where('o.company_id', $effectiveCompanyId);
        }
        if ($allowedMachineIds !== null) {
            if (empty($allowedMachineIds)) {
                $trendSalesQuery->whereRaw('1 = 0');
            } else {
                $trendSalesQuery->whereIn('o.machine_id', $allowedMachineIds);
            }
        }
        if ($selectedMachineId && $selectedMachineId !== 'ALL') {
            $trendSalesQuery->where('o.machine_id', $selectedMachineId);
        }
        if (!empty($paymentTypes)) {
            $trendSalesQuery->whereIn('o.payment_type', $paymentTypes);
        }

        // 如果篩選特定商品，營業額只計算有成功出貨的商品價格
        if ($selectedProductId && $selectedProductId !== 'ALL') {
            $trendSalesQuery->join('dispense_records as dr', function($join) {
                    $join->on('dr.order_id', '=', 'o.id')
                         ->where('dr.dispense_status', '=', 1);
                })
                ->leftJoinSub($itemPriceQuery, 'oi', function($join) {
                    $join->on('oi.order_id', '=', 'dr.order_id')
                         ->on('oi.product_id', '=', 'dr.product_id');
                })
                ->where('dr.product_id', $selectedProductId)
                ->select([
                    \Illuminate\Support\Facades\DB::raw("DATE_FORMAT(o.created_at, '%Y-%m-%d') as date"),
                    \Illuminate\Support\Facades\DB::raw('SUM(COALESCE(oi.unit_price, 0)) as total_sales')
                ]);
        }
        $trendSalesQuery->groupBy('date');
        $trendSalesData = $trendSalesQuery->get();

        // 每日出貨件數與成本
        $trendDispenseQuery = \Illuminate\Support\Facades\DB::table('dispense_records as dr')
            ->join('orders as o', 'o.id', '=', 'dr.order_id')
            ->leftJoin('products as p', 'p.id', '=', 'dr.product_id')
            ->select([
                \Illuminate\Support\Facades\DB::raw("DATE_FORMAT(o.created_at, '%Y-%m-%d') as date"),
                \Illuminate\Support\Facades\DB::raw('COUNT(dr.id) as total_quantity'),
                \Illuminate\Support\Facades\DB::raw('SUM(COALESCE(p.cost, 0)) as total_cost')
            ])
            ->where('dr.dispense_status', 1)
            ->whereNull('o.deleted_at')
            ->whereBetween('o.created_at', [$startDate, $endDate]);

        // 套用篩選到走勢出貨
        if ($effectiveCompanyId) {
            $trendDispenseQuery->where('o.company_id', $effectiveCompanyId);
        }
        if ($allowedMachineIds !== null) {
            if (empty($allowedMachineIds)) {
                $trendDispenseQuery->whereRaw('1 = 0');
            } else {
                $trendDispenseQuery->whereIn('o.machine_id', $allowedMachineIds);
            }
        }
        if ($selectedMachineId && $selectedMachineId !== 'ALL') {
            $trendDispenseQuery->where('o.machine_id', $selectedMachineId);
        }
        if ($selectedProductId && $selectedProductId !== 'ALL') {
            $trendDispenseQuery->where('dr.product_id', $selectedProductId);
        }
        if (!empty($paymentTypes)) {
            $trendDispenseQuery->whereIn('o.payment_type', $paymentTypes);
        }
        $trendDispenseQuery->groupBy('date');
        $trendDispenseData = $trendDispenseQuery->get();

        // 將子查詢結果轉換為 PHP Map 對照
        $salesMap = [];
        foreach ($trendSalesData as $row) {
            $salesMap[$row->date] = $row->total_sales;
        }

        $dispenseMap = [];
        foreach ($trendDispenseData as $row) {
            $dispenseMap[$row->date] = [
                'quantity' => $row->total_quantity,
                'cost' => $row->total_cost
            ];
        }

        // 補齊日期區間內所有空缺日走勢
        $period = new \DatePeriod(
            $startDate->copy()->startOfDay(),
            new \DateInterval('P1D'),
            $endDate->copy()->endOfDay()
        );

        $chartData = [];
        foreach ($period as $date) {
            $dateStr = $date->format('Y-m-d');
            $hasSales = isset($salesMap[$dateStr]);
            $hasDispense = isset($dispenseMap[$dateStr]);

            $chartData[$dateStr] = [
                'date' => $dateStr,
                'quantity' => $hasDispense ? (int)$dispenseMap[$dateStr]['quantity'] : 0,
                'sales' => $hasSales ? round((float)$salesMap[$dateStr], 2) : 0.0,
                'cost' => $hasDispense ? round((float)$dispenseMap[$dateStr]['cost'], 2) : 0.0,
            ];
        }
        $chartData = array_values($chartData);

        // 9. 判斷是否為 AJAX 請求，回傳 JSON 格式
        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'summary' => $summary,
                'chart_data' => $chartData,
                'table_data' => $tableData,
            ]);
        }

        // 10. 一般 GET 載入視圖
        return view('admin.analysis.machine-reports', [
            'title' => '機台報表分析',
            'description' => '機台運營數據分析報表',
            'companies' => $companies,
            'machines' => $machines,
            'products' => $products,
            'availablePaymentTypes' => $availablePaymentTypes,
            'selectedCompanyId' => $selectedCompanyId,
            'selectedMachineId' => $selectedMachineId,
            'selectedProductId' => $selectedProductId,
            'selectedPaymentTypes' => $paymentTypes,
            'dateRange' => $dateRange,
            'summary' => $summary,
            'chartData' => $chartData,
            'tableData' => $tableData,
        ]);
    }

    // 商品報表分析
    public function productReports(Request $request)
    {
        $user = auth()->user();
        $companyId = $user->company_id;

        // 1. 取得篩選參數
        $selectedCompanyId = $request->input('company_id');
        $selectedProductId = $request->input('product_id', 'ALL');
        $selectedMachineId = $request->input('machine_id', 'ALL');
        $dateRange = $request->input('date_range');

        // 2. 解析日期區間
        if ($dateRange) {
            $dates = explode(' to ', $dateRange);
            if (count($dates) === 2) {
                $startDate = \Illuminate\Support\Carbon::parse($dates[0])->startOfDay();
                $endDate = \Illuminate\Support\Carbon::parse($dates[1])->endOfDay();
            } else {
                $startDate = \Illuminate\Support\Carbon::parse($dates[0])->startOfDay();
                $endDate = \Illuminate\Support\Carbon::parse($dates[0])->endOfDay();
            }
        } else {
            // 預設為最近 7 天 (含今日)
            $startDate = now()->subDays(6)->startOfDay();
            $endDate = now()->endOfDay();
            $dateRange = $startDate->format('Y-m-d') . ' to ' . $endDate->format('Y-m-d');
        }

        // 3. 租戶隔離：非系統管理員強制限制為其所屬公司
        if (!$user->isSystemAdmin()) {
            $effectiveCompanyId = $companyId;
        } else {
            $effectiveCompanyId = $selectedCompanyId;
        }

        // 取得使用者授權的機台 ID 範圍 (一般用戶)
        $allowedMachineIds = null;
        if (!$user->isSystemAdmin()) {
            $allowedMachineIds = \App\Models\Machine\Machine::pluck('id')->toArray();
        }

        // 4. 加載下拉選單選項
        // 公司選單 (僅限 Super Admin)
        $companies = [];
        if ($user->isSystemAdmin()) {
            $companies = \App\Models\System\Company::active()->get(['id', 'name']);
        }

        // 機台選單 (自動套用多租戶與授權隔離)
        $machinesQuery = \App\Models\Machine\Machine::query();
        if ($effectiveCompanyId) {
            $machinesQuery->where('company_id', $effectiveCompanyId);
        }
        $machines = $machinesQuery->get(['id', 'name', 'serial_no']);

        // 商品選單
        $productsQuery = \App\Models\Product\Product::query();
        if ($effectiveCompanyId) {
            $productsQuery->where('company_id', $effectiveCompanyId);
        }
        $products = $productsQuery->active()->get(['id', 'name', 'barcode']);

        // 5. 執行主報表統計 SQL
        // 子查詢：取得每個 order_id + product_id 的銷售單價與品名
        $itemPriceQuery = \Illuminate\Support\Facades\DB::table('order_items')
            ->select([
                'order_id',
                'product_id',
                \Illuminate\Support\Facades\DB::raw('MIN(price) as unit_price'),
                \Illuminate\Support\Facades\DB::raw('MIN(barcode) as barcode'),
                \Illuminate\Support\Facades\DB::raw('MIN(product_name) as product_name')
            ])
            ->groupBy('order_id', 'product_id');

        // 計算各商品的銷售數量、銷售額、總成本、利潤 (以出貨成功為主體)
        $mainQuery = \Illuminate\Support\Facades\DB::table('dispense_records as dr')
            ->join('orders as o', 'o.id', '=', 'dr.order_id')
            ->leftJoinSub($itemPriceQuery, 'oi', function($join) {
                $join->on('oi.order_id', '=', 'dr.order_id')
                     ->on('oi.product_id', '=', 'dr.product_id');
            })
            ->leftJoin('products as p', 'p.id', '=', 'dr.product_id')
            ->select([
                'dr.product_id',
                \Illuminate\Support\Facades\DB::raw('COALESCE(p.barcode, oi.barcode) as barcode'),
                \Illuminate\Support\Facades\DB::raw('COALESCE(p.name, oi.product_name) as name'),
                \Illuminate\Support\Facades\DB::raw('CAST(COUNT(dr.id) as SIGNED) as total_quantity'),
                \Illuminate\Support\Facades\DB::raw('CAST(SUM(COALESCE(oi.unit_price, 0)) as DECIMAL(10,2)) as total_sales'),
                \Illuminate\Support\Facades\DB::raw('CAST(SUM(COALESCE(p.cost, 0)) as DECIMAL(10,2)) as total_cost'),
                \Illuminate\Support\Facades\DB::raw('CAST((SUM(COALESCE(oi.unit_price, 0)) - SUM(COALESCE(p.cost, 0))) as DECIMAL(10,2)) as total_profit')
            ])
            ->where('dr.dispense_status', 1)
            ->where('o.payment_status', \App\Models\Transaction\Order::PAYMENT_STATUS_SUCCESS)
            ->whereNull('o.deleted_at')
            ->whereBetween('o.created_at', [$startDate, $endDate]);

        // 套用公司篩選
        if ($effectiveCompanyId) {
            $mainQuery->where('o.company_id', $effectiveCompanyId);
        }

        // 套用機台權限限制
        if ($allowedMachineIds !== null) {
            if (empty($allowedMachineIds)) {
                $mainQuery->whereRaw('1 = 0');
            } else {
                $mainQuery->whereIn('o.machine_id', $allowedMachineIds);
            }
        }

        // 套用特定機台篩選
        if ($selectedMachineId && $selectedMachineId !== 'ALL') {
            $mainQuery->where('o.machine_id', $selectedMachineId);
        }

        // 套用特定商品篩選
        if ($selectedProductId && $selectedProductId !== 'ALL') {
            $mainQuery->where('dr.product_id', $selectedProductId);
        }

        $mainQuery->groupBy('dr.product_id')
            ->groupBy(\Illuminate\Support\Facades\DB::raw('COALESCE(p.barcode, oi.barcode)'))
            ->groupBy(\Illuminate\Support\Facades\DB::raw('COALESCE(p.name, oi.product_name)'));
        $tableData = $mainQuery->get();

        // 6. 計算總計 Summary
        $totalQuantity = 0;
        $totalSales = 0.0;
        $totalCost = 0.0;
        $totalProfit = 0.0;

        foreach ($tableData as $row) {
            $row->total_quantity = (int)$row->total_quantity;
            $row->total_sales = (float)$row->total_sales;
            $row->total_cost = (float)$row->total_cost;
            $row->total_profit = (float)$row->total_profit;

            $totalQuantity += $row->total_quantity;
            $totalSales += $row->total_sales;
            $totalCost += $row->total_cost;
            $totalProfit += $row->total_profit;
        }

        $summary = [
            'total_quantity' => $totalQuantity,
            'total_sales' => round($totalSales, 2),
            'total_cost' => round($totalCost, 2),
            'total_profit' => round($totalProfit, 2),
        ];

        // 7. 圖表日走勢統計 SQL
        $trendQuery = \Illuminate\Support\Facades\DB::table('dispense_records as dr')
            ->join('orders as o', 'o.id', '=', 'dr.order_id')
            ->leftJoinSub($itemPriceQuery, 'oi', function($join) {
                $join->on('oi.order_id', '=', 'dr.order_id')
                     ->on('oi.product_id', '=', 'dr.product_id');
            })
            ->leftJoin('products as p', 'p.id', '=', 'dr.product_id')
            ->select([
                \Illuminate\Support\Facades\DB::raw("DATE_FORMAT(o.created_at, '%Y-%m-%d') as date"),
                \Illuminate\Support\Facades\DB::raw('CAST(COUNT(dr.id) as SIGNED) as total_quantity'),
                \Illuminate\Support\Facades\DB::raw('CAST(SUM(COALESCE(oi.unit_price, 0)) as DECIMAL(10,2)) as total_sales'),
                \Illuminate\Support\Facades\DB::raw('CAST(SUM(COALESCE(p.cost, 0)) as DECIMAL(10,2)) as total_cost')
            ])
            ->where('dr.dispense_status', 1)
            ->where('o.payment_status', \App\Models\Transaction\Order::PAYMENT_STATUS_SUCCESS)
            ->whereNull('o.deleted_at')
            ->whereBetween('o.created_at', [$startDate, $endDate]);

        if ($effectiveCompanyId) {
            $trendQuery->where('o.company_id', $effectiveCompanyId);
        }

        if ($allowedMachineIds !== null) {
            if (empty($allowedMachineIds)) {
                $trendQuery->whereRaw('1 = 0');
            } else {
                $trendQuery->whereIn('o.machine_id', $allowedMachineIds);
            }
        }

        if ($selectedMachineId && $selectedMachineId !== 'ALL') {
            $trendQuery->where('o.machine_id', $selectedMachineId);
        }

        if ($selectedProductId && $selectedProductId !== 'ALL') {
            $trendQuery->where('dr.product_id', $selectedProductId);
        }

        $trendQuery->groupBy('date')->orderBy('date', 'asc');
        $trendData = $trendQuery->get();

        // 補齊日期區間內所有空缺日走勢
        $period = new \DatePeriod(
            $startDate->copy()->startOfDay(),
            new \DateInterval('P1D'),
            $endDate->copy()->endOfDay()
        );

        $chartData = [];
        foreach ($period as $date) {
            $dateStr = $date->format('Y-m-d');
            $chartData[$dateStr] = [
                'date' => $dateStr,
                'quantity' => 0,
                'sales' => 0.0,
                'cost' => 0.0,
            ];
        }

        foreach ($trendData as $row) {
            if (isset($chartData[$row->date])) {
                $chartData[$row->date]['quantity'] = (int)$row->total_quantity;
                $chartData[$row->date]['sales'] = round((float)$row->total_sales, 2);
                $chartData[$row->date]['cost'] = round((float)$row->total_cost, 2);
            }
        }
        $chartData = array_values($chartData);

        // 8. 判斷是否為 AJAX 請求，回傳 JSON 格式
        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'summary' => $summary,
                'chart_data' => $chartData,
                'table_data' => $tableData,
            ]);
        }

        // 9. 一般 GET 載入視圖
        return view('admin.analysis.product-reports', [
            'title' => '商品報表分析',
            'description' => '商品銷售數據 analysis',
            'companies' => $companies,
            'machines' => $machines,
            'products' => $products,
            'selectedCompanyId' => $selectedCompanyId,
            'selectedMachineId' => $selectedMachineId,
            'selectedProductId' => $selectedProductId,
            'dateRange' => $dateRange,
            'summary' => $summary,
            'chartData' => $chartData,
            'tableData' => $tableData,
        ]);
    }

    // 互動問卷分析
    public function surveyAnalysis()
    {
        return view('admin.placeholder', [
            'title' => '互動問卷分析',
            'description' => '問卷結果統計與分析',
        ]);
    }
}
