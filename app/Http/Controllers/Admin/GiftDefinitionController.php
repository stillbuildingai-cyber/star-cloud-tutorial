<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Member\GiftDefinition;
use App\Models\Member\MembershipTier;
use Illuminate\Http\Request;

class GiftDefinitionController extends Controller
{
    public function index()
    {
        $gifts = GiftDefinition::with('tier')->get();
        $tiers = MembershipTier::orderBy('sort_order')->get();
        return view('admin.gift-definitions.index', compact('gifts', 'tiers'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:points,coupon,product,discount,cash',
            'value' => 'required|numeric|min:0',
            'tier_id' => 'nullable|exists:membership_tiers,id',
            'trigger' => 'required|in:register,birthday,annual,upgrade,manual',
            'validity_days' => 'required|integer|min:1',
            'is_active' => 'boolean',
        ]);

        GiftDefinition::create($validated);

        return redirect()->route('admin.gift-definitions.index')->with('success', '禮品已建立');
    }

    public function update(Request $request, GiftDefinition $giftDefinition)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:points,coupon,product,discount,cash',
            'value' => 'required|numeric|min:0',
            'tier_id' => 'nullable|exists:membership_tiers,id',
            'trigger' => 'required|in:register,birthday,annual,upgrade,manual',
            'validity_days' => 'required|integer|min:1',
            'is_active' => 'boolean',
        ]);

        $giftDefinition->update($validated);

        return redirect()->route('admin.gift-definitions.index')->with('success', '禮品已更新');
    }

    public function destroy(GiftDefinition $giftDefinition)
    {
        $giftDefinition->delete();
        return redirect()->route('admin.gift-definitions.index')->with('success', '禮品已刪除');
    }
}
