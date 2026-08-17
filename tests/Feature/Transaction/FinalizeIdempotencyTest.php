<?php

namespace Tests\Feature\Transaction;

use App\Models\Machine\Machine;
use App\Models\System\Company;
use App\Models\Transaction\DispenseRecord;
use App\Models\Transaction\Invoice;
use App\Models\Transaction\Order;
use App\Services\Transaction\TransactionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * 交易 finalize 的冪等性、跨機台防撞號與 RelateNumber 等價性測試。
 *
 * 對應改動:flow_id 入口以 serial 前綴命名空間化(無分隔符)+ 終態短路移入交易內加排他鎖。
 * 註:sqlite :memory: 無真實併發,故此處驗的是「順序重送」的冪等(由終態短路保證);
 *     lockForUpdate 的真實併發效果需 MySQL 環境另測。
 */
class FinalizeIdempotencyTest extends TestCase
{
    use RefreshDatabase;

    private Company $company;
    private TransactionService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->company = Company::create(['name' => 'Test Company']);
        $this->service = app(TransactionService::class);
    }

    private function makeMachine(string $serialNo, bool $taxInvoice = false): Machine
    {
        return Machine::factory()->create([
            'company_id' => $this->company->id,
            'serial_no' => $serialNo,
            'tax_invoice_enabled' => $taxInvoice,
        ]);
    }

    private function completedPayload(string $serialNo, string $rawFlowId, array $dispense = []): array
    {
        return [
            'serial_no' => $serialNo,
            'flow_id' => $rawFlowId,
            'payment_type' => 1,
            'order' => [
                'flow_id' => $rawFlowId,
                'total_amount' => 50,
                'pay_amount' => 50,
                'payment_type' => 1,
                'status' => 'completed',
                'payment_status' => 1,
            ],
            'dispense' => $dispense,
        ];
    }

    /** 順序重送同一筆 → 只建一張單、出貨一次、庫存只扣一次。 */
    public function test_resending_same_transaction_is_idempotent(): void
    {
        $serial = '1234567890';
        $raw = '202606181530450001';
        $this->makeMachine($serial);

        // 不建貨道:recordDispense 在 slot=null 時仍建出貨紀錄,但跳過 decrement/stockMovement
        // (stockMovement 的 type enum 是 MySQL-only DDL,sqlite :memory: 無法驗,屬既有環境限制)。
        // 「出貨紀錄只一筆」與「庫存只扣一次」由同一道終態短路保證,此處以前者代證。
        $dispense = [[
            'slot_no' => '99',
            'product_id' => 101,
            'amount' => 50,
            'dispense_status' => 1,
            'remaining_stock' => 4,
        ]];

        $first = $this->service->finalizeTransaction($this->completedPayload($serial, $raw, $dispense));
        $second = $this->service->finalizeTransaction($this->completedPayload($serial, $raw, $dispense));

        // 同一張單(終態短路)、出貨紀錄只有一筆(重送不重複扣庫存的同源保證)
        $this->assertEquals($first->id, $second->id);
        $this->assertEquals(1, Order::where('flow_id', $serial . $raw)->count());
        $this->assertEquals(1, DispenseRecord::where('flow_id', $serial . $raw)->count());
    }

    /** 不同機台、相同原始 flow_id → 命名空間化後為兩張獨立單,不互吞。 */
    public function test_same_raw_flow_id_across_machines_creates_separate_orders(): void
    {
        $raw = '202606181530450001';
        $machineA = $this->makeMachine('1111111111');
        $machineB = $this->makeMachine('2222222222');

        $orderA = $this->service->finalizeTransaction($this->completedPayload('1111111111', $raw));
        $orderB = $this->service->finalizeTransaction($this->completedPayload('2222222222', $raw));

        $this->assertNotEquals($orderA->id, $orderB->id);
        $this->assertEquals('1111111111' . $raw, $orderA->flow_id);
        $this->assertEquals('2222222222' . $raw, $orderB->flow_id);
    }

    /** display_flow_id 顯示用 accessor:剝除機台序號前綴,DB 仍存完整 serial+raw 唯一值。 */
    public function test_display_flow_id_strips_serial_prefix(): void
    {
        $serial = '2501000062';
        $raw = '202606190839470011';
        $this->makeMachine($serial);

        $order = $this->service->finalizeTransaction($this->completedPayload($serial, $raw));

        // DB 值維持完整 28 碼;顯示值剝掉序號前綴 → 乾淨 18 碼
        $this->assertEquals($serial . $raw, $order->flow_id);
        $this->assertEquals($raw, $order->fresh()->display_flow_id);
    }

    /** RelateNumber = serial + rawFlowId(無分隔符、≤30、與線上推導逐字等價,不可雙重 serial)。 */
    public function test_relate_number_equals_serial_plus_raw_flow_id(): void
    {
        $serial = '1234567890';
        $raw = '202606181530450001';
        $this->makeMachine($serial, taxInvoice: true);

        $payload = $this->completedPayload($serial, $raw);
        $payload['invoice'] = ['status' => 'pending', 'amount' => 50];

        $order = $this->service->finalizeTransaction($payload);
        $invoice = Invoice::where('flow_id', $serial . $raw)->first();

        $this->assertNotNull($invoice);
        $this->assertEquals($serial . $raw, $invoice->relate_number);
        $this->assertLessThanOrEqual(30, strlen($invoice->relate_number));
        // 不可出現雙重 serial 前綴
        $this->assertStringStartsNotWith($serial . $serial, $invoice->relate_number);
    }

    /** pending 先送、completed 後送 → 同一張單狀態轉移(命名空間化前後一致)。 */
    public function test_pending_then_completed_transitions_same_order(): void
    {
        $serial = '1234567890';
        $raw = '202606181530450001';
        $this->makeMachine($serial);

        $pending = $this->completedPayload($serial, $raw);
        $pending['order']['status'] = 'pending';
        $pending['order']['payment_status'] = 0;
        $pending['dispense'] = [];
        $pendingOrder = $this->service->finalizeTransaction($pending);
        $this->assertEquals('pending', $pendingOrder->status);

        // slot_no=99 不存在 → 跳過 stockMovement(MySQL-only),仍建出貨紀錄
        $completed = $this->completedPayload($serial, $raw, [[
            'slot_no' => '99', 'product_id' => 101, 'amount' => 50,
            'dispense_status' => 1, 'remaining_stock' => 4,
        ]]);
        $completedOrder = $this->service->finalizeTransaction($completed);

        // 同一張單(命名空間化前後 key 一致)、轉為終態、出貨紀錄只一筆
        $this->assertEquals($pendingOrder->id, $completedOrder->id);
        $this->assertEquals('completed', $completedOrder->fresh()->status);
        $this->assertEquals(1, Order::where('flow_id', $serial . $raw)->count());
        $this->assertEquals(1, DispenseRecord::where('flow_id', $serial . $raw)->count());
    }
}
