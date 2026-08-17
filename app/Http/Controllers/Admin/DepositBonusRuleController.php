<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Member\DepositBonusRule;
use Illuminate\Http\Request;

class DepositBonusRuleController extends Controller
{
    public function index()
    {
        $rules = DepositBonusRule::orderBy('min_amount')->get();
        return view('admin.deposit-bonus-rules.index', compact('rules'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'min_amount' => 'required|numeric|min:0',
            'bonus_type' => 'required|in:fixed,percentage',
            'bonus_value' => 'required|numeric|min:0',
            'is_active' => 'boolean',
            'start_at' => 'nullable|date',
            'end_at' => 'nullable|date|after:start_at',
        ]);

        DepositBonusRule::create($validated);

        return redirect()->route('admin.deposit-bonus-rules.index')->with('success', '儲值回饋規則已建立');
    }

    public function update(Request $request, DepositBonusRule $depositBonusRule)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'min_amount' => 'required|numeric|min:0',
            'bonus_type' => 'required|in:fixed,percentage',
            'bonus_value' => 'required|numeric|min:0',
            'is_active' => 'boolean',
            'start_at' => 'nullable|date',
            'end_at' => 'nullable|date|after:start_at',
        ]);

        $depositBonusRule->update($validated);

        return redirect()->route('admin.deposit-bonus-rules.index')->with('success', '儲值回饋規則已更新');
    }

    public function destroy(DepositBonusRule $depositBonusRule)
    {
        $depositBonusRule->delete();
        return redirect()->route('admin.deposit-bonus-rules.index')->with('success', '儲值回饋規則已刪除');
    }
}
