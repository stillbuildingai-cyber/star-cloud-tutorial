<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class LineController extends Controller
{
    // Line會員管理
    public function members()
    {
        return view('admin.placeholder', [
            'title' => 'Line會員管理',
            'description' => 'Line會員資料管理',
        ]);
    }

    // Line機台管理
    public function machines()
    {
        return view('admin.placeholder', [
            'title' => 'Line機台管理',
            'description' => 'Line綁定機台管理',
        ]);
    }

    // Line商品管理
    public function products()
    {
        return view('admin.placeholder', [
            'title' => 'Line商品管理',
            'description' => 'Line商城商品設定',
        ]);
    }

    // Line生活圈
    public function officialAccount()
    {
        return view('admin.placeholder', [
            'title' => 'Line生活圈',
            'description' => 'Line官方帳號整合',
        ]);
    }

    // Line商城訂單
    public function orders()
    {
        return view('admin.placeholder', [
            'title' => 'Line商城訂單',
            'description' => 'Line商城訂單管理',
        ]);
    }

    // Line優惠券
    public function coupons()
    {
        return view('admin.placeholder', [
            'title' => 'Line優惠券',
            'description' => 'Line優惠券發放與管理',
        ]);
    }
}
