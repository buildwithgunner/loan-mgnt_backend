<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Mail;
use Carbon\Carbon;
use App\Models\VerificationCode;

class AuthController extends Controller
{
    /**
     * Register a new partner user.
     */
    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'phone' => 'nullable|string|max:20',
            'password' => 'required|string|min:8|confirmed',
        ]);

        // Verify that the email was verified with OTP
        $verification = VerificationCode::where('email', $request->email)
            ->where('is_verified', true)
            ->first();

        if (!$verification) {
            return response()->json(['message' => 'Email verification required.'], 403);
        }

        // Optional: delete verification after use or mark it as used
        $verification->delete();

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'role' => 'user', 
            'password' => Hash::make($request->password),
            // Profile fields
            'address' => $request->address,
            'city' => $request->city,
            'state' => $request->state,
            'zip_code' => $request->zip_code,
            'ssn' => $request->ssn,
            'dob' => $request->dob,
            'marital_status' => $request->marital_status,
            'occupation' => $request->occupation,
            'self_employed' => $request->self_employed,
            'estimated_fico' => $request->estimated_fico,
            'estimated_net_worth' => $request->estimated_net_worth,
            'referral_source' => $request->referral_source,
            'working_with_consultant' => $request->working_with_consultant,
            'loan_intent' => $request->loan_intent,
        ]);

        try {
            $adminEmail = 'infoblackwolvesacc@blackwolvesacquisitionllc.com';
            $html = "
                <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #c5a059; border-radius: 15px;'>
                    <h2 style='color: #05101c; border-bottom: 2px solid #c5a059; padding-bottom: 10px;'>New Client Account Registered</h2>
                    <p style='margin: 10px 0;'><strong>Client Name:</strong> {$user->name}</p>
                    <p style='margin: 10px 0;'><strong>Email Address:</strong> {$user->email}</p>
                    <p style='margin: 10px 0;'><strong>Phone Number:</strong> " . ($user->phone ?? 'N/A') . "</p>
                    <p style='margin: 10px 0;'><strong>Estimated FICO Score:</strong> " . ($user->estimated_fico ?? 'N/A') . "</p>
                    <p style='margin: 10px 0;'><strong>Estimated Net Worth:</strong> " . ($user->estimated_net_worth ?? 'N/A') . "</p>
                    <p style='margin: 20px 0 5px 0;'><strong>Strategic Intent:</strong></p>
                    <div style='background: #f9f7f2; padding: 15px; border-left: 4px solid #c5a059; border-radius: 5px; color: #333;'>
                        " . nl2br(e($user->loan_intent ?? 'No strategic intent provided at genesis.')) . "
                    </div>
                    <p style='font-size: 11px; color: #888; margin-top: 30px; border-top: 1px solid #eee; padding-top: 10px;'>
                        Sent from Black Wolves Acquisition LLC System Protocol.
                    </p>
                </div>
            ";
            
            Mail::html($html, function ($message) use ($user, $adminEmail) {
                $message->to($adminEmail)
                    ->subject("New User Signup: {$user->name} - Black Wolves Acquisition");
            });
        } catch (\Exception $e) {
            \Log::error('Failed to send registration email notification: ' . $e->getMessage());
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => $user,
        ]);
    }

    /**
     * Register a new admin user (Restricted by passcode).
     */
    public function adminRegister(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:admins', // Changed to admins table
            'password' => 'required|string|min:8|confirmed',
            'passcode' => 'required|string',
        ]);

        if ($request->passcode !== 'BWA-ADMIN-2026') {
             return response()->json(['message' => 'Invalid system passcode.'], 403);
        }

        $admin = \App\Models\Admin::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        $token = $admin->createToken('auth_token')->plainTextToken;

        return response()->json([
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => clone $admin, // Sending back representation under 'user' so frontend admin.js works
        ]);
    }

    /**
     * Login admin user and create token.
     */
    public function adminLogin(Request $request)
    {
        $request->validate([
            'email' => 'required|string|email',
            'password' => 'required|string',
        ]);

        $admin = \App\Models\Admin::where('email', $request->email)->first();

        if (!$admin || !Hash::check($request->password, $admin->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        $token = $admin->createToken('auth_token')->plainTextToken;

        return response()->json([
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => $admin,
        ]);
    }

    /**
     * Login user and create token.
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|string|email',
            'password' => 'required|string',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => $user,
        ]);
    }

    /**
     * Update user profile information.
     */
    public function updateProfile(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|string|email|max:255|unique:users,email,' . $user->id,
            'phone' => 'nullable|string|max:20',
            'company' => 'nullable|string|max:255',
            'address' => 'nullable|string|max:255',
            'ssn' => 'nullable|string|max:20',
            'password' => 'sometimes|nullable|string|min:8|confirmed',
        ]);

        if (isset($validated['password']) && !empty($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }

        $user->update($validated);

        return response()->json([
            'message' => 'Profile updated successfully',
            'user' => $user->fresh(),
        ]);
    }

    /**
     * Send OTP to user's email.
     */
    public function sendOtp(Request $request)
    {
        $request->validate([
            'email' => 'required|email|max:255'
        ]);

        // Check if user already exists
        if (User::where('email', $request->email)->exists()) {
            return response()->json(['message' => 'Email already registered.'], 422);
        }

        $code = rand(100000, 999999);
        $expiresAt = Carbon::now()->addMinutes(10);

        VerificationCode::updateOrCreate(
            ['email' => $request->email],
            [
                'code' => $code,
                'expires_at' => $expiresAt,
                'is_verified' => false
            ]
        );

        // Send Email
        try {
            Mail::raw("Your verification code is: {$code}. It expires in 10 minutes.", function ($message) use ($request) {
                $message->to($request->email)
                    ->subject('Signup Verification Code');
            });
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to send OTP. Please check your email configuration.', 'error' => $e->getMessage()], 500);
        }

        return response()->json(['message' => 'OTP sent successfully.']);
    }

    /**
     * Verify OTP.
     */
    public function verifyOtp(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'code' => 'required|string|size:6'
        ]);

        $verification = VerificationCode::where('email', $request->email)
            ->where('code', $request->code)
            ->where('expires_at', '>', Carbon::now())
            ->first();

        if (!$verification) {
            return response()->json(['message' => 'Invalid or expired OTP.'], 422);
        }

        $verification->update(['is_verified' => true]);

        return response()->json(['message' => 'Email verified successfully.']);
    }
}
