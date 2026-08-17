<?php

namespace Database\Factories\Machine;

use App\Models\Machine\Machine;
use Illuminate\Database\Eloquent\Factories\Factory;

class MachineFactory extends Factory
{
    protected $model = Machine::class;

    public function definition(): array
    {
        return [
            'name' => 'Machine-' . fake()->unique()->numberBetween(101, 999),
            'location' => fake()->address(),
            'temperature' => fake()->randomFloat(2, 2, 10),
            'firmware_version' => 'v' . fake()->randomElement(['1.0.0', '1.1.2', '2.0.1']),
            'serial_no' => 'SN-' . strtoupper(fake()->unique()->bothify('??###?')),
            'last_heartbeat_at' => fake()->dateTimeBetween('-1 day', 'now'),
        ];
    }
}
