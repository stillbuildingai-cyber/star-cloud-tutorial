<?php

namespace App\Http\Controllers\Api\V1\App;

use App\Http\Controllers\Controller;
use App\Models\System\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class CustomerAuthController extends Controller
{
    /**
     * App 端使用者登入，比照 TenantLoginController 的帳密驗證邏輯，
     * 成功後改為核發 Sanctum Token 而不是建立 Session。
     */
    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        $user = User::where('username', $request->username)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => __('These credentials do not match our records.'),
            ], 401);
        }

        if ($user->status !== 1) {
            return response()->json([
                'success' => false,
                'message' => __('Your account is disabled.'),
            ], 403);
        }

        $token = $user->createToken('customer-app', ['app:*'])->plainTextToken;

        return response()->json([
            'success' => true,
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'username' => $user->username,
            ],
        ]);
    }

    /**
     * 登出：只撤銷這支 App 目前使用的那一個 Token，不影響使用者其他裝置的登入狀態。
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['success' => true]);
    }
}
