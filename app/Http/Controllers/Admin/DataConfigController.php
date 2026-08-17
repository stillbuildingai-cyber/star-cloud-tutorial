<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class DataConfigController extends Controller
{
    // 商品管理
    public function products()
    {
        return view('admin.placeholder', [
            'title' => '商品管理',
            'description' => '商品資料維護',
        ]);
    }

    // 廣告管理
    public function advertisements()
    {
        return view('admin.placeholder', [
            'title' => '廣告管理',
            'description' => '機台廣告影片管理',
        ]);
    }


    // 子帳號管理
    public function subAccounts()
    {
        return view('admin.placeholder', [
            'title' => '子帳號管理',
            'description' => '子帳號建立與管理',
        ]);
    }

    // 子帳號角色管理
    public function subAccountRoles()
    {
        return view('admin.placeholder', [
            'title' => '子帳號角色管理',
            'description' => '子帳號權限角色設定',
        ]);
    }

    // 點數設定
    public function points()
    {
        return view('admin.placeholder', [
            'title' => '點數設定',
            'description' => '客戶點數系統設定',
        ]);
    }

    // 識別證管理
    public function badges()
    {
        return view('admin.placeholder', [
            'title' => '識別證管理',
            'description' => '識別證資料管理（安霸系統使用）',
        ]);
    }
}
