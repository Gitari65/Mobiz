<?php

namespace App\Http\Controllers;

use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class FeatureToggleController extends Controller
{
    /**
     * Get feature toggles for a specific company or global
     */
    public function index(Request $request)
    {
        $user = $request->user();
        
        // Check if user is superuser
        $role = strtolower($user->role->name ?? '');
        if (!in_array($role, ['superuser', 'super user'])) {
            return response()->json(['error' => 'Unauthorized. SuperUser access required.'], 403);
        }

        $companyId = $request->query('company_id');

        $query = DB::table('feature_toggles');
        
        if ($companyId) {
            $query->where('company_id', $companyId);
        } else {
            $query->whereNull('company_id'); // Global features
        }

        $features = $query->get();

        return response()->json([
            'features' => $features,
            'company_id' => $companyId,
        ]);
    }

    /**
     * Toggle a feature for a company or globally
     */
    public function toggle(Request $request)
    {
        $user = $request->user();
        
        $role = strtolower($user->role->name ?? '');
        if (!in_array($role, ['superuser', 'super user'])) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $validator = Validator::make($request->all(), [
            'company_id' => 'nullable|exists:companies,id',
            'feature_key' => 'required|string',
            'is_enabled' => 'required|boolean',
            'description' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $feature = DB::table('feature_toggles')
            ->where('company_id', $request->company_id)
            ->where('feature_key', $request->feature_key)
            ->first();

        if ($feature) {
            // Update existing
            DB::table('feature_toggles')
                ->where('id', $feature->id)
                ->update([
                    'is_enabled' => $request->is_enabled,
                    'description' => $request->description ?? $feature->description,
                    'updated_at' => now(),
                ]);
        } else {
            // Create new
            DB::table('feature_toggles')->insert([
                'company_id' => $request->company_id,
                'feature_key' => $request->feature_key,
                'is_enabled' => $request->is_enabled,
                'description' => $request->description,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return response()->json([
            'message' => 'Feature toggle updated successfully',
            'feature_key' => $request->feature_key,
            'is_enabled' => $request->is_enabled,
        ]);
    }

    /**
     * Get all available features (for a company or admin to check)
     */
    public function available(Request $request)
    {
        $companyId = $request->query('company_id') ?? $request->user()->company_id;

        $features = [
            ['key' => 'expenses_module', 'name' => 'Expenses Module', 'description' => 'Enable expense tracking and management'],
            ['key' => 'multi_warehouse', 'name' => 'Multi-Warehouse', 'description' => 'Support for multiple warehouse locations'],
            ['key' => 'advanced_reports', 'name' => 'Advanced Reports', 'description' => 'Detailed analytics and custom reports'],
            ['key' => 'email_marketing', 'name' => 'Email Marketing', 'description' => 'Send promotional emails to customers'],
            ['key' => 'sms_notifications', 'name' => 'SMS Notifications', 'description' => 'Send SMS alerts and notifications'],
            ['key' => 'multi_currency', 'name' => 'Multi-Currency', 'description' => 'Support for multiple currencies'],
            ['key' => 'barcode_scanner', 'name' => 'Barcode Scanner', 'description' => 'Barcode scanning for products'],
            ['key' => 'loyalty_program', 'name' => 'Loyalty Program', 'description' => 'Customer rewards and points system'],
            ['key' => 'online_ordering', 'name' => 'Online Ordering', 'description' => 'Enable online order placement'],
            ['key' => 'api_access', 'name' => 'API Access', 'description' => 'Enable API for third-party integrations'],
        ];

        // Get enabled status for each feature
        foreach ($features as &$feature) {
            $toggle = DB::table('feature_toggles')
                ->where('company_id', $companyId)
                ->where('feature_key', $feature['key'])
                ->first();

            $feature['is_enabled'] = $toggle ? (bool) $toggle->is_enabled : false;
        }

        return response()->json([
            'features' => $features,
            'company_id' => $companyId,
        ]);
    }

    /**
     * Check if a feature is enabled for a company
     */
    public function isEnabled(Request $request, $featureKey)
    {
        $companyId = $request->query('company_id') ?? $request->user()->company_id;

        $toggle = DB::table('feature_toggles')
            ->where('company_id', $companyId)
            ->where('feature_key', $featureKey)
            ->first();

        return response()->json([
            'feature_key' => $featureKey,
            'is_enabled' => $toggle ? (bool) $toggle->is_enabled : false,
            'company_id' => $companyId,
        ]);
    }

    /**
     * Bulk update feature toggles
     */
    public function bulkUpdate(Request $request)
    {
        $user = $request->user();
        
        $role = strtolower($user->role->name ?? '');
        if (!in_array($role, ['superuser', 'super user'])) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $validator = Validator::make($request->all(), [
            'company_id' => 'nullable|exists:companies,id',
            'features' => 'required|array',
            'features.*.feature_key' => 'required|string',
            'features.*.is_enabled' => 'required|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $updated = [];

        foreach ($request->features as $featureData) {
            $feature = DB::table('feature_toggles')
                ->where('company_id', $request->company_id)
                ->where('feature_key', $featureData['feature_key'])
                ->first();

            if ($feature) {
                DB::table('feature_toggles')
                    ->where('id', $feature->id)
                    ->update([
                        'is_enabled' => $featureData['is_enabled'],
                        'updated_at' => now(),
                    ]);
            } else {
                DB::table('feature_toggles')->insert([
                    'company_id' => $request->company_id,
                    'feature_key' => $featureData['feature_key'],
                    'is_enabled' => $featureData['is_enabled'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            $updated[] = $featureData['feature_key'];
        }

        return response()->json([
            'message' => 'Feature toggles updated successfully',
            'updated_count' => count($updated),
            'features' => $updated,
        ]);
    }
}
