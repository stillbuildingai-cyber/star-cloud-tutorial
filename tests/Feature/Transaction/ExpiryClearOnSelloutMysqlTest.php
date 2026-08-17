<?php

namespace Tests\Feature\Transaction;

use App\Models\Machine\Machine;
use App\Models\System\Company;
use App\Services\Transaction\TransactionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * 售完（庫存歸 0）自動清空效期/批號 —— 需 MySQL（recordDispense 內部寫
 * machine_stock_movements，其 type enum 為 MySQL-only DDL，sqlite :memory: 無法驗）。執行:
 *
 *   ./vendor/bin/sail test -c phpunit.mysql.xml --filter ExpiryClearOnSelloutMysqlTest
 *
 * 在預設 sqlite 套件中自動 skip,不影響主套件綠燈。
 *
 * 動機：機台端「賣完清效期」已做，但 B009 補貨上報不回傳 expiry，後台若殘留舊效期，
 * 日後管理員只補 stock 未改效期 → update_inventory 把過期日下發 → 新批貨被誤鎖。
 * 故後台扣庫存歸 0 時亦須清效期，與機台端對稱。
 */
class ExpiryClearOnSelloutMysqlTest extends TestCase
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

    private function dispense(string $serial, string $slotNo): array
    {
        return [
            'serial_no' => $serial,
            'slot_no' => $slotNo,
            'product_id' => 101,
            'amount' => 50,
            'dispense_status' => 1,
        ];
    }

    /** 庫存賣到 0 → 效期與批號被清空。 */
    public function test_clears_expiry_and_batch_when_stock_hits_zero(): void
    {
        $serial = '1234567890';
        $machine = Machine::factory()->create([
            'company_id' => $this->company->id,
            'serial_no' => $serial,
        ]);
        $slot = $machine->slots()->create([
            'slot_no' => '1',
            'stock' => 1,
            'max_stock' => 10,
            'expiry_date' => '2030-12-31',
            'batch_no' => 'B-001',
        ]);

        $this->service->recordDispense($this->dispense($serial, '1'));

        $fresh = $slot->fresh();
        $this->assertEquals(0, $fresh->stock, '庫存應扣為 0');
        $this->assertNull($fresh->expiry_date, '售完應清空效期');
        $this->assertNull($fresh->batch_no, '售完應清空批號');
    }

    /** 庫存尚未歸 0 → 效期與批號保留（同批貨還在販售）。 */
    public function test_keeps_expiry_when_stock_remains(): void
    {
        $serial = '1234567891';
        $machine = Machine::factory()->create([
            'company_id' => $this->company->id,
            'serial_no' => $serial,
        ]);
        $slot = $machine->slots()->create([
            'slot_no' => '2',
            'stock' => 5,
            'max_stock' => 10,
            'expiry_date' => '2030-12-31',
            'batch_no' => 'B-001',
        ]);

        $this->service->recordDispense($this->dispense($serial, '2'));

        $fresh = $slot->fresh();
        $this->assertEquals(4, $fresh->stock, '庫存應扣為 4');
        $this->assertEquals('2030-12-31', $fresh->expiry_date->format('Y-m-d'), '未售完應保留效期');
        $this->assertEquals('B-001', $fresh->batch_no, '未售完應保留批號');
    }
}
