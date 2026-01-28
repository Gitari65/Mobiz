<?php

namespace App\Http\Controllers;

use App\Models\SystemSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SystemSettingController extends Controller
{
    /**
     * Get all system settings (SuperUser only)
     */
    public function index(Request $request)
    {
        $user = $request->user();
        
        $role = strtolower($user->role->name ?? '');
        if (!in_array($role, ['superuser', 'super user'])) {
            return response()->json(['error' => 'Unauthorized. SuperUser access required.'], 403);
        }

        $group = $request->query('group');
        
        $query = SystemSetting::query();
        
        if ($group) {
            $query->where('group', $group);
        }
        
        $settings = $query->orderBy('group')->orderBy('key')->get();

        return response()->json([
            'settings' => $settings,
            'groups' => ['general', 'security', 'billing', 'performance', 'features']
        ]);
    }

    /**
     * Get a single system setting by key
     */
    public function show(Request $request, $key)
    {
        $user = $request->user();
        
        $role = strtolower($user->role->name ?? '');
        if (!in_array($role, ['superuser', 'super user'])) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $setting = SystemSetting::where('key', $key)->first();
        
        if (!$setting) {
            return response()->json(['error' => 'Setting not found'], 404);
        }

        return response()->json($setting);
    }

    /**
     * Create or update a system setting (SuperUser only)
     */
    public function store(Request $request)
    {
        $user = $request->user();
        
        $role = strtolower($user->role->name ?? '');
        if (!in_array($role, ['superuser', 'super user'])) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $validator = Validator::make($request->all(), [
            'key' => 'required|string|max:255',
            'value' => 'nullable|string',
            'type' => 'required|string|in:string,boolean,integer,float,json',
            'description' => 'nullable|string',
            'group' => 'nullable|string|in:general,security,billing,performance,features',
            'is_public' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $setting = SystemSetting::updateOrCreate(
            ['key' => $request->key],
            [
                'value' => $request->value,
                'type' => $request->type,
                'description' => $request->description,
                'group' => $request->group ?? 'general',
                'is_public' => $request->is_public ?? false,
            ]
        );

        return response()->json([
            'message' => 'System setting saved successfully',
            'setting' => $setting
        ]);
    }

    /**
     * Update a system setting (SuperUser only)
     */
    public function update(Request $request, $id)
    {
        $user = $request->user();
        
        $role = strtolower($user->role->name ?? '');
        if (!in_array($role, ['superuser', 'super user'])) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $setting = SystemSetting::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'key' => 'nullable|string|max:255',
            'value' => 'nullable|string',
            'type' => 'nullable|string|in:string,boolean,integer,float,json',
            'description' => 'nullable|string',
            'group' => 'nullable|string|in:general,security,billing,performance,features',
            'is_public' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $setting->update($request->only([
            'key', 'value', 'type', 'description', 'group', 'is_public'
        ]));

        return response()->json([
            'message' => 'System setting updated successfully',
            'setting' => $setting
        ]);
    }

    /**
     * Delete a system setting (SuperUser only)
     */
    public function destroy(Request $request, $id)
    {
        $user = $request->user();
        
        $role = strtolower($user->role->name ?? '');
        if (!in_array($role, ['superuser', 'super user'])) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $setting = SystemSetting::findOrFail($id);
        $setting->delete();

        return response()->json(['message' => 'System setting deleted successfully']);
    }

    /**
     * Bulk update system settings (SuperUser only)
     */
    public function bulkUpdate(Request $request)
    {
        $user = $request->user();
        
        $role = strtolower($user->role->name ?? '');
        if (!in_array($role, ['superuser', 'super user'])) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $validator = Validator::make($request->all(), [
            'settings' => 'required|array',
            'settings.*.key' => 'required|string',
            'settings.*.value' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $updated = [];
        foreach ($request->settings as $settingData) {
            $setting = SystemSetting::where('key', $settingData['key'])->first();
            if ($setting) {
                $setting->value = $settingData['value'];
                $setting->save();
                $updated[] = $setting;
            }
        }

        return response()->json([
            'message' => 'System settings updated successfully',
            'updated_count' => count($updated),
            'settings' => $updated
        ]);
    }

    /**
     * Get public settings (accessible to all users)
     */
    public function getPublicSettings()
    {
        $settings = SystemSetting::where('is_public', true)->get();
        
        $formatted = [];
        foreach ($settings as $setting) {
            $formatted[$setting->key] = $setting->getCastedValue();
        }

        return response()->json($formatted);
    }
}
