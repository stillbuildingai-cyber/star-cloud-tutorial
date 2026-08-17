<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Transaction\Order;
use App\Models\Machine\Machine;
use Illuminate\Http\Request;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        // 每頁顯示筆數限制 (預設為 10)
        $perPage = (int) request()->input('per_page', 10);
        if ($perPage <= 0)
            $perPage = 10;

        // 從資料庫獲取真實統計數據
        $activeMachines = Machine::online()->count();
        $offlineMachines = Machine::offline()->count();
        $alertsPending = Machine::hasAnyAlert()->count();
        $memberCount = \App\Models\Member\Member::count();

        // 交易營收統計 (真實數據)
        $todayRevenue = Order::where('payment_status', Order::PAYMENT_STATUS_SUCCESS)
            ->whereDate('payment_at', Carbon::today())
            ->sum('pay_amount');

        $yesterdayRevenue = Order::where('payment_status', Order::PAYMENT_STATUS_SUCCESS)
            ->whereDate('payment_at', Carbon::yesterday())
            ->sum('pay_amount');

        $dayBeforeRevenue = Order::where('payment_status', Order::PAYMENT_STATUS_SUCCESS)
            ->whereDate('payment_at', Carbon::yesterday()->subDay())
            ->sum('pay_amount');

        $monthlyRevenue = Order::where('payment_status', Order::PAYMENT_STATUS_SUCCESS)
            ->whereMonth('payment_at', Carbon::now()->month)
            ->whereYear('payment_at', Carbon::now()->year)
            ->sum('pay_amount');

        // 計算昨日增長趨勢 (百分比)
        $yesterdayTrend = 0;
        if ($yesterdayRevenue > 0) {
            $yesterdayTrend = (($todayRevenue - $yesterdayRevenue) / $yesterdayRevenue) * 100;
        } elseif ($todayRevenue > 0) {
            $yesterdayTrend = 100;
        }

        // 獲取機台列表 (分頁)
        $machines = Machine::when($request->search, function ($query, $search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('serial_no', 'like', "%{$search}%");
            });
        })
            ->withSum('slots', 'stock')
            ->withSum('slots', 'max_stock')
            ->withSum(['orders as today_sales_sum' => function($query) {
                $query->whereDate('payment_at', now()->toDateString())
                    ->where('payment_status', Order::PAYMENT_STATUS_SUCCESS);
            }], 'pay_amount')
            ->orderByDesc('last_heartbeat_at')
            ->paginate($perPage)
            ->withQueryString();

        return view('admin.dashboard', compact(
            'activeMachines',
            'offlineMachines',
            'alertsPending',
            'memberCount',
            'todayRevenue',
            'yesterdayRevenue',
            'dayBeforeRevenue',
            'monthlyRevenue',
            'yesterdayTrend',
            'machines'
        ));
    }
}
