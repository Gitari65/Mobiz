<?php
namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;


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
            'password' => 'required|string|min:6',
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

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role_id' => $request->role_id,
            'company_id' => $company->id,
            'created_by_user_id' => Auth::id(),
            'verified' => false, // Only superuser can verify
        ]);

        // Automatically assign a warehouse to the new user
        $warehouseName = \App\Models\Warehouse::generateNameForUser($user);
        \App\Models\Warehouse::create([
            'name' => $warehouseName,
            'user_id' => $user->id,
            'type' => 'user',
        ]);

        return response()->json(['user' => $user, 'company' => $company], 201);
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

            \Illuminate\Support\Facades\Auth::login($user);

            // Load relations and return role name explicitly so frontend can redirect correctly
            $user->load('role'); // ensures $user->role is available
            $roleName = $user->role ? $user->role->name : null;

            Log::info('Login successful', ['user_id' => $user->id, 'role' => $roleName]);
            return response()->json(['user' => $user, 'role' => $roleName], 200);

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
}
