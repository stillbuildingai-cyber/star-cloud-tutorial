<?php

namespace Tests\Feature\Admin\BasicSettings;

use Tests\TestCase;
use App\Models\System\User;
use App\Models\Machine\Machine;
use App\Models\Machine\MachineModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;

class MachineSettingTest extends TestCase
{
    use RefreshDatabase;

    protected $admin;
    protected $machineModel;

    protected function setUp(): void
    {
        parent::setUp();

        // 建立擁有 menu.basic.machines 權限的系統管理員
        $this->admin = User::factory()->create(['company_id' => null]);
        $this->admin->givePermissionTo(Permission::create(['name' => 'menu.basic.machines']));

        // 建立測試機台型號
        $this->machineModel = MachineModel::create([
            'name' => 'Test Model 01',
        ]);
    }

    public function test_system_admin_can_create_machine_with_key_no()
    {
        $payload = [
            'name' => 'New Test Machine',
            'serial_no' => 'TEST-SN-999',
            'machine_model_id' => $this->machineModel->id,
            'key_no' => 'KEY-999-ABC',
            'location' => 'Taipei Main Station',
        ];

        $response = $this->actingAs($this->admin)
            ->post(route('admin.basic-settings.machines.store'), $payload);

        $response->assertRedirect(route('admin.basic-settings.machines.index'));
        
        $this->assertDatabaseHas('machines', [
            'name' => 'New Test Machine',
            'serial_no' => 'TEST-SN-999',
            'key_no' => 'KEY-999-ABC',
        ]);
    }

    public function test_system_admin_can_update_machine_key_no()
    {
        // 先建立一個預設機台
        $machine = Machine::factory()->create([
            'machine_model_id' => $this->machineModel->id,
            'key_no' => 'OLD-KEY-111',
        ]);

        $payload = [
            'name' => 'Updated Machine Name',
            'serial_no' => $machine->serial_no,
            'machine_model_id' => $this->machineModel->id,
            'card_reader_seconds' => 30,
            'payment_buffer_seconds' => 5,
            'invoice_status' => 0,
            'key_no' => 'NEW-KEY-888',
        ];

        $response = $this->actingAs($this->admin)
            ->put(route('admin.basic-settings.machines.update', $machine), $payload);

        $response->assertRedirect(route('admin.basic-settings.machines.index'));

        $this->assertDatabaseHas('machines', [
            'id' => $machine->id,
            'key_no' => 'NEW-KEY-888',
        ]);
    }
}
