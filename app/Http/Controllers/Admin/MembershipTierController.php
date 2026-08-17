<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Member\MembershipTier;
use Illuminate\Http\Request;

class MembershipTierController extends Controller
{
    public function index()
    {
        $tiers = MembershipTier::orderBy('sort_order')->get();
        return view('admin.membership-tiers.index', compact('tiers'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'annual_fee' => 'required|numeric|min:0',
            'discount_rate' => 'required|numeric|min:0|max:1',
            'point_multiplier' => 'required|numeric|min:0',
            'description' => 'nullable|string',
            'is_default' => 'boolean',
        ]);

        if ($request->is_default) {
            MembershipTier::where('is_default', true)->update(['is_default' => false]);
        }

        MembershipTier::create($validated);

        return redirect()->route('admin.membership-tiers.index')->with('success', '會員等級已建立');
    }

    public function update(Request $request, MembershipTier $membershipTier)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'annual_fee' => 'required|numeric|min:0',
            'discount_rate' => 'required|numeric|min:0|max:1',
            'point_multiplier' => 'required|numeric|min:0',
            'description' => 'nullable|string',
            'is_default' => 'boolean',
        ]);

        if ($request->is_default && !$membershipTier->is_default) {
            MembershipTier::where('is_default', true)->update(['is_default' => false]);
        }

        $membershipTier->update($validated);

        return redirect()->route('admin.membership-tiers.index')->with('success', '會員等級已更新');
    }

    public function destroy(MembershipTier $membershipTier)
    {
        $membershipTier->delete();
        return redirect()->route('admin.membership-tiers.index')->with('success', '會員等級已刪除');
    }
}
