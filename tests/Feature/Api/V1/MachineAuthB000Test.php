<?php

namespace Tests\Feature\Api\V1;

use App\Models\Machine\Machine;
use App\Models\System\Company;
use App\Models\System\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class MachineAuthB000Test extends TestCase
{
    use RefreshDatabase;

    private Company $company;
    private Machine $machine;
    private string $apiToken;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::create([
            'name' => 'Test Company',
        ]);

        $this->apiToken = 'test-machine-api-token-123456';

        $this->machine = Machine::factory()->create([
            'company_id' => $this->company->id,
            'api_token' => $this->apiToken,
        ]);
    }

    private function loginAs(string $account, string $password = 'password')
    {
        return $this->withHeader('Authorization', "Bearer {$this->apiToken}")
            ->postJson('/api/v1/app/admin/login/B000', [
                'Su_Account' => $account,
                'Su_Password' => $password,
            ]);
    }

    /**
     * 系統登入者 (company_id = null) → identity: system
     */
    public function test_system_admin_login_returns_identity_system(): void
    {
        User::factory()->create([
            'company_id' => null,
            'username' => 'sysadmin',
            'password' => Hash::make('password'),
        ]);

        $this->loginAs('sysadmin')
            ->assertStatus(200)
            ->assertJson([
                'success' => true,
                'code' => 200,
                'identity' => 'system',
            ]);
    }

    /**
     * 公司帳號 (租戶管理員，is_admin = true，同 company_id 機台) → identity: company
     */
    public function test_company_admin_login_returns_identity_company(): void
    {
        User::factory()->create([
            'company_id' => $this->company->id,
            'username' => 'companyadmin',
            'is_admin' => true,
            'password' => Hash::make('password'),
        ]);

        $this->loginAs('companyadmin')
            ->assertStatus(200)
            ->assertJson([
                'success' => true,
                'identity' => 'company',
            ]);
    }

    /**
     * 一般人員 (is_admin = false，已加入 machine_user 授權) → identity: staff
     */
    public function test_staff_login_returns_identity_staff(): void
    {
        $staff = User::factory()->create([
            'company_id' => $this->company->id,
            'username' => 'staff',
            'is_admin' => false,
            'password' => Hash::make('password'),
        ]);
        $staff->machines()->attach($this->machine->id);

        $this->loginAs('staff')
            ->assertStatus(200)
            ->assertJson([
                'success' => true,
                'identity' => 'staff',
            ]);
    }

    /**
     * 帳密錯誤 → 401，回應不含 identity
     */
    public function test_invalid_credentials_returns_401_without_identity(): void
    {
        User::factory()->create([
            'company_id' => null,
            'username' => 'sysadmin',
            'password' => Hash::make('password'),
        ]);

        $response = $this->loginAs('sysadmin', 'wrong-password');

        $response->assertStatus(401)
            ->assertJson(['success' => false, 'code' => 401]);
        $this->assertArrayNotHasKey('identity', $response->json());
    }

    /**
     * 未授權 (一般人員未加入 machine_user) → 403，回應不含 identity
     */
    public function test_unauthorized_staff_returns_403_without_identity(): void
    {
        User::factory()->create([
            'company_id' => $this->company->id,
            'username' => 'staff',
            'is_admin' => false,
            'password' => Hash::make('password'),
        ]);

        $response = $this->loginAs('staff');

        $response->assertStatus(403)
            ->assertJson(['success' => false, 'code' => 403]);
        $this->assertArrayNotHasKey('identity', $response->json());
    }
}
