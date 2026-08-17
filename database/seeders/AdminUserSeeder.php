<?php

namespace Database\Seeders;

use App\Models\System\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * 管理員帳號 Seeder
 * 
 * 執行方式：php artisan db:seed --class=AdminUserSeeder
 */
class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 檢查是否已存在 admin 帳號，避免重複建立
        $admin = User::where('username', 'admin')->first();

        if ($admin) {
            $this->command->info('Admin 帳號已存在，執行更新密碼與資料。');
            $admin->update([
                'name' => 'Admin',
                'email' => 'admin@star-cloud.com',
                'password' => Hash::make('password'),
            ]);
            $admin->assignRole('super-admin');
            return;
        }

        $admin = User::create([
            'username' => 'admin',
            'name' => 'Admin',
            'email' => 'admin@star-cloud.com',
            'password' => Hash::make('password'),
        ]);

        $admin->assignRole('super-admin');

        $this->command->info('Admin 帳號建立成功！');
    }
}