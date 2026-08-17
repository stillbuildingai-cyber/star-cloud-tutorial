<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * 回填既有機台的顯示語系：將尚未設定 settings.languages 的機台補為
     * ["zh_TW","en","ja"]，使既有 demo/prod 行為與現況一致 —— 公司機台語系
     * 聯集維持中英日，商品編輯器照常顯示三 Tab，機台 B014 LangSet 亦得中英日。
     *
     * 僅補「缺鍵或空值」者，已設定者不動。直接以 DB 更新避免觸發 Machine 事件
     * （MQTT 同步等）與全域範圍。
     */
    public function up(): void
    {
        $default = ['zh_TW', 'en', 'ja'];

        DB::table('machines')->select('id', 'settings')->orderBy('id')
            ->chunkById(200, function ($machines) use ($default) {
                foreach ($machines as $m) {
                    $settings = $m->settings ? json_decode($m->settings, true) : [];
                    if (!is_array($settings)) {
                        $settings = [];
                    }
                    // 已有非空 languages 者跳過
                    if (!empty($settings['languages']) && is_array($settings['languages'])) {
                        continue;
                    }
                    $settings['languages'] = $default;
                    DB::table('machines')->where('id', $m->id)->update([
                        'settings' => json_encode($settings, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                    ]);
                }
            });
    }

    /**
     * 不反向移除 languages：無法區分回填值與使用者後續手動設定值，
     * 貿然移除恐誤刪設定，故 down 不動資料。
     */
    public function down(): void
    {
        // no-op
    }
};
