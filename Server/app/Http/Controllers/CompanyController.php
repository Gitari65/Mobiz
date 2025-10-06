<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Company;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class CompanyController extends Controller
{
    public function registerCompany(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:6',
            'business_name' => 'required|string|max:255',
            'category' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'owner_name' => 'required|string|max:255',
            'owner_position' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 422);
        }

        // Create company (inactive by default)
        $company = Company::create([
    'name' => $request->business_name,
    'category' => $request->category,
    'phone' => $request->phone,
    'email' => $request->email,
    'owner_name' => $request->owner_name,
    'owner_position' => $request->owner_position,
    'approved' => false,
    'active' => false,
]);


        // Find or create admin role
        $adminRole = Role::firstOrCreate(['name' => 'Admin']);

        // Create user (admin of this company)
        $user = User::create([
            'name' => $request->owner_name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role_id' => $adminRole->id,
            'company_id' => $company->id,
            'verified' => false,
            'position' => $request->owner_position,

        ]);

        return response()->json([
            'message' => 'Company registration submitted successfully. Await approval.',
            'company' => $company,
            'user' => $user,
        ], 201);
    }
     // View all pending companies
    public function index()
    {
        $companies = Company::where('active', false)->get();
        return response()->json($companies);
    }

    // Approve a company
    public function approve($id)
    {
        $company = Company::findOrFail($id);
        $company->approved = true;
        $company->save();
        return response()->json(['message' => 'Company approved successfully', 'company' => $company]);
    }

    // Reject/Delete a company
    public function reject($id)
    {
        $company = Company::findOrFail($id);
        /*deactivate associated users or handle as needed*/
        $company->active = false;
        $company->approved = false;
        $company->save();
        return response()->json(['message' => 'Company registration rejected and removed']);
    }

    // View all approved companies
    public function approvedCompanies()
    {
        $companies = Company::where('approved', true)->get();
        return response()->json($companies);
    }
}
