<?php

namespace Tests\Feature\Admin;

use App\Models\System\Company;
use App\Models\System\Role;
use App\Models\System\User;
use Spatie\Permission\Models\Permission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * 帳號樹狀層級隔離（第二+三批）行為測試：
 * - 主帳號唯一性（第一個帳號自動成為主帳號）
 * - parent_id / level 歸屬
 * - 子帳號深度上限（level 2 不可再建）
 * - visibleTo 可見範圍隔離（只看自己直建的）
 * - canManageAccount 越權防護（不能管旁線）
 */
class AccountHierarchyTest extends TestCase
{
    use RefreshDatabase;

    protected User $sysAdmin;
    protected Company $company;
    protected Role $tenantRole;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['menu.permissions.accounts', 'menu.data-config.sub-accounts'] as $p) {
            Permission::create(['name' => $p, 'guard_name' => 'web']);
        }

        $superAdmin = Role::create(['name' => 'super-admin', 'company_id' => null, 'is_system' => true, 'guard_name' => 'web']);
        $superAdmin->syncPermissions(['menu.permissions.accounts', 'menu.data-config.sub-accounts']);

        Role::create(['name' => '客戶管理員角色模板', 'company_id' => null, 'is_system' => true, 'guard_name' => 'web'])
            ->syncPermissions(['menu.permissions.accounts', 'menu.data-config.sub-accounts']);

        $this->company = Company::create(['name' => 'Test Co', 'code' => 'TEST']);

        $this->sysAdmin = User::factory()->create(['company_id' => null]);
        $this->sysAdmin->assignRole($superAdmin);

        // 供租戶帳號使用、帶有子帳號管理權限的公司層級角色
        $this->tenantRole = Role::create(['name' => 'tenant-mgr', 'company_id' => $this->company->id, 'is_system' => false, 'guard_name' => 'web']);
        $this->tenantRole->syncPermissions(['menu.permissions.accounts', 'menu.data-config.sub-accounts']);
    }

    /** 建立一個租戶帳號（直接寫入層級欄位）並賦予可建子帳號的角色 */
    private function makeTenantUser(array $attrs): User
    {
        $user = User::factory()->create(array_merge(['company_id' => $this->company->id], $attrs));
        $user->assignRole($this->tenantRole);
        return $user;
    }

    public function test_first_company_account_becomes_main(): void
    {
        $this->actingAs($this->sysAdmin)->post(route('admin.permission.accounts.store'), [
            'name' => 'First', 'username' => 'first', 'email' => 'first@test.co', 'password' => 'password123',
            'role' => '客戶管理員角色模板', 'status' => 1, 'company_id' => $this->company->id,
        ])->assertSessionHas('success');

        $u = User::where('username', 'first')->first();
        $this->assertTrue($u->is_admin);
        $this->assertSame(0, $u->level);
        $this->assertNull($u->parent_id);
    }

    public function test_second_company_account_is_not_main_and_is_child(): void
    {
        $main = $this->makeTenantUser(['is_admin' => true, 'level' => 0, 'parent_id' => null]);

        $this->actingAs($this->sysAdmin)->post(route('admin.permission.accounts.store'), [
            'name' => 'Second', 'username' => 'second', 'email' => 'second@test.co', 'password' => 'password123',
            'role' => 'tenant-mgr', 'status' => 1, 'company_id' => $this->company->id,
        ])->assertSessionHas('success');

        $u = User::where('username', 'second')->first();
        $this->assertFalse($u->is_admin);
        $this->assertSame(1, $u->level);
        $this->assertSame($main->id, $u->parent_id);
    }

    public function test_tenant_admin_creates_sub_account_sets_parent_and_level(): void
    {
        $main = $this->makeTenantUser(['is_admin' => true, 'level' => 0, 'parent_id' => null]);

        $this->actingAs($main)->post(route('admin.data-config.sub-accounts.store'), [
            'name' => 'Sub', 'username' => 'sub', 'email' => 'sub@test.co', 'password' => 'password123',
            'role' => 'tenant-mgr', 'status' => 1,
        ])->assertSessionHas('success');

        $u = User::where('username', 'sub')->first();
        $this->assertFalse($u->is_admin);
        $this->assertSame(1, $u->level);
        $this->assertSame($main->id, $u->parent_id);
        $this->assertSame($this->company->id, $u->company_id);
    }

    public function test_level_two_sub_account_cannot_create_further(): void
    {
        $deep = $this->makeTenantUser(['is_admin' => false, 'level' => 2, 'parent_id' => null]);

        $this->actingAs($deep)->post(route('admin.data-config.sub-accounts.store'), [
            'name' => 'TooDeep', 'username' => 'toodeep', 'email' => 'toodeep@test.co', 'password' => 'password123',
            'role' => 'tenant-mgr', 'status' => 1,
        ])->assertSessionHas('error');

        $this->assertDatabaseMissing('users', ['username' => 'toodeep']);
    }

    public function test_visible_to_isolates_siblings_and_self(): void
    {
        $main = $this->makeTenantUser(['is_admin' => true, 'level' => 0, 'parent_id' => null]);
        $a = $this->makeTenantUser(['is_admin' => false, 'level' => 1, 'parent_id' => $main->id]);
        $b = $this->makeTenantUser(['is_admin' => false, 'level' => 1, 'parent_id' => $main->id]);
        $c = $this->makeTenantUser(['is_admin' => false, 'level' => 2, 'parent_id' => $a->id]);

        // 子帳號 A 只看得到自己直建的 C，看不到自己、旁線 B、主帳號
        $visibleToA = User::visibleTo($a)->pluck('id')->all();
        $this->assertSame([$c->id], $visibleToA);

        // 主帳號看得到同公司全部
        $visibleToMain = User::visibleTo($main)->pluck('id')->sort()->values()->all();
        $this->assertEquals(collect([$main->id, $a->id, $b->id, $c->id])->sort()->values()->all(), $visibleToMain);
    }

    public function test_sub_account_cannot_manage_sibling(): void
    {
        $main = $this->makeTenantUser(['is_admin' => true, 'level' => 0, 'parent_id' => null]);
        $a = $this->makeTenantUser(['is_admin' => false, 'level' => 1, 'parent_id' => $main->id]);
        $b = $this->makeTenantUser(['is_admin' => false, 'level' => 1, 'parent_id' => $main->id]);

        // A 嘗試刪除旁線 B → 被擋
        $this->actingAs($a)->delete(route('admin.data-config.sub-accounts.destroy', $b->id))
            ->assertSessionHas('error');
        $this->assertDatabaseHas('users', ['id' => $b->id, 'deleted_at' => null]);

        // A 嘗試停用旁線 B → 被擋
        $this->actingAs($a)->patch(route('admin.data-config.sub-accounts.status.toggle', $b->id))
            ->assertSessionHas('error');
    }

    public function test_main_account_can_manage_company_sub_account(): void
    {
        $main = $this->makeTenantUser(['is_admin' => true, 'level' => 0, 'parent_id' => null]);
        $a = $this->makeTenantUser(['is_admin' => false, 'level' => 1, 'parent_id' => $main->id]);

        // 主帳號可停用同公司子帳號
        $this->actingAs($main)->patch(route('admin.data-config.sub-accounts.status.toggle', $a->id))
            ->assertSessionHas('success');
    }
}
