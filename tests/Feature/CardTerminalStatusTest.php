<?php

namespace Tests\Feature;

use App\Models\Machine\Machine;
use App\Models\Machine\MachineLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * 刷卡機 (Nexsys 終端機) 燈號/日誌 (card_terminal_status) — Machine 硬體屬性測試。
 * 涵蓋 payment_type {1 信用卡, 2 電子票證, 10 手機支付} 共用同一台實體刷卡機的概念仍保留於
 * card_terminal_enabled 硬體開關上，但「哪些交易失敗會寫入 card_terminal 日誌」的分類邏輯
 * 原本位於已整支移除的 TransactionService (販賣機交易流程)，教學版不再需要，故本檔案
 * 改為直接建立 MachineLog 紀錄來驗證 Machine/MachineLog 本身的燈號計算與翻譯邏輯，
 * 不再透過 Order/Transaction 觸發。
 *
 * 原本測試「哪些付款失敗會寫入 card_terminal 日誌」(test_scan_payment_failure_is_excluded /
 * test_failure_without_response_code_does_not_log) 的商業分類規則已隨 TransactionService
 * 一併移除，教學版程式碼中已無任何地方會建立 card_terminal 日誌，故不再適用，予以移除。
 */
class CardTerminalStatusTest extends TestCase
{
    use RefreshDatabase;

    private function createCardTerminalLog(Machine $machine, string $level, string $cardCode, bool $isResolved = false): MachineLog
    {
        return MachineLog::create([
            'machine_id' => $machine->id,
            'company_id' => $machine->company_id,
            'type' => 'card_terminal',
            'level' => $level,
            'message' => 'Card payment failed',
            'context' => ['card_code' => $cardCode],
            'is_resolved' => $isResolved,
        ]);
    }

    public function test_unresolved_warning_log_sets_card_terminal_status_warning(): void
    {
        $machine = Machine::factory()->create(['last_heartbeat_at' => now()]);

        $this->createCardTerminalLog($machine, 'warning', '0003');

        $this->assertEquals('warning', $machine->fresh()->card_terminal_status);
    }

    public function test_unresolved_error_log_sets_card_terminal_status_error(): void
    {
        $machine = Machine::factory()->create(['last_heartbeat_at' => now()]);

        $this->createCardTerminalLog($machine, 'error', '0004');

        $this->assertEquals('error', $machine->fresh()->card_terminal_status);
    }

    public function test_no_unresolved_log_means_normal_status(): void
    {
        $machine = Machine::factory()->create(['last_heartbeat_at' => now()]);

        $this->assertEquals('normal', $machine->fresh()->card_terminal_status);
    }

    public function test_resolving_prior_warning_restores_normal_status(): void
    {
        $machine = Machine::factory()->create(['last_heartbeat_at' => now()]);

        $log = $this->createCardTerminalLog($machine, 'warning', '0003');
        $this->assertEquals('warning', $machine->fresh()->card_terminal_status);

        // 模擬下一次刷卡成功後，系統將先前的警告日誌標記為已解決 (自動消警)
        $log->update(['is_resolved' => true]);

        $this->assertEquals('normal', $machine->fresh()->card_terminal_status, '消警後應恢復 normal');
        $unresolved = MachineLog::where('machine_id', $machine->id)
            ->where('type', 'card_terminal')->where('is_resolved', false)->count();
        $this->assertEquals(0, $unresolved);
    }

    public function test_translated_message_renders_official_chinese(): void
    {
        app()->setLocale('zh_TW');
        $machine = Machine::factory()->create();

        $log = MachineLog::create([
            'machine_id' => $machine->id,
            'type' => 'card_terminal',
            'level' => 'warning',
            'message' => 'Card payment failed',
            'context' => ['card_code' => '0003', 'order_no' => 'X'],
        ]);

        $this->assertStringContainsString('刷卡失敗', $log->translated_message);
        $this->assertStringContainsString('交易逾時', $log->translated_message);
        $this->assertStringContainsString('0003', $log->translated_message);
    }

    public function test_show_card_terminal_only_when_basic_and_enabled(): void
    {
        // 基礎版 + 刷卡機啟用 → 顯示
        $m1 = Machine::factory()->create();
        $m1->shopping_mode = 'basic';
        $m1->card_terminal_enabled = true;
        $m1->save();
        $this->assertTrue($m1->fresh()->show_card_terminal);

        // 基礎版 + 刷卡機未啟用 → 不顯示
        $m2 = Machine::factory()->create();
        $m2->shopping_mode = 'basic';
        $m2->card_terminal_enabled = false;
        $m2->save();
        $this->assertFalse($m2->fresh()->show_card_terminal);

        // 非基礎版(員工卡=晟崴 / 取物單=中國醫) → 無支付設備，不顯示
        foreach (['employee_card', 'pickup_sheet'] as $mode) {
            $m = Machine::factory()->create();
            $m->shopping_mode = $mode;
            $m->card_terminal_enabled = true; // 即便旗標誤開，非 basic 也不顯示
            $m->save();
            $this->assertFalse($m->fresh()->show_card_terminal, "shopping_mode={$mode} 不應顯示刷卡機");
        }
    }

    public function test_preloaded_aggregate_path_avoids_n_plus_one(): void
    {
        $machine = Machine::factory()->create();
        MachineLog::create([
            'machine_id' => $machine->id,
            'type' => 'card_terminal',
            'level' => 'warning',
            'message' => 'Card payment failed',
            'context' => ['card_code' => '0003'],
        ]);

        // 模擬 MachineController index 的預載 (withCount + 相關子查詢)
        $loaded = Machine::query()
            ->select('machines.*')
            ->withCount([
                'logs as card_terminal_error_count' => fn($q) => $q->where('type', 'card_terminal')->where('level', 'error')->where('is_resolved', false),
                'logs as card_terminal_warning_count' => fn($q) => $q->where('type', 'card_terminal')->where('level', 'warning')->where('is_resolved', false),
            ])
            ->addSelect(['latest_card_terminal_log_at' => MachineLog::select('created_at')
                ->whereColumn('machine_id', 'machines.id')
                ->where('type', 'card_terminal')
                ->whereIn('level', ['error', 'warning'])
                ->where('is_resolved', false)
                ->latest()
                ->limit(1)
            ])
            ->find($machine->id);

        $this->assertArrayHasKey('card_terminal_warning_count', $loaded->getAttributes());
        $this->assertEquals('warning', $loaded->card_terminal_status);
        $this->assertNotNull($loaded->latest_card_terminal_log_time);
    }
}
