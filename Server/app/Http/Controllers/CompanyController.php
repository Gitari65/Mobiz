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
    /**
     * Get the authenticated admin's company profile
     */
    public function myCompany(Request $request)
    {
        $user = $request->user();
        if (! $user) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }
        $user->load('role');
        $roleName = strtolower($user->role->name ?? '');
        if (! in_array($roleName, ['admin', 'superuser'])) {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        if (! $user->company_id) {
            return response()->json(['company' => null]);
        }

        $company = Company::find($user->company_id);
        if (! $company) {
            return response()->json(['company' => null]);
        }

        return response()->json([
            'company' => $company
        ]);
    }

    /**
     * Update the authenticated admin's company profile
     */
    public function updateMyCompany(Request $request)
    {
        $user = $request->user();
        if (! $user) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }
        $user->load('role');
        $roleName = strtolower($user->role->name ?? '');
        if (! in_array($roleName, ['admin', 'superuser'])) {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        if (! $user->company_id) {
            return response()->json(['error' => 'No company associated'], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'category' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'kra_pin' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:100',
            'county' => 'nullable|string|max:100',
            'zip_code' => 'nullable|string|max:20',
            'country' => 'nullable|string|max:100',
            'owner_name' => 'nullable|string|max:255',
            'owner_position' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()->first()], 422);
        }

        $company = Company::findOrFail($user->company_id);
        $company->fill($request->only([
            'name','category','phone','email','kra_pin','address','city','county','zip_code','country','owner_name','owner_position'
        ]));
        $company->save();

        return response()->json([
            'message' => 'Company profile updated successfully',
            'company' => $company
        ]);
    }

    /**
     * Get the current company's subscription summary
     */
    public function mySubscription(Request $request)
    {
        $user = $request->user();
        if (! $user) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }
        $user->load('role');
        $roleName = strtolower($user->role->name ?? '');
        if (! in_array($roleName, ['admin', 'superuser'])) {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        if (! $user->company_id) {
            return response()->json(['subscription' => null]);
        }

        $subscription = \App\Models\Subscription::with(['plan'])
            ->where('company_id', $user->company_id)
            ->first();

        if (! $subscription) {
            return response()->json(['subscription' => null]);
        }

        $summary = [
            'id' => $subscription->id,
            'plan' => optional($subscription->plan)->name ?? null,
            'status' => $subscription->status,
            'starts_at' => $subscription->starts_at,
            'ends_at' => $subscription->ends_at,
            'trial_ends_at' => $subscription->trial_ends_at,
            'on_trial' => (bool) $subscription->on_trial,
            'monthly_fee' => $subscription->monthly_fee,
        ];

        return response()->json(['subscription' => $summary]);
    }

    public function getAllCompanies(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }

        $user->load('role');
        $roleName = strtolower($user->role->name ?? '');

        // SuperUser sees all companies, Admin sees only their own
        if ($roleName === 'superuser') {
            $companies = \App\Models\Company::select('id', 'name', 'email', 'phone', 'category')->get();
        } elseif ($roleName === 'admin' && $user->company_id) {
            $companies = \App\Models\Company::where('id', $user->company_id)->select('id', 'name', 'email', 'phone', 'category')->get();
        } else {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        return response()->json([
            'success' => true,
            'companies' => $companies
        ]);
    }

    public function registerCompany(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:6',
            'business_name' => 'required|string|max:255',
            'category' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'kra_pin' => 'required|string|max:20',
            'address' => 'required|string|max:255',
            'city' => 'required|string|max:100',
            'county' => 'required|string|max:100',
            'zip_code' => 'nullable|string|max:20',
            'country' => 'required|string|max:100',
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
            'kra_pin' => $request->kra_pin,
            'address' => $request->address,
            'city' => $request->city,
            'county' => $request->county,
            'zip_code' => $request->zip_code,
            'country' => $request->country,
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
        $companies = Company::withCount('users')->orderBy('created_at', 'desc')->get();
        \Log::info('Fetching companies:', ['count' => $companies->count()]);
        return response()->json(['data' => $companies]);
    }

    // Store a new company
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'nullable|email|max:255|unique:companies,email',
                'phone' => 'nullable|string|max:50',
                'category' => 'nullable|string|max:100',
                'address' => 'nullable|string|max:500'
            ]);

            $validated['active'] = true;
            $validated['approved'] = true;

            $company = Company::create($validated);

            return response()->json([
                'message' => 'Business created successfully',
                'company' => $company
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['error' => 'Invalid input', 'details' => $e->errors()], 422);
        } catch (\Exception $e) {
            \Log::error('Failed to create company: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to create business'], 500);
        }
    }

    // Update a company
    public function update(Request $request, $id)
    {
        try {
            $company = Company::findOrFail($id);

            $validated = $request->validate([
                'name' => 'sometimes|required|string|max:255',
                'email' => 'nullable|email|max:255|unique:companies,email,' . $id,
                'phone' => 'nullable|string|max:50',
                'category' => 'nullable|string|max:100',
                'address' => 'nullable|string|max:500'
            ]);

            $company->update($validated);

            return response()->json([
                'message' => 'Business updated successfully',
                'company' => $company
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['error' => 'Invalid input', 'details' => $e->errors()], 422);
        } catch (\Exception $e) {
            \Log::error('Failed to update company: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to update business'], 500);
        }
    }

    // Delete a company
    public function destroy($id)
    {
        try {
            $company = Company::findOrFail($id);
            
            // Check if company has users
            if ($company->users()->count() > 0) {
                return response()->json([
                    'error' => 'Cannot delete business with active users. Please remove or reassign users first.'
                ], 400);
            }

            $company->delete();

            return response()->json(['message' => 'Business deleted successfully']);

        } catch (\Exception $e) {
            \Log::error('Failed to delete company: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to delete business'], 500);
        }
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

    // Get all companies for dropdowns (superuser)
    public function getCompanies()
    {
        $companies = Company::all();
        return response()->json(['companies' => $companies, 'data' => $companies]);
    }
}
