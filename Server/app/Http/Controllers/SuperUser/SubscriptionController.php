<?php
namespace App\Http\Controllers\SuperUser;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Subscription;
use App\Models\Plan;
use App\Models\Transaction;
use App\Models\SubscriptionUpgradeRequest;
use App\Models\AppNotification;
use Carbon\Carbon;

class SubscriptionController extends Controller
{
    // GET /api/super/subscriptions - list subscriptions
    public function index(Request $request)
    {
        $query = Subscription::with('company', 'plan');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('q')) {
            $q = $request->q;
            $query->whereHas('company', fn($qr) => $qr->where('name', 'like', "%$q%"));
        }

        $subscriptions = $query->orderBy('created_at', 'desc')->paginate(25);
        return response()->json($subscriptions);
    }

    // GET /api/super/subscriptions/{id} - get subscription with transactions
    public function show($id)
    {
        $sub = Subscription::with('company', 'plan', 'transactions')->findOrFail($id);
        return response()->json($sub);
    }

    // PATCH /api/super/subscriptions/{id}/activate
    public function activate($id)
    {
        $sub = Subscription::findOrFail($id);
        $sub->status = 'active';
        $sub->starts_at = $sub->starts_at ?? now();
        $sub->save();
        return response()->json(['message' => 'Subscription activated']);
    }

    // PATCH /api/super/subscriptions/{id}/deactivate
    public function deactivate($id)
    {
        $sub = Subscription::findOrFail($id);
        $sub->status = 'inactive';
        $sub->save();
        return response()->json(['message' => 'Subscription deactivated']);
    }

    // POST /api/super/subscriptions/{id}/renew - manually renew subscription
    public function renew(Request $request, $id)
    {
        $sub = Subscription::findOrFail($id);
        $plan = $sub->plan;

        // Create transaction
        $transaction = Transaction::create([
            'subscription_id' => $sub->id,
            'amount' => $plan->price,
            'currency' => 'USD',
            'status' => 'completed',
            'payment_method' => 'manual',
            'description' => "Manual renewal for {$plan->name}",
            'processed_at' => now()
        ]);

        // Extend subscription
        $cycleMonths = $plan->billing_cycle === 'annual' ? 12 : 1;
        $sub->ends_at = $sub->ends_at ? $sub->ends_at->addMonths($cycleMonths) : now()->addMonths($cycleMonths);
        $sub->status = 'active';
        $sub->save();

        return response()->json(['message' => 'Subscription renewed', 'transaction' => $transaction]);
    }

    // POST /api/super/subscriptions/{id}/trial - assign free trial
    public function assignTrial(Request $request, $id)
    {
        $request->validate(['trial_days' => 'required|integer|min:1|max:365']);

        $sub = Subscription::findOrFail($id);
        $sub->on_trial = true;
        $sub->trial_ends_at = now()->addDays($request->trial_days);
        $sub->save();

        return response()->json(['message' => 'Free trial assigned']);
    }

    // GET /api/super/subscriptions/{id}/transactions - payment history
    public function transactions($id)
    {
        $sub = Subscription::findOrFail($id);
        $transactions = $sub->transactions()->orderBy('created_at', 'desc')->paginate(20);
        return response()->json($transactions);
    }

    // Plan management
    // GET /api/super/plans
    public function listPlans(Request $request)
    {
        $query = Plan::query();

        if ($request->filled('active')) {
            $query->where('is_active', filter_var($request->active, FILTER_VALIDATE_BOOLEAN));
        }

        $plans = $query->orderBy('price')->get();
        return response()->json(['plans' => $plans]);
    }

    // POST /api/super/plans - create plan
    public function storePlan(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|unique:plans,name',
            'slug' => 'required|string|unique:plans,slug',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'billing_cycle' => 'required|in:monthly,annual,custom',
            'features' => 'nullable|array'
        ]);

        $plan = Plan::create($data);
        return response()->json($plan, 201);
    }

    // PUT /api/super/plans/{id} - update plan
    public function updatePlan(Request $request, $id)
    {
        $plan = Plan::findOrFail($id);

        $data = $request->validate([
            'name' => 'nullable|string|unique:plans,name,' . $id,
            'description' => 'nullable|string',
            'price' => 'nullable|numeric|min:0',
            'billing_cycle' => 'nullable|in:monthly,annual,custom',
            'features' => 'nullable|array',
            'is_active' => 'nullable|boolean'
        ]);

        $plan->update($data);
        return response()->json($plan);
    }

    // DELETE /api/super/plans/{id}
    public function deletePlan($id)
    {
        $plan = Plan::findOrFail($id);
        $plan->delete();
        return response()->json(['message' => 'Plan deleted']);
    }

    // PUT /api/super/subscriptions/{id}/plan - change a subscription's plan
    public function changePlan(Request $request, $id)
    {
        $request->validate(['plan_id' => 'required|exists:plans,id']);

        $sub = Subscription::with('plan')->findOrFail($id);
        $plan = Plan::where('id', $request->plan_id)->where('is_active', true)->first();

        if (! $plan) {
            return response()->json(['error' => 'Selected plan is not active'], 422);
        }

        $sub->plan_id = $plan->id;
        $sub->monthly_fee = $plan->price;
        $sub->status = 'active';
        $sub->starts_at = $sub->starts_at ?? now();
        $sub->save();

        return response()->json([
            'message' => 'Plan changed successfully',
            'subscription' => $sub->fresh(['company', 'plan']),
        ]);
    }

    // POST /api/super/subscriptions/company/{companyId} - create subscription for a company
    public function createForCompany(Request $request, $companyId)
    {
        $request->validate(['plan_id' => 'required|exists:plans,id']);

        $plan = Plan::where('id', $request->plan_id)->where('is_active', true)->first();
        if (! $plan) {
            return response()->json(['error' => 'Selected plan is not active'], 422);
        }

        $existing = Subscription::where('company_id', $companyId)->first();
        if ($existing) {
            return response()->json(['error' => 'Company already has a subscription. Use the plan change action instead.'], 422);
        }

        $company = \App\Models\Company::findOrFail($companyId);

        $sub = Subscription::create([
            'company_id' => $companyId,
            'plan_id' => $plan->id,
            'status' => 'active',
            'starts_at' => now(),
            'ends_at' => null,
            'trial_ends_at' => null,
            'on_trial' => false,
            'monthly_fee' => $plan->price,
        ]);

        return response()->json([
            'message' => "Subscription created for {$company->name}",
            'subscription' => $sub->load(['company', 'plan']),
        ], 201);
    }

    // GET /api/super/companies-without-subscription - list companies that have no subscription yet
    public function companiesWithoutSubscription()
    {
        $withSub = Subscription::pluck('company_id');
        $companies = \App\Models\Company::whereNotIn('id', $withSub)
            ->select('id', 'name', 'email')
            ->orderBy('name')
            ->get();

        return response()->json(['companies' => $companies]);
    }
    // --- Upgrade Requests ---

    // GET /api/super/upgrade-requests
    public function listUpgradeRequests(Request $request)
    {
        $query = SubscriptionUpgradeRequest::with([
            'company:id,name,email',
            'currentPlan:id,name',
            'requestedPlan:id,name,price,billing_cycle',
            'requestedBy:id,name,email',
            'reviewedBy:id,name',
        ]);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $requests = $query->latest()->paginate(25);

        return response()->json($requests);
    }

    // POST /api/super/upgrade-requests/{id}/approve
    public function approveUpgrade(Request $request, $id)
    {
        $upgradeReq = SubscriptionUpgradeRequest::with('requestedPlan')->findOrFail($id);

        if ($upgradeReq->status !== 'pending') {
            return response()->json(['error' => 'This request is no longer pending'], 422);
        }

        $plan = $upgradeReq->requestedPlan;
        if (! $plan || ! $plan->is_active) {
            return response()->json(['error' => 'Requested plan is no longer active'], 422);
        }

        // Apply the plan change
        $subscription = Subscription::where('company_id', $upgradeReq->company_id)->first();
        if (! $subscription) {
            $subscription = Subscription::create([
                'company_id'  => $upgradeReq->company_id,
                'plan_id'     => $plan->id,
                'status'      => 'active',
                'starts_at'   => now(),
                'monthly_fee' => $plan->price,
                'on_trial'    => false,
            ]);
        } else {
            $subscription->plan_id     = $plan->id;
            $subscription->monthly_fee = $plan->price;
            $subscription->status      = 'active';
            $subscription->starts_at   = $subscription->starts_at ?? now();
            $subscription->save();
        }

        // Mark request approved
        $upgradeReq->status        = 'approved';
        $upgradeReq->reviewed_by   = $request->user()?->id;
        $upgradeReq->reviewer_notes = $request->input('reviewer_notes');
        $upgradeReq->reviewed_at   = now();
        $upgradeReq->subscription_id = $subscription->id;
        $upgradeReq->save();

        // Notify the admin who requested
        if ($upgradeReq->requested_by) {
            AppNotification::notify(
                $upgradeReq->requested_by,
                "Your upgrade request to {$plan->name} has been approved!",
                'success',
                ['upgrade_request_id' => $upgradeReq->id]
            );
        }

        return response()->json([
            'message' => "Upgrade approved. Company is now on {$plan->name}.",
        ]);
    }

    // POST /api/super/upgrade-requests/{id}/reject
    public function rejectUpgrade(Request $request, $id)
    {
        $upgradeReq = SubscriptionUpgradeRequest::findOrFail($id);

        if ($upgradeReq->status !== 'pending') {
            return response()->json(['error' => 'This request is no longer pending'], 422);
        }

        $upgradeReq->status         = 'rejected';
        $upgradeReq->reviewed_by    = $request->user()?->id;
        $upgradeReq->reviewer_notes = $request->input('reviewer_notes');
        $upgradeReq->reviewed_at    = now();
        $upgradeReq->save();

        // Notify the admin who requested
        if ($upgradeReq->requested_by) {
            $reason = $request->input('reviewer_notes') ? " Reason: {$request->input('reviewer_notes')}" : '';
            AppNotification::notify(
                $upgradeReq->requested_by,
                "Your subscription upgrade request has been declined.{$reason}",
                'error',
                ['upgrade_request_id' => $upgradeReq->id]
            );
        }

        return response()->json(['message' => 'Upgrade request rejected.']);
    }}
