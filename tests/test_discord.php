<?php

// 載入 Laravel Bootstrap
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Machine\Machine;
use App\Models\Machine\MachineLog;
use App\Models\System\Company;
use App\Services\Notification\DiscordWebhookService;
use Illuminate\Support\Facades\Http;

// 1. Fake HTTP 請求以攔截 payload
Http::fake(function ($request) {
    $payload = json_decode($request->body(), true);
    
    echo "========================================================\n";
    echo "📡 Discord Webhook URL: " . $request->url() . "\n";
    echo "📦 Username: " . ($payload['username'] ?? 'N/A') . "\n";
    
    if (!empty($payload['embeds'])) {
        foreach ($payload['embeds'] as $i => $embed) {
            echo "--- [Embed " . ($i + 1) . "] ---\n";
            echo "🔴 Title: " . ($embed['title'] ?? 'N/A') . "\n";
            echo "📝 Description: " . ($embed['description'] ?? 'N/A') . "\n";
            echo "🎨 Color: " . ($embed['color'] ?? 'N/A') . "\n";
            echo "📊 Fields:\n";
            if (!empty($embed['fields'])) {
                foreach ($embed['fields'] as $field) {
                    echo "   • " . $field['name'] . ": " . $field['value'] . " (Inline: " . ($field['inline'] ? 'Yes' : 'No') . ")\n";
                }
            } else {
                echo "   (No Fields)\n";
            }
        }
    }
    echo "========================================================\n\n";
    
    return Http::response(['status' => 'ok'], 200);
});

// 2. 準備假數據 (用真實 Model)
$company = Company::firstOrNew(['code' => 'TEST_COMP']);
$company->name = '星科技 (Star Tech)';
$company->settings = ['locale' => 'zh_TW']; // 測試 Locale 變更
$company->save();

$machine = Machine::firstOrNew(['serial_no' => 'M1103']);
$machine->name = '機台MQTT測試1';
$machine->location = '台中車站1樓';
$machine->company_id = $company->id;
$machine->save();

$service = app(DiscordWebhookService::class);
$webhookUrl = 'https://discord.com/api/webhooks/mock-url-123';

$locales = ['zh_TW', 'en', 'ja'];

foreach ($locales as $loc) {
    echo "💡 [測試語系: {$loc}]\n";
    $company->settings = array_merge($company->settings ?? [], ['locale' => $loc]);
    $company->save();
    
    // (A) 連線中斷 LWT
    $logLwt = new MachineLog([
        'machine_id' => $machine->id,
        'company_id' => $company->id,
        'type' => 'status',
        'level' => 'warning',
        'message' => 'Connection lost (LWT)',
    ]);
    $service->sendMachineLogAlert($webhookUrl, $logLwt);
    
    // (B) 連線恢復
    $logRestored = new MachineLog([
        'machine_id' => $machine->id,
        'company_id' => $company->id,
        'type' => 'status',
        'level' => 'info',
        'message' => 'Connection restored',
    ]);
    $service->sendMachineLogAlert($webhookUrl, $logRestored);

    // (C) B013 0205 貨道阻塞異常
    $logB013 = new MachineLog([
        'machine_id' => $machine->id,
        'company_id' => $company->id,
        'type' => 'submachine',
        'level' => 'error',
        'message' => 'Slot 1: Slot sensor blocked (Code: 0205)',
        'context' => [
            'error_code' => '0205',
            'tid' => '1',
            'translated_label' => 'Slot sensor blocked',
        ]
    ]);
    $service->sendMachineLogAlert($webhookUrl, $logB013);
}

echo "🎉 測試完成！\n";
