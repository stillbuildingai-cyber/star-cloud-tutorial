<?php

namespace Database\Seeders;

use App\Models\Machine\Machine;
use App\Models\Machine\MachineLog;
use Illuminate\Database\Seeder;

class MachineSeeder extends Seeder
{
    public function run(): void
    {
        // 建立 2 台機台
        Machine::factory()->count(2)->create()->each(function ($machine) {
            // 每台機台隨機建立 5-10 筆初始日誌
            MachineLog::factory()->count(rand(5, 10))->create([
                'machine_id' => $machine->id,
            ]);
        });
    }
}
