<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Subscription;
use App\Models\Company;

class SubscriptionController extends Controller
{
    // List all subscriptions
    public function index(Request $request)
    {
        $subs = Subscription::with('business')->get()->map(function($s) {
            return [
                'id' => $s->id,
                'business_name' => $s->business->name ?? '',
                'plan' => $s->plan,
                'status' => $s->status,
                'renewal_date' => $s->renewal_date,
                'features' => $s->features ?? [],
            ];
        });
        return response()->json($subs);
    }

    // Change plan
    public function changePlan($id, Request $request)
    {
        $request->validate(['plan' => 'required|string']);
        $sub = Subscription::findOrFail($id);
        $sub->plan = $request->plan;
        $sub->save();
        return response()->json(['success' => true]);
    }

    // Activate subscription
    public function activate($id)
    {
        $sub = Subscription::findOrFail($id);
        $sub->status = 'active';
        $sub->save();
        return response()->json(['success' => true]);
    }

    // Deactivate subscription
    public function deactivate($id)
    {
        $sub = Subscription::findOrFail($id);
        $sub->status = 'inactive';
        $sub->save();
        return response()->json(['success' => true]);
    }
}
