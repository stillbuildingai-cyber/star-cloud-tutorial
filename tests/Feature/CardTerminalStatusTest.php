<?php

namespace Tests\Feature;

use App\Jobs\Transaction\ProcessTransactionFinalized;
use App\Models\Machine\Machine;
use App\Models\Machine\MachineLog;
use App\Services\Machine\MqttService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * 刷卡機 (Nexsys 終端機) 交易訊號 → 後台機台日誌/燈號 (Plan A) 測試。
 * 涵蓋 payment_type {1 信用卡, 2 電子票證, 10 手機支付} 共用同一台實體刷卡機。
 */
class CardTerminalStatusTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Job handle 內注入 MqttService 發 ack，測試環境以 mock 取代避免真的連 MQTT
        $this->mock(MqttService::class, function ($m) {
            $m->shouldReceive('pushCommand')->andReturnTrue();
        });
    }

    /** 組裝一筆 finalize payload */
    private function finalizePayload(string $serialNo, string $flowId, int $paymentType, int $paymentStatus, ?string $paymentResponse, string $status): array
    {
        return [
            'action' => 'finalize',
            'flow_id' => $flowId,
            'payment_type' => $paymentType,
            'serial_no' => $serialNo,
            'order' => [
                'flow_id' => $flowId,
                'order_no' => $flowId,
                'total_amount' => 59,
                'pay_amount' => 59,
                'payment_type' => $paymentType,
                'payment_status' => $paymentStatus,
                'payment_response' => $paymentResponse,
                'status' => $status,
                'items' => [],
            ],
        ];
    }

    public function test_failed_credit_card_writes_card_terminal_warning_log(): void
    {
        $machine = Machine::factory()->create(['last_heartbeat_at' => now()]);

        ProcessTransactionFinalized::dispatchSync(
            $machine->serial_no,
            $this->finalizePayload($machine->serial_no, 'FLOW-CC-FAIL', 1, 0, 'RAWMSG... | code=0003', 'failed')
        );

        $log = MachineLog::where('machine_id', $machine->id)->where('type', 'card_terminal')->first();
        $this->assertNotNull($log, '應寫入一筆 card_terminal 日誌');
        $this->assertEquals('warning', $log->level, 'card_terminal 應為 warning 級 (不觸發 Discord)');
        $this->assertEquals('0003', $log->context['card_code']);

        // 燈號 (fallback 即時查詢路徑)
        $this->assertEquals('warning', $machine->fresh()->card_terminal_status);
    }

    public function test_failed_eticket_and_mobile_pay_also_write_log(): void
    {
        foreach ([2 => 'FLOW-ET', 10 => 'FLOW-MO'] as $type => $flow) {
            $machine = Machine::factory()->create(['last_heartbeat_at' => now()]);

            ProcessTransactionFinalized::dispatchSync(
                $machine->serial_no,
                $this->finalizePayload($machine->serial_no, $flow, $type, 0, ' | code=0005', 'failed')
            );

            $this->assertEquals('warning', $machine->fresh()->card_terminal_status, "payment_type={$type} 應納入刷卡機燈號");
        }
    }

    public function test_scan_payment_failure_is_excluded(): void
    {
        $machine = Machine::factory()->create(['last_heartbeat_at' => now()]);

        // payment_type=3 掃碼支付 (走網路，非實體刷卡機) → 不應寫 card_terminal 日誌
        ProcessTransactionFinalized::dispatchSync(
            $machine->serial_no,
            $this->finalizePayload($machine->serial_no, 'FLOW-SCAN', 3, 0, ' | code=scan_failed', 'failed')
        );

        $this->assertEquals(0, MachineLog::where('machine_id', $machine->id)->where('type', 'card_terminal')->count());
        $this->assertEquals('normal', $machine->fresh()->card_terminal_status);
    }

    public function test_successful_transaction_auto_resolves_prior_warnings(): void
    {
        $machine = Machine::factory()->create(['last_heartbeat_at' => now()]);

        // 先製造一筆未解決的刷卡機警告
        ProcessTransactionFinalized::dispatchSync(
            $machine->serial_no,
            $this->finalizePayload($machine->serial_no, 'FLOW-FAIL', 1, 0, ' | code=0003', 'failed')
        );
        $this->assertEquals('warning', $machine->fresh()->card_terminal_status);

        // 下一筆刷卡成功 → 應自動消警，燈號恢復 normal
        ProcessTransactionFinalized::dispatchSync(
            $machine->serial_no,
            $this->finalizePayload($machine->serial_no, 'FLOW-OK', 1, 1, 'RAWMSG-SUCCESS', 'completed')
        );

        $this->assertEquals('normal', $machine->fresh()->card_terminal_status, '刷卡成功後應自動消警');
        $unresolved = MachineLog::where('machine_id', $machine->id)
            ->where('type', 'card_terminal')->where('is_resolved', false)->count();
        $this->assertEquals(0, $unresolved);
    }

    public function test_failure_without_response_code_does_not_log(): void
    {
        $machine = Machine::factory()->create(['last_heartbeat_at' => now()]);

        // 失敗但沒有刷卡機回應碼 (例如顧客未感應就放棄) → 不視為硬體訊號問題
        ProcessTransactionFinalized::dispatchSync(
            $machine->serial_no,
            $this->finalizePayload($machine->serial_no, 'FLOW-NOCODE', 1, 0, 'JUSTRAWNOCODE', 'abandoned')
        );

        $this->assertEquals(0, MachineLog::where('machine_id', $machine->id)->where('type', 'card_terminal')->count());
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
