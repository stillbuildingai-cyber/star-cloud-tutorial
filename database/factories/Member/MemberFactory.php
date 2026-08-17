<?php

namespace Database\Factories\Member;

use App\Models\Member\Member;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class MemberFactory extends Factory
{
    protected $model = Member::class;

    public function definition(): array
    {
        return [
            'uuid' => (string) Str::uuid(),
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => '09' . fake()->numberBetween(10000000, 99999999),
            'password' => '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', // password
            'birthday' => fake()->date(),
            'gender' => fake()->randomElement(['male', 'female', 'other']),
            'is_active' => true,
            'email_verified_at' => now(),
        ];
    }
}
