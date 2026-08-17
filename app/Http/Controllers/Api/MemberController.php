<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Member\Member;
use App\Models\Member\SocialAccount;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

class MemberController extends Controller
{
    /**
     * 會員註冊
     */
    public function register(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'unique:members,email'],
            'phone' => ['nullable', 'string', 'unique:members,phone'],
            'password' => ['required', Password::min(6)],
            'birthday' => ['nullable', 'date'],
            'gender' => ['nullable', 'in:male,female,other'],
        ], [
            'name.required' => '請輸入姓名',
            'email.unique' => '此 Email 已被註冊',
            'phone.unique' => '此手機號碼已被註冊',
            'password.required' => '請輸入密碼',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => '驗證失敗',
                'errors' => $validator->errors(),
            ], 422);
        }

        // 必須提供 email 或 phone 其中之一
        if (empty($request->email) && empty($request->phone)) {
            return response()->json([
                'success' => false,
                'message' => '請提供 Email 或手機號碼',
            ], 422);
        }

        $member = Member::create([
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'password' => $request->password,
            'birthday' => $request->birthday,
            'gender' => $request->gender,
        ]);

        $token = $member->createToken('member-token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => '註冊成功',
            'data' => [
                'member' => $member,
                'token' => $token,
            ],
        ], 201);
    }

    /**
     * 會員登入（Email/Phone + Password）
     */
    public function login(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'account' => ['required', 'string'],
            'password' => ['required', 'string'],
        ], [
            'account.required' => '請輸入帳號',
            'password.required' => '請輸入密碼',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => '驗證失敗',
                'errors' => $validator->errors(),
            ], 422);
        }

        // 嘗試以 email 或 phone 查詢
        $member = Member::where('email', $request->account)
            ->orWhere('phone', $request->account)
            ->first();

        if (!$member || !Hash::check($request->password, $member->password)) {
            return response()->json([
                'success' => false,
                'message' => '帳號或密碼錯誤',
            ], 401);
        }

        if (!$member->is_active) {
            return response()->json([
                'success' => false,
                'message' => '帳號已被停用',
            ], 403);
        }

        $token = $member->createToken('member-token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => '登入成功',
            'data' => [
                'member' => $member,
                'token' => $token,
            ],
        ]);
    }

    /**
     * 社群登入
     */
    public function socialLogin(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'provider' => ['required', 'in:line,google,facebook'],
            'provider_id' => ['required', 'string'],
            'access_token' => ['nullable', 'string'],
            'name' => ['nullable', 'string'],
            'email' => ['nullable', 'email'],
            'avatar' => ['nullable', 'string'],
        ], [
            'provider.required' => '請指定登入平台',
            'provider.in' => '不支援的登入平台',
            'provider_id.required' => '缺少社群用戶 ID',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => '驗證失敗',
                'errors' => $validator->errors(),
            ], 422);
        }

        // 查詢是否已綁定
        $socialAccount = SocialAccount::where('provider', $request->provider)
            ->where('provider_id', $request->provider_id)
            ->first();

        if ($socialAccount) {
            // 已綁定，直接登入
            $member = $socialAccount->member;

            // 更新 token
            $socialAccount->update([
                'access_token' => $request->access_token,
            ]);
        } else {
            // 未綁定，建立新會員
            $member = Member::create([
                'name' => $request->name ?? '會員',
                'email' => $request->email,
                'avatar' => $request->avatar,
                'email_verified_at' => $request->email ? now() : null, // 社群登入自動驗證
            ]);

            // 綁定社群帳號
            $member->socialAccounts()->create([
                'provider' => $request->provider,
                'provider_id' => $request->provider_id,
                'access_token' => $request->access_token,
                'profile_data' => $request->only(['name', 'email', 'avatar']),
            ]);
        }

        if (!$member->is_active) {
            return response()->json([
                'success' => false,
                'message' => '帳號已被停用',
            ], 403);
        }

        $token = $member->createToken('member-token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => '登入成功',
            'data' => [
                'member' => $member,
                'token' => $token,
            ],
        ]);
    }

    /**
     * 取得個人資料
     */
    public function profile(Request $request): JsonResponse
    {
        $member = $request->user();

        return response()->json([
            'success' => true,
            'data' => [
                'member' => $member->load('socialAccounts'),
            ],
        ]);
    }

    /**
     * 更新個人資料
     */
    public function updateProfile(Request $request): JsonResponse
    {
        $member = $request->user();

        $validator = Validator::make($request->all(), [
            'name' => ['nullable', 'string', 'max:255'],
            'birthday' => ['nullable', 'date'],
            'gender' => ['nullable', 'in:male,female,other'],
            'avatar' => ['nullable', 'string'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => '驗證失敗',
                'errors' => $validator->errors(),
            ], 422);
        }

        $member->update($request->only(['name', 'birthday', 'gender', 'avatar']));

        return response()->json([
            'success' => true,
            'message' => '更新成功',
            'data' => [
                'member' => $member,
            ],
        ]);
    }

    /**
     * 登出
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => '登出成功',
        ]);
    }
}
