<?php

namespace Tests\Feature\Admin;

use App\Models\System\Company;
use App\Models\System\User;
use App\Models\Machine\Machine;
use App\Models\Product\Product;
use App\Models\Transaction\Order;
use App\Models\Transaction\OrderItem;
use App\Models\Transaction\DispenseRecord;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AnalysisReportsTest extends TestCase
{
    use RefreshDatabase;

    protected $company;
    protected $superAdmin;
    protected $tenantUser;
    protected $machineA;
    protected $machineB;
    protected $productX;
    protected $productY;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutExceptionHandling();

        // 1. Seed Roles & Permissions
        $this->artisan('db:seed', ['--class' => 'RoleSeeder']);

        // 2. Create Company
        $this->company = Company::create([
            'name' => 'Report Test Company',
            'code' => 'REPTEST',
            'status' => 1,
        ]);

        // Register SQLite compatible DATE_FORMAT function
        if (\Illuminate\Support\Facades\Schema::getConnection()->getDriverName() === 'sqlite') {
            $pdo = \Illuminate\Support\Facades\Schema::getConnection()->getPdo();
            $pdo->sqliteCreateFunction('DATE_FORMAT', function ($date, $format) {
                if (empty($date)) return null;
                $phpFormat = str_replace(
                    ['%Y', '%m', '%d', '%H', '%i', '%s'],
                    ['Y', 'm', 'd', 'H', 'i', 's'],
                    $format
                );
                return date($phpFormat, strtotime($date));
            }, 2);
        }

        // 3. Create Super Admin
        $this->superAdmin = User::create([
            'name' => 'Super Admin',
            'username' => 'super_reports_test',
            'email' => 'superadmin@example.com',
            'password' => bcrypt('password'),
            'company_id' => null,
        ]);
        $this->superAdmin->assignRole('super-admin');

        // 4. Create Tenant User
        $this->tenantUser = User::create([
            'name' => 'Tenant User',
            'username' => 'tenant_reports_test',
            'email' => 'tenantuser@example.com',
            'password' => bcrypt('password'),
            'company_id' => $this->company->id,
        ]);
        // Give them a role that has analysis permissions
        $role = \App\Models\System\Role::create([
            'name' => '測試角色',
            'company_id' => $this->company->id,
            'guard_name' => 'web',
            'is_system' => false,
        ]);
        $role->syncPermissions([
            'menu.analysis',
            'menu.analysis.machine-reports',
        ]);
        $this->tenantUser->assignRole($role);

        // 5. Create Machines
        $this->machineA = Machine::create([
            'company_id' => $this->company->id,
            'name' => 'Machine Alpha',
            'serial_no' => 'MC0000A',
            'status' => 'online',
        ]);

        $this->machineB = Machine::create([
            'company_id' => $this->company->id,
            'name' => 'Machine Beta',
            'serial_no' => 'MC0000B',
            'status' => 'online',
        ]);

        // Authorize Tenant User to ONLY see Machine A
        $this->machineA->users()->attach($this->tenantUser->id);

        // 6. Create Products
        $this->productX = Product::create([
            'company_id' => $this->company->id,
            'name' => 'Cola',
            'barcode' => 'BAR0001',
            'cost' => 10.00,
            'price' => 15.00,
            'is_active' => true,
        ]);

        $this->productY = Product::create([
            'company_id' => $this->company->id,
            'name' => 'Soda',
            'barcode' => 'BAR0002',
            'cost' => 20.00,
            'price' => 25.00,
            'is_active' => true,
        ]);

        // 7. Seed Orders
        // Order 1: Machine A, Product X, payment type 1 (Credit Card), price 15, quantity 2. (Sales: 30, Cost: 20, Profit: 10)
        $order1 = Order::create([
            'company_id' => $this->company->id,
            'order_no' => 'ORD0001',
            'machine_id' => $this->machineA->id,
            'payment_type' => 1,
            'total_amount' => 30.00,
            'pay_amount' => 30.00,
            'payment_status' => Order::PAYMENT_STATUS_SUCCESS,
            'payment_at' => now(),
        ]);
        OrderItem::create([
            'order_id' => $order1->id,
            'product_id' => $this->productX->id,
            'product_name' => $this->productX->name,
            'barcode' => $this->productX->barcode,
            'quantity' => 2,
            'price' => 15.00,
            'subtotal' => 30.00,
        ]);

        DispenseRecord::create([
            'company_id' => $this->company->id,
            'order_id' => $order1->id,
            'machine_id' => $this->machineA->id,
            'slot_no' => '1',
            'product_id' => $this->productX->id,
            'amount' => 15.00,
            'dispense_status' => 1,
            'machine_time' => now(),
        ]);
        DispenseRecord::create([
            'company_id' => $this->company->id,
            'order_id' => $order1->id,
            'machine_id' => $this->machineA->id,
            'slot_no' => '1',
            'product_id' => $this->productX->id,
            'amount' => 15.00,
            'dispense_status' => 1,
            'machine_time' => now(),
        ]);

        // Order 2: Machine B, Product Y, payment type 2 (E-ticket), price 25, quantity 1. (Sales: 25, Cost: 20, Profit: 5)
        $order2 = Order::create([
            'company_id' => $this->company->id,
            'order_no' => 'ORD0002',
            'machine_id' => $this->machineB->id,
            'payment_type' => 2,
            'total_amount' => 25.00,
            'pay_amount' => 25.00,
            'payment_status' => Order::PAYMENT_STATUS_SUCCESS,
            'payment_at' => now(),
        ]);
        OrderItem::create([
            'order_id' => $order2->id,
            'product_id' => $this->productY->id,
            'product_name' => $this->productY->name,
            'barcode' => $this->productY->barcode,
            'quantity' => 1,
            'price' => 25.00,
            'subtotal' => 25.00,
        ]);

        DispenseRecord::create([
            'company_id' => $this->company->id,
            'order_id' => $order2->id,
            'machine_id' => $this->machineB->id,
            'slot_no' => '1',
            'product_id' => $this->productY->id,
            'amount' => 25.00,
            'dispense_status' => 1,
            'machine_time' => now(),
        ]);
    }

    /**
     * 測試超級管理員可以撈出所有機台的銷售統計
     */
    public function test_super_admin_gets_all_machines_statistics()
    {
        $this->actingAs($this->superAdmin);

        $response = $this->getJson(route('admin.analysis.machine-reports', [
            'company_id' => $this->company->id
        ]));

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        $data = $response->json();
        
        // 總和驗證
        $this->assertEquals(3, $data['summary']['total_quantity']);
        $this->assertEquals(55.0, $data['summary']['total_sales']);
        $this->assertEquals(40.0, $data['summary']['total_cost']);
        $this->assertEquals(15.0, $data['summary']['total_profit']);

        // 機台明細驗證
        $this->assertCount(2, $data['table_data']);
        
        $machineAData = collect($data['table_data'])->firstWhere('machine_id', $this->machineA->id);
        $this->assertNotNull($machineAData);
        $this->assertEquals(2, $machineAData['total_quantity']);
        $this->assertEquals(30.0, $machineAData['total_sales']);

        $machineBData = collect($data['table_data'])->firstWhere('machine_id', $this->machineB->id);
        $this->assertNotNull($machineBData);
        $this->assertEquals(1, $machineBData['total_quantity']);
        $this->assertEquals(25.0, $machineBData['total_sales']);
    }

    /**
     * 測試一般租戶使用者只能撈出有被授權機台的數據 (Machine A)，無法看到 Machine B
     */
    public function test_tenant_user_only_sees_authorized_machine_statistics()
    {
        $this->actingAs($this->tenantUser);

        $response = $this->getJson(route('admin.analysis.machine-reports'));

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        $data = $response->json();

        // 總和應只包含 Machine A
        $this->assertEquals(2, $data['summary']['total_quantity']);
        $this->assertEquals(30.0, $data['summary']['total_sales']);
        $this->assertEquals(20.0, $data['summary']['total_cost']);
        $this->assertEquals(10.0, $data['summary']['total_profit']);

        // 機台明細應只有 1 筆 (Machine A)
        $this->assertCount(1, $data['table_data']);
        $this->assertEquals($this->machineA->id, $data['table_data'][0]['machine_id']);
    }

    /**
     * 測試支付工具篩選器能正確過濾資料
     */
    public function test_payment_types_filter_works()
    {
        $this->actingAs($this->superAdmin);

        // 僅篩選支付類型 1 (信用卡)
        $response = $this->getJson(route('admin.analysis.machine-reports', [
            'company_id' => $this->company->id,
            'payment_types' => '1'
        ]));

        $response->assertStatus(200);
        $data = $response->json();

        // 應只有 Order 1 的資料 (Machine A)
        $this->assertEquals(2, $data['summary']['total_quantity']);
        $this->assertEquals(30.0, $data['summary']['total_sales']);
        $this->assertCount(1, $data['table_data']);
        $this->assertEquals($this->machineA->id, $data['table_data'][0]['machine_id']);
    }
}
