<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class PermissionController extends Controller
{
    // 權限角色設定
    public function roles()
    {
        $per_page = request()->input('roles_per_page', request()->input('per_page', 10));
        $user = auth()->user();
        $query = \App\Models\System\Role::query()->with(['permissions', 'users', 'company']);

        // 層級隔離：系統管理員看全部；主帳號看同公司全部；子帳號僅看自己建立的角色。
        $query->visibleTo($user);

        // 搜尋：角色名稱（支援 roles_search 命名空間 與舊版 search）
        $search = request()->input('roles_search', request()->input('search'));
        if ($search) {
            $query->where('name', 'like', "%{$search}%");
        }

        // 篩選：公司名稱（支援 roles_company_id 命名空間 與舊版 company_id）
        $company_id = request()->input('roles_company_id', request()->input('company_id'));
        if ($user->isSystemAdmin() && filled($company_id)) {
            if ($company_id === 'system') {
                $query->whereNull('company_id');
            } else {
                $query->where('company_id', $company_id);
            }
        }

        $roles = $query->latest()->paginate($per_page, ['*'], 'roles_page')->withQueryString();
        $companies = $user->isSystemAdmin() ? \App\Models\System\Company::all() : collect();
        
        // 權限分組邏輯中的標題與過濾
        $isSubAccountRoles = request()->routeIs('*.sub-account-roles');
        $title = $isSubAccountRoles ? __('Sub Account Roles') : __('Role Settings');
        $permissionQuery = \Spatie\Permission\Models\Permission::query();
        if (!$user->isSystemAdmin()) {
            $permissionQuery->whereIn('name', $user->getAllPermissions()->pluck('name'));
        }

        // 權限分組邏輯
        $all_permissions = $permissionQuery->get()
            ->reject(fn($p) => $p->name === 'menu.data-config.sub-account-roles')
            ->groupBy(function($perm) {
                if (str_starts_with($perm->name, 'menu.')) {
                    return 'menu';
                }
                return 'other';
            });

        $currentUserRoleIds = $user->roles->pluck('id')->toArray();
        return view('admin.permission.roles', compact('roles', 'all_permissions', 'title', 'currentUserRoleIds', 'companies'));
    }

    /**
     * Show the form for creating a new role.
     */
    public function createRole()
    {
        $role = new \App\Models\System\Role();
        $user = auth()->user();

        // 權限遞迴約束由 getFilteredPermissions 處理

        $all_permissions = $this->getFilteredPermissions($user);

        $title = request()->routeIs('*.sub-account-roles.create') ? __('Create Sub Account Role') : __('Create New Role');
        $back_url = request()->routeIs('*.sub-account-roles.create') 
            ? route('admin.data-config.sub-accounts', ['tab' => 'roles']) 
            : route('admin.permission.roles');

        return view('admin.permission.roles-edit', compact('role', 'all_permissions', 'title', 'back_url'));
    }

    /**
     * Show the form for editing the specified role.
     */
    public function editRole($id)
    {
        $role = \App\Models\System\Role::findOrFail($id);
        $user = auth()->user();

        // 層級隔離：子帳號只能編輯自己建立的角色，不可開啟上層/旁線建立的角色。
        if (!$role->canBeManagedBy($user)) {
            return redirect()->back()->with('error', __('You do not have permission to manage this role.'));
        }

        // 權限遞迴約束與分組邏輯由 getFilteredPermissions 處理

        $all_permissions = $this->getFilteredPermissions($user);

        // 根據路由決定標題
        $title = request()->routeIs('*.sub-account-roles.edit') ? __('Edit Sub Account Role') : __('Edit Role Permissions');
        
        // 麵包屑/返回路徑
        $back_url = request()->routeIs('*.sub-account-roles.edit') 
            ? route('admin.data-config.sub-accounts', ['tab' => 'roles']) 
            : route('admin.permission.roles');

        return view('admin.permission.roles-edit', compact('role', 'all_permissions', 'title', 'back_url'));
    }

    /**
     * Store a newly created role in storage.
     */
    public function storeRole(Request $request)
    {
        $is_system = auth()->user()->isSystemAdmin() && $request->boolean('is_system');
        $company_id = $is_system ? null : auth()->user()->company_id;

        $validated = $request->validate([
            'name' => [
                'required', 'string', 'max:255',
                \Illuminate\Validation\Rule::unique('roles', 'name')->where(function ($query) use ($company_id) {
                    return $query->where('company_id', $company_id);
                })
            ],
            'permissions' => 'nullable|array',
            'permissions.*' => 'string|exists:permissions,name',
        ]);

        $role = \App\Models\System\Role::query()->create([
            'name' => $validated['name'],
            'guard_name' => 'web',
            'company_id' => $is_system ? null : auth()->user()->company_id,
            'created_by' => auth()->id(),
            'is_system' => $is_system,
        ]);

        if (!empty($validated['permissions'])) {
            $perms = $validated['permissions'];
            
            // 權限遞迴約束驗證
            if (!auth()->user()->isSystemAdmin()) {
                $currentUserPerms = auth()->user()->getAllPermissions()->pluck('name');
                if (collect($perms)->diff($currentUserPerms)->isNotEmpty()) {
                    return redirect()->back()->with('error', __('You cannot assign permissions you do not possess.'));
                }
            }

            // 如果不是系統角色，排除主選單的系統權限
            if (!$is_system) {
                $perms = array_diff($perms, ['menu.basic', 'menu.permissions']);
            }
            $role->syncPermissions($perms);
        }

        $target_route = request()->routeIs('*.sub-account-roles.*') ? route('admin.data-config.sub-accounts', ['tab' => 'roles']) : route('admin.permission.roles');
        return redirect()->to($target_route)->with('success', __('Role created successfully.'));
    }

    /**
     * Update the specified role in storage.
     */
    public function updateRole(Request $request, $id)
    {
        $role = \App\Models\System\Role::findOrFail($id);

        $is_system = $role->is_system;
        $company_id = $role->company_id;

        $validated = $request->validate([
            'name' => [
                'required', 'string', 'max:255',
                \Illuminate\Validation\Rule::unique('roles', 'name')
                    ->ignore($id)
                    ->where(function ($query) use ($company_id) {
                        return $query->where('company_id', $company_id);
                    })
            ],
            'permissions' => 'nullable|array',
            'permissions.*' => 'string|exists:permissions,name',
        ]);

        if ($role->name === 'super-admin') {
             return redirect()->back()->with('error', __('The Super Admin role is immutable.'));
        }

        if (!auth()->user()->isSystemAdmin() && $role->is_system) {
             return redirect()->back()->with('error', __('System roles cannot be modified by tenant administrators.'));
        }

        // 層級隔離：子帳號只能修改自己建立的角色。
        if (!$role->canBeManagedBy(auth()->user())) {
            return redirect()->back()->with('error', __('You do not have permission to manage this role.'));
        }

        $is_system = auth()->user()->isSystemAdmin() ? $request->boolean('is_system') : $role->is_system;

        $updateData = [
            'name' => $validated['name'],
            'is_system' => $is_system,
            'company_id' => $is_system ? null : $role->company_id,
        ];

        $role->update($updateData);

        $perms = $validated['permissions'] ?? [];

        // 權限遞迴約束驗證
        if (!auth()->user()->isSystemAdmin()) {
            $currentUserPerms = auth()->user()->getAllPermissions()->pluck('name');
            if (collect($perms)->diff($currentUserPerms)->isNotEmpty()) {
                return redirect()->back()->with('error', __('You cannot assign permissions you do not possess.'));
            }
        }

        // 如果不是系統角色，排除主選單的系統權限
        if (!$is_system) {
            $perms = array_diff($perms, ['menu.basic', 'menu.permissions']);
        }
        $role->syncPermissions($perms);

        $target_route = request()->routeIs('*.sub-account-roles.*') ? route('admin.data-config.sub-accounts', ['tab' => 'roles']) : route('admin.permission.roles');
        return redirect()->to($target_route)->with('success', __('Role updated successfully.'));
    }

    /**
     * Remove the specified role from storage.
     */
    public function destroyRole($id)
    {
        $role = \App\Models\System\Role::findOrFail($id);

        if ($role->name === 'super-admin') {
            return redirect()->back()->with('error', __('The Super Admin role cannot be deleted.'));
        }

        // 公司主帳號角色保護：比照 super-admin，公司層級的「管理員」角色為該公司最高權限角色，不可刪除。
        if ($role->is_company_admin) {
            return redirect()->back()->with('error', __('The company admin role cannot be deleted.'));
        }

        if (!auth()->user()->isSystemAdmin() && $role->is_system) {
            return redirect()->back()->with('error', __('System roles cannot be deleted by tenant administrators.'));
        }

        // 層級隔離：子帳號只能刪除自己建立的角色。
        if (!$role->canBeManagedBy(auth()->user())) {
            return redirect()->back()->with('error', __('You do not have permission to manage this role.'));
        }

        if ($role->users()->count() > 0) {
            return redirect()->back()->with('error', __('Cannot delete role with active users.'));
        }

        $role->delete();

        if (request()->routeIs('*.sub-account-roles.*')) {
            return redirect()->route('admin.data-config.sub-accounts', ['tab' => 'roles'])->with('success', __('Role deleted successfully.'));
        }

        return redirect()->back()->with('success', __('Role deleted successfully.'));
    }

    // 帳號管理
    public function accounts(Request $request)
    {
        $user = auth()->user();
        $isSubAccountRoute = $request->routeIs('admin.data-config.sub-accounts');
        $tab = $request->input('tab', 'accounts'); // 僅用於設定 Alpine activeTab 初始值

        $companies = $user->isSystemAdmin() ? \App\Models\System\Company::all() : collect();
        $currentUserRoleIds = $user->roles->pluck('id')->toArray();

        // ── 帳號列表 ──────────────────────────────────────────────────
        // 可見範圍：系統管理員看全部；主帳號看同公司全部；子帳號僅看自己直接建立的下層。
        $usersQuery = \App\Models\System\User::query()->with(['company', 'roles', 'machines'])->visibleTo($user);
        if ($search = $request->input('accounts_search', $request->input('search'))) {
            $usersQuery->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('username', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }
        if ($user->isSystemAdmin() && $request->filled('accounts_company_id')) {
            $cid = $request->input('accounts_company_id');
            $cid === 'system' ? $usersQuery->whereNull('company_id') : $usersQuery->where('company_id', $cid);
        }
        $accounts_per_page = $request->input('accounts_per_page', 10);
        $users = $usersQuery->latest()->paginate($accounts_per_page, ['*'], 'accounts_page')->withQueryString();

        // Modal 用的角色選單（不分頁）。層級隔離：子帳號僅見自己建立的角色（與角色清單一致）。
        // 預先載入 permissions 以避免下方子集過濾造成 N+1。
        $rolesForSelect = \App\Models\System\Role::query()->with('permissions')->visibleTo($user);
        $roles = $rolesForSelect->get();
        // 子集過濾：租戶只能指派「權限為自身子集」的角色（與 store/update 的後端 guard 一致，避免下拉出現越權選項）。
        if (!$user->isSystemAdmin()) {
            $operatorPerms = $user->getAllPermissions()->pluck('name');
            $roles = $roles->filter(
                fn($r) => collect($r->getPermissionNames())->diff($operatorPerms)->isEmpty()
            )->values();
        }

        // ── 角色列表 (僅 data-config Tab 版需要) ─────────────────────
        $paginated_roles = collect();
        $all_permissions = collect();

        if ($isSubAccountRoute) {
            $rolesQuery = \App\Models\System\Role::query()->with(['permissions', 'users', 'company'])->visibleTo($user);
            if ($search = $request->input('roles_search')) {
                $rolesQuery->where('name', 'like', "%{$search}%");
            }
            if ($user->isSystemAdmin() && $request->filled('roles_company_id')) {
                $cid = $request->input('roles_company_id');
                $cid === 'system' ? $rolesQuery->where('is_system', true) : $rolesQuery->where('company_id', $cid);
            }
            $roles_per_page = $request->input('roles_per_page', 10);
            $paginated_roles = $rolesQuery->latest()->paginate($roles_per_page, ['*'], 'roles_page')->withQueryString();

            $permissionQuery = \Spatie\Permission\Models\Permission::query();
            if (!$user->isSystemAdmin()) {
                $permissionQuery->whereIn('name', $user->getAllPermissions()->pluck('name'));
            }
            $all_permissions = $permissionQuery->get()
                ->reject(fn($p) => $p->name === 'menu.data-config.sub-account-roles')
                ->groupBy(fn($p) => str_starts_with($p->name, 'menu.') ? 'menu' : 'other');
        }

        $title = $isSubAccountRoute ? __('Sub Account Management') : __('Account Management');
        $view  = $isSubAccountRoute ? 'admin.data-config.accounts' : 'admin.permission.accounts';

        return view($view, compact(
            'users', 'companies', 'roles', 'paginated_roles', 'all_permissions', 'title', 'tab', 'currentUserRoleIds'
        ));
    }

    /**
     * Store a newly created account in storage.
     */
    public function storeAccount(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'username' => 'required|string|max:255|unique:users,username',
            'email' => 'nullable|email|max:255|unique:users,email',
            'password' => 'required|string|min:8',
            'role' => 'required|string',
            'status' => 'required|boolean',
            'company_id' => 'nullable|exists:companies,id',
            'phone' => 'nullable|string|max:20',
        ]);

        // 深度上限：level >= MAX_LEVEL 的子帳號不可再建立下層（系統管理員不受限）。
        // 此為後端硬擋，獨立於權限判斷（即使持有子帳號管理權限也不得突破層級）。
        if (!auth()->user()->canCreateSubAccount()) {
            return redirect()->back()->with('error', __('Sub-accounts at this level cannot create further sub-accounts.'));
        }

        $company_id = auth()->user()->isSystemAdmin() ? ($validated['company_id'] ?? null) : auth()->user()->company_id;

        // 查找角色：優先尋找該公司的角色，若無則尋找全域範本
        $role = \App\Models\System\Role::where('name', $validated['role'])
            ->where(function($q) use ($company_id) {
                $q->where('company_id', $company_id)->orWhereNull('company_id');
            })
            ->first();

        if (!$role) {
            return redirect()->back()->with('error', __('Role not found.'));
        }

        // 驗證角色與公司的匹配性 (RBAC Safeguard)
        if ($company_id !== null) {
            // 如果是租戶帳號，絕對不能指派超級管理員角色 (super-admin)
            if ($role->name === 'super-admin') {
                return redirect()->back()->with('error', __('Super-admin role cannot be assigned to tenant accounts.'));
            }
            // 如果角色有特定的 company_id，必須匹配
            if ($role->company_id !== null && $role->company_id != $company_id) {
                return redirect()->back()->with('error', __('This role belongs to another company and cannot be assigned.'));
            }
        } else {
            // 如果是系統層級帳號，只能選全域系統角色 (is_system = 1)
            if (!$role->is_system) {
                return redirect()->back()->with('error', __('Only system roles can be assigned to platform administrative accounts.'));
            }
        }

        // 角色初始化與克隆邏輯 (只有 super-admin 在幫空白公司開帳號時觸發)
        $company_id = auth()->user()->isSystemAdmin() ? ($validated['company_id'] ?? null) : auth()->user()->company_id;

        if ($company_id && $role && $role->company_id === null && $role->name !== 'super-admin') {
            // 檢查該公司是否已有名為「管理員」的角色
            $existingRole = \App\Models\System\Role::where('company_id', $company_id)
                ->where('name', '管理員')
                ->first();

            if (!$existingRole) {
                // 克隆範本為該公司的「管理員」
                $newRole = \App\Models\System\Role::query()->create([
                    'name' => '管理員',
                    'guard_name' => 'web',
                    'company_id' => $company_id,
                    'is_system' => false,
                    'is_company_admin' => true,
                ]);
                $newRole->syncPermissions($role->getPermissionNames());
                $role = $newRole;
            } else {
                // 如果已存在名為「管理員」的角色，則直接使用它
                $role = $existingRole;
            }
        }

        // 角色指派子集驗證：租戶只能指派「權限為自身子集」的角色，避免透過指派高權限角色造成權限提升。
        if (!auth()->user()->isSystemAdmin()) {
            $operatorPerms = auth()->user()->getAllPermissions()->pluck('name');
            if (collect($role->getPermissionNames())->diff($operatorPerms)->isNotEmpty()) {
                return redirect()->back()->with('error', __('You cannot assign a role with permissions you do not possess.'));
            }
        }

        // 主帳號唯一性：一家公司只在「尚無主帳號」時，由系統管理員建立的第一個帳號自動成為主帳號 (is_admin)。
        // 之後建立的帳號（含子帳號自建）一律 is_admin=false，確保每家公司恰有一個主帳號。
        $creator = auth()->user();
        $companyHasAdmin = $company_id
            ? \App\Models\System\User::where('company_id', $company_id)->where('is_admin', true)->exists()
            : false;
        $isMainAccount = $creator->isSystemAdmin() && !empty($company_id) && !$companyHasAdmin;

        // 樹狀層級歸屬：
        // - 主帳號：level 0、parent_id null。
        // - 系統管理員建立的非主帳號：掛在該公司主帳號下、level 1。
        // - 租戶自建：掛在建立者下、level = 建立者 level + 1。
        if ($isMainAccount) {
            $parentId = null;
            $level = 0;
        } elseif ($creator->isSystemAdmin()) {
            $parentId = $company_id
                ? \App\Models\System\User::where('company_id', $company_id)->where('is_admin', true)->value('id')
                : null;
            $level = $parentId ? 1 : 0;
        } else {
            $parentId = $creator->id;
            $level = $creator->level + 1;
        }

        $user = \App\Models\System\User::create([
            'name' => $validated['name'],
            'username' => $validated['username'],
            'email' => $validated['email'],
            'password' => \Illuminate\Support\Facades\Hash::make($validated['password']),
            'status' => $validated['status'],
            'company_id' => $company_id,
            'parent_id' => $parentId,
            'level' => $level,
            'phone' => $validated['phone'] ?? null,
            'is_admin' => $isMainAccount,
        ]);

        $user->assignRole($role);

        return redirect()->back()->with('success', __('Account created successfully.'));
    }

    /**
     * Update the specified account in storage.
     */
    public function updateAccount(Request $request, $id)
    {
        $user = \App\Models\System\User::findOrFail($id);

        if ($user->hasRole('super-admin') && !auth()->user()->hasRole('super-admin')) {
            return redirect()->back()->with('error', __('System super admin accounts can only be modified by other super admins.'));
        }

        // 層級越權防護：租戶只能管理可管轄範圍內的帳號（主帳號=同公司全部；子帳號=自己直接建立的下層）。
        if (!auth()->user()->canManageAccount($user)) {
            return redirect()->back()->with('error', __('You do not have permission to manage this account.'));
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'username' => 'required|string|max:255|unique:users,username,' . $id,
            'email' => 'nullable|email|max:255|unique:users,email,' . $id,
            'password' => 'nullable|string|min:8',
            'role' => 'required|string',
            'status' => 'required|boolean',
            'company_id' => 'nullable|exists:companies,id',
            'phone' => 'nullable|string|max:20',
        ]);

        $target_company_id = auth()->user()->isSystemAdmin() ? ($validated['company_id'] ?? null) : auth()->user()->company_id;

        // 查找角色：優先尋找該公司的角色，若無則尋找全域範本
        $roleObj = \App\Models\System\Role::where('name', $validated['role'])
            ->where(function($q) use ($target_company_id) {
                $q->where('company_id', $target_company_id)->orWhereNull('company_id');
            })
            ->first();

        if (!$roleObj) {
            return redirect()->back()->with('error', __('Role not found.'));
        }

        // 驗證角色與公司的匹配性 (RBAC Safeguard)
        if ($user->id !== auth()->id()) { // 排除編輯自己 (super-admin 有特殊邏輯)
            if ($target_company_id !== null) {
                // 租戶層級排除 super-admin
                if ($roleObj->name === 'super-admin') {
                    return redirect()->back()->with('error', __('Super-admin role cannot be assigned to tenant accounts.'));
                }
                if ($roleObj->company_id !== null && $roleObj->company_id != $target_company_id) {
                    return redirect()->back()->with('error', __('This role belongs to another company and cannot be assigned.'));
                }
            } else {
                if (!$roleObj->is_system) {
                    return redirect()->back()->with('error', __('Only global system roles can be assigned to platform administrative accounts.'));
                }
            }
        }

        $updateData = [
            'name' => $validated['name'],
            'username' => $validated['username'],
            'email' => $validated['email'],
            'status' => $validated['status'],
            'phone' => $validated['phone'] ?? null,
        ];

        // 只有系統管理員在編輯租戶帳號時，且該帳號原本不是管理員，才可能觸發標記（視需求而定）
        // 這裡我們維持 storeAccount 的邏輯：如果是系統管理員幫公司「開站」或「首配」，才自動標記
        // 為求嚴謹，我們檢查該公司是否已經有 is_admin，如果沒有，當前這個人可以是第一個
        if (auth()->user()->isSystemAdmin() && !empty($validated['company_id']) && !$user->is_admin) {
            $hasAdmin = \App\Models\System\User::where('company_id', $validated['company_id'])
                ->where('is_admin', true)
                ->exists();
            if (!$hasAdmin) {
                $updateData['is_admin'] = true;
            }
        }

        if (auth()->user()->isSystemAdmin()) {
            // 防止超級管理員不小心把自己綁定到租客公司或降級
            if ($user->id === auth()->id()) {
                $updateData['company_id'] = null;
                $validated['role'] = 'super-admin';
            } else {
                $updateData['company_id'] = $validated['company_id'];
            }
        }

        if (!empty($validated['password'])) {
            $updateData['password'] = \Illuminate\Support\Facades\Hash::make($validated['password']);
        }

        // 角色初始化與克隆邏輯
        $target_company_id = auth()->user()->isSystemAdmin() ? ($validated['company_id'] ?? null) : auth()->user()->company_id;

        if ($target_company_id && $roleObj && $roleObj->company_id === null && $roleObj->name !== 'super-admin') {
            // 檢查該公司是否已有名為「管理員」的角色
            $existingRole = \App\Models\System\Role::where('company_id', $target_company_id)
                ->where('name', '管理員')
                ->first();

            if (!$existingRole) {
                $newRole = \App\Models\System\Role::query()->create([
                    'name' => '管理員',
                    'guard_name' => 'web',
                    'company_id' => $target_company_id,
                    'is_system' => false,
                    'is_company_admin' => true,
                ]);
                $newRole->syncPermissions($roleObj->getPermissionNames());
                $roleObj = $newRole;
            } else {
                $roleObj = $existingRole;
            }
        }

        // 角色指派子集驗證：租戶只能指派「權限為自身子集」的角色，避免權限提升。
        if (!auth()->user()->isSystemAdmin()) {
            $operatorPerms = auth()->user()->getAllPermissions()->pluck('name');
            if (collect($roleObj->getPermissionNames())->diff($operatorPerms)->isNotEmpty()) {
                return redirect()->back()->with('error', __('You cannot assign a role with permissions you do not possess.'));
            }
        }

        $user->update($updateData);

        // 如果是編輯自己且原本是超級管理員，強制保留 super-admin 角色
        if ($user->id === auth()->id() && auth()->user()->isSystemAdmin()) {
            $user->syncRoles(['super-admin']);
        } else {
            $user->syncRoles([$roleObj]);
        }

        return redirect()->back()->with('success', __('Account updated successfully.'));
    }

    /**
     * Remove the specified account from storage.
     */
    public function destroyAccount($id)
    {
        $user = \App\Models\System\User::findOrFail($id);

        if ($user->hasRole('super-admin') && !auth()->user()->hasRole('super-admin')) {
            return redirect()->back()->with('error', __('System super admin accounts can only be deleted by other super admins.'));
        }

        // 層級越權防護：租戶只能刪除可管轄範圍內的帳號。
        if (!auth()->user()->canManageAccount($user)) {
            return redirect()->back()->with('error', __('You do not have permission to manage this account.'));
        }

        if ($user->id === auth()->id()) {
            return redirect()->back()->with('error', __('You cannot delete your own account.'));
        }

        // 主帳號保護：若該帳號是公司唯一主帳號，且公司仍有其他帳號，禁止刪除（須先轉移主帳號身分），
        // 以維持「有帳號的公司恰有一個主帳號」的不變量。公司僅剩此主帳號時允許刪除（公司歸零）。
        if ($user->is_admin && $user->company_id) {
            $hasOtherAccounts = \App\Models\System\User::where('company_id', $user->company_id)
                ->where('id', '!=', $user->id)
                ->exists();
            if ($hasOtherAccounts) {
                return redirect()->back()->with('error', __('Cannot delete the company main account while other accounts exist. Please transfer the main account role first.'));
            }
        }

        // 為了解決軟刪除導致的唯一索引佔用問題，刪除前先重命名唯一欄位
        $timestamp = now()->getTimestamp();
        $user->username = $user->username . '.deleted.' . $timestamp;
        $user->email = $user->email . '.deleted.' . $timestamp;
        $user->save();

        $user->delete();

        return redirect()->back()->with('success', __('Account deleted successfully.'));
    }

    public function toggleAccountStatus($id)
    {
        $user = \App\Models\System\User::findOrFail($id);
        
        // 非超級管理員禁止切換 Super Admin 狀態
        if ($user->hasRole('super-admin') && !auth()->user()->hasRole('super-admin')) {
            return back()->with('error', __('Only Super Admins can change other Super Admin status.'));
        }

        // 層級越權防護：租戶只能切換可管轄範圍內的帳號狀態。
        if (!auth()->user()->canManageAccount($user)) {
            return back()->with('error', __('You do not have permission to manage this account.'));
        }

        $user->status = $user->status ? 0 : 1;
        $user->save();

        $statusText = $user->status ? __('Enabled') : __('Disabled');
        return back()->with('success', __('Account :name status has been changed to :status.', ['name' => $user->name, 'status' => $statusText]));
    }

    /**
     * 獲取過濾後的活動模組權限清單
     */
    private function getFilteredPermissions($user)
    {
        $permissionQuery = \Spatie\Permission\Models\Permission::query();
        if (!$user->isSystemAdmin()) {
            $permissionQuery->whereIn('name', $user->getAllPermissions()->pluck('name'));
        }

        $activeModules = [
            'menu.machines',
            'menu.analysis',
            'menu.data-config',
            'menu.remote',
            'menu.basic',
            'menu.permissions',
        ];

        return $permissionQuery->get()
            ->filter(function($p) use ($activeModules) {
                if (str_starts_with($p->name, 'menu.')) {
                    foreach ($activeModules as $module) {
                        if ($p->name === $module || str_starts_with($p->name, $module . '.')) {
                            return true;
                        }
                    }
                    return false;
                }
                return true;
            })
            ->reject(fn($p) => $p->name === 'menu.data-config.sub-account-roles')
            ->groupBy(function($perm) {
                if (str_starts_with($perm->name, 'menu.')) {
                    return 'menu';
                }
                return 'other';
            });
    }
}
