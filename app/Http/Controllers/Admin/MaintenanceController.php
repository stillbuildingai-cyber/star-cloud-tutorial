<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Machine\Machine;
use App\Models\Machine\MaintenanceRecord;
use App\Models\System\Company;
use App\Traits\ImageHandler;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class MaintenanceController extends Controller
{
    use ImageHandler;

    /**
     * 維修紀錄列表
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', MaintenanceRecord::class);

        $currentUser = auth()->user();
        $companyId = trim((string) $request->input('company_id', ''));

        $query = MaintenanceRecord::with(['machine', 'user', 'company'])
            ->whereHas('machine') // 確保僅顯示該帳號「看得見」的機台紀錄，避開因權限隔離導致的 null 報錯
            ->latest('maintenance_at');

        // 搜尋邏輯
        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('machine', function($q) use ($search) {
                $q->where('serial_no', 'like', "%{$search}%")
                  ->orWhere('name', 'like', "%{$search}%");
            });
        }

        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        if ($currentUser->isSystemAdmin() && $companyId !== '') {
            $query->where('company_id', $companyId);
        }

        $records = $query->paginate(15)->withQueryString();
        $companies = $currentUser->isSystemAdmin()
            ? Company::select('id', 'name', 'code')->orderBy('name')->get()
            : collect();

        return view('admin.maintenance.index', compact('records', 'companies'));
    }

    /**
     * 顯示新增維修單頁面
     */
    public function create(Request $request, $serial_no = null)
    {
        $this->authorize('create', MaintenanceRecord::class);

        $machine = null;
        if ($serial_no) {
            $machine = Machine::where('serial_no', $serial_no)->firstOrFail();
        }

        // 供手動新增時選擇的機台清單 (僅限有權限存取的)
        $machines = Machine::all();

        return view('admin.maintenance.create', compact('machine', 'machines'));
    }

    /**
     * 儲存維修單
     */
    public function store(Request $request)
    {
        $this->authorize('create', MaintenanceRecord::class);

        $validated = $request->validate([
            'machine_id' => 'required|exists:machines,id',
            'category' => 'required|in:Repair,Installation,Removal,Maintenance',
            'content' => 'nullable|string',
            'maintenance_at' => 'required|date',
            'photos.*' => 'nullable|image|max:5120', // 每張上限 5MB
            'is_confirmed' => 'required|accepted',
        ]);

        $machine = Machine::findOrFail($validated['machine_id']);
        
        $photoPaths = [];
        if ($request->hasFile('photos')) {
            foreach ($request->file('photos') as $photo) {
                if (!$photo) continue;
                if (count($photoPaths) >= 3) break;
                
                // 轉為 WebP 格式與保存
                $path = $this->storeAsWebp($photo, "maintenance/{$machine->id}");
                $photoPaths[] = $path;
            }
        }

        $record = MaintenanceRecord::create([
            'company_id' => $machine->company_id, // 從機台帶入歸屬客戶
            'machine_id' => $machine->id,
            'user_id' => Auth::id(),
            'category' => $validated['category'],
            'content' => $validated['content'],
            'photos' => $photoPaths,
            'maintenance_at' => $validated['maintenance_at'],
            'is_confirmed' => true, // 既然通過驗證(accepted)，則存為 true
        ]);

        return redirect()->route('admin.maintenance.index')
            ->with('success', __('Maintenance record created successfully'));
    }
}
