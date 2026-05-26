<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        return $this->registerUser($request, false);
    }

    public function registerAdmin(Request $request)
    {
        $invite = (string) env('ADMIN_INVITE_CODE', 'SWIFTADMIN');
        if ($request->input('invite_code') !== $invite) {
            throw ValidationException::withMessages(['invite_code' => 'Invalid admin invite code.']);
        }

        return $this->registerUser($request, true);
    }

    public function login(Request $request)
    {
        return $this->loginUser($request, false);
    }

    public function loginAdmin(Request $request)
    {
        return $this->loginUser($request, true);
    }

    public function me(Request $request)
    {
        return response()->json(['user' => $request->user()]);
    }

    public function logout(Request $request)
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json(['message' => 'Logged out']);
    }

    private function registerUser(Request $request, bool $asAdmin)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'status' => 'Active',
            'is_admin' => $asAdmin,
        ]);

        Auth::login($user);
        $request->session()->regenerate();

        return response()->json(['user' => $user], 201);
    }

    private function loginUser(Request $request, bool $requireAdmin)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        if (!Auth::attempt($credentials, (bool) $request->boolean('remember'))) {
            return response()->json(['message' => 'Invalid credentials'], 422);
        }

        $request->session()->regenerate();
        $user = $request->user();

        if ($requireAdmin && !$user->is_admin) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            return response()->json(['message' => 'Admin account required.'], 403);
        }

        return response()->json(['user' => $user]);
    }
}
