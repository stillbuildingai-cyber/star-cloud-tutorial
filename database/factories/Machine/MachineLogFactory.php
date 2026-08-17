<?php

namespace Database\Factories\Machine;

use App\Models\Machine\Machine;
use App\Models\Machine\MachineLog;
use Illuminate\Database\Eloquent\Factories\Factory;

class MachineLogFactory extends Factory
{
    protected $model = MachineLog::class;

    public function definition(): array
    {
        $messages = [
            'info' => [
                '機台啟動完成',
                '系統心跳上報',
                '交易成功 (訂單 #'.fake()->numberBetween(1000, 9999).')',
                '補貨作業完成',
                '環境溫度穩定 (24C)',
            ],
            'warning' => [
                '貨道 A3 庫存偏低',
                '通訊品質不穩定',
                '感測器回報數值異常',
                '機門開啟次數過多',
            ],
            'error' => [
                '馬達轉動失效 (貨道 B2)',
                '硬幣器卡幣',
                '散熱風扇停止運作',
                '電源供應模組故障',
                '網路連線中斷',
            ]
        ];

        $level = fake()->randomElement(['info', 'warning', 'error']);

        return [
            'machine_id' => Machine::factory(),
            'level' => $level,
            'message' => fake()->randomElement($messages[$level]),
            'context' => [
                'ip' => fake()->ipv4(),
                'uptime' => fake()->numberBetween(1000, 100000),
            ],
            'created_at' => fake()->dateTimeBetween('-1 day', 'now'),
        ];
    }
}
