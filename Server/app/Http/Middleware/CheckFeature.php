<?php

namespace App\Http\Middleware;

use App\Models\Subscription;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CheckFeature
{
    /**
     * Handle an incoming request.
     * 
     * Usage in routes:
     * Route::get('/sales', 'SalesController@index')->middleware('feature:sales');
     * Route::get('/mpesa', 'MpesaController@index')->middleware('feature:mpesa');
     * Route::get('/inventory', 'InventoryController@index')->middleware('feature:inventory,purchases');
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string  $features  Comma-separated list of features (user needs ONE of them)
     * @return mixed
     */
    public function handle(Request $request, Closure $next, ...$features)
    {
        $user = Auth::user();
        
        // If not authenticated, deny
        if (!$user) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }

        // Superuser has access to everything
        $user->load('role');
        $roleName = strtolower($user->role->name ?? '');
        if ($roleName === 'superuser') {
            return $next($request);
        }

        // For admin/cashier, check subscription features
        if (in_array($roleName, ['admin', 'cashier'])) {
            $alwaysOpenFeatures = ['sales', 'dashboard', 'view_dashboard', 'products', 'inventory', 'reports'];
            $normalizedRequested = array_map([$this, 'normalizeFeature'], $features);
            $requiresOnlyAlwaysOpen = !empty($normalizedRequested)
                && count(array_diff($normalizedRequested, $alwaysOpenFeatures)) === 0;

            if ($requiresOnlyAlwaysOpen) {
                return $next($request);
            }

            if (! $user->company_id) {
                return response()->json([
                    'error' => 'No company associated with user',
                    'reason' => 'no_company'
                ], 403);
            }

            $subscription = Subscription::with('plan')
                ->where('company_id', $user->company_id)
                ->orderByRaw("CASE WHEN status = 'active' THEN 0 WHEN on_trial = 1 THEN 1 ELSE 2 END")
                ->orderByDesc('starts_at')
                ->orderByDesc('id')
                ->first();
            
            if (!$subscription) {
                return response()->json([
                    'error' => 'No subscription found for company',
                    'reason' => 'no_subscription'
                ], 403);
            }

            $plan = $subscription->plan;
            if (!$plan) {
                return response()->json([
                    'error' => 'Invalid subscription plan',
                    'reason' => 'invalid_plan'
                ], 403);
            }

            $userFeatures = $this->extractFeatureKeys($plan->features ?? []);
            
            // Check if user has at least one of the required features
            $hasFeature = false;
            $normalizedAvailableFeatures = array_map([$this, 'normalizeFeature'], $userFeatures);
            foreach ($features as $feature) {
                $normalizedFeature = $this->normalizeFeature($feature);
                if (in_array($normalizedFeature, $normalizedAvailableFeatures, true)) {
                    $hasFeature = true;
                    break;
                }
            }

            if (!$hasFeature) {
                return response()->json([
                    'error' => 'Feature not available in your subscription',
                    'reason' => 'subscription_feature_unavailable',
                    'required_features' => $features,
                    'available_features' => $userFeatures
                ], 403);
            }
        }

        return $next($request);
    }

    /**
     * Normalize feature name for comparison
     */
    private function normalizeFeature($feature)
    {
        return strtolower(trim(str_replace(['-', ' '], '_', $feature)));
    }

    /**
     * Normalize feature payload from either string arrays or keyed maps.
     */
    private function extractFeatureKeys($rawFeatures): array
    {
        if (is_array($rawFeatures)) {
            $isList = array_is_list($rawFeatures);

            if (! $isList) {
                $keys = array_keys(array_filter($rawFeatures, fn ($value) => (bool) $value));
                return array_map([$this, 'normalizeFeature'], $keys);
            }

            return array_values(array_filter(array_map(function ($feature) {
                if (is_string($feature) || is_numeric($feature)) {
                    return $this->normalizeFeature((string) $feature);
                }

                if (is_array($feature)) {
                    $candidate = $feature['slug'] ?? $feature['key'] ?? $feature['name'] ?? $feature['feature'] ?? null;
                    return $candidate ? $this->normalizeFeature((string) $candidate) : null;
                }

                return null;
            }, $rawFeatures)));
        }

        return [];
    }
}
