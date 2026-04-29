<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Company;
use App\Models\Role;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\SubscriptionUpgradeRequest;
use App\Models\AppNotification;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Mail;
use App\Mail\CompanyRegistrationPending;
use App\Mail\CompanyVerified;
use App\Services\PriceGroupService;
use App\Services\SubscriptionPlanService;

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

        if (! $user->company_id) {
            return response()->json(['subscription' => null]);
        }

        $subscription = \App\Models\Subscription::with(['plan'])
            ->where('company_id', $user->company_id)
            ->orderByRaw("CASE WHEN status = 'active' THEN 0 WHEN on_trial = 1 THEN 1 ELSE 2 END")
            ->orderByDesc('starts_at')
            ->orderByDesc('id')
            ->first();

        if (! $subscription) {
            return response()->json(['subscription' => null]);
        }

        $summary = [
            'id' => $subscription->id,
            'plan' => optional($subscription->plan)->name ?? null,
            'plan_slug' => optional($subscription->plan)->slug ?? null,
            'features' => array_values(optional($subscription->plan)->features ?? []),
            'status' => $subscription->status,
            'starts_at' => $subscription->starts_at,
            'ends_at' => $subscription->ends_at,
            'trial_ends_at' => $subscription->trial_ends_at,
            'on_trial' => (bool) $subscription->on_trial,
            'monthly_fee' => $subscription->monthly_fee,
        ];

        return response()->json(['subscription' => $summary]);
    }

    /**
     * List active subscription plans available to this company.
     */
    public function subscriptionPlans(Request $request)
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

        $plans = Plan::where('is_active', true)
            ->orderBy('price')
            ->get(['id', 'name', 'slug', 'description', 'price', 'billing_cycle', 'features']);

        return response()->json(['plans' => $plans]);
    }

    /**
     * Create or update current company's subscription.
     */
    public function upsertMySubscription(Request $request)
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

        $validated = $request->validate([
            'plan_id' => 'required|exists:plans,id',
        ]);

        $plan = Plan::where('id', $validated['plan_id'])
            ->where('is_active', true)
            ->first();

        if (! $plan) {
            return response()->json(['error' => 'Selected plan is not active'], 422);
        }

        $subscription = Subscription::where('company_id', $user->company_id)->first();

        if (! $subscription) {
            $subscription = Subscription::create([
                'company_id' => $user->company_id,
                'plan_id' => $plan->id,
                'status' => 'active',
                'starts_at' => now(),
                'ends_at' => null,
                'trial_ends_at' => null,
                'on_trial' => false,
                'monthly_fee' => $plan->price,
            ]);
        } else {
            $subscription->plan_id = $plan->id;
            $subscription->monthly_fee = $plan->price;
            $subscription->status = 'active';
            $subscription->starts_at = $subscription->starts_at ?: now();
            $subscription->save();
        }

        $subscription->load('plan');

        return response()->json([
            'message' => 'Subscription saved successfully',
            'subscription' => [
                'id' => $subscription->id,
                'plan' => optional($subscription->plan)->name,
                'plan_slug' => optional($subscription->plan)->slug,
                'features' => array_values(optional($subscription->plan)->features ?? []),
                'status' => $subscription->status,
                'starts_at' => $subscription->starts_at,
                'ends_at' => $subscription->ends_at,
                'trial_ends_at' => $subscription->trial_ends_at,
                'on_trial' => (bool) $subscription->on_trial,
                'monthly_fee' => $subscription->monthly_fee,
            ]
        ]);
    }

    /**
     * Renew current company's subscription by one billing cycle.
     */
    public function renewMySubscription(Request $request)
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

        $subscription = Subscription::with('plan')
            ->where('company_id', $user->company_id)
            ->first();

        if (! $subscription) {
            return response()->json(['error' => 'No subscription found'], 404);
        }

        $cycleMonths = optional($subscription->plan)->billing_cycle === 'annual' ? 12 : 1;
        $baseDate = $subscription->ends_at
            ? Carbon::parse($subscription->ends_at)
            : now();
        if ($baseDate->lt(now())) {
            $baseDate = now();
        }

        $subscription->ends_at = $baseDate->copy()->addMonths($cycleMonths);
        $subscription->status = 'active';
        $subscription->save();

        return response()->json(['message' => 'Subscription renewed successfully']);
    }

    /**
     * Cancel current company's subscription.
     */
    public function cancelMySubscription(Request $request)
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

        $subscription = Subscription::where('company_id', $user->company_id)->first();
        if (! $subscription) {
            return response()->json(['error' => 'No subscription found'], 404);
        }

        $subscription->status = 'canceled';
        $subscription->save();

        return response()->json(['message' => 'Subscription canceled successfully']);
    }

    /**
     * Reactivate current company's subscription.
     */
    public function activateMySubscription(Request $request)
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

        $subscription = Subscription::where('company_id', $user->company_id)->first();
        if (! $subscription) {
            return response()->json(['error' => 'No subscription found'], 404);
        }

        $subscription->status = 'active';
        $subscription->starts_at = $subscription->starts_at ?: now();
        $subscription->save();

        return response()->json(['message' => 'Subscription activated successfully']);
    }

    /**
     * Request a subscription plan upgrade (creates a pending request for superuser approval).
     */
    public function requestUpgrade(Request $request)
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

        $validated = $request->validate([
            'plan_id'      => 'required|exists:plans,id',
            'admin_notes'  => 'nullable|string|max:1000',
        ]);

        $plan = Plan::where('id', $validated['plan_id'])->where('is_active', true)->first();
        if (! $plan) {
            return response()->json(['error' => 'Selected plan is not active'], 422);
        }

        $subscription = Subscription::where('company_id', $user->company_id)->first();

        // Block if requesting the same plan already active
        if ($subscription && $subscription->plan_id == $plan->id) {
            return response()->json(['error' => 'You are already on this plan'], 422);
        }

        // Block if there is already a pending request for this company
        $pending = SubscriptionUpgradeRequest::where('company_id', $user->company_id)
            ->where('status', 'pending')
            ->first();

        if ($pending) {
            return response()->json([
                'error' => 'You already have a pending upgrade request. Please wait for superuser review.',
            ], 422);
        }

        $upgradeRequest = SubscriptionUpgradeRequest::create([
            'company_id'        => $user->company_id,
            'subscription_id'   => $subscription?->id,
            'current_plan_id'   => $subscription?->plan_id,
            'requested_plan_id' => $plan->id,
            'requested_by'      => $user->id,
            'status'            => 'pending',
            'admin_notes'       => $validated['admin_notes'] ?? null,
        ]);

        // Notify all superusers
        $company = $user->company ?? \App\Models\Company::find($user->company_id);
        $companyName = $company?->name ?? 'A company';
        AppNotification::notifyRole(
            'superuser',
            "{$companyName} has requested an upgrade to {$plan->name}.",
            'info',
            ['upgrade_request_id' => $upgradeRequest->id, 'company_id' => $user->company_id]
        );

        return response()->json([
            'message' => 'Upgrade request submitted. Awaiting superuser approval.',
            'request' => $upgradeRequest->load(['currentPlan', 'requestedPlan']),
        ], 201);
    }

    /**
     * Get the current company's latest upgrade request (so admin tab can show pending status).
     */
    public function myUpgradeRequest(Request $request)
    {
        $user = $request->user();
        if (! $user) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }

        if (! $user->company_id) {
            return response()->json(['upgrade_request' => null]);
        }

        $req = SubscriptionUpgradeRequest::with(['currentPlan', 'requestedPlan', 'reviewedBy'])
            ->where('company_id', $user->company_id)
            ->latest()
            ->first();

        if (! $req) {
            return response()->json(['upgrade_request' => null]);
        }

        return response()->json([
            'upgrade_request' => [
                'id'             => $req->id,
                'status'         => $req->status,
                'current_plan'   => optional($req->currentPlan)->name,
                'requested_plan' => optional($req->requestedPlan)->name,
                'admin_notes'    => $req->admin_notes,
                'reviewer_notes' => $req->reviewer_notes,
                'reviewed_at'    => $req->reviewed_at,
                'created_at'     => $req->created_at,
            ],
        ]);
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

        PriceGroupService::ensureDefaultsForCompany($company->id);
        SubscriptionPlanService::ensureCompanyDefaultSubscription($company->id);


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

        // Send notification email to superusers about pending registration
        $superuserRole = Role::where('name', 'Superuser')->first();
        if ($superuserRole) {
            $superusers = User::where('role_id', $superuserRole->id)->get();
            foreach ($superusers as $superuser) {
                Mail::to($superuser->email)->send(new CompanyRegistrationPending($company, $user));
            }
        }

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

            PriceGroupService::ensureDefaultsForCompany($company->id);
            SubscriptionPlanService::ensureCompanyDefaultSubscription($company->id);

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
        $company->active = true;
        $company->save();

        // Generate a default password for the admin user
        $defaultPassword = \Illuminate\Support\Str::random(12);
        
        // Find the admin user of this company and update their password
        $admin = User::where('company_id', $company->id)
                    ->whereHas('role', function($q) {
                        $q->where('name', 'Admin');
                    })
                    ->first();
        
        if ($admin) {
            $admin->password = Hash::make($defaultPassword);
            $admin->verified = true;
            $admin->save();
            
            // Send approval email with credentials to the admin
            Mail::to($admin->email)->send(new CompanyVerified($company, $admin, $defaultPassword));
        }

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
