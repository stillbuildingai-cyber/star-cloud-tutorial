<?php

namespace App\Http\Controllers\Guest;

use App\Http\Controllers\Controller;
use App\Models\Transaction\PassCode;
use Illuminate\Http\Request;

class PassCodeController extends Controller
{
    /**
     * 顯示公開通行憑證頁面
     */
    public function show($slug)
    {
        $passCode = PassCode::with(['machine'])
            ->where('slug', $slug)
            ->where('status', 'active')
            ->where(function($query) {
                $query->whereNull('expires_at')
                      ->orWhere('expires_at', '>', now());
            })
            ->firstOrFail();

        return view('guest.pass-code.show', compact('passCode'));
    }
}
