<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class SpecialPermissionController extends Controller
{
    // 庫存清空
    public function clearStock()
    {
        return view('admin.placeholder', [
            'title' => '庫存清空',
            'description' => '特殊權限庫存清空功能',
        ]);
    }
}
