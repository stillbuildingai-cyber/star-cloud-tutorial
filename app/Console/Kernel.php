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
        $schedule->command('ota:process-schedules')->everyMinute();
        // 電子發票對帳：對 pending 發票去綠界查證（補登 issued / 標 failed 待補開）
        $schedule->command('invoices:reconcile')->everyFiveMinutes()->withoutOverlapping();
        // 交易結案掃描：把逾時停留在 pending 的單標記為 abandoned（只動 pending，碰不到線上 main 機台）
        $schedule->command('orders:sweep-abandoned')->everyFiveMinutes()->withoutOverlapping();
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
