<?php

namespace App\Http\Controllers\Api\V1\App;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Models\System\User;
use App\Models\Machine\Machine;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use App\Jobs\Machine\ProcessStateLog;

class MachineAuthController extends Controller
{
    /**
     * B000: 機台本機管理員/補貨員 離線同步登入驗證
     * 這個 API 僅用於機台的 Java App 登入畫面驗證帳密。不必進入 Queue。
     */
    public function loginB000(Request $request): JsonResponse
    {
        // 1. 驗證帳密與 IP
        $validated = $request->validate([
            'Su_Account' => 'required|string',
            'Su_Password' => 'required|string',
            'ip' => 'nullable|string',
        ]);

        // 2. 取得機台物件 (由 iot.auth 中介層注入)
        $machine = $request->get('machine');

        // 3. 透過帳號尋找使用者 (允許使用 username 或 email)
        $user = User::where('username', $validated['Su_Account'])
            ->orWhere('email', $validated['Su_Account'])
            ->first();

        // 4. 驗證密碼
        if (!$user || !Hash::check($validated['Su_Password'], $user->password)) {
            Log::warning("B000 機台登入失敗: 帳密錯誤", [
                'account' => $validated['Su_Account'],
                'machine' => $machine->serial_no
            ]);

            // 寫入機台日誌
            ProcessStateLog::dispatch(
                $machine->id,
                $machine->company_id,
                __("Login failed: :account", ['account' => $validated['Su_Account']]),
                'warning',
                [],
                'login'
            );

            return response()->json([
                'success' => false,
                'code' => 401,
                'message' => 'Failed'
            ], 401);
        }

        // 5. RBAC 權限驗證 (遵循多租戶與機台授權規範)
        // $identity: 登入者身分 (system:系統登入者 / company:公司帳號 / staff:一般人員)
        // 僅在授權通過時賦值,確保未授權者不洩漏身分
        $isAuthorized = false;
        $identity = null;
        if ($user->isSystemAdmin()) {
            $isAuthorized = true;
            $identity = 'system';
        } elseif ($user->is_admin) {
            if ($machine->company_id === $user->company_id) {
                $isAuthorized = true;
                $identity = 'company';
            }
        } else {
            if ($user->machines()->where('machine_id', $machine->id)->exists()) {
                $isAuthorized = true;
                $identity = 'staff';
            }
        }

        if (!$isAuthorized) {
            Log::warning("B000 機台登入失敗: 權限不足", [
                'user_id' => $user->id,
                'machine_id' => $machine->id
            ]);

            ProcessStateLog::dispatch(
                $machine->id,
                $machine->company_id,
                __("Unauthorized login attempt: :account", ['account' => $user->username]),
                'warning',
                [],
                'login'
            );

            return response()->json([
                'success' => false,
                'code' => 403,
                'message' => 'Forbidden'
            ], 403);
        }

        // 6. 驗證完美通過！
        Log::info("B000 機台登入成功", [
            'account' => $user->username,
            'machine' => $machine->serial_no
        ]);

        // 自我修復：自動將先前所有未解決的登入失敗警告標記為已解決
        $machine->logs()
            ->where('type', 'login')
            ->where('is_resolved', false)
            ->update(['is_resolved' => true]);

        // 寫入成功登入日誌
        ProcessStateLog::dispatch(
            $machine->id,
            $machine->company_id,
            __("User logged in: :name", ['name' => $user->name ?? $user->username]),
            'info',
            [],
            'login'
        );

        return response()->json([
            'success' => true,
            'code' => 200,
            'message' => 'Success',
            'identity' => $identity,
            'token' => $user->createToken('technician-setup', ['*'], now()->addHours(8))->plainTextToken
        ]);
    }
}
