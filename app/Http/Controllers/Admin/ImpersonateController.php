<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\System\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ImpersonateController extends Controller
{
    /**
     * Start impersonating a user.
     */
    public function store(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
        ]);

        $currentUser = $request->user();

        // 1. Only system admins can impersonate
        if (!$currentUser->isSystemAdmin()) {
            abort(403, '只有系統管理員可以使用此功能。');
        }

        // 2. Prevent nested impersonation (cannot impersonate someone while already impersonating)
        if (session()->has('impersonated_by')) {
            return back()->with('error', '您目前已經處於切換狀態，無法再次切換。請先退出目前的切換狀態。');
        }

        $targetUserId = $request->input('user_id');

        // 3. Cannot impersonate oneself
        if ($currentUser->id == $targetUserId) {
            return back()->with('error', '無法切換至自己的帳號。');
        }

        $targetUser = User::findOrFail($targetUserId);

        // 4. Set session and login
        session()->put('impersonated_by', $currentUser->id);
        Auth::loginUsingId($targetUser->id);

        return redirect()->route('admin.dashboard')->with('success', "已成功切換至 {$targetUser->name} 的帳號。");
    }

    /**
     * Stop impersonating and return to the original user.
     */
    public function leave(Request $request)
    {
        if (!session()->has('impersonated_by')) {
            return back()->with('error', '您目前並未處於切換狀態。');
        }

        $originalUserId = session()->pull('impersonated_by');
        Auth::loginUsingId($originalUserId);

        return redirect()->route('admin.dashboard')->with('success', '已退出切換狀態，返回原帳號。');
    }

    /**
     * Fetch companies for the impersonate modal.
     */
    public function companies(Request $request)
    {
        if (!$request->user()->isSystemAdmin()) abort(403);

        $companies = \App\Models\System\Company::select('id', 'name')->orderBy('name')->get();
        return response()->json($companies);
    }

    /**
     * Fetch users for the impersonate modal, optionally filtered by company.
     */
    public function users(Request $request)
    {
        if (!$request->user()->isSystemAdmin()) abort(403);

        $query = User::select('id', 'name', 'username', 'email', 'company_id');

        if ($request->filled('company_id')) {
            $query->where('company_id', $request->company_id);
        } else {
            // Include system accounts (company_id is null) when no company is selected?
            // Actually let's just return all users if no company selected, or just system accounts.
            // But if company_id is explicitly sent as 'system', we filter for null.
            if ($request->company_id === 'system') {
                $query->whereNull('company_id');
            }
        }

        $users = $query->orderBy('name')->get();
        return response()->json($users);
    }
}
