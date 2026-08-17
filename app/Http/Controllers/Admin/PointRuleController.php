<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Member\PointRule;
use Illuminate\Http\Request;

class PointRuleController extends Controller
{
    public function index()
    {
        $rules = PointRule::all();
        return view('admin.point-rules.index', compact('rules'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'trigger' => 'required|in:purchase,deposit,register,birthday,referral',
            'points_per_unit' => 'required|integer|min:1',
            'unit_amount' => 'required|numeric|min:0',
            'validity_days' => 'required|integer|min:1',
            'is_active' => 'boolean',
        ]);

        PointRule::create($validated);

        return redirect()->route('admin.point-rules.index')->with('success', '點數規則已建立');
    }

    public function update(Request $request, PointRule $pointRule)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'trigger' => 'required|in:purchase,deposit,register,birthday,referral',
            'points_per_unit' => 'required|integer|min:1',
            'unit_amount' => 'required|numeric|min:0',
            'validity_days' => 'required|integer|min:1',
            'is_active' => 'boolean',
        ]);

        $pointRule->update($validated);

        return redirect()->route('admin.point-rules.index')->with('success', '點數規則已更新');
    }

    public function destroy(PointRule $pointRule)
    {
        $pointRule->delete();
        return redirect()->route('admin.point-rules.index')->with('success', '點數規則已刪除');
    }
}
