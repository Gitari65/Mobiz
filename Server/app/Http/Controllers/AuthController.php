<?php
namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

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
        $credentials = $request->only('email', 'password');
        $user = User::where('email', $credentials['email'])->first();
        if ($user) {
            if (Hash::check($credentials['password'], $user->password)) {
                if (!$user->verified) {
                    return response()->json(['error' => 'Account not verified.'], 403);
                }
                Auth::login($user);
                return response()->json(['user' => $user], 200);
            } else {
                return response()->json(['error' => 'Password is incorrect.'], 401);
            }
        }
        return response()->json(['error' => 'Email not found.'], 401);
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
