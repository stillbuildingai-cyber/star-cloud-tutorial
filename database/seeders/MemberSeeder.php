<?php

namespace Database\Seeders;

use App\Models\Member\Member;
use App\Models\Member\MemberWallet;
use Illuminate\Database\Seeder;

class MemberSeeder extends Seeder
{
    public function run(): void
    {
        // 建立 20 位會員並分配錢包
        Member::factory()->count(20)->create()->each(function ($member) {
            MemberWallet::create([
                'member_id' => $member->id,
                'balance' => fake()->randomFloat(2, 100, 5000),
                'bonus_balance' => fake()->randomFloat(2, 0, 500),
            ]);
        });
    }
}
