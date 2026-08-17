<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTenantAccess
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth()->user();

        // 如果是租戶帳號，檢查公司狀態
        if ($user && $user->isTenant()) {
            $company = $user->company;

            if (!$company || $company->status === 0) {
                auth()->logout();
                return redirect()->route('login')->with('error', __('Your account is associated with a deactivated company.'));
            }

            if ($company->end_date && $company->end_date->isPast()) {
                auth()->logout();
                return redirect()->route('login')->with('error', __('Your company contract has expired.'));
            }
        }

        return $next($request);
    }
}
