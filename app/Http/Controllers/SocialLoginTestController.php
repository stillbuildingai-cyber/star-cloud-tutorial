<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class SocialLoginTestController extends Controller
{
    public function index()
    {
        return view('test.social-login');
    }

    public function lineCallback(Request $request)
    {
        // 這裡可以實作後端換發 Token 的邏輯
        // 為了測試方便，我們先直接顯示回傳的 code 與 state
        // 或者嘗試交換 Token 並取得 User Profile
        
        $code = $request->input('code');
        $state = $request->input('state');
        $error = $request->input('error');
        
        return view('test.social-login', [
            'line_data' => [
                'code' => $code,
                'state' => $state,
                'error' => $error
            ]
        ]);
    }
}
