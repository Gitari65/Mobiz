<?php

namespace App\Http\Controllers;

use App\Models\CompanySetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class CompanySettingController extends Controller
{
    /**
     * Get company settings (Admin/SuperUser)
     */
    public function show(Request $request)
    {
        $user = $request->user();
        
        // Check if user is admin or superuser
        $role = strtolower($user->role->name ?? '');
        if (!in_array($role, ['admin', 'administrator', 'superuser', 'super user'])) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        // Get company settings or create default
        $settings = CompanySetting::firstOrCreate(
            ['company_id' => $user->company_id],
            [
                'timezone' => 'Africa/Nairobi',
                'currency' => 'KES',
                'currency_symbol' => 'KSh',
                'decimal_places' => 2,
                'tax_enabled' => false,
                'tax_rate' => 0.00,
                'tax_inclusive' => true,
                'invoice_prefix' => 'INV-',
                'invoice_number_start' => 1000,
                'low_stock_alerts' => true,
                'low_stock_threshold' => 10,
                'allow_negative_stock' => false,
                'allow_discount' => true,
                'max_discount_percent' => 20.00,
                'session_timeout_minutes' => 60,
            ]
        );

        return response()->json($settings);
    }

    /**
     * Update company settings (Admin/SuperUser)
     */
    public function update(Request $request)
    {
        $user = $request->user();
        
        // Check if user is admin or superuser
        $role = strtolower($user->role->name ?? '');
        if (!in_array($role, ['admin', 'administrator', 'superuser', 'super user'])) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $validator = Validator::make($request->all(), [
            'business_hours_start' => 'nullable|string|max:10',
            'business_hours_end' => 'nullable|string|max:10',
            'timezone' => 'nullable|string|max:50',
            'currency' => 'nullable|string|max:10',
            'currency_symbol' => 'nullable|string|max:10',
            'decimal_places' => 'nullable|integer|min:0|max:4',
            'tax_enabled' => 'nullable|boolean',
            'tax_name' => 'nullable|string|max:50',
            'tax_rate' => 'nullable|numeric|min:0|max:100',
            'tax_inclusive' => 'nullable|boolean',
            'receipt_header' => 'nullable|string|max:255',
            'receipt_footer' => 'nullable|string',
            'auto_print_receipt' => 'nullable|boolean',
            'invoice_prefix' => 'nullable|string|max:20',
            'invoice_number_start' => 'nullable|integer|min:1',
            'low_stock_alerts' => 'nullable|boolean',
            'low_stock_threshold' => 'nullable|integer|min:0',
            'allow_negative_stock' => 'nullable|boolean',
            'track_stock_expiry' => 'nullable|boolean',
            'require_customer_info' => 'nullable|boolean',
            'allow_discount' => 'nullable|boolean',
            'max_discount_percent' => 'nullable|numeric|min:0|max:100',
            'allow_credit_sales' => 'nullable|boolean',
            'email_notifications' => 'nullable|boolean',
            'sms_notifications' => 'nullable|boolean',
            'notification_email' => 'nullable|email',
            'notification_phone' => 'nullable|string|max:20',
            'require_receipt_approval' => 'nullable|boolean',
            'enable_audit_log' => 'nullable|boolean',
            'session_timeout_minutes' => 'nullable|integer|min:5|max:480',
            'two_factor_auth' => 'nullable|boolean',
            'auto_backup' => 'nullable|boolean',
            'backup_frequency' => 'nullable|string|in:daily,weekly,monthly',
            'backup_retention_days' => 'nullable|integer|min:1|max:365',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $settings = CompanySetting::updateOrCreate(
            ['company_id' => $user->company_id],
            $request->only([
                'business_hours_start',
                'business_hours_end',
                'timezone',
                'currency',
                'currency_symbol',
                'decimal_places',
                'tax_enabled',
                'tax_name',
                'tax_rate',
                'tax_inclusive',
                'receipt_header',
                'receipt_footer',
                'auto_print_receipt',
                'invoice_prefix',
                'invoice_number_start',
                'low_stock_alerts',
                'low_stock_threshold',
                'allow_negative_stock',
                'track_stock_expiry',
                'require_customer_info',
                'allow_discount',
                'max_discount_percent',
                'allow_credit_sales',
                'email_notifications',
                'sms_notifications',
                'notification_email',
                'notification_phone',
                'require_receipt_approval',
                'enable_audit_log',
                'session_timeout_minutes',
                'two_factor_auth',
                'auto_backup',
                'backup_frequency',
                'backup_retention_days',
            ])
        );

        return response()->json([
            'message' => 'Company settings updated successfully',
            'settings' => $settings
        ]);
    }

    /**
     * Upload receipt logo
     */
    public function uploadLogo(Request $request)
    {
        $user = $request->user();
        
        $role = strtolower($user->role->name ?? '');
        if (!in_array($role, ['admin', 'administrator', 'superuser', 'super user'])) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $validator = Validator::make($request->all(), [
            'logo' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $file = $request->file('logo');
        $filename = 'receipt_logo_' . $user->company_id . '_' . time() . '.' . $file->getClientOriginalExtension();
        $path = $file->storeAs('receipt_logos', $filename, 'public');

        // Update settings with logo path
        $settings = CompanySetting::updateOrCreate(
            ['company_id' => $user->company_id],
            ['receipt_logo_path' => $path]
        );

        return response()->json([
            'message' => 'Receipt logo uploaded successfully',
            'logo_path' => $path,
            'logo_url' => Storage::url($path),
        ]);
    }

    /**
     * Remove receipt logo
     */
    public function removeLogo(Request $request)
    {
        $user = $request->user();
        
        $role = strtolower($user->role->name ?? '');
        if (!in_array($role, ['admin', 'administrator', 'superuser', 'super user'])) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $settings = CompanySetting::where('company_id', $user->company_id)->first();
        
        if ($settings && $settings->receipt_logo_path) {
            // Delete the file
            Storage::disk('public')->delete($settings->receipt_logo_path);
            
            // Update settings
            $settings->receipt_logo_path = null;
            $settings->save();
        }

        return response()->json(['message' => 'Receipt logo removed successfully']);
    }
}
