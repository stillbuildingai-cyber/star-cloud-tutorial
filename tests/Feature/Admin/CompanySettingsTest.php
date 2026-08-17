<?php

namespace Tests\Feature\Admin;

use Tests\TestCase;
use App\Models\System\Company;
use App\Models\System\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class CompanySettingsTest extends TestCase
{
    use RefreshDatabase;

    protected $admin;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Setup a system admin with permissions
        $this->admin = User::factory()->create(['company_id' => null]);
        $this->admin->givePermissionTo(Permission::create(['name' => 'menu.permissions.companies']));
    }

    public function test_can_update_company_settings()
    {
        $company = Company::factory()->create(['original_type' => 'lease', 'current_type' => 'lease']);
        
        $settings = [
            'enable_material_code' => true,
            'enable_points' => true,
        ];

        $response = $this->actingAs($this->admin)
            ->put(route('admin.permission.companies.update', $company->id), [
                'name' => 'Updated Name',
                'code' => $company->code,
                'status' => 1,
                'current_type' => 'lease',
                'settings' => $settings,
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('companies', [
            'id' => $company->id,
            'name' => 'Updated Name',
        ]);
        
        $updatedCompany = Company::find($company->id);
        $this->assertEquals(array_merge($updatedCompany->settings ?? [], $settings), $updatedCompany->settings);
    }

    public function test_can_create_company_with_settings()
    {
        $settings = [
            'enable_material_code' => false,
            'enable_points' => true,
        ];

        $response = $this->actingAs($this->admin)
            ->post(route('admin.permission.companies.store'), [
                'name' => 'New Company',
                'code' => 'NEW001',
                'original_type' => 'lease',
                'status' => 1,
                'settings' => $settings,
            ]);

        $response->assertRedirect();
        $company = Company::where('code', 'NEW001')->first();
        $this->assertNotNull($company);
        $this->assertEquals(array_merge($company->settings ?? [], $settings), $company->settings);
    }

    public function test_can_reuse_code_after_deletion()
    {
        $code = 'REUSE001';
        $company = Company::factory()->create(['code' => $code]);
        
        // Delete the company
        $response = $this->actingAs($this->admin)
            ->delete(route('admin.permission.companies.destroy', $company->id));
            
        $response->assertRedirect();
        $this->assertSoftDeleted('companies', ['id' => $company->id]);
        
        // The original code should be freed (renamed in the DB)
        $this->assertDatabaseMissing('companies', [
            'id' => $company->id,
            'code' => $code,
            'deleted_at' => null
        ]);
        
        // Should be able to create a new company with the same code
        $response = $this->actingAs($this->admin)
            ->post(route('admin.permission.companies.store'), [
                'name' => 'Brand New Company',
                'code' => $code,
                'original_type' => 'lease',
                'status' => 1,
            ]);
            
        $response->assertRedirect();
        $this->assertDatabaseHas('companies', [
            'name' => 'Brand New Company',
            'code' => $code,
            'deleted_at' => null
        ]);
    }

    public function test_can_update_branding_settings()
    {
        $company = Company::factory()->create([
            'settings' => ['enable_custom_branding' => true]
        ]);

        $response = $this->actingAs($this->admin)
            ->post(route('admin.permission.companies.branding.update', $company->id), [
                'login_title' => 'Custom Welcome Title',
                'login_main_title' => 'Custom Left Main Title',
                'login_sub_title' => 'Custom Left Subtitle',
            ]);

        $response->assertRedirect(route('admin.permission.companies.branding.edit', $company->id));
        
        $company->refresh();
        $this->assertEquals('Custom Welcome Title', $company->settings['login_title']);
        $this->assertEquals('Custom Left Main Title', $company->settings['login_main_title']);
        $this->assertEquals('Custom Left Subtitle', $company->settings['login_sub_title']);
    }

    public function test_cannot_update_branding_if_not_enabled()
    {
        $company = Company::factory()->create([
            'settings' => ['enable_custom_branding' => false]
        ]);

        $response = $this->actingAs($this->admin)
            ->post(route('admin.permission.companies.branding.update', $company->id), [
                'login_title' => 'Custom Welcome Title',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('error');
        
        $company->refresh();
        $this->assertArrayNotHasKey('login_title', $company->settings ?? []);
    }

    public function test_companies_index_page_can_be_rendered()
    {
        $response = $this->actingAs($this->admin)
            ->get(route('admin.permission.companies.index'));

        $response->assertOk();
        $response->assertViewIs('admin.companies.index');
    }

    public function test_can_upload_branding_logo()
    {
        \Illuminate\Support\Facades\Storage::fake('public');

        $company = Company::factory()->create([
            'settings' => ['enable_custom_branding' => true]
        ]);

        $file = \Illuminate\Http\UploadedFile::fake()->image('logo.png');

        $response = $this->actingAs($this->admin)
            ->post(route('admin.permission.companies.branding.update', $company->id), [
                'logo_file' => $file,
                'login_title' => 'Custom Title',
            ]);

        $response->assertRedirect();
        
        $company->refresh();
        $this->assertNotNull($company->settings['logo_path'] ?? null);
        \Illuminate\Support\Facades\Storage::disk('public')->assertExists($company->settings['logo_path']);
    }

    public function test_branding_validation_fails_redirects_with_errors_and_session()
    {
        $company = Company::factory()->create([
            'settings' => ['enable_custom_branding' => true]
        ]);

        // 上傳大於 10MB 的圖片來觸發驗證失敗 (10241 KB)
        $file = \Illuminate\Http\UploadedFile::fake()->image('logo.png')->size(10241);

        $response = $this->actingAs($this->admin)
            ->post(route('admin.permission.companies.branding.update', $company->id), [
                'logo_file' => $file,
                'login_title' => str_repeat('a', 101), // 超過 100 字元限制
            ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors(['logo_file', 'login_title']);
    }

    public function test_can_upload_all_branding_images()
    {
        \Illuminate\Support\Facades\Storage::fake('public');

        $company = Company::factory()->create([
            'settings' => ['enable_custom_branding' => true]
        ]);

        $logoFile = \Illuminate\Http\UploadedFile::fake()->image('logo.png');
        $bgFile = \Illuminate\Http\UploadedFile::fake()->image('bg.jpg');
        $cardBgFile = \Illuminate\Http\UploadedFile::fake()->image('card_bg.png');

        $response = $this->actingAs($this->admin)
            ->post(route('admin.permission.companies.branding.update', $company->id), [
                'logo_file' => $logoFile,
                'login_bg_file' => $bgFile,
                'login_card_bg_file' => $cardBgFile,
                'login_title' => 'Branding Title',
            ]);

        $response->assertRedirect();

        $company->refresh();
        $this->assertNotNull($company->settings['logo_path'] ?? null);
        $this->assertNotNull($company->settings['login_bg_path'] ?? null);
        $this->assertNotNull($company->settings['login_card_bg_path'] ?? null);

        \Illuminate\Support\Facades\Storage::disk('public')->assertExists($company->settings['logo_path']);
        \Illuminate\Support\Facades\Storage::disk('public')->assertExists($company->settings['login_bg_path']);
        \Illuminate\Support\Facades\Storage::disk('public')->assertExists($company->settings['login_card_bg_path']);
    }

    public function test_can_clear_all_branding_images()
    {
        \Illuminate\Support\Facades\Storage::fake('public');

        // 先把檔案上傳並建立
        $logoPath = \Illuminate\Support\Facades\Storage::disk('public')->putFile('company_logos', \Illuminate\Http\UploadedFile::fake()->image('logo.png'));
        $bgPath = \Illuminate\Support\Facades\Storage::disk('public')->putFile('company_backgrounds', \Illuminate\Http\UploadedFile::fake()->image('bg.jpg'));
        $cardBgPath = \Illuminate\Support\Facades\Storage::disk('public')->putFile('company_backgrounds', \Illuminate\Http\UploadedFile::fake()->image('card_bg.png'));

        $company = Company::factory()->create([
            'settings' => [
                'enable_custom_branding' => true,
                'logo_path' => $logoPath,
                'login_bg_path' => $bgPath,
                'login_card_bg_path' => $cardBgPath,
                'login_title' => 'Old Title'
            ]
        ]);

        // 確保檔案原本存在
        \Illuminate\Support\Facades\Storage::disk('public')->assertExists($logoPath);
        \Illuminate\Support\Facades\Storage::disk('public')->assertExists($bgPath);
        \Illuminate\Support\Facades\Storage::disk('public')->assertExists($cardBgPath);

        // 呼叫清除
        $response = $this->actingAs($this->admin)
            ->post(route('admin.permission.companies.branding.update', $company->id), [
                'clear_logo' => '1',
                'clear_login_bg' => '1',
                'clear_login_card_bg' => '1',
                'login_title' => 'New Title'
            ]);

        $response->assertRedirect();

        $company->refresh();
        $this->assertNull($company->settings['logo_path'] ?? null);
        $this->assertNull($company->settings['login_bg_path'] ?? null);
        $this->assertNull($company->settings['login_card_bg_path'] ?? null);

        // 檔案應該已經被物理刪除
        \Illuminate\Support\Facades\Storage::disk('public')->assertMissing($logoPath);
        \Illuminate\Support\Facades\Storage::disk('public')->assertMissing($bgPath);
        \Illuminate\Support\Facades\Storage::disk('public')->assertMissing($cardBgPath);
    }

    public function test_branding_validation_fails_for_all_files()
    {
        $company = Company::factory()->create([
            'settings' => ['enable_custom_branding' => true]
        ]);

        // 上傳大於 10MB 的大背景圖與卡片背景圖 (10241 KB)
        $bgFile = \Illuminate\Http\UploadedFile::fake()->image('bg.jpg')->size(10241);
        $cardBgFile = \Illuminate\Http\UploadedFile::fake()->image('card_bg.png')->size(10241);

        $response = $this->actingAs($this->admin)
            ->post(route('admin.permission.companies.branding.update', $company->id), [
                'login_bg_file' => $bgFile,
                'login_card_bg_file' => $cardBgFile,
            ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors(['login_bg_file', 'login_card_bg_file']);
    }

    public function test_tenant_index_redirects_correctly_based_on_auth_status()
    {
        $company = Company::factory()->create([
            'code' => 'test_redirect_comp',
            'status' => 1,
        ]);

        // 未登入狀態：重導向至登入頁面
        $response = $this->get('/c/test_redirect_comp');
        $response->assertRedirect(route('tenant.login', 'test_redirect_comp'));

        // 已登入狀態：重導向至後台 dashboard
        $response = $this->actingAs($this->admin)->get('/c/test_redirect_comp');
        $response->assertRedirect(route('admin.dashboard'));
    }

    public function test_tenant_preview_page_can_be_rendered()
    {
        $company = Company::factory()->create([
            'code' => 'test_preview_comp',
            'status' => 1,
            'settings' => ['enable_custom_branding' => true]
        ]);

        // 不論是否登入，preview 頁面都應該能正常渲染
        $response = $this->actingAs($this->admin)->get('/c/test_preview_comp/preview');
        $response->assertOk();
        $response->assertViewIs('auth.login');
        $response->assertViewHas('company');
        $response->assertViewHas('isPreview', true);
    }

    public function test_tenant_preview_page_redirects_if_not_enabled()
    {
        $company = Company::factory()->create([
            'code' => 'test_disabled_preview',
            'status' => 1,
            'settings' => ['enable_custom_branding' => false]
        ]);

        // 未開啟客製化品牌功能，導回一般登入
        $response = $this->actingAs($this->admin)->get('/c/test_disabled_preview/preview');
        $response->assertRedirect(route('login'));
    }

    public function test_branding_edit_page_can_be_rendered()
    {
        $company = Company::factory()->create([
            'settings' => ['enable_custom_branding' => true]
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.permission.companies.branding.edit', $company->id));
        $response->assertOk();
        $response->assertViewIs('admin.companies.branding');
        $response->assertViewHas('company');
    }

    public function test_branding_edit_page_redirects_if_not_enabled()
    {
        $company = Company::factory()->create([
            'settings' => ['enable_custom_branding' => false]
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.permission.companies.branding.edit', $company->id));
        $response->assertRedirect(route('admin.permission.companies.index'));
        $response->assertSessionHas('error');
    }

    public function test_tenant_can_login_successfully()
    {
        $company = Company::factory()->create([
            'code' => 'tenant_a',
            'status' => 1,
            'settings' => ['enable_custom_branding' => true]
        ]);

        $user = User::factory()->create([
            'company_id' => $company->id,
            'username' => 'tenant_user_a',
            'password' => bcrypt('password123'),
            'status' => 1
        ]);

        $response = $this->post('/c/tenant_a/login', [
            'username' => 'tenant_user_a',
            'password' => 'password123',
        ]);

        $response->assertRedirect(route('admin.dashboard'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_tenant_cannot_login_if_user_belongs_to_another_company()
    {
        $companyA = Company::factory()->create([
            'code' => 'tenant_a',
            'status' => 1,
            'settings' => ['enable_custom_branding' => true]
        ]);

        $companyB = Company::factory()->create([
            'code' => 'tenant_b',
            'status' => 1,
            'settings' => ['enable_custom_branding' => true]
        ]);

        // User B belongs to Company B
        $userB = User::factory()->create([
            'company_id' => $companyB->id,
            'username' => 'user_b',
            'password' => bcrypt('password123'),
            'status' => 1
        ]);

        // Attempting to login User B at Company A's portal
        $response = $this->from('/c/tenant_a/login')->post('/c/tenant_a/login', [
            'username' => 'user_b',
            'password' => 'password123',
        ]);

        $response->assertRedirect('/c/tenant_a/login');
        $response->assertSessionHasErrors(['username']);
        $this->assertGuest();
    }

    public function test_tenant_cannot_login_if_account_disabled()
    {
        $company = Company::factory()->create([
            'code' => 'tenant_a',
            'status' => 1,
            'settings' => ['enable_custom_branding' => true]
        ]);

        // User is disabled (status = 0)
        $user = User::factory()->create([
            'company_id' => $company->id,
            'username' => 'disabled_user',
            'password' => bcrypt('password123'),
            'status' => 0
        ]);

        $response = $this->from('/c/tenant_a/login')->post('/c/tenant_a/login', [
            'username' => 'disabled_user',
            'password' => 'password123',
        ]);

        $response->assertRedirect('/c/tenant_a/login');
        $response->assertSessionHasErrors(['username']);
        $this->assertGuest();
    }

    public function test_tenant_cannot_login_with_incorrect_password()
    {
        $company = Company::factory()->create([
            'code' => 'tenant_a',
            'status' => 1,
            'settings' => ['enable_custom_branding' => true]
        ]);

        $user = User::factory()->create([
            'company_id' => $company->id,
            'username' => 'tenant_user',
            'password' => bcrypt('password123'),
            'status' => 1
        ]);

        $response = $this->from('/c/tenant_a/login')->post('/c/tenant_a/login', [
            'username' => 'tenant_user',
            'password' => 'wrongpassword',
        ]);

        $response->assertRedirect('/c/tenant_a/login');
        $response->assertSessionHasErrors(['password']);
        $this->assertGuest();
    }
}

