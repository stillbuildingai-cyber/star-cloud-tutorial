<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ReservationController extends Controller
{
    // Line會員管理
    public function members()
    {
        return view('admin.placeholder', [
            'title' => '預約系統會員管理',
            'description' => '預約系統會員資料管理',
        ]);
    }

    // Line店家管理
    public function stores()
    {
        return view('admin.placeholder', [
            'title' => '店家管理',
            'description' => '店家資訊設定',
        ]);
    }

    // Line時段組合
    public function timeSlots()
    {
        return view('admin.placeholder', [
            'title' => '時段組合',
            'description' => '預約時段設定',
        ]);
    }

    // Line場地管理
    public function venues()
    {
        return view('admin.placeholder', [
            'title' => '場地管理',
            'description' => '場地資源管理',
        ]);
    }

    // Line優惠券管理
    public function coupons()
    {
        return view('admin.placeholder', [
            'title' => '優惠券管理',
            'description' => '預約優惠券管理',
        ]);
    }

    // Line預約管理
    public function reservations()
    {
        return view('admin.placeholder', [
            'title' => '預約管理',
            'description' => '預約單管理',
        ]);
    }

    // Line訂單管理
    public function orders()
    {
        return view('admin.placeholder', [
            'title' => '訂單管理',
            'description' => '預約訂單處理',
        ]);
    }
}
