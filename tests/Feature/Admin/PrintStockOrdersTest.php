<?php

namespace Tests\Feature\Admin;

use App\Models\Machine\Machine;
use App\Models\System\Company;
use App\Models\System\User;
use App\Models\Warehouse\Warehouse;
use App\Models\Warehouse\TransferOrder;
use App\Models\Warehouse\ReplenishmentOrder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PrintStockOrdersTest extends TestCase
{
    use RefreshDatabase;

    private $companyA;
    private $companyB;
    private $userA;
    private $userB;
    private $warehouseA;
    private $warehouseB;
    private $machineA;
    private $machineB;

    protected function setUp(): void
    {
        parent::setUp();

        // 1. 建立租戶公司
        $this->companyA = Company::create(['name' => 'Company A', 'code' => 'COA']);
        $this->companyB = Company::create(['name' => 'Company B', 'code' => 'COB']);

        // 2. 建立使用者
        $this->userA = User::factory()->create([
            'company_id' => $this->companyA->id,
            'name' => 'User A',
            'email' => 'userA@star-cloud.com'
        ]);
        $this->userB = User::factory()->create([
            'company_id' => $this->companyB->id,
            'name' => 'User B',
            'email' => 'userB@star-cloud.com'
        ]);

        // 3. 建立倉庫與機台（包含 serial_no 避免 Integrity constraint violation 錯誤）
        $this->warehouseA = Warehouse::create([
            'company_id' => $this->companyA->id,
            'name' => 'Warehouse A',
            'type' => 'main',
            'is_active' => true
        ]);
        $this->warehouseB = Warehouse::create([
            'company_id' => $this->companyB->id,
            'name' => 'Warehouse B',
            'type' => 'main',
            'is_active' => true
        ]);

        $this->machineA = Machine::create([
            'company_id' => $this->companyA->id,
            'name' => 'Machine A',
            'serial_no' => 'SN-MAA-01'
        ]);
        $this->machineB = Machine::create([
            'company_id' => $this->companyB->id,
            'name' => 'Machine B',
            'serial_no' => 'SN-MBB-02'
        ]);
    }

    /**
     * 測試租戶可以成功列印自己公司的庫存調撥單
     */
    public function test_tenant_can_print_own_transfer_order()
    {
        $this->actingAs($this->userA);

        // 建立調撥單
        $transfer = TransferOrder::create([
            'company_id' => $this->companyA->id,
            'order_no' => 'TF-202605210001',
            'type' => 'warehouse_to_warehouse',
            'from_warehouse_id' => $this->warehouseA->id,
            'to_warehouse_id' => $this->warehouseA->id, // 本地測試可以使用同一個以簡化
            'status' => 'draft',
            'created_by' => $this->userA->id
        ]);

        $response = $this->get("/admin/warehouses/transfers/{$transfer->id}/print");

        $response->assertStatus(200);
        $response->assertSee('TF-202605210001');
        $response->assertSee('Star Cloud System');
        $response->assertSee('經辦人');
        $response->assertSee('調出確認');
    }

    /**
     * 測試租戶可以成功列印自己公司的機台補貨單
     */
    public function test_tenant_can_print_own_replenishment_order()
    {
        $this->actingAs($this->userA);

        // 建立補貨單
        $replenishment = ReplenishmentOrder::create([
            'company_id' => $this->companyA->id,
            'order_no' => 'RP-202605210002',
            'warehouse_id' => $this->warehouseA->id,
            'machine_id' => $this->machineA->id,
            'status' => 'pending',
            'created_by' => $this->userA->id
        ]);

        $response = $this->get("/admin/warehouses/replenishments/{$replenishment->id}/print");

        $response->assertStatus(200);
        $response->assertSee('RP-202605210002');
        $response->assertSee('Star Cloud System');
        $response->assertSee('經辦人');
        $response->assertSee('倉庫出庫確認');
    }

    /**
     * 測試租戶嘗試越權存取（列印）其他租戶的調撥單會被阻斷（返回 404）
     */
    public function test_tenant_cannot_print_other_tenant_transfer_order()
    {
        // 先以 User B 建立調撥單
        $this->actingAs($this->userB);
        $transferB = TransferOrder::create([
            'company_id' => $this->companyB->id,
            'order_no' => 'TF-B-9999',
            'type' => 'warehouse_to_warehouse',
            'from_warehouse_id' => $this->warehouseB->id,
            'to_warehouse_id' => $this->warehouseB->id,
            'status' => 'draft',
            'created_by' => $this->userB->id
        ]);

        // 切換為 User A 請求列印 User B 的調撥單
        $this->actingAs($this->userA);
        $response = $this->get("/admin/warehouses/transfers/{$transferB->id}/print");

        // 由於 TenantScoped Trait 自動隔離，會直接拋出 ModelNotFoundException 進而由 Laravel 轉換為 404
        $response->assertStatus(404);
    }

    /**
     * 測試租戶嘗試越權存取（列印）其他租戶的補貨單會被阻斷（返回 404）
     */
    public function test_tenant_cannot_print_other_tenant_replenishment_order()
    {
        // 先以 User B 建立補貨單
        $this->actingAs($this->userB);
        $replenishmentB = ReplenishmentOrder::create([
            'company_id' => $this->companyB->id,
            'order_no' => 'RP-B-9999',
            'warehouse_id' => $this->warehouseB->id,
            'machine_id' => $this->machineB->id,
            'status' => 'pending',
            'created_by' => $this->userB->id
        ]);

        // 切換為 User A 請求列印 User B 的補貨單
        $this->actingAs($this->userA);
        $response = $this->get("/admin/warehouses/replenishments/{$replenishmentB->id}/print");

        // 由於 TenantScoped Trait 自動隔離，會直接拋出 ModelNotFoundException 進而由 Laravel 轉換為 404
        $response->assertStatus(404);
    }
}
