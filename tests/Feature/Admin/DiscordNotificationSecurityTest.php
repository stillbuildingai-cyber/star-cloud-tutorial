<?php

namespace Tests\Feature\Admin;

use App\Models\System\Company;
use App\Models\System\User;
use App\Models\System\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DiscordNotificationSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected $company;
    protected $superAdmin;
    protected $tenantAdmin;
    protected $regularUser;

    protected function setUp(): void
    {
        parent::setUp();

        // 1. 執行 RoleSeeder 以初始化權限
        $this->artisan('db:seed', ['--class' => 'RoleSeeder']);

        // 2. 建立測試公司
        $this->company = Company::create([
            'name' => 'Test Company',
            'code' => 'TEST',
            'status' => 1,
        ]);

        // 3. 建立 Super Admin 帳號
        $this->superAdmin = User::create([
            'name' => 'Super Admin User',
            'username' => 'superadmin_test',
            'email' => 'superadmin@example.com',
            'password' => bcrypt('password'),
            'company_id' => null,
        ]);
        $this->superAdmin->assignRole('super-admin');

        // 4. 建立租戶管理員帳號
        $this->tenantAdmin = User::create([
            'name' => 'Tenant Admin User',
            'username' => 'tenantadmin_test',
            'email' => 'tenantadmin@example.com',
            'password' => bcrypt('password'),
            'company_id' => $this->company->id,
        ]);

        $tenantAdminTemplate = Role::where('name', '客戶管理員角色模板')->first();
        $tenantRole = Role::create([
            'name' => '管理員',
            'company_id' => $this->company->id,
            'guard_name' => 'web',
            'is_system' => false,
        ]);
        
        // 確保 tenantAdminTemplate 包含我們新加的 menu.basic.discord-notifications
        $tenantRole->syncPermissions($tenantAdminTemplate->permissions);
        // 手動指派以測試有權限時的存取行為 (因現已改為比照機台設定預設不配給範本)
        $tenantRole->givePermissionTo('menu.basic.discord-notifications');
        $this->tenantAdmin->assignRole($tenantRole);

        // 5. 建立普通無此權限之帳號
        $this->regularUser = User::create([
            'name' => 'Regular User',
            'username' => 'regular_user',
            'email' => 'regular@example.com',
            'password' => bcrypt('password'),
            'company_id' => $this->company->id,
        ]);
        
        $emptyRole = Role::create([
            'name' => '普通人員',
            'company_id' => $this->company->id,
            'guard_name' => 'web',
            'is_system' => false,
        ]);
        // 給予基本會員權限而非 Discord 設定權限
        $emptyRole->syncPermissions(['menu.members']);
        $this->regularUser->assignRole($emptyRole);
    }

    /**
     * 測試超級管理員可以訪問 Discord Webhook 設定
     */
    public function test_super_admin_can_access_discord_notifications()
    {
        $this->actingAs($this->superAdmin);

        $response = $this->get(route('admin.basic-settings.discord-notifications.index'));
        $response->assertStatus(200);

        $response = $this->post(route('admin.basic-settings.discord-notifications.test'), [
            'webhook_url' => 'https://discord.com/api/webhooks/test',
        ]);
        // 發送測試可能會失敗 (如 Mock Discord 伺服器)，但應通過 403 路由認證
        $this->assertNotEquals(403, $response->status());
    }

    /**
     * 測試租戶管理員可以訪問 Discord Webhook 設定
     */
    public function test_tenant_admin_can_access_discord_notifications()
    {
        $this->actingAs($this->tenantAdmin);

        $response = $this->get(route('admin.basic-settings.discord-notifications.index'));
        $response->assertStatus(200);
    }

    /**
     * 測試無權限之普通用戶會被阻擋且返回 403 錯誤
     */
    public function test_regular_user_cannot_access_discord_notifications()
    {
        $this->actingAs($this->regularUser);

        $response = $this->get(route('admin.basic-settings.discord-notifications.index'));
        $response->assertStatus(403);

        $response = $this->put(route('admin.basic-settings.discord-notifications.update-company', ['id' => $this->company->id]), [
            'discord_webhook_url' => 'https://discord.com/api/webhooks/test',
            'discord_notify_enabled' => true,
            'discord_notify_locale' => 'zh_TW'
        ]);
        $response->assertStatus(403);
    }
}
