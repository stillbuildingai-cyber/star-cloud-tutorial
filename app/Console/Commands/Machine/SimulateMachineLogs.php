<?php

namespace App\Console\Commands\Machine;

use App\Models\Machine\Machine;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class SimulateMachineLogs extends Command
{
    /**
     * @var string
     */
    protected $signature = 'simulate:machine-logs {--count=10 : 發送日誌的次數}';

    /**
     * @var string
     */
    protected $description = '模擬機台發送 API 日誌請求到後端 (用於壓測與驗證 Queue)';

    public function handle(): void
    {
        $count = (int) $this->option('count');
        $machines = Machine::all();

        if ($machines->isEmpty()) {
            $this->error('No machines found. Please run MachineSeeder first.');
            return;
        }

        $this->info("Starting simulation of {$count} logs...");

        $bar = $this->output->createProgressBar($count);
        $bar->start();

        // 由於是在同一個開發環境，且在 Sail 容器內部執行，
        // 外部 8090 埠對應容器內部 8080 埠。
        $baseUrl = 'http://localhost:8080/api/v1/machines/';

        for ($i = 0; $i < $count; $i++) {
            $machine = $machines->random();
            $level = collect(['info', 'warning', 'error'])->random();
            
            try {
                Http::post($baseUrl . $machine->id . '/logs', [
                    'level' => $level,
                    'message' => "Simulated message #{$i} for machine {$machine->name}",
                    'context' => [
                        'simulated' => true,
                        'timestamp' => now()->toIso8601String(),
                    ]
                ]);
            } catch (\Exception $e) {
                $this->error("\nFailed to send log: " . $e->getMessage());
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info('Simulation completed.');
    }
}
