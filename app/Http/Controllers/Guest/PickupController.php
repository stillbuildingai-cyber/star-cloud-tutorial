<?php

namespace App\Http\Controllers\Guest;

use App\Http\Controllers\Controller;
use App\Models\Transaction\PickupCode;
use Illuminate\Http\Request;

class PickupController extends Controller
{
    /**
     * 顯示公開取貨憑證頁面
     */
    public function show($slug)
    {
        $pickupCode = PickupCode::with(['machine.slots.product', 'product'])
            ->where(function($query) use ($slug) {
                $query->where('slug', $slug)
                      ->orWhere('code', $slug); // 相容舊版或測試
            })
            ->where('status', 'active')
            ->where('expires_at', '>', now())
            ->firstOrFail();

        return view('guest.pickup.show', compact('pickupCode'));
    }
}
