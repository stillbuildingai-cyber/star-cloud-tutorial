<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use App\Traits\ImageHandler;

class ProfileController extends Controller
{
    use ImageHandler;
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): View
    {
        $user = $request->user();

        return view('profile.edit', [
            // 只取最新 10 筆登入紀錄
            'user' => $user->load(['loginLogs' => fn($q) => $q->latest('login_at')->limit(10)]),
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $user = $request->user();
        $user->fill($request->validated());

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        return Redirect::route('profile.edit')->with('success', __('Profile updated successfully.'));
    }

    /**
     * Update the user's avatar via AJAX.
     */
    public function updateAvatar(Request $request): \Illuminate\Http\JsonResponse
    {
        $request->validate([
            'avatar' => ['required', 'image', 'mimes:jpeg,png,jpg,gif,webp', 'max:5120'],
        ]);

        $user = $request->user();

        if ($request->hasFile('avatar')) {
            // Delete old avatar if exists
            if ($user->avatar) {
                Storage::disk('public')->delete($user->avatar);
            }

            // 將上傳的大頭貼轉換為高壓縮率且清晰的 WebP 格式 (300x300 居中裁切)
            $path = $this->storeAsWebp($request->file('avatar'), 'avatars', 85, 300, 300);
            $user->avatar = $path;
            $user->save();

            return response()->json([
                'success' => true,
                'avatar_url' => $user->avatar_url,
                'message' => __('Avatar updated successfully.'),
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => __('No file uploaded.'),
        ], 400);
    }

}
