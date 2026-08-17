<?php

namespace Tests\Feature\Admin;

use App\Models\Machine\Machine;
use App\Models\System\Company;
use App\Models\System\Role;
use App\Models\System\User;
use Spatie\Permission\Models\Permission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * 銷售紀錄機台權限隔離：使用者只能看到「自己可存取機台」的交易訂單，
 * 無該機台權限者不得看到其交易（修正先前訂單清單未依機台授權過濾的漏洞）。
 */
class SalesMachineAccessTest extends TestCase
{
    use RefreshDatabase;

    private Company $company;
    private User $main;
    private Machine $mA;
    private Machine $mB;
    private int $orderA;
    private int $orderB;

    protected function setUp(): void
    {
        parent::setUp();

        Permission::create(['name' => 'menu.sales', 'guard_name' => 'web']);
        $this->company = Company::create(['name' => 'Co', 'code' => 'CO']);
        $role = Role::create(['name' => 'r', 'company_id' => $this->company->id, 'is_system' => false, 'guard_name' => 'web']);
        $role->syncPermissions(['menu.sales']);

        $this->main = User::factory()->create(['company_id' => $this->company->id, 'is_admin' => true, 'level' => 0]);
        $this->main->assignRole($role);

        $this->mA = Machine::factory()->create(['company_id' => $this->company->id]);
        $this->mB = Machine::factory()->create(['company_id' => $this->company->id]);

        $this->orderA = $this->makeOrder($this->mA->id);
        $this->orderB = $this->makeOrder($this->mB->id);
    }

    private function makeOrder(int $machineId): int
    {
        return DB::table('orders')->insertGetId([
            'company_id' => $this->company->id,
            'machine_id' => $machineId,
            'payment_type' => 1,
            'total_amount' => 100,
            'pay_amount' => 100,
            'payment_status' => 1,
            'status' => 'completed',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function visibleOrderIds(User $user): array
    {
        $response = $this->actingAs($user)->get(route('admin.sales.index'));
        $response->assertOk();
        return collect($response->viewData('orders')->items())->pluck('id')->all();
    }

    public function test_sub_account_with_machine_access_sees_only_that_machine_orders(): void
    {
        $sub = User::factory()->create(['company_id' => $this->company->id, 'is_admin' => false, 'level' => 1, 'parent_id' => $this->main->id]);
        $sub->assignRole(Role::where('company_id', $this->company->id)->first());
        $sub->machines()->attach($this->mA->id); // 只被授權 mA

        $ids = $this->visibleOrderIds($sub);
        $this->assertContains($this->orderA, $ids);
        $this->assertNotContains($this->orderB, $ids); // 未授權 mB → 看不到其訂單
    }

    public function test_sub_account_without_any_machine_access_sees_no_orders(): void
    {
        $sub = User::factory()->create(['company_id' => $this->company->id, 'is_admin' => false, 'level' => 1, 'parent_id' => $this->main->id]);
        $sub->assignRole(Role::where('company_id', $this->company->id)->first());
        // 不授權任何機台

        $ids = $this->visibleOrderIds($sub);
        $this->assertNotContains($this->orderA, $ids);
        $this->assertNotContains($this->orderB, $ids);
    }

    public function test_system_admin_sees_all_orders(): void
    {
        $sysAdmin = User::factory()->create(['company_id' => null]);

        $ids = $this->visibleOrderIds($sysAdmin);
        $this->assertContains($this->orderA, $ids);
        $this->assertContains($this->orderB, $ids);
    }
}
