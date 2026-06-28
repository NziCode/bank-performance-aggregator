<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthController extends Controller
{
    // --- API ---

    public function login(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'personnel_code' => ['required', 'string'],
            'password'       => ['required', 'string'],
        ]);

        if (! Auth::attempt([
            'personnel_code' => $credentials['personnel_code'],
            'password'       => $credentials['password'],
        ])) {
            return response()->json([
                'message' => 'کد پرسنلی یا رمز عبور اشتباه است',
            ], 401);
        }

        $user  = Auth::user();
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user'  => [
                'personnel_code' => $user->personnel_code,
                'full_name'      => $user->full_name,
                'position'       => $user->position,
                'workplace_type' => $user->workplace_type,
            ],
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'با موفقیت خارج شدید',
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        $user = $request->user()->load([
            'branch',
            'zone',
            'branchOffice',
            'staffUnit',
        ]);

        return response()->json([
            'personnel_code' => $user->personnel_code,
            'full_name'      => $user->full_name,
            'position'       => $user->position,
            'mobile'         => $user->mobile,
            'education'      => $user->education,
            'gender'         => $user->gender,
            'workplace_type' => $user->workplace_type,
            'workplace'      => match($user->workplace_type) {
                'branch'        => $user->branch?->name,
                'zone'          => $user->zone?->name,
                'branch_office' => $user->branchOffice?->name,
                'staff'         => $user->staffUnit?->name,
                default         => null,
            },
        ]);
    }

    // --- Web ---

    public function showLogin(): View
    {
        return view('auth.login');
    }

    public function loginWeb(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'personnel_code' => ['required', 'string'],
            'password'       => ['required', 'string'],
        ]);

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            return back()->withErrors([
                'personnel_code' => 'کد پرسنلی یا رمز عبور اشتباه است',
            ]);
        }

        return redirect()->route('dashboard');
    }

    public function logoutWeb(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('login');
    }
}
