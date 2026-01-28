<?php
namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Cache;
use App\Mail\WelcomeOneTimePassword;


class AuthController extends Controller
{
    // SUPER USER: List all companies (pending and active)
    public function listCompanies()
    {
        $companies = \App\Models\Company::all();
        return response()->json(['companies' => $companies]);
    }

    // SUPER USER: Approve (activate) a company
    public function activateCompany($id)
    {
        $company = \App\Models\Company::findOrFail($id);
        $company->active = true;
        $company->save();
        return response()->json(['message' => 'Company activated.']);
    }

    // SUPER USER: Deactivate a company
    public function deactivateCompany($id)
    {
        $company = \App\Models\Company::findOrFail($id);
        $company->active = false;
        $company->save();
        return response()->json(['message' => 'Company deactivated.']);
    }
    // Register a new user (admin or user)
    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'role_id' => 'required|exists:roles,id',
            'business_name' => 'required|string|max:255',
            'business_details' => 'nullable|string|max:1000',
        ]);

        // Create company (pending by default)
        $company = \App\Models\Company::create([
            'name' => $request->business_name,
            'email' => $request->email,
            'details' => $request->business_details,
            'active' => false, // Pending
        ]);

        // Generate one-time temporary password (OTP)
        $otp = strtoupper(\Illuminate\Support\Str::random(8));

        // Determine if role should be auto-verified (Business Admins can login immediately)
        $role = Role::find($request->role_id);
        $autoVerify = false;
        if ($role && in_array(strtolower($role->name), ['admin','businessadmin','business_admin','business admin'])) {
            $autoVerify = true;
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($otp), // store hashed OTP
            'role_id' => $request->role_id,
            'company_id' => $company->id,
            'created_by_user_id' => Auth::id(),
            'verified' => $autoVerify, // auto-verify business admins so they can log in
            'must_change_password' => true, // force change on first login
        ]);

        // Optionally create a warehouse for the user if implementation exists
        if (class_exists(\App\Models\Warehouse::class) && method_exists(\App\Models\Warehouse, 'generateNameForUser')) {
            $warehouseName = \App\Models\Warehouse::generateNameForUser($user);
            \App\Models\Warehouse::create([
                'name' => $warehouseName,
                'user_id' => $user->id,
                'type' => 'user',
            ]);
        }

        // Send OTP email (thanking and instructing)
        try {
            Mail::to($user->email)->send(new WelcomeOneTimePassword($user, $otp));
            Log::info('OTP email queued/sent for new user', ['user_id' => $user->id, 'email' => $user->email]);
        } catch (\Throwable $e) {
            Log::error('Failed to send OTP email', ['error' => $e->getMessage(), 'user_id' => $user->id]);
        }

        return response()->json([
            'message' => 'Account created. A temporary password has been sent to the provided email. Please log in using the temporary password and then change it.',
            'user' => $user->only(['id','name','email','company_id','role_id']),
            'company' => $company->only(['id','name','email'])
        ], 201);
    }

    // Login
    public function login(Request $request)
    {
        // Validate required fields
        $validator = Validator::make($request->all(), [
            'email' => 'required|string',
            'password' => 'required|string',
        ]);
        if ($validator->fails()) {
            Log::warning('Login validation failed', [
                'input' => $request->all(),
                'errors' => $validator->errors()
            ]);
            return response()->json([
                'error' => 'Validation failed.',
                'messages' => $validator->errors()
            ], 422);
        }

        try {
            $credentials = $request->only('email', 'password');
            $loginValue = $credentials['email'];
            Log::info('Login attempt', ['loginValue' => $loginValue]);

            // Try to find user by email or username
            $user = \App\Models\User::where('email', $loginValue)
                ->orWhere('name', $loginValue)
                ->with(['role', 'company'])
                ->first();

            if (!$user) {
                Log::warning('Login failed: user not found', ['loginValue' => $loginValue]);
                return response()->json(['error' => 'User not found.'], 401);
            }

            if (!\Illuminate\Support\Facades\Hash::check($credentials['password'], $user->password)) {
                Log::warning('Login failed: incorrect password', ['user_id' => $user->id]);
                return response()->json(['error' => 'Password is incorrect.'], 401);
            }

            // Allow unverified users to login if they're using temporary password (must_change_password flag)
            // But verified users who are deactivated should not be able to login
            if (!$user->verified && !($user->must_change_password ?? false)) {
                Log::warning('Login failed: account not verified', ['user_id' => $user->id]);
                return response()->json(['error' => 'Account not verified.'], 403);
            }

            // Ensure role is loaded
            $user->load('role', 'company');
            $roleName = $user->role ? $user->role->name : null;

            // Generate OTP for first-time login verification
            $otp = strtoupper(\Illuminate\Support\Str::random(6, '0123456789'));
            $cacheKey = 'login_otp_' . $user->id;
            
            // Store OTP in cache for 10 minutes
            Cache::put($cacheKey, $otp, now()->addMinutes(10));

            // Reset OTP resend count on fresh login
            $user->otp_resend_count = 0;
            $user->otp_resend_limit_reset_at = null;
            $user->save();

            // Send OTP email
            try {
                Mail::to($user->email)->send(new WelcomeOneTimePassword($user, $otp));
                Log::info('Login OTP sent', [
                    'user_id' => $user->id,
                    'email' => $user->email
                ]);
            } catch (\Throwable $e) {
                Log::error('Failed to send login OTP', [
                    'user_id' => $user->id,
                    'error' => $e->getMessage()
                ]);
                return response()->json(['error' => 'Failed to send OTP. Try again later.'], 500);
            }

            // Include must_change_password flag in response
            $mustChange = $user->must_change_password ?? false;

            Log::info('Login OTP flow initiated', [
                'user_id' => $user->id,
                'role' => $roleName,
                'company_id' => $user->company_id
            ]);

            return response()->json([
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'company_id' => $user->company_id,
                    'role_id' => $user->role_id,
                    'verified' => $user->verified,
                    'must_change_password' => $mustChange,
                    'role' => $user->role,
                    'company' => $user->company
                ],
                'role' => $roleName,
                'otp' => [
                    'sent' => true,
                    'destination' => substr_replace($user->email, '***', 1, strpos($user->email, '@') - 2),
                    'expires_in' => 600
                ],
                'must_change_password' => $mustChange
            ], 200);

        } catch (\Throwable $e) {
            Log::error('Login exception', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['error' => 'Server error: ' . $e->getMessage()], 500);
        }
    }

    // Superuser verifies a user
    public function verifyUser($id)
    {
        $user = User::findOrFail($id);
        $user->verified = true;
        $user->save();
        return response()->json(['message' => 'User verified.']);
    }

    // List users for verification (superuser only)
    public function unverifiedUsers()
    {
        $users = User::where('verified', false)->get();
        return response()->json(['users' => $users]);
    }

    // POST /change-password (auth:sanctum) - change password, supports first-login forced change
    public function changePassword(Request $request)
    {
        $user = Auth::user();
        if (! $user) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }

        $request->validate([
            'current_password' => 'nullable|string',
            'password' => 'required|string|min:8|confirmed'
        ]);

        // If user is not in forced-change state, require current password
        if (! ($user->must_change_password ?? false)) {
            if (empty($request->current_password) || ! Hash::check($request->current_password, $user->password)) {
                return response()->json(['error' => 'Current password is incorrect'], 422);
            }
        }

        $user->password = Hash::make($request->password);
        $user->must_change_password = false;
        $user->save();

        Log::info('User changed password', ['user_id' => $user->id]);

        return response()->json(['message' => 'Password updated successfully']);
    }

    // POST /login/verify-otp - verify OTP code sent during login
    public function verifyLoginOtp(Request $request)
    {
        $request->validate([
            'email' => 'required|string|email',
            'code' => 'required|string'
        ]);

        try {
            $user = User::where('email', $request->email)->first();
            if (!$user) {
                return response()->json(['error' => 'User not found.'], 404);
            }

            // Get cached OTP for this user
            $cacheKey = 'login_otp_' . $user->id;
            $storedOtp = \Illuminate\Support\Facades\Cache::get($cacheKey);

            if (!$storedOtp) {
                return response()->json(['error' => 'OTP expired. Request a new code.'], 410);
            }

            if (strtoupper($request->code) !== strtoupper($storedOtp)) {
                return response()->json(['error' => 'Invalid OTP code.'], 422);
            }

            // OTP is valid; clear it from cache
            \Illuminate\Support\Facades\Cache::forget($cacheKey);

            // Create a personal access token (Sanctum)
            Auth::login($user);
            $plainToken = '';
            if (method_exists($user, 'createToken')) {
                $tokenResult = $user->createToken('api-token');
                $plainToken = $tokenResult->plainTextToken ?? '';
            }

            $user->load('role', 'company');
            $roleName = $user->role ? $user->role->name : null;
            $mustChange = $user->must_change_password ?? false;

            Log::info('Login OTP verified successfully', [
                'user_id' => $user->id,
                'role' => $roleName
            ]);

            return response()->json([
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'company_id' => $user->company_id,
                    'role_id' => $user->role_id,
                    'verified' => $user->verified,
                    'must_change_password' => $mustChange,
                    'role' => $user->role,
                    'company' => $user->company
                ],
                'role' => $roleName,
                'token' => $plainToken,
                'must_change_password' => $mustChange
            ], 200);
        } catch (\Throwable $e) {
            Log::error('OTP verification exception', [
                'message' => $e->getMessage(),
                'email' => $request->email
            ]);
            return response()->json(['error' => 'Server error: ' . $e->getMessage()], 500);
        }
    }

    // POST /login/resend-otp - resend OTP to user email
    public function resendLoginOtp(Request $request)
    {
        $request->validate([
            'email' => 'required|string|email'
        ]);

        try {
            $user = User::where('email', $request->email)->first();
            if (!$user) {
                return response()->json(['error' => 'User not found.'], 404);
            }

            // Check if resend limit has expired (24 hours)
            if ($user->otp_resend_limit_reset_at && now()->greaterThan($user->otp_resend_limit_reset_at)) {
                // Reset the counter
                $user->otp_resend_count = 0;
                $user->otp_resend_limit_reset_at = null;
                $user->save();
            }

            // Track resend attempts (max 3 per 24 hours)
            $resendCount = $user->otp_resend_count ?? 0;

            if ($resendCount >= 3) {
                return response()->json([
                    'error' => 'OTP resend limit reached (3 attempts). Please contact support.',
                    'limit_reached' => true,
                    'resend_count' => $resendCount,
                    'remaining_attempts' => 0
                ], 429);
            }

            // Generate new OTP
            $otp = strtoupper(\Illuminate\Support\Str::random(6, '0123456789'));
            $cacheKey = 'login_otp_' . $user->id;

            // Store OTP in cache for 10 minutes
            Cache::put($cacheKey, $otp, now()->addMinutes(10));

            // Increment resend count and set reset time if first attempt
            $user->otp_resend_count = $resendCount + 1;
            if ($resendCount === 0) {
                $user->otp_resend_limit_reset_at = now()->addHours(24);
            }
            $user->save();

            // Send OTP email
            try {
                Mail::to($user->email)->send(new WelcomeOneTimePassword($user, $otp));
                Log::info('Resent OTP via email', [
                    'user_id' => $user->id,
                    'email' => $user->email,
                    'resend_count' => $user->otp_resend_count
                ]);
            } catch (\Throwable $e) {
                Log::error('Failed to resend OTP email', [
                    'user_id' => $user->id,
                    'error' => $e->getMessage()
                ]);
                return response()->json(['error' => 'Failed to send OTP. Try again later.'], 500);
            }

            $remainingAttempts = 3 - $user->otp_resend_count;

            return response()->json([
                'message' => 'OTP resent successfully.',
                'resend_count' => $user->otp_resend_count,
                'remaining_attempts' => $remainingAttempts,
                'destination' => substr_replace($user->email, '***', 1, strpos($user->email, '@') - 2)
            ], 200);

        } catch (\Throwable $e) {
            Log::error('Resend OTP exception', [
                'message' => $e->getMessage(),
                'email' => $request->email
            ]);
            return response()->json(['error' => 'Server error: ' . $e->getMessage()], 500);
        }
    }

    // POST /reset-otp-limit/{userId} - reset OTP resend limit for a user (superuser only)
    public function resetOtpLimit($userId)
    {
        $user = User::findOrFail($userId);
        $user->otp_resend_count = 0;
        $user->otp_resend_limit_reset_at = null;
        $user->save();

        Log::info('OTP resend limit reset', ['user_id' => $user->id, 'reset_by' => Auth::id()]);

        return response()->json([
            'message' => 'OTP resend limit has been reset for this user.',
            'user' => $user->only(['id', 'name', 'email', 'otp_resend_count'])
        ], 200);
    }

    // POST /forgot-password - request password reset OTP (public)
    public function forgotPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|string|email'
        ]);

        try {
            $user = User::where('email', $request->email)->first();
            if (!$user) {
                // Don't reveal if email exists or not (security best practice)
                return response()->json([
                    'message' => 'If an account exists with this email, a password reset code will be sent.',
                    'sent' => true
                ], 200);
            }

            // Generate 6-digit OTP for password reset
            $otp = strtoupper(\Illuminate\Support\Str::random(6, '0123456789'));
            $cacheKey = 'forgot_password_otp_' . $user->id;

            // Store OTP in cache for 15 minutes
            Cache::put($cacheKey, $otp, now()->addMinutes(15));

            // Send OTP email
            try {
                Mail::to($user->email)->send(new \App\Mail\WelcomeOneTimePassword($user, $otp));
                Log::info('Password reset OTP sent', [
                    'user_id' => $user->id,
                    'email' => $user->email
                ]);
            } catch (\Throwable $e) {
                Log::error('Failed to send password reset OTP', [
                    'user_id' => $user->id,
                    'error' => $e->getMessage()
                ]);
                return response()->json(['error' => 'Failed to send reset code. Try again later.'], 500);
            }

            return response()->json([
                'message' => 'If an account exists with this email, a password reset code will be sent.',
                'sent' => true,
                'destination' => substr_replace($user->email, '***', 1, strpos($user->email, '@') - 2)
            ], 200);

        } catch (\Throwable $e) {
            Log::error('Forgot password exception', [
                'message' => $e->getMessage(),
                'email' => $request->email
            ]);
            return response()->json(['error' => 'Server error: ' . $e->getMessage()], 500);
        }
    }

    // POST /forgot-password/verify-otp - verify reset OTP and reset password
    public function verifyResetOtp(Request $request)
    {
        $request->validate([
            'email' => 'required|string|email',
            'code' => 'required|string',
            'password' => 'required|string|min:8|confirmed'
        ]);

        try {
            $user = User::where('email', $request->email)->first();
            if (!$user) {
                return response()->json(['error' => 'User not found.'], 404);
            }

            // Get cached OTP for this user
            $cacheKey = 'forgot_password_otp_' . $user->id;
            $storedOtp = Cache::get($cacheKey);

            if (!$storedOtp) {
                return response()->json(['error' => 'Reset code expired. Request a new one.'], 410);
            }

            if (strtoupper($request->code) !== strtoupper($storedOtp)) {
                return response()->json(['error' => 'Invalid reset code.'], 422);
            }

            // OTP is valid; clear it from cache
            Cache::forget($cacheKey);

            // Update password
            $user->password = Hash::make($request->password);
            $user->must_change_password = false;
            $user->save();

            Log::info('Password reset successfully', [
                'user_id' => $user->id,
                'email' => $user->email
            ]);

            return response()->json([
                'message' => 'Password reset successfully. You can now login with your new password.',
                'success' => true
            ], 200);

        } catch (\Throwable $e) {
            Log::error('Verify reset OTP exception', [
                'message' => $e->getMessage(),
                'email' => $request->email
            ]);
            return response()->json(['error' => 'Server error: ' . $e->getMessage()], 500);
        }
    }
}

