<?php

namespace App\Http\Controllers\Admin\Machine;

use App\Http\Controllers\Admin\AdminController;
use App\Models\System\Company;
use App\Models\Machine\Machine;
use App\Models\System\User;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class MachinePermissionController extends AdminController
{
    /**
     * 顯示機台權限管理列表
     */
    public function index(Request $request): View
    {
        $per_page = $request->input('per_page', 10);
        $search = $request->input('search');
        $company_id = $request->input('company_id');

        $currentUser = auth()->user();

        // 僅列出租戶中具有「is_admin」標記的角色帳號以供分配
        $userQuery = User::query()
            ->with(['machines' => function($query) {
                $query->withoutGlobalScope('machine_access')
                      ->select('machines.id', 'machines.name', 'machines.serial_no');
            }])
            ->whereNotNull('company_id');

        // 可見範圍：系統管理員看全部；主帳號看同公司全部；子帳號僅看自己直接建立的下層（避免洩漏旁線帳號）。
        $userQuery->visibleTo($currentUser);
        if ($currentUser->isSystemAdmin() && $company_id) {
            // 系統管理員的篩選邏輯
            $userQuery->where('company_id', $company_id);
        }

        if ($search) {
            $userQuery->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('username', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $users_list = $userQuery->latest()->paginate($per_page)->withQueryString();
        $companies = $currentUser->isSystemAdmin() ? Company::all() : collect();

        return view('admin.machines.permissions', compact('users_list', 'companies'));
    }

    /**
     * 操作者「可授權」的機台 ID 集合（授權子集約束）：
     * - 系統管理員：指定公司（或全部）的所有機台
     * - 主帳號：同公司全部機台
     * - 子帳號：僅自己被授權的機台（machine_user）
     */
    private function assignableMachineIds(User $operator, ?int $companyId): \Illuminate\Support\Collection
    {
        // 目標帳號無所屬公司（如系統管理員帳號）時，沒有可授權的公司機台。
        if (is_null($companyId)) {
            return collect();
        }

        if ($operator->isSystemAdmin() || $operator->is_admin) {
            return Machine::withoutGlobalScope('machine_access')
                ->where('company_id', $companyId)
                ->pluck('id');
        }

        return $operator->machines()
            ->withoutGlobalScope('machine_access')
            ->where('machines.company_id', $companyId)
            ->pluck('machines.id');
    }

    /**
     * AJAX: 取得特定帳號的機台分配狀態
     */
    public function getAccountMachines(User $user): JsonResponse
    {
        $currentUser = auth()->user();

        // 層級越權防護：只能操作可管轄範圍內的帳號。
        if (!$currentUser->canManageAccount($user)) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        // 可授權機台清單限縮為「操作者本身可授權」的機台子集，避免把自己沒有的機台授權出去。
        $assignableIds = $this->assignableMachineIds($currentUser, $user->company_id);
        $machines = Machine::withoutGlobalScope('machine_access')
            ->whereIn('id', $assignableIds)
            ->get(['id', 'name', 'serial_no']);

        $assignedIds = $user->machines()->withoutGlobalScope('machine_access')->pluck('machines.id')->toArray();

        return response()->json([
            'user' => $user,
            'machines' => $machines,
            'assigned_ids' => $assignedIds
        ]);
    }

    /**
     * AJAX: 儲存特定帳號的機台分配
     */
    public function syncAccountMachines(Request $request, User $user): JsonResponse
    {
        $currentUser = auth()->user();

        // 層級越權防護：只能操作可管轄範圍內的帳號。
        if (!$currentUser->canManageAccount($user)) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $request->validate([
            'machine_ids' => 'nullable|array',
            'machine_ids.*' => 'exists:machines,id'
        ]);

        // 授權子集約束：被指派的機台必須是「操作者本身可授權機台」的子集，
        // 既確保同公司，也避免子帳號把自己沒被授權的機台授權給他人。
        $assignableIds = $this->assignableMachineIds($currentUser, $user->company_id);
        $submittedIds = collect($request->machine_ids ?? [])->map(fn ($id) => (int) $id)->unique();
        if ($submittedIds->diff($assignableIds)->isNotEmpty()) {
            return response()->json(['error' => 'Invalid machine IDs provided.'], 422);
        }

        // 只在「操作者可授權子集」範圍內做增刪；範圍外的既有授權一律保留，避免覆蓋式 sync 誤刪
        // 操作者看不到（不在其可授權子集內）的機台。明確 detach/attach 以避開 machine_access
        // 全域 scope 對 sync 取「現有附加」造成的干擾（該 scope 依登入者過濾 Machine 查詢）。
        $existingIds = $user->machines()->withoutGlobalScope('machine_access')->pluck('machines.id');
        $currentInScope = $existingIds->intersect($assignableIds);
        $toAttach = $submittedIds->diff($currentInScope);
        $toDetach = $currentInScope->diff($submittedIds);
        if ($toDetach->isNotEmpty()) {
            $user->machines()->detach($toDetach->all());
        }
        if ($toAttach->isNotEmpty()) {
            $user->machines()->attach($toAttach->all());
        }
        
        $message = __('Machine permissions updated successfully.');
        session()->flash('success', $message);

        return response()->json([
            'success' => true, 
            'message' => $message,
            'assigned_machines' => $user->machines()->select('machines.id', 'machines.name', 'machines.serial_no')->get()
        ]);
    }
}
