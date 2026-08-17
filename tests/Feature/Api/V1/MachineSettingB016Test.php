<?php

namespace Tests\Feature\Api\V1;

use App\Models\Machine\Machine;
use App\Models\System\Company;
use App\Models\System\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * B016：機台端回寫系統設定。機台僅可編輯硬體貨道 (is_spring_slot_*)，
 * 其餘系統設定一律忽略；僅系統方 (company_id = null) 可操作。
 */
class MachineSettingB016Test extends TestCase
{
    use RefreshDatabase;

    private const URL = '/api/v1/app/machine/setting/B016';

    private Company $company;
    private Machine $machine;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::create(['name' => 'Test Company']);

        // 六個貨道初始皆為 false（履帶）
        $this->machine = Machine::factory()->create([
            'company_id' => $this->company->id,
            'is_spring_slot_1_10' => false,
            'is_spring_slot_11_20' => false,
            'is_spring_slot_21_30' => false,
            'is_spring_slot_31_40' => false,
            'is_spring_slot_41_50' => false,
            'is_spring_slot_51_60' => false,
        ]);
    }

    private function actingAsSystemAdmin(): User
    {
        $admin = User::factory()->create(['company_id' => null]);
        Sanctum::actingAs($admin);
        return $admin;
    }

    /**
     * 系統方部分更新：只送兩個貨道 → 只有這兩個被改，其餘四個維持原值，並寫入 updater_id。
     */
    public function test_system_admin_partial_update_only_touches_sent_slots(): void
    {
        $admin = $this->actingAsSystemAdmin();

        $this->patchJson(self::URL, [
            'machine' => $this->machine->serial_no,
            'settings' => [
                'is_spring_slot_1_10' => true,
                'is_spring_slot_51_60' => true,
            ],
        ])->assertStatus(200)->assertJson(['success' => true, 'code' => 200]);

        $this->assertDatabaseHas('machines', [
            'id' => $this->machine->id,
            'is_spring_slot_1_10' => 1,   // 有送 → 改成彈簧
            'is_spring_slot_51_60' => 1,  // 有送 → 改成彈簧
            'is_spring_slot_11_20' => 0,  // 未送 → 維持履帶
            'is_spring_slot_21_30' => 0,
            'is_spring_slot_31_40' => 0,
            'is_spring_slot_41_50' => 0,
            'updater_id' => $admin->id,
        ]);
    }

    /**
     * 一次送齊六個貨道：全部正確寫入。
     */
    public function test_system_admin_can_update_all_six_slots(): void
    {
        $this->actingAsSystemAdmin();

        $this->patchJson(self::URL, [
            'machine' => $this->machine->serial_no,
            'settings' => [
                'is_spring_slot_1_10' => true,
                'is_spring_slot_11_20' => true,
                'is_spring_slot_21_30' => true,
                'is_spring_slot_31_40' => true,
                'is_spring_slot_41_50' => true,
                'is_spring_slot_51_60' => true,
            ],
        ])->assertStatus(200)->assertJson(['success' => true]);

        $this->machine->refresh();
        $this->assertTrue($this->machine->is_spring_slot_1_10);
        $this->assertTrue($this->machine->is_spring_slot_11_20);
        $this->assertTrue($this->machine->is_spring_slot_21_30);
        $this->assertTrue($this->machine->is_spring_slot_31_40);
        $this->assertTrue($this->machine->is_spring_slot_41_50);
        $this->assertTrue($this->machine->is_spring_slot_51_60);
    }

    /**
     * 非貨道鍵（如支付/現金旗標）一律忽略：只跟著的貨道鍵會生效。
     */
    public function test_non_slot_keys_are_ignored(): void
    {
        $this->actingAsSystemAdmin();

        $this->patchJson(self::URL, [
            'machine' => $this->machine->serial_no,
            'settings' => [
                'is_spring_slot_1_10' => true,
                'cash_module_enabled' => true,     // 非白名單 → 忽略
                'credit_card_enabled' => true,     // 非白名單 → 忽略
                'shopping_cart_enabled' => false,  // 非白名單 → 忽略
            ],
        ])->assertStatus(200)->assertJson(['success' => true]);

        $this->machine->refresh();
        $this->assertTrue($this->machine->is_spring_slot_1_10);
        // 非貨道欄位不存在於 machines 直接欄位，確認沒被當成欄位寫入而報錯即可（請求已 200）
    }

    /**
     * 完全沒送任何貨道鍵 → 422（無可更新欄位）。
     */
    public function test_payload_without_any_slot_key_returns_422(): void
    {
        $this->actingAsSystemAdmin();

        $this->patchJson(self::URL, [
            'machine' => $this->machine->serial_no,
            'settings' => [
                'cash_module_enabled' => true,
            ],
        ])->assertStatus(422)->assertJson(['success' => false, 'code' => 422]);
    }

    /**
     * settings 非陣列 → 422。
     */
    public function test_non_array_settings_returns_422(): void
    {
        $this->actingAsSystemAdmin();

        $this->patchJson(self::URL, [
            'machine' => $this->machine->serial_no,
            'settings' => 'not-an-array',
        ])->assertStatus(422)->assertJson(['success' => false, 'code' => 422]);
    }

    /**
     * 機台不存在 → 404。
     */
    public function test_unknown_machine_returns_404(): void
    {
        $this->actingAsSystemAdmin();

        $this->patchJson(self::URL, [
            'machine' => 'SN-DOES-NOT-EXIST',
            'settings' => ['is_spring_slot_1_10' => true],
        ])->assertStatus(404)->assertJson(['success' => false, 'code' => 404]);
    }

    /**
     * 公司管理員（company_id 不為 null，非系統方）→ 403，且資料不變。
     */
    public function test_company_admin_is_forbidden(): void
    {
        $companyAdmin = User::factory()->create([
            'company_id' => $this->company->id,
            'is_admin' => true,
        ]);
        Sanctum::actingAs($companyAdmin);

        $this->patchJson(self::URL, [
            'machine' => $this->machine->serial_no,
            'settings' => ['is_spring_slot_1_10' => true],
        ])->assertStatus(403)->assertJson(['success' => false, 'code' => 403]);

        $this->assertDatabaseHas('machines', [
            'id' => $this->machine->id,
            'is_spring_slot_1_10' => 0,
        ]);
    }

    /**
     * 未帶 token（未認證）→ 401。
     */
    public function test_unauthenticated_returns_401(): void
    {
        $this->patchJson(self::URL, [
            'machine' => $this->machine->serial_no,
            'settings' => ['is_spring_slot_1_10' => true],
        ])->assertStatus(401);
    }
}
