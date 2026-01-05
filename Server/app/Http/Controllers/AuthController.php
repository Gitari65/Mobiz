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
                ->first();

            if (!$user) {
                Log::warning('Login failed: user not found', ['loginValue' => $loginValue]);
                return response()->json(['error' => 'User not found.'], 401);
            }

            if (!\Illuminate\Support\Facades\Hash::check($credentials['password'], $user->password)) {
                Log::warning('Login failed: incorrect password', ['user_id' => $user->id]);
                return response()->json(['error' => 'Password is incorrect.'], 401);
            }

            if (!$user->verified) {
                Log::warning('Login failed: account not verified', ['user_id' => $user->id]);
                return response()->json(['error' => 'Account not verified.'], 403);
            }

            // Create a personal access token (Sanctum)
            Auth::login($user);
            $tokenName = 'api-token';
            $plainToken = '';
            if (method_exists($user, 'createToken')) {
                $tokenResult = $user->createToken($tokenName);
                $plainToken = $tokenResult->plainTextToken ?? '';
            }

            // Load relations and return role name explicitly so frontend can redirect correctly
            $user->load('role'); // ensures $user->role is available
            $roleName = $user->role ? $user->role->name : null;

            // include must_change_password flag in response
            $mustChange = $user->must_change_password ?? false;

            Log::info('Login successful', ['user_id' => $user->id, 'role' => $roleName]);
            return response()->json(['user' => $user, 'role' => $roleName, 'token' => $plainToken, 'must_change_password' => $mustChange], 200);

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
}
