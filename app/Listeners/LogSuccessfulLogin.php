<?php

namespace App\Listeners;

use App\Models\System\UserLoginLog;
use Illuminate\Auth\Events\Login;
use Illuminate\Http\Request;

class LogSuccessfulLogin
{
    /**
     * The request instance.
     *
     * @var \Illuminate\Http\Request
     */
    protected $request;

    /**
     * Create the event listener.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return void
     */
    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    /**
     * Handle the event.
     *
     * @param  \Illuminate\Auth\Events\Login  $event
     * @return void
     */
    public function handle(Login $event)
    {
        $ip = $this->request->ip();
        $userAgent = $this->request->userAgent();

        // 防重覆機制 (Debouncing): 10 秒內同使用者、同 IP 的記錄視為重複
        $recentLog = UserLoginLog::where('user_id', $event->user->id)
            ->where('ip_address', $ip)
            ->where('login_at', '>=', now()->subSeconds(10))
            ->first();

        if ($recentLog) {
            return;
        }

        $agent = new \Jenssegers\Agent\Agent();
        $agent->setUserAgent($userAgent);

        $deviceType = 'desktop';
        if ($agent->isTablet()) {
            $deviceType = 'tablet';
        } elseif ($agent->isMobile()) {
            $deviceType = 'mobile';
        }

        UserLoginLog::create([
            'user_id' => $event->user->id,
            'ip_address' => $ip,
            'user_agent' => $userAgent,
            'device_type' => $deviceType,
            'browser' => $agent->browser(),
            'platform' => $agent->platform(),
            'login_at' => now(),
        ]);
    }
}
