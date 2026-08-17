<?php

namespace Tests\Feature\Api\V1;

use App\Jobs\Machine\ProcessMachineLog;
use App\Models\Machine\Machine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class MachineLogTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 測試機台日誌 API 是否能正確接收並派發 Job
     */
    public function test_machine_can_send_log_and_dispatch_job(): void
    {
        Queue::fake();

        $machine = Machine::factory()->create();

        $response = $this->postJson("/api/v1/machines/{$machine->id}/logs", [
            'level' => 'info',
            'message' => 'Test log message',
            'context' => ['foo' => 'bar'],
        ]);

        $response->assertStatus(202)
            ->assertJson([
                'success' => true,
                'message' => 'Log accepted. Processing asynchronously.',
            ]);

        Queue::assertPushed(ProcessMachineLog::class, function ($job) use ($machine) {
            return $job->getMachineId() === $machine->id;
        });
    }

    /**
     * 測試不存在的機台應回傳 404
     */
    public function test_send_log_to_non_existent_machine_returns_404(): void
    {
        $response = $this->postJson("/api/v1/machines/999/logs", [
            'level' => 'info',
            'message' => 'Should fail',
        ]);

        $response->assertStatus(404);
    }

    /**
     * 測試屬性驗證失敗
     */
    public function test_send_invalid_log_data_returns_422(): void
    {
        $machine = Machine::factory()->create();

        $response = $this->postJson("/api/v1/machines/{$machine->id}/logs", [
            'level' => 'invalid-level', // 不符合 in:info,warning,error
            'message' => '', // 必填
        ]);

        $response->assertStatus(422);
    }
}
