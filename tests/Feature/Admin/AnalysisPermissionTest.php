<?php

namespace Tests\Feature\Admin;

use App\Models\System\Company;
use App\Models\System\User;
use App\Models\System\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AnalysisPermissionTest extends TestCase
{
    use RefreshDatabase;

    protected $company;
    protected $superAdmin;
    protected $tenantAdmin;

    protected function setUp(): void
    {
        parent::setUp();

        // 1. 運行 RoleSeeder 以確保所有權限與角色都被播種
        $this->artisan('db:seed', ['--class' => 'RoleSeeder']);

        // 2. 建立測試公司
        $this->company = Company::create([
            'name' => 'Test Company',
            'code' => 'TEST',
            'status' => 1,
        ]);

        // 註冊 SQLite 相容的 DATE_FORMAT 函數
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

        // 3. 建立 Super Admin 帳號
        $this->superAdmin = User::create([
            'name' => 'Super Admin User',
            'username' => 'superadmin_test',
            'email' => 'superadmin@example.com',
            'password' => bcrypt('password'),
            'company_id' => null,
        ]);
        $this->superAdmin->assignRole('super-admin');

        // 4. 建立租戶管理員帳號，並克隆其「管理員」角色
        // 我們的 RoleSeeder 會將 'menu.analysis.product-reports' 賦予 tenantAdmin 角色範本
        // 租戶的「管理員」將繼承該範本的權限。
        $this->tenantAdmin = User::create([
            'name' => 'Tenant Admin User',
            'username' => 'tenantadmin_test',
            'email' => 'tenantadmin@example.com',
            'password' => bcrypt('password'),
            'company_id' => $this->company->id,
        ]);

        // 克隆 RoleSeeder 建立的 tenantAdmin 角色權限至該租戶下命名為「管理員」
        $tenantAdminTemplate = Role::where('name', '客戶管理員角色模板')->first();
        $this->assertNotNull($tenantAdminTemplate);

        $tenantRole = Role::create([
            'name' => '管理員',
            'company_id' => $this->company->id,
            'guard_name' => 'web',
            'is_system' => false,
        ]);

        // 同步權限
        $tenantRole->syncPermissions($tenantAdminTemplate->permissions);
        $this->tenantAdmin->assignRole($tenantRole);
    }

    /**
     * 測試超級管理員可以訪問所有的分析管理子路由
     */
    public function test_super_admin_can_access_all_analysis_routes()
    {
        $this->actingAs($this->superAdmin);

        $routes = [
            'admin.analysis.change-stock',
            'admin.analysis.machine-reports',
            'admin.analysis.product-reports',
            'admin.analysis.survey-analysis',
        ];

        foreach ($routes as $route) {
            $response = $this->get(route($route));
            // 應成功訪問 (由於 Controller 實作可能回傳檢視或 200)
            $response->assertStatus(200);
        }
    }

    /**
     * 測試租戶管理員只能訪問商品報表，其他 3 個路由會回傳 403 越權錯誤
     */
    public function test_tenant_admin_can_access_allowed_reports()
    {
        $this->actingAs($this->tenantAdmin);

        // 1. 可以正常訪問商品報表與機台報表
        $response = $this->get(route('admin.analysis.product-reports'));
        $response->assertStatus(200);

        $response = $this->get(route('admin.analysis.machine-reports'));
        $response->assertStatus(200);

        // 2. 其他 2 個路由皆應回傳 403 Forbidden 越權錯誤
        $forbiddenRoutes = [
            'admin.analysis.change-stock',
            'admin.analysis.survey-analysis',
        ];

        foreach ($forbiddenRoutes as $route) {
            $response = $this->get(route($route));
            $response->assertStatus(403);
        }
    }
}
