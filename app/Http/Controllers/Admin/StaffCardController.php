<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\StaffCard;
use App\Models\StaffCardLog;
use App\Services\StaffCardImportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StaffCardController extends Controller
{
    public function index(Request $request)
    {
        $tab = $request->get('tab', 'staff_list');
        $isSystemAdmin = auth()->user()->isSystemAdmin();

        $companies = [];
        if ($isSystemAdmin) {
            $companies = \App\Models\System\Company::active()->orderBy('name')->get();
        }

        $cardsQuery = StaffCard::with('lastMachine')
            ->when($request->search, function ($q) use ($request) {
                $q->where(function ($sq) use ($request) {
                    $sq->where('name', 'like', "%{$request->search}%")
                        ->orWhere('employee_id', 'like', "%{$request->search}%")
                        ->orWhere('card_uid', 'like', "%{$request->search}%");
                });
            })
            ->when($isSystemAdmin && $request->company_id, function ($q) use ($request) {
                $q->where('company_id', $request->company_id);
            });

        $logsQuery = StaffCardLog::with(['staffCard', 'machine', 'order'])
            ->when($request->log_search, function ($q) use ($request) {
                $q->whereHas('staffCard', function ($sq) use ($request) {
                    $sq->where('name', 'like', "%{$request->log_search}%")
                        ->orWhere('employee_id', 'like', "%{$request->log_search}%")
                        ->orWhere('card_uid', 'like', "%{$request->log_search}%");
                });
            })
            ->when($isSystemAdmin && $request->log_company_id, function ($q) use ($request) {
                $q->where('company_id', $request->log_company_id);
            });

        $staffPerPage = $request->input('staff_per_page', 10);
        $usagePerPage = $request->input('usage_per_page', 10);

        if ($request->ajax() && $request->has('_ajax')) {
            if ($tab === 'staff_list') {
                $cards = $cardsQuery->latest()->paginate($staffPerPage, ['*'], 'staff_page');
                return response()->json([
                    'success' => true,
                    'html' => view('admin.data-config.staff-cards.partials.tab-staff-list', compact('cards', 'companies'))->render()
                ]);
            } else {
                $logs = $logsQuery->latest()->paginate($usagePerPage, ['*'], 'usage_page');
                return response()->json([
                    'success' => true,
                    'html' => view('admin.data-config.staff-cards.partials.tab-usage-logs', compact('logs', 'companies'))->render()
                ]);
            }
        }

        $cards = $cardsQuery->latest()->paginate($staffPerPage, ['*'], 'staff_page');
        $logs = $logsQuery->latest()->paginate($usagePerPage, ['*'], 'usage_page');


        return view('admin.data-config.staff-cards.index', compact('cards', 'logs', 'tab', 'companies'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'company_id' => 'nullable|exists:companies,id',
            'employee_id' => 'required|string|max:50',
            'name' => 'required|string|max:100',
            'card_uid' => 'required|string|max:100',
            'status' => 'nullable|string|in:active,inactive',
            'notes' => 'nullable|string',
        ]);

        $validated['status'] = $request->get('status', 'active');

        $card = StaffCard::create($validated);

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => __('Staff card created successfully'),
                'data' => $card
            ]);
        }

        return redirect()->route('admin.data-config.staff-cards.index')->with('success', __('Staff card created successfully'));
    }

    public function update(Request $request, StaffCard $staffCard)
    {
        $validated = $request->validate([
            'company_id' => 'nullable|exists:companies,id',
            'employee_id' => 'required|string|max:50',
            'name' => 'required|string|max:100',
            'card_uid' => 'required|string|max:100',
            'status' => 'nullable|string|in:active,inactive',
            'notes' => 'nullable|string',
        ]);

        $validated['status'] = $request->get('status', $staffCard->status);

        // 寫入安全：company_id 不得由前端任意決定或被洗成 null
        // - 非系統管理員：強制綁定自身公司 (租戶隔離)
        // - 系統管理員：若未帶入公司，保留原本歸屬，避免變成無公司歸屬的孤兒卡 (機台將查無此卡)
        if (!auth()->user()->isSystemAdmin()) {
            $validated['company_id'] = auth()->user()->company_id;
        } elseif (empty($validated['company_id'])) {
            $validated['company_id'] = $staffCard->company_id;
        }

        $staffCard->update($validated);

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => __('Staff card updated successfully'),
            ]);
        }

        return redirect()->route('admin.data-config.staff-cards.index')->with('success', __('Staff card updated successfully'));
    }

    public function destroy(StaffCard $staffCard)
    {
        $staffCard->delete();

        if (request()->ajax()) {
            return response()->json([
                'success' => true,
                'message' => __('Staff card deleted successfully'),
            ]);
        }

        return redirect()->route('admin.data-config.staff-cards.index')->with('success', __('Staff card deleted successfully'));
    }

    public function toggleStatus(StaffCard $staffCard)
    {
        $staffCard->status = $staffCard->status === 'active' ? 'inactive' : 'active';
        $staffCard->save();

        return response()->json([
            'success' => true,
            'message' => __('Status updated successfully'),
        ]);
    }

    public function downloadTemplate(StaffCardImportService $importService)
    {
        return $importService->downloadTemplate();
    }

    public function import(Request $request, StaffCardImportService $importService)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv|max:5120',
            'company_id' => 'nullable|exists:companies,id',
        ]);

        $companyId = $request->get('company_id') ?: auth()->user()->company_id;

        if (auth()->user()->isSystemAdmin() && !$companyId) {
            return response()->json([
                'success' => false,
                'message' => __('Please select a company'),
            ], 422);
        }

        $results = $importService->import($request->file('file')->getRealPath(), $companyId);

        return response()->json([
            'success' => true,
            'message' => __(':count staff cards imported successfully', ['count' => $results['success_count']]),
            'results' => $results,
        ]);
    }
}
