<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Machine\Machine;
use App\Models\Machine\MachineLog;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        // 每頁顯示筆數限制 (預設為 10)
        $perPage = (int) request()->input('per_page', 10);
        if ($perPage <= 0)
            $perPage = 10;

        // 從資料庫獲取真實統計數據 (智慧插座艦隊相關，教學版不含販賣機營收/庫存數據)
        $totalMachines = Machine::count();
        $activeMachines = Machine::online()->count();
        $offlineMachines = Machine::offline()->count();
        $alertsPending = Machine::hasAnyAlert()->count();

        // 最近機台日誌 (取代原本的販賣機營收卡片，遵循機台可視範圍的租戶隔離)
        $recentLogs = MachineLog::whereIn('machine_id', Machine::pluck('id'))
            ->with('machine:id,name,serial_no')
            ->latest()
            ->limit(10)
            ->get();

        // 獲取機台列表 (分頁)
        $machines = Machine::when($request->search, function ($query, $search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('serial_no', 'like', "%{$search}%");
            });
        })
            ->orderByDesc('last_heartbeat_at')
            ->paginate($perPage)
            ->withQueryString();

        return view('admin.dashboard', compact(
            'totalMachines',
            'activeMachines',
            'offlineMachines',
            'alertsPending',
            'recentLogs',
            'machines'
        ));
    }
}
