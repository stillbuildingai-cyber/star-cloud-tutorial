<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class AuditController extends Controller
{
    // 採購單稽核
    public function purchases()
    {
        return view('admin.placeholder', [
            'title' => '採購單稽核',
            'description' => '採購單審核流程',
        ]);
    }

    // 調撥單稽核
    public function transfers()
    {
        return view('admin.placeholder', [
            'title' => '調撥單稽核',
            'description' => '調撥單審核流程',
        ]);
    }

    // 補貨單稽核
    public function replenishments()
    {
        return view('admin.placeholder', [
            'title' => '補貨單稽核',
            'description' => '補貨單審核流程',
        ]);
    }
}
