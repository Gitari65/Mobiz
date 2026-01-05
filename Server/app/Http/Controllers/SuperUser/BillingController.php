<?php
namespace App\Http\Controllers\SuperUser;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Subscription; // create model/migration as needed
use App\Models\AuditLog;

class BillingController extends Controller
{
    // ...existing code...

    public function activate($id)
    {
        $sub = Subscription::findOrFail($id);
        $sub->status = 'active';
        $sub->save();
        // Use auth()->id() to avoid calling ->id on null
        AuditLog::create([
            'action' => 'subscription_activate',
            'user_id' => auth()->id(),
            'auditable_type' => Subscription::class,
            'auditable_id' => $sub->getKey()
        ]);
        return response()->json(['message'=>'Activated']);
    }

    public function deactivate($id)
    {
        $sub = Subscription::findOrFail($id);
        $sub->status = 'inactive';
        $sub->save();
        // Use auth()->id() to avoid calling ->id on null
        AuditLog::create([
            'action' => 'subscription_deactivate',
            'user_id' => auth()->id(),
            'auditable_type' => Subscription::class,
            'auditable_id' => $sub->getKey()
        ]);
        return response()->json(['message'=>'Deactivated']);
    }

    public function renew(Request $request, $id)
    {
        $sub = Subscription::findOrFail($id);
        // implement billing renewal logic here (charge, create transaction)
        AuditLog::create([
            'action' => 'subscription_renew',
            'user_id' => auth()->id(),
            'auditable_type' => Subscription::class,
            'auditable_id' => $sub->getKey()
        ]);
        return response()->json(['message'=>'Renewal attempted (stub)']);
    }

    // ...existing code...
}