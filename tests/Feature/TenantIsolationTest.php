<?php

namespace Tests\Feature;

use App\Models\Machine\Machine;
use App\Models\System\Company;
use App\Models\System\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    public function test_tenant_can_only_see_their_own_machines()
    {
        // 建立兩家公司
        $companyA = Company::create(['name' => 'Company A', 'code' => 'COA']);
        $companyB = Company::create(['name' => 'Company B', 'code' => 'COB']);

        // 建立各自的機台
        Machine::create(['name' => 'Machine A', 'company_id' => $companyA->id, 'serial_no' => 'SN001']);
        Machine::create(['name' => 'Machine B', 'company_id' => $companyB->id, 'serial_no' => 'SN002']);

        // 建立租戶 A 的使用者並登入
        $userA = User::factory()->create(['company_id' => $companyA->id]);
        $this->actingAs($userA);

        // 驗證 Eloquent 查詢是否被過濾
        $machines = Machine::all();
        
        $this->assertCount(1, $machines);
        $this->assertEquals('Machine A', $machines->first()->name);
        $this->assertFalse($machines->contains('name', 'Machine B'));
    }

    public function test_system_admin_can_see_all_machines()
    {
        // 建立兩家公司
        $companyA = Company::create(['name' => 'Company A', 'code' => 'COA']);
        $companyB = Company::create(['name' => 'Company B', 'code' => 'COB']);

        // 建立各自的機台
        Machine::create(['name' => 'Machine A', 'company_id' => $companyA->id, 'serial_no' => 'SN001']);
        Machine::create(['name' => 'Machine B', 'company_id' => $companyB->id, 'serial_no' => 'SN002']);

        // 建立系統管理員 (company_id = null) 並登入
        $admin = User::factory()->create(['company_id' => null]);
        $this->actingAs($admin);

        // 驗證 Eloquent 查詢未被過濾
        $machines = Machine::all();
        
        $this->assertCount(2, $machines);
    }

    public function test_creating_machine_automatically_sets_company_id_for_tenant()
    {
        $companyA = Company::create(['name' => 'Company A', 'code' => 'COA']);
        $userA = User::factory()->create(['company_id' => $companyA->id]);
        $this->actingAs($userA);

        // 建立新機台（不指定 company_id）
        $machine = Machine::create(['name' => 'New Machine', 'serial_no' => 'SN003']);

        $this->assertEquals($companyA->id, $machine->company_id);
    }
}
