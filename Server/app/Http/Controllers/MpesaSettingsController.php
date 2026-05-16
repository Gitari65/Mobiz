<?php

namespace App\Http\Controllers;

use App\Models\CompanySetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class MpesaSettingsController extends Controller
{
    /**
     * Get company M-Pesa settings
     */
    public function getSettings(Request $request)
    {
        $user = $request->user();
        if (!$user || !$user->company_id) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }

        $setting = CompanySetting::where('company_id', $user->company_id)->first();
        
        if (!$setting) {
            // Return default settings if none exist
            return response()->json([
                'mpesa_enabled' => false,
                'mpesa_type' => null,
                'mpesa_number' => null,
                'mpesa_business_name' => null,
            ]);
        }

        return response()->json([
            'mpesa_enabled' => $setting->mpesa_enabled,
            'mpesa_type' => $setting->mpesa_type,
            'mpesa_number' => $setting->mpesa_number,
            'mpesa_business_name' => $setting->mpesa_business_name,
        ]);
    }

    /**
     * Update company M-Pesa settings
     * Validates that PayBill/Till details are provided when enabling
     */
    public function updateSettings(Request $request)
    {
        $user = $request->user();
        if (!$user || !$user->company_id) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }

        // Check authorization - must be admin
        if (!in_array(strtolower($user->role->name ?? ''), ['admin', 'administrator'])) {
            return response()->json(['error' => 'Unauthorized - Admin access required'], 403);
        }

        $validated = $request->validate([
            'mpesa_enabled' => 'required|boolean',
            'mpesa_type' => 'nullable|in:till,paybill',
            'mpesa_number' => 'nullable|string|max:20',
            'mpesa_business_name' => 'nullable|string|max:100',
        ]);

        // Validation: If enabling M-Pesa, PayBill/Till details must be provided
        if ($validated['mpesa_enabled']) {
            if (!$validated['mpesa_type'] || !$validated['mpesa_number']) {
                return response()->json([
                    'error' => 'M-Pesa configuration incomplete',
                    'message' => 'PayBill/Till type and number are required to enable M-Pesa',
                    'required_fields' => ['mpesa_type', 'mpesa_number'],
                ], 422);
            }

            // Validate PayBill/Till number format (typically 5-6 digits)
            if (!preg_match('/^\d{5,9}$/', $validated['mpesa_number'])) {
                return response()->json([
                    'error' => 'Invalid M-Pesa number format',
                    'message' => 'PayBill/Till number must be 5-9 digits',
                ], 422);
            }
        }

        $setting = CompanySetting::where('company_id', $user->company_id)->first();

        if (!$setting) {
            // Create new settings
            $setting = CompanySetting::create([
                'company_id' => $user->company_id,
                'mpesa_enabled' => $validated['mpesa_enabled'],
                'mpesa_type' => $validated['mpesa_type'],
                'mpesa_number' => $validated['mpesa_number'],
                'mpesa_business_name' => $validated['mpesa_business_name'],
            ]);
        } else {
            // Update existing settings
            $setting->update($validated);
        }

        Log::info('M-Pesa settings updated', [
            'company_id' => $user->company_id,
            'user_id' => $user->id,
            'mpesa_enabled' => $validated['mpesa_enabled'],
            'mpesa_type' => $validated['mpesa_type'],
        ]);

        return response()->json([
            'message' => 'M-Pesa settings updated successfully',
            'data' => [
                'mpesa_enabled' => $setting->mpesa_enabled,
                'mpesa_type' => $setting->mpesa_type,
                'mpesa_number' => $setting->mpesa_number,
                'mpesa_business_name' => $setting->mpesa_business_name,
            ],
        ]);
    }

    /**
     * Validate M-Pesa configuration
     */
    public function validateConfiguration(Request $request)
    {
        $user = $request->user();
        if (!$user || !$user->company_id) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }

        $setting = CompanySetting::where('company_id', $user->company_id)->first();

        $isValid = $setting 
            && $setting->mpesa_enabled 
            && $setting->mpesa_type 
            && $setting->mpesa_number;

        return response()->json([
            'is_configured' => $isValid,
            'status' => $isValid ? 'configured' : 'incomplete',
            'missing_fields' => $this->getMissingFields($setting),
        ]);
    }

    private function getMissingFields(?CompanySetting $setting): array
    {
        $missing = [];

        if (!$setting || !$setting->mpesa_enabled) {
            $missing[] = 'mpesa_enabled';
        }

        if (!$setting || !$setting->mpesa_type) {
            $missing[] = 'mpesa_type';
        }

        if (!$setting || !$setting->mpesa_number) {
            $missing[] = 'mpesa_number';
        }

        return $missing;
    }
}
