<?php

namespace App\Http\Controllers\Guest;

use App\Http\Controllers\Controller;
use App\Models\Transaction\WelcomeGift;

class WelcomeGiftController extends Controller
{
    /**
     * 顯示公開來店禮憑證頁面
     */
    public function show($slug)
    {
        $welcomeGift = WelcomeGift::with(['machine'])
            ->where('slug', $slug)
            ->where('status', 'active')
            ->where(function ($query) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->firstOrFail();

        return view('guest.welcome-gift.show', compact('welcomeGift'));
    }
}
