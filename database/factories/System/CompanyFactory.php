<?php

namespace Database\Factories\System;

use App\Models\System\Company;
use Illuminate\Database\Eloquent\Factories\Factory;

class CompanyFactory extends Factory
{
    protected $model = Company::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->company,
            'code' => $this->faker->unique()->bothify('COMP###'),
            'status' => 1,
            'settings' => [
                'enable_material_code' => false,
                'enable_points' => false,
            ],
        ];
    }
}
