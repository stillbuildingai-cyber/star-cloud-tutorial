<?php

namespace Tests\Feature\Transaction;

use App\Models\Machine\Machine;
use App\Models\System\Company;
use App\Models\Transaction\DispenseRecord;
use App\Models\Transaction\Order;
use App\Services\Transaction\TransactionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * 真實庫存扣減的冪等驗證 —— 需 MySQL（machine_stock_movements.type enum 為 MySQL-only DDL，
 * sqlite :memory: 無法驗）。執行:
 *
 *   ./vendor/bin/sail test -c phpunit.mysql.xml --filter FinalizeStockMysqlTest
 *
 * 在預設 sqlite 套件中自動 skip,不影響主套件綠燈。
 */
class FinalizeStockMysqlTest extends TestCase
{
    use RefreshDatabase;

    private Company $company;
    private TransactionService $service;

    protected function setUp(): void
    {
        parent::setUp();

        if (DB::connection()->getDriverName() !== 'mysql') {
            $this->markTestSkipped('需 MySQL:machine_stock_movements.type enum 為 MySQL-only DDL。');
        }

        $this->company = Company::create(['name' => 'Test Company']);
        $this->service = app(TransactionService::class);
    }

    private function completedPayloadWithDispense(string $serialNo, string $rawFlowId, string $slotNo): array
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
            'dispense' => [[
                'slot_no' => $slotNo,
                'product_id' => 101,
                'amount' => 50,
                'dispense_status' => 1,
                'remaining_stock' => 4,
            ]],
        ];
    }

    /** 出貨成功扣庫存一次;重送同一筆 → 庫存不再被扣、出貨/異動紀錄不重複(終態短路 + 命名空間化)。 */
    public function test_dispense_decrements_stock_once_and_resend_is_idempotent(): void
    {
        $serial = '1234567890';
        $raw = '202606181530450001';
        $machine = Machine::factory()->create([
            'company_id' => $this->company->id,
            'serial_no' => $serial,
        ]);
        $slot = $machine->slots()->create(['slot_no' => '1', 'stock' => 5, 'max_stock' => 10]);

        $payload = $this->completedPayloadWithDispense($serial, $raw, '1');

        // 第一次:扣庫存一次
        $first = $this->service->finalizeTransaction($payload);
        $this->assertEquals(4, $slot->fresh()->stock);
        $this->assertEquals(1, DispenseRecord::where('flow_id', $serial . $raw)->count());
        $this->assertEquals(1, DB::table('machine_stock_movements')
            ->where('slot_no', '1')->where('machine_id', $machine->id)->count());

        // 重送:庫存維持 4,紀錄不重複
        $second = $this->service->finalizeTransaction($payload);
        $this->assertEquals($first->id, $second->id);
        $this->assertEquals(4, $slot->fresh()->stock, '重送不可再扣庫存');
        $this->assertEquals(1, DispenseRecord::where('flow_id', $serial . $raw)->count());
        $this->assertEquals(1, DB::table('machine_stock_movements')
            ->where('slot_no', '1')->where('machine_id', $machine->id)->count());
        $this->assertEquals(1, Order::where('flow_id', $serial . $raw)->count());
    }
}
