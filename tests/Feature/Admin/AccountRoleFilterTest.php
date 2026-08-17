<?php

namespace Tests\Feature\Admin;

use App\Models\System\Company;
use Spatie\Permission\Models\Permission;
use App\Models\System\Role;
use App\Models\System\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccountRoleFilterTest extends TestCase
{
    use RefreshDatabase;

    protected $admin;
    protected $company;
    protected $templateRole;
    protected $superAdminRole;

    protected function setUp(): void
    {
        parent::setUp();

        // 建立測試權限
        Permission::create(['name' => 'menu.permissions.accounts', 'guard_name' => 'web']);

        // 建立測試角色
        $this->superAdminRole = Role::create([
            'name' => 'super-admin',
            'company_id' => null,
            'is_system' => true,
            'guard_name' => 'web'
        ]);
        $this->superAdminRole->givePermissionTo('menu.permissions.accounts');

        $this->templateRole = Role::create([
            'name' => '客戶管理員角色模板',
            'company_id' => null,
            'is_system' => true,
            'guard_name' => 'web'
        ]);

        $this->company = Company::create([
            'name' => 'Test Company',
            'code' => 'TEST'
        ]);

        // 建立系統管理員
        $this->admin = User::factory()->create(['company_id' => null]);
        $this->admin->assignRole($this->superAdminRole);
    }

    /**
     * 測試租戶帳號不能被指派 super-admin 角色
     */
    public function test_super_admin_cannot_be_assigned_to_tenant_account()
    {
        $this->withoutExceptionHandling();
        $this->actingAs($this->admin);

        $response = $this->post(route('admin.permission.accounts.store'), [
            'name' => 'Tenant User',
            'username' => 'tenantuser',
            'email' => 'tenant@example.com',
            'password' => 'password123',
            'role' => 'super-admin',
            'status' => 1,
            'company_id' => $this->company->id,
        ]);

        $response->assertSessionHas('error');
        $this->assertDatabaseMissing('users', ['username' => 'tenantuser']);
    }

    /**
     * 測試角色範本可以被指派給租戶，並轉換為「管理員」
     */
    public function test_template_role_can_be_assigned_and_cloned_for_tenant()
    {
        $this->actingAs($this->admin);

        $response = $this->post(route('admin.permission.accounts.store'), [
            'name' => 'Tenant User',
            'username' => 'tenantuser',
            'email' => 'tenant@example.com',
            'password' => 'password123',
            'role' => '客戶管理員角色模板',
            'status' => 1,
            'company_id' => $this->company->id,
        ]);

        $response->assertSessionHas('success');
        $this->assertDatabaseHas('users', ['username' => 'tenantuser']);
        
        $user = User::where('username', 'tenantuser')->first();
        // 根據控制器邏輯，非 super-admin 的全域角色會被克隆為該公司的「管理員」
        $this->assertTrue($user->hasRole('管理員'));
        $this->assertEquals($this->company->id, $user->company_id);
    }
}
