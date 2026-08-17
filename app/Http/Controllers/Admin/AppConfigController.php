<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\System\AppConfig;
use Illuminate\Http\Request;

class AppConfigController extends Controller
{
    public function index()
    {
        // 確保預設設定存在
        $defaults = [
            'ui_primary_color' => ['value' => '#4F46E5', 'group' => 'ui', 'type' => 'string'],
            'ui_logo_url' => ['value' => '', 'group' => 'ui', 'type' => 'string'],
            'helper_enabled' => ['value' => '1', 'group' => 'helper', 'type' => 'boolean'],
            'game_enabled' => ['value' => '0', 'group' => 'game', 'type' => 'boolean'],
            'questionnaire_url' => ['value' => '', 'group' => 'game', 'type' => 'string'],
        ];

        foreach ($defaults as $key => $data) {
            AppConfig::firstOrCreate(['key' => $key], $data);
        }

        $configs = AppConfig::all()->groupBy('group');

        return view('admin.app-configs.index', compact('configs'));
    }

    public function update(Request $request)
    {
        $data = $request->except('_token', '_method');

        foreach ($data as $key => $value) {
            AppConfig::updateOrCreate(
                ['key' => $key],
                ['value' => $value]
            );
        }

        return redirect()->back()->with('success', '設定已更新');
    }

    // UI元素設定
    public function uiElements()
    {
        return view('admin.placeholder', [
            'title' => 'UI元素設定',
            'description' => 'APP版面配置設定',
        ]);
    }

    // 小幫手設定
    public function helper()
    {
        return view('admin.placeholder', [
            'title' => '小幫手設定',
            'description' => 'APP內建輔助功能設定',
        ]);
    }

    // 問卷設定
    public function questionnaire()
    {
        return view('admin.placeholder', [
            'title' => '問卷設定',
            'description' => '互動問卷建立與管理',
        ]);
    }

    // 互動遊戲設定
    public function games()
    {
        return view('admin.placeholder', [
            'title' => '互動遊戲設定',
            'description' => 'APP互動遊戲配置',
        ]);
    }

    // 計時器
    public function timer()
    {
        return view('admin.placeholder', [
            'title' => '計時器',
            'description' => '時間相關功能設定',
        ]);
    }
}
