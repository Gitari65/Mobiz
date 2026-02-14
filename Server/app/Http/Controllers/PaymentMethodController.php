<?php
namespace App\Http\Controllers;

use App\Models\PaymentMethod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Auth;

class PaymentMethodController extends Controller
{
    public function index()
    {
        return PaymentMethod::all();
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
            'mpesa_type' => 'nullable|in:till,paybill',
            'mpesa_number' => 'nullable|string',
        ]);
        return PaymentMethod::create($data);
    }

    public function destroy($id)
    {
        PaymentMethod::destroy($id);
        return response()->noContent();
    }

    public function enabled()
    {
        try {
            // Use Auth::guard('sanctum')->user() for API authentication
            $user = Auth::guard('sanctum')->user();
            
            if (!$user) {
                return response()->json(['error' => 'Authentication required'], 401);
            }

            if (!$user->company_id) {
                return response()->json(['error' => 'No company associated with account'], 403);
            }
            
            // Check if required tables exist
            if (!Schema::hasTable('payment_methods') || !Schema::hasTable('company_payment_methods')) {
                // Return default payment methods if tables don't exist
                return response()->json([
                    ['id' => 1, 'name' => 'Cash', 'description' => 'Cash payment'],
                    ['id' => 2, 'name' => 'M-Pesa', 'description' => 'Mobile money payment']
                ]);
            }
            
            // Get only enabled payment methods for this company
            $enabledMethods = DB::table('company_payment_methods')
                ->join('payment_methods', 'company_payment_methods.payment_method_id', '=', 'payment_methods.id')
                ->where('company_payment_methods.company_id', $user->company_id)
                ->where('company_payment_methods.is_enabled', true)
                ->where('payment_methods.is_active', true)
                ->select('payment_methods.id', 'payment_methods.name', 'payment_methods.description')
                ->get();
            
            // If no methods configured, return defaults
            if ($enabledMethods->isEmpty()) {
                $enabledMethods = PaymentMethod::where('is_active', true)
                    ->whereIn('name', ['Cash', 'M-Pesa'])
                    ->select('id', 'name', 'description')
                    ->get();
                
                // If PaymentMethod model fails, return hardcoded defaults
                if ($enabledMethods->isEmpty()) {
                    $enabledMethods = collect([
                        (object)['id' => 1, 'name' => 'Cash', 'description' => 'Cash payment'],
                        (object)['id' => 2, 'name' => 'M-Pesa', 'description' => 'Mobile money payment']
                    ]);
                }
            }
            
            return response()->json($enabledMethods);
            
        } catch (\Exception $e) {
            \Log::error('Payment methods error: ' . $e->getMessage());
            \Log::error('Stack trace: ' . $e->getTraceAsString());
            
            // Always return some payment methods to prevent frontend errors
            return response()->json([
                ['id' => 1, 'name' => 'Cash', 'description' => 'Cash payment'],
                ['id' => 2, 'name' => 'M-Pesa', 'description' => 'Mobile money payment']
            ]);
        }
    }
}
