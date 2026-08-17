<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     * 
     * 執行全部 Seeder：php artisan db:seed
     * 執行單一 Seeder：php artisan db:seed --class=AdminUserSeeder
     */
    public function run(): void
    {
        $this->call([
            RoleSeeder::class,
            AdminUserSeeder::class,
            MachineSeeder::class,
            MemberSeeder::class,
            SmartSocketDemoSeeder::class,
        ]);
    }
}
