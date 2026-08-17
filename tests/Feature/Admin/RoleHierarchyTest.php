<?php

namespace Tests\Feature\Admin;

use App\Models\System\Company;
use App\Models\System\Role;
use App\Models\System\User;
use Spatie\Permission\Models\Permission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * 第五批：角色層級隔離測試。
 * - 子帳號只看得到 / 只能管理 自己建立的角色（看不到上層/旁線建的）。
 * - 主帳號看得到同公司全部角色。
 * - storeRole 寫入 created_by；update/destroy 受 canBeManagedBy 守衛。
 */
class RoleHierarchyTest extends TestCase
{
    use RefreshDatabase;

    protected Company $company;
    protected User $main;
    protected User $subA;
    protected User $subB;

    protected function setUp(): void
    {
        parent::setUp();

        Permission::create(['name' => 'menu.data-config.sub-accounts', 'guard_name' => 'web']);

        $this->company = Company::create(['name' => 'Test Co', 'code' => 'TEST']);

        $role = Role::create(['name' => 'tenant-mgr', 'company_id' => $this->company->id, 'is_system' => false, 'guard_name' => 'web']);
        $role->syncPermissions(['menu.data-config.sub-accounts']);

        $this->main = User::factory()->create(['company_id' => $this->company->id, 'is_admin' => true, 'level' => 0, 'parent_id' => null]);
        $this->main->assignRole($role);

        $this->subA = User::factory()->create(['company_id' => $this->company->id, 'is_admin' => false, 'level' => 1, 'parent_id' => $this->main->id]);
        $this->subA->assignRole($role);

        $this->subB = User::factory()->create(['company_id' => $this->company->id, 'is_admin' => false, 'level' => 1, 'parent_id' => $this->main->id]);
        $this->subB->assignRole($role);
    }

    private function makeRole(string $name, User $creator): Role
    {
        return Role::create([
            'name' => $name, 'company_id' => $this->company->id, 'is_system' => false,
            'guard_name' => 'web', 'created_by' => $creator->id,
        ]);
    }

    public function test_role_visible_to_isolates_by_creator(): void
    {
        $roleMain = $this->makeRole('role-main', $this->main);
        $roleA = $this->makeRole('role-a', $this->subA);
        $roleB = $this->makeRole('role-b', $this->subB);

        // 子帳號 A 只看得到自己建立的角色
        $this->assertSame([$roleA->id], Role::visibleTo($this->subA)->pluck('id')->all());

        // 主帳號看得到同公司全部角色（含 tenant-mgr 與三個新建的）
        $mainVisible = Role::visibleTo($this->main)->pluck('id')->all();
        foreach ([$roleMain->id, $roleA->id, $roleB->id] as $id) {
            $this->assertContains($id, $mainVisible);
        }
    }

    public function test_can_be_managed_by_creator_only_for_sub_account(): void
    {
        $roleMain = $this->makeRole('role-main', $this->main);
        $roleA = $this->makeRole('role-a', $this->subA);

        $this->assertTrue($roleA->canBeManagedBy($this->subA));      // 自己建的
        $this->assertFalse($roleMain->canBeManagedBy($this->subA));  // 上層建的
        $this->assertTrue($roleMain->canBeManagedBy($this->main));   // 主帳號管全公司
        $this->assertTrue($roleA->canBeManagedBy($this->main));      // 主帳號管全公司
    }

    public function test_store_role_sets_created_by(): void
    {
        $this->actingAs($this->subA)->post(route('admin.data-config.sub-account-roles.store'), [
            'name' => 'a-created-role',
            'permissions' => ['menu.data-config.sub-accounts'],
        ])->assertSessionHas('success');

        $role = Role::where('name', 'a-created-role')->where('company_id', $this->company->id)->first();
        $this->assertNotNull($role);
        $this->assertSame($this->subA->id, $role->created_by);
    }

    public function test_sub_account_cannot_update_others_role(): void
    {
        $roleB = $this->makeRole('role-b', $this->subB);

        // A 嘗試改 B 建的角色 → 被擋
        $this->actingAs($this->subA)->put(route('admin.data-config.sub-account-roles.update', $roleB->id), [
            'name' => 'hijacked',
            'permissions' => [],
        ])->assertSessionHas('error');

        $this->assertDatabaseHas('roles', ['id' => $roleB->id, 'name' => 'role-b']);
    }

    public function test_sub_account_cannot_delete_others_role(): void
    {
        $roleMain = $this->makeRole('role-main', $this->main);

        $this->actingAs($this->subA)->delete(route('admin.data-config.sub-account-roles.destroy', $roleMain->id))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('roles', ['id' => $roleMain->id]);
    }
}
