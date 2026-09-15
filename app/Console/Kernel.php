<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // $schedule->command('inspire')->hourly();
        // 註：原本這裡還會排程 ota:process-schedules (APK OTA 派送)、invoices:reconcile (電子發票對帳)、
        // orders:sweep-abandoned (逾時訂單清理) 三個 Artisan 指令，皆屬於已整支移除的販賣機
        // 商品/交易/發票模組，對應的 Console Command 類別已刪除，教學版智慧插座不需要，予以移除。

        // 機台定時開機/關機排程：每分鐘檢查一次有沒有排程到期
        $schedule->command('schedules:dispatch')->everyMinute();
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
