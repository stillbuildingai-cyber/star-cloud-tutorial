<?php

namespace Tests\Feature;

use App\Models\Machine\Machine;
use App\Models\Machine\MachineLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MachineStatusTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test machine is online with recent heartbeat and no errors.
     */
    public function test_machine_is_online_with_recent_heartbeat(): void
    {
        $machine = Machine::factory()->create([
            'last_heartbeat_at' => now()->subSeconds(10),
        ]);

        $this->assertEquals('online', $machine->calculated_status);
    }

    /**
     * Test machine is error with recent heartbeat but recent errors.
     */
    public function test_machine_is_error_with_recent_logs(): void
    {
        $machine = Machine::factory()->create([
            'last_heartbeat_at' => now()->subSeconds(10),
        ]);

        // Add an error log 10 mins ago
        MachineLog::create([
            'machine_id' => $machine->id,
            'level' => 'error',
            'created_at' => now()->subMinutes(10),
            'message' => 'Test error',
        ]);

        $this->assertEquals('error', $machine->calculated_status);
    }

    /**
     * Test machine is offline with old heartbeat (30s+).
     */
    public function test_machine_is_offline_with_old_heartbeat(): void
    {
        $machine = Machine::factory()->create([
            'last_heartbeat_at' => now()->subSeconds(35),
        ]);

        $this->assertEquals('offline', $machine->calculated_status);
    }

    /**
     * Test machine scopes (online, offline, hasError).
     */
    public function test_machine_scopes(): void
    {
        // 1. Online machine
        $onlineMachine = Machine::factory()->create(['last_heartbeat_at' => now()->subSeconds(10)]);
        
        // 2. Offline machine
        $offlineMachine = Machine::factory()->create(['last_heartbeat_at' => now()->subSeconds(40)]);
        
        // 3. Online machine with error
        $errorMachine = Machine::factory()->create(['last_heartbeat_at' => now()->subSeconds(5)]);
        MachineLog::create([
            'machine_id' => $errorMachine->id,
            'level' => 'error',
            'created_at' => now()->subMinutes(5),
            'message' => 'Error log',
        ]);

        $this->assertEquals(2, Machine::online()->count());
        $this->assertEquals(1, Machine::offline()->count());
        $this->assertEquals(1, Machine::online()->hasError()->count());
        
        $this->assertTrue(Machine::online()->get()->contains($onlineMachine));
        $this->assertTrue(Machine::offline()->get()->contains($offlineMachine));
        $this->assertTrue(Machine::online()->hasError()->get()->contains($errorMachine));
    }
}
