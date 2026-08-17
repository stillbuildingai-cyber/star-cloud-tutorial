<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;

class ThemeController extends Controller
{
    public function update(Request $request)
    {
        $theme = $request->input('theme', 'dark-blue');
        
        // 儲存主題偏好到 Cookie (365天)
        Cookie::queue('theme', $theme, 525600);
        
        return redirect()->back()->with('success', '主題已更新');
    }
}
