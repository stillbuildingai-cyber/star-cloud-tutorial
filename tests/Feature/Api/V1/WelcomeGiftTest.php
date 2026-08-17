<?php

namespace Tests\Feature\Api\V1;

use App\Models\Machine\Machine;
use App\Models\System\Company;
use App\Models\Transaction\Order;
use App\Models\Transaction\WelcomeGift;
use App\Models\Transaction\WelcomeGiftLog;
use App\Services\Transaction\TransactionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class WelcomeGiftTest extends TestCase
{
    use RefreshDatabase;

    private Company $company;
    private Machine $machine;
    private string $apiToken;

    protected function setUp(): void
    {
        parent::setUp();

        // 建立 Company
        $this->company = Company::create([
            'name' => 'Test Company',
        ]);

        $this->apiToken = 'test-machine-api-token-123456';

        // 建立 Machine，並帶入 api_token 與 company_id
        $this->machine = Machine::factory()->create([
            'company_id' => $this->company->id,
            'api_token' => $this->apiToken,
        ]);
    }

    /**
     * 1. 測試折數與趴數雙向換算 (Model Accessors)
     */
    public function test_welcome_gift_discount_conversion_logic(): void
    {
        // A. 百分比折抵：15% off (8.5折)
        $giftA = WelcomeGift::create([
            'company_id' => $this->company->id,
            'machine_id' => $this->machine->id,
            'name' => '15% off Welcome Gift',
            'code' => '85000000',
            'discount_type' => 'percentage',
            'discount_value' => 15,
            'usage_type' => 'once',
            'usage_limit' => 1,
            'status' => 'active',
        ]);

        $this->assertEquals(8.5, $giftA->input_fold);
        $this->assertEquals('八五折', $giftA->discount_label);

        // B. 百分比折抵：20% off (8折)
        $giftB = WelcomeGift::create([
            'company_id' => $this->company->id,
            'machine_id' => $this->machine->id,
            'name' => '20% off Welcome Gift',
            'code' => '80000000',
            'discount_type' => 'percentage',
            'discount_value' => 20,
            'usage_type' => 'once',
            'usage_limit' => 1,
            'status' => 'active',
        ]);

        $this->assertEquals(8, $giftB->input_fold);
        $this->assertEquals('八折', $giftB->discount_label);

        // C. 金額折抵：折 50 元
        $giftC = WelcomeGift::create([
            'company_id' => $this->company->id,
            'machine_id' => $this->machine->id,
            'name' => '50 NTD off Welcome Gift',
            'code' => '50000000',
            'discount_type' => 'amount',
            'discount_value' => 50,
            'usage_type' => 'once',
            'usage_limit' => 1,
            'status' => 'active',
        ]);

        $this->assertNull($giftC->input_fold);
        $this->assertEquals('折 50 元', $giftC->discount_label);
    }

    /**
     * 2. 測試 B690 API 驗證成功
     */
    public function test_b690_verification_success(): void
    {
        $gift = WelcomeGift::create([
            'company_id' => $this->company->id,
            'machine_id' => $this->machine->id,
            'name' => '15% off Welcome Gift',
            'code' => '12345678',
            'discount_type' => 'percentage',
            'discount_value' => 15,
            'usage_type' => 'once',
            'usage_limit' => 1,
            'status' => 'active',
        ]);

        $response = $this->withHeader('Authorization', "Bearer {$this->apiToken}")
            ->postJson('/api/v1/app/machine/welcome-gift/verify/B690', [
                'code' => '12345678',
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'code' => 200,
                'data' => [
                    'name' => '15% off Welcome Gift',
                    'code_id' => $gift->id,
                    'discount_type' => 'percentage',
                    'discount_value' => 15,
                    'discount_label' => '八五折',
                    'usage_type' => 'once',
                    'status' => 'active',
                ],
            ]);

        // 驗證是否有寫入 verify_success 日誌
        $this->assertDatabaseHas('welcome_gift_logs', [
            'company_id' => $this->company->id,
            'machine_id' => $this->machine->id,
            'welcome_gift_id' => $gift->id,
            'action' => 'verify_success',
        ]);
    }

    /**
     * 3. 測試 B690 驗證防刷與 Lockout 限流鎖定
     */
    public function test_b690_lockout_mechanism(): void
    {
        $lockoutKey = "welcome_gift_lockout:{$this->machine->id}";
        $failsKey = "welcome_gift_fails:{$this->machine->id}";

        Cache::forget($lockoutKey);
        Cache::forget($failsKey);

        // 連續 4 次錯誤
        for ($i = 0; $i < 4; $i++) {
            $response = $this->withHeader('Authorization', "Bearer {$this->apiToken}")
                ->postJson('/api/v1/app/machine/welcome-gift/verify/B690', [
                    'code' => '00000000',
                ]);
            $response->assertStatus(404)
                ->assertJson([
                    'success' => false,
                    'remaining_attempts' => 4 - $i,
                ]);
        }

        // 第 5 次錯誤，觸發 Lockout 60 秒鎖定
        $response = $this->withHeader('Authorization', "Bearer {$this->apiToken}")
            ->postJson('/api/v1/app/machine/welcome-gift/verify/B690', [
                'code' => '00000000',
            ]);

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'remaining_attempts' => 0,
            ]);

        // 第 6 次呼叫，因 Lockout 鎖定，直接回傳 429
        $response = $this->withHeader('Authorization', "Bearer {$this->apiToken}")
            ->postJson('/api/v1/app/machine/welcome-gift/verify/B690', [
                'code' => '00000000',
            ]);

        $response->assertStatus(429)
            ->assertJson([
                'success' => false,
                'message' => 'Too many attempts. Locked.',
            ]);
    }

    /**
     * 4. 測試交易核銷閉環 (Finalize Transaction)
     */
    public function test_transaction_service_finalize_will_consume_welcome_gift(): void
    {
        // 建立一個單次使用限制的來店禮
        $gift = WelcomeGift::create([
            'company_id' => $this->company->id,
            'machine_id' => $this->machine->id,
            'name' => '15% off Welcome Gift',
            'code' => '12345678',
            'discount_type' => 'percentage',
            'discount_value' => 15,
            'usage_type' => 'once',
            'usage_limit' => 1,
            'status' => 'active',
        ]);

        $transactionService = app(TransactionService::class);

        $payload = [
            'serial_no' => $this->machine->serial_no,
            'flow_id' => 'FLOW-123456-WELCOME-GIFT',
            'order' => [
                'flow_id' => 'FLOW-123456-WELCOME-GIFT',
                'total_amount' => 100,
                'discount_amount' => 15,
                'pay_amount' => 85,
                'welcome_gift_id' => $gift->id,
            ],
        ];

        // 執行交易 finalize
        $order = $transactionService->finalizeTransaction($payload);

        $this->assertInstanceOf(Order::class, $order);
        $this->assertEquals($gift->id, $order->welcome_gift_id);

        // 驗證來店禮 usage_count 被加 1，且狀態變為 used (因為 usage_limit 為 1)
        $gift->refresh();
        $this->assertEquals(1, $gift->usage_count);
        $this->assertEquals('used', $gift->status);

        // 驗證核銷日誌被建立
        $this->assertDatabaseHas('welcome_gift_logs', [
            'company_id' => $this->company->id,
            'machine_id' => $this->machine->id,
            'welcome_gift_id' => $gift->id,
            'order_id' => $order->id,
            'action' => 'consume',
        ]);
    }
}
