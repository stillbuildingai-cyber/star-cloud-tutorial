<?php

namespace Database\Seeders;

use App\Models\Machine\Machine;
use App\Models\System\Company;
use Illuminate\Database\Seeder;

/**
 * 教學用：建立一台固定序號/Token 的智慧插座示範機台，
 * 讓 ESP32 / D1 mini 韌體、mqtt_sim.py 都能直接照著 README 連線，不用自己查資料庫。
 *
 * 執行方式：php artisan db:seed --class=SmartSocketDemoSeeder
 */
class SmartSocketDemoSeeder extends Seeder
{
    public const DEMO_SERIAL = 'SW-DEMO-001';
    public const DEMO_TOKEN = 'tutorial-demo-token';

    public function run(): void
    {
        $company = Company::firstOrCreate(
            ['code' => 'TUTORIAL'],
            [
                'name' => '教學示範公司',
                'status' => 1,
                'settings' => [
                    'enable_material_code' => false,
                    'enable_points' => false,
                ],
            ]
        );

        $machine = Machine::updateOrCreate(
            ['serial_no' => self::DEMO_SERIAL],
            [
                'company_id' => $company->id,
                'name' => '教學示範智慧插座',
                'model' => 'smart-socket',
                'status' => 'offline',
                'api_token' => self::DEMO_TOKEN,
                'relay_state' => false,
                'current_power' => 0,
                'current_amp' => 0,
                'voltage' => 110,
                'total_energy' => 0,
            ]
        );

        $this->command->info("示範機台已建立：");
        $this->command->info("  序號 (serial_no):  {$machine->serial_no}");
        $this->command->info("  Token (api_token): {$machine->api_token}");
        $this->command->info("MQTT 連線帳密就是上面這組（username=序號, password=token）。");
    }
}
