<?php

namespace Tests\Feature\Admin;

use App\Models\Machine\Machine;
use App\Models\System\Company;
use App\Models\System\Role;
use App\Models\System\User;
use Spatie\Permission\Models\Permission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * 第四批：角色指派子集 與 機台授權子集 把關測試。
 * - 操作者不可指派「含自身未持有權限」的角色（防權限提升）。
 * - 操作者不可授權「自己未被授權」的機台給他人（防機台層級提升）。
 * - 操作者不可對非管轄帳號操作機台授權（越權）。
 */
class AccountSubsetGuardTest extends TestCase
{
    use RefreshDatabase;

    protected Company $company;
    protected User $main;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['menu.permissions.accounts', 'menu.data-config.sub-accounts', 'extra.high.perm'] as $p) {
            Permission::create(['name' => $p, 'guard_name' => 'web']);
        }

        $this->company = Company::create(['name' => 'Test Co', 'code' => 'TEST']);

        // 主帳號角色：僅持有兩個權限（不含 extra.high.perm）
        $mainRole = Role::create(['name' => 'tenant-mgr', 'company_id' => $this->company->id, 'is_system' => false, 'guard_name' => 'web']);
        $mainRole->syncPermissions(['menu.permissions.accounts', 'menu.data-config.sub-accounts']);

        $this->main = User::factory()->create([
            'company_id' => $this->company->id, 'is_admin' => true, 'level' => 0, 'parent_id' => null,
        ]);
        $this->main->assignRole($mainRole);
    }

    // ── 角色指派子集 ─────────────────────────────────────────────

    public function test_operator_cannot_assign_role_exceeding_own_permissions(): void
    {
        // 高權限角色：多出操作者沒有的 extra.high.perm
        $highRole = Role::create(['name' => 'high-role', 'company_id' => $this->company->id, 'is_system' => false, 'guard_name' => 'web']);
        $highRole->syncPermissions(['menu.permissions.accounts', 'menu.data-config.sub-accounts', 'extra.high.perm']);

        $this->actingAs($this->main)->post(route('admin.data-config.sub-accounts.store'), [
            'name' => 'X', 'username' => 'escalated', 'email' => 'e@test.co', 'password' => 'password123',
            'role' => 'high-role', 'status' => 1,
        ])->assertSessionHas('error');

        $this->assertDatabaseMissing('users', ['username' => 'escalated']);
    }

    public function test_operator_can_assign_role_within_own_permissions(): void
    {
        $this->actingAs($this->main)->post(route('admin.data-config.sub-accounts.store'), [
            'name' => 'X', 'username' => 'ok_sub', 'email' => 'ok@test.co', 'password' => 'password123',
            'role' => 'tenant-mgr', 'status' => 1,
        ])->assertSessionHas('success');

        $this->assertDatabaseHas('users', ['username' => 'ok_sub']);
    }

    // ── 機台授權子集 ─────────────────────────────────────────────

    public function test_operator_cannot_grant_machine_they_are_not_assigned(): void
    {
        $operator = User::factory()->create([
            'company_id' => $this->company->id, 'is_admin' => false, 'level' => 1, 'parent_id' => $this->main->id,
        ]);
        $target = User::factory()->create([
            'company_id' => $this->company->id, 'is_admin' => false, 'level' => 2, 'parent_id' => $operator->id,
        ]);

        $m1 = Machine::factory()->create(['company_id' => $this->company->id]);
        $m2 = Machine::factory()->create(['company_id' => $this->company->id]);
        $operator->machines()->attach($m1->id); // 操作者只被授權 m1

        // 嘗試把自己沒有的 m2 授權給 target → 422
        $this->actingAs($operator)->postJson(route('admin.machines.permissions.accounts.sync', $target->id), [
            'machine_ids' => [$m1->id, $m2->id],
        ])->assertStatus(422);

        // 只授權自己有的 m1 → 成功
        $this->actingAs($operator)->postJson(route('admin.machines.permissions.accounts.sync', $target->id), [
            'machine_ids' => [$m1->id],
        ])->assertStatus(200);

        $this->assertDatabaseHas('machine_user', ['user_id' => $target->id, 'machine_id' => $m1->id]);
        $this->assertDatabaseMissing('machine_user', ['user_id' => $target->id, 'machine_id' => $m2->id]);
    }

    public function test_operator_cannot_assign_machines_to_non_managed_account(): void
    {
        $operator = User::factory()->create([
            'company_id' => $this->company->id, 'is_admin' => false, 'level' => 1, 'parent_id' => $this->main->id,
        ]);
        // 旁線帳號（非 operator 的下層）
        $sibling = User::factory()->create([
            'company_id' => $this->company->id, 'is_admin' => false, 'level' => 1, 'parent_id' => $this->main->id,
        ]);
        $m1 = Machine::factory()->create(['company_id' => $this->company->id]);
        $operator->machines()->attach($m1->id);

        $this->actingAs($operator)->postJson(route('admin.machines.permissions.accounts.sync', $sibling->id), [
            'machine_ids' => [$m1->id],
        ])->assertStatus(403);
    }

    public function test_sync_preserves_authorizations_outside_operator_subset(): void
    {
        $operator = User::factory()->create([
            'company_id' => $this->company->id, 'is_admin' => false, 'level' => 1, 'parent_id' => $this->main->id,
        ]);
        $target = User::factory()->create([
            'company_id' => $this->company->id, 'is_admin' => false, 'level' => 2, 'parent_id' => $operator->id,
        ]);
        $m1 = Machine::factory()->create(['company_id' => $this->company->id]);
        $m2 = Machine::factory()->create(['company_id' => $this->company->id]);

        $operator->machines()->attach($m1->id);       // 操作者只可授權 m1
        $target->machines()->attach([$m1->id, $m2->id]); // 目標既有 m1、m2（m2 在操作者範圍外）

        // 操作者送出清空（[]）：應只移除自己範圍內的 m1，保留範圍外的 m2
        $this->actingAs($operator)->postJson(route('admin.machines.permissions.accounts.sync', $target->id), [
            'machine_ids' => [],
        ])->assertStatus(200);

        $this->assertDatabaseMissing('machine_user', ['user_id' => $target->id, 'machine_id' => $m1->id]);
        $this->assertDatabaseHas('machine_user', ['user_id' => $target->id, 'machine_id' => $m2->id]);
    }

    public function test_get_account_machines_only_lists_operator_subset(): void
    {
        $operator = User::factory()->create([
            'company_id' => $this->company->id, 'is_admin' => false, 'level' => 1, 'parent_id' => $this->main->id,
        ]);
        $target = User::factory()->create([
            'company_id' => $this->company->id, 'is_admin' => false, 'level' => 2, 'parent_id' => $operator->id,
        ]);
        $m1 = Machine::factory()->create(['company_id' => $this->company->id]);
        $m2 = Machine::factory()->create(['company_id' => $this->company->id]);
        $operator->machines()->attach($m1->id);

        $response = $this->actingAs($operator)->getJson(route('admin.machines.permissions.accounts.get', $target->id));
        $response->assertStatus(200);
        $ids = collect($response->json('machines'))->pluck('id')->all();

        $this->assertContains($m1->id, $ids);
        $this->assertNotContains($m2->id, $ids); // 操作者沒被授權的機台不應出現在可授權清單
    }
}
