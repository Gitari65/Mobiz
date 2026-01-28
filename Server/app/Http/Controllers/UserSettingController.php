<?php

namespace App\Http\Controllers;

use App\Models\UserSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class UserSettingController extends Controller
{
    /**
     * Get user settings (All authenticated users)
     */
    public function show(Request $request)
    {
        $user = $request->user();

        // Get user settings or create default
        $settings = UserSetting::firstOrCreate(
            ['user_id' => $user->id],
            [
                'theme' => 'light',
                'language' => 'en',
                'items_per_page' => 20,
                'date_format' => 'Y-m-d',
                'time_format' => 'H:i',
                'email_notifications' => true,
                'push_notifications' => true,
                'low_stock_alerts' => true,
                'sale_alerts' => false,
                'report_alerts' => true,
                'auto_print_receipt' => false,
                'default_page' => '/',
            ]
        );

        return response()->json($settings);
    }

    /**
     * Update user settings (All authenticated users)
     */
    public function update(Request $request)
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'theme' => 'nullable|string|in:light,dark',
            'language' => 'nullable|string|max:10',
            'items_per_page' => 'nullable|integer|min:5|max:100',
            'date_format' => 'nullable|string|max:20',
            'time_format' => 'nullable|string|max:20',
            'email_notifications' => 'nullable|boolean',
            'push_notifications' => 'nullable|boolean',
            'low_stock_alerts' => 'nullable|boolean',
            'sale_alerts' => 'nullable|boolean',
            'report_alerts' => 'nullable|boolean',
            'dashboard_widgets' => 'nullable|array',
            'default_page' => 'nullable|string|max:255',
            'auto_print_receipt' => 'nullable|boolean',
            'printer_name' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $settings = UserSetting::updateOrCreate(
            ['user_id' => $user->id],
            $request->only([
                'theme',
                'language',
                'items_per_page',
                'date_format',
                'time_format',
                'email_notifications',
                'push_notifications',
                'low_stock_alerts',
                'sale_alerts',
                'report_alerts',
                'dashboard_widgets',
                'default_page',
                'auto_print_receipt',
                'printer_name',
            ])
        );

        return response()->json([
            'message' => 'User settings updated successfully',
            'settings' => $settings
        ]);
    }
}
