<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Machine\Machine;
use App\Services\Machine\MachineService;

class MqttSyncAuth extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mqtt:sync-auth';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync all machine API tokens to Redis for MQTT authentication';

    /**
     * Execute the console command.
     */
    public function handle(MachineService $machineService)
    {
        // 1. 同步 Gateway 自身的認證資料 (與 mqtt-gateway/main.go 對應)
        $this->info("Syncing star-cloud-gateway auth...");
        $gatewayKey = "machine_auth:star-cloud-gateway";
        $gatewayPass = hash('sha256', env('MQTT_PASSWORD', 'tutorial-gateway-secret'));
        \Illuminate\Support\Facades\Redis::hSet($gatewayKey, 'password', $gatewayPass);
        $this->info("Gateway auth synced.");

        // 2. 同步所有機台的認證資料 (僅限未刪除的)
        $machines = Machine::get();
        $this->info("Syncing " . $machines->count() . " machines to Redis...");

        $bar = $this->output->createProgressBar($machines->count());
        $bar->start();

        foreach ($machines as $machine) {
            $machineService->syncMqttAuth($machine);
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info("Sync completed.");
    }
}
