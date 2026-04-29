<?php
namespace App\Http\Controllers;

use App\Models\PaymentMethod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Models\Subscription;

class PaymentMethodController extends Controller
{
    private function isDefaultEnabledMethod(string $methodName): bool
    {
        $normalized = strtolower(trim($methodName));
        return in_array($normalized, ['cash', 'm-pesa', 'mpesa', 'm pesa']);
    }

    private function requiredFeatureForMethod(string $methodName): ?string
    {
        return match ($this->normalizeFeature($methodName)) {
            'mpesa' => 'mpesa',
            default => null,
        };
    }

    private function normalizeFeature(string $feature): string
    {
        return strtolower(trim(str_replace(['-', ' '], '_', $feature)));
    }

    private function companyHasFeature($user, string $feature): bool
    {
        if (!$user || !$user->company_id) {
            return false;
        }

        $subscription = Subscription::with('plan')
            ->where('company_id', $user->company_id)
            ->first();

        $planFeatures = array_map(
            fn ($item) => $this->normalizeFeature((string) $item),
            (array) optional($subscription?->plan)->features
        );

        return in_array($this->normalizeFeature($feature), $planFeatures, true);
    }

    private function fallbackEnabledMethods($user): array
    {
        $methods = [
            ['id' => 1, 'name' => 'Cash', 'description' => 'Cash payment'],
        ];

        if ($this->companyHasFeature($user, 'mpesa')) {
            $methods[] = ['id' => 2, 'name' => 'M-Pesa', 'description' => 'Mobile money payment'];
        }

        return $methods;
    }

    private function buildCompanyMethods($user)
    {
        $methods = PaymentMethod::where('is_active', true)
            ->select('id', 'name', 'description', 'mpesa_type', 'mpesa_number', 'created_at')
            ->get();

        return $methods->map(function ($method) use ($user) {
            $requiredFeature = $this->requiredFeatureForMethod((string) $method->name);
            $allowedBySubscription = $requiredFeature ? $this->companyHasFeature($user, $requiredFeature) : true;

            $pivot = DB::table('company_payment_methods')
                ->where('company_id', $user->company_id)
                ->where('payment_method_id', $method->id)
                ->first();

            $defaultEnabled = $allowedBySubscription && $this->isDefaultEnabledMethod((string) $method->name);
            $isEnabled = $allowedBySubscription ? ($pivot ? (bool) $pivot->is_enabled : $defaultEnabled) : false;

            return [
                'id' => $method->id,
                'name' => $method->name,
                'description' => $method->description,
                'mpesa_type' => $method->mpesa_type,
                'mpesa_number' => $method->mpesa_number,
                'created_at' => $method->created_at,
                'is_enabled' => $isEnabled,
                'allowed_by_subscription' => $allowedBySubscription,
                'required_feature' => $requiredFeature,
                'locked_reason' => $allowedBySubscription ? null : 'Not available in your current subscription plan',
            ];
        })->values();
    }

    public function companyIndex(Request $request)
    {
        $user = Auth::guard('sanctum')->user() ?: $request->user();

        if (!$user || !in_array(strtolower($user->role->name ?? ''), ['admin', 'administrator'])) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        return response()->json($this->buildCompanyMethods($user));
    }

    public function setEnabled(Request $request, $id)
    {
        $user = Auth::guard('sanctum')->user() ?: $request->user();

        if (!$user || !in_array(strtolower($user->role->name ?? ''), ['admin', 'administrator'])) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'is_enabled' => 'required|boolean',
        ]);

        $method = PaymentMethod::findOrFail($id);
        $requiredFeature = $this->requiredFeatureForMethod((string) $method->name);

        if ($validated['is_enabled'] && $requiredFeature && ! $this->companyHasFeature($user, $requiredFeature)) {
            return response()->json([
                'error' => sprintf('%s is not available in your current subscription plan', $method->name),
                'reason' => 'subscription_feature_unavailable',
                'required_feature' => $requiredFeature,
            ], 403);
        }

        $pivot = DB::table('company_payment_methods')
            ->where('company_id', $user->company_id)
            ->where('payment_method_id', $method->id)
            ->first();

        if ($pivot) {
            DB::table('company_payment_methods')
                ->where('company_id', $user->company_id)
                ->where('payment_method_id', $method->id)
                ->update([
                    'is_enabled' => (bool) $validated['is_enabled'],
                    'updated_at' => now(),
                ]);
        } else {
            DB::table('company_payment_methods')->insert([
                'company_id' => $user->company_id,
                'payment_method_id' => $method->id,
                'is_enabled' => (bool) $validated['is_enabled'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return response()->json([
            'message' => 'Payment method updated successfully',
            'id' => (int) $method->id,
            'is_enabled' => (bool) $validated['is_enabled'],
        ]);
    }

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

    public function show($id)
    {
        $method = PaymentMethod::findOrFail($id);
        return response()->json($method);
    }

    public function destroy($id)
    {
        PaymentMethod::destroy($id);
        return response()->noContent();
    }

    public function toggle($id)
    {
        $user = Auth::guard('sanctum')->user();
        
        if (!$user) {
            return response()->json(['error' => 'Authentication required'], 401);
        }
        
        if (!in_array(strtolower($user->role->name), ['admin', 'administrator'])) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }
        
        $method = PaymentMethod::findOrFail($id);

        $requiredFeature = $this->requiredFeatureForMethod((string) $method->name);

        if ($requiredFeature && ! $this->companyHasFeature($user, $requiredFeature)) {
            return response()->json([
                'error' => sprintf('%s is not available in your current subscription plan', $method->name),
                'reason' => 'subscription_feature_unavailable',
                'required_feature' => $requiredFeature,
            ], 403);
        }
        
        $pivot = DB::table('company_payment_methods')
            ->where('company_id', $user->company_id)
            ->where('payment_method_id', $id)
            ->first();
        
        if ($pivot) {
            // Toggle existing status
            DB::table('company_payment_methods')
                ->where('company_id', $user->company_id)
                ->where('payment_method_id', $id)
                ->update([
                    'is_enabled' => !$pivot->is_enabled,
                    'updated_at' => now()
                ]);
        } else {
            // Create new entry - determine initial state based on defaults
            $defaultEnabled = in_array($method->name, ['Cash', 'M-Pesa']);
            DB::table('company_payment_methods')->insert([
                'company_id' => $user->company_id,
                'payment_method_id' => $id,
                'is_enabled' => !$defaultEnabled, // Toggle from default
                'created_at' => now(),
                'updated_at' => now()
            ]);
        }
        
        return response()->json(['message' => 'Payment method toggled successfully']);
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
                return response()->json($this->fallbackEnabledMethods($user));
            }

            $enabledMethods = collect($this->buildCompanyMethods($user))
                ->filter(fn ($method) => $method['allowed_by_subscription'] && $method['is_enabled'])
                ->map(fn ($method) => (object) [
                    'id' => $method['id'],
                    'name' => $method['name'],
                    'description' => $method['description'],
                    'mpesa_type' => $method['mpesa_type'],
                    'mpesa_number' => $method['mpesa_number'],
                ])
                ->values();

            // Safety fallback if table has no seeded methods yet.
            if ($enabledMethods->isEmpty()) {
                $enabledMethods = collect($this->fallbackEnabledMethods($user))->map(fn ($method) => (object) $method);
            }
            
            return response()->json($enabledMethods);
            
        } catch (\Exception $e) {
            Log::error('Payment methods error: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
            
            // Always return some payment methods to prevent frontend errors
            return response()->json($this->fallbackEnabledMethods($user));
        }
    }
}
