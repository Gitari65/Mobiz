<?php
namespace App\Http\Controllers\SuperUser;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Subscription;
use App\Models\Plan;
use App\Models\Transaction;
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
}
