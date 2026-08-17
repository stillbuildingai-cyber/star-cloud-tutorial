<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\Machine\Machine;
use Symfony\Component\HttpFoundation\Response;

class IotAuth
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();
        
        // Phase 1: 暫時也接受 Request Body 中的 key 欄位 (相容模式)
        if (!$token) {
            $token = $request->input('key');
        }

        if (!$token) {
            return response()->json(['success' => false, 'message' => 'Unauthorized: Missing Token'], 401);
        }

        $machine = Machine::where('api_token', $token)->first();

        if (!$machine) {
            return response()->json(['success' => false, 'message' => 'Unauthorized: Invalid Token'], 401);
        }

        // 將機台物件注入 Request 供後端使用
        $request->merge(['machine' => $machine]);

        return $next($request);
    }
}
