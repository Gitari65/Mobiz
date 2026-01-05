<?php
namespace App\Http\Controllers\SuperUser;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\SystemSetting;

class SystemSettingController extends Controller
{
    public function index()
    {
        return response()->json(SystemSetting::orderBy('category')->get());
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'key' => 'required|string|unique:system_settings,key',
            'value' => 'nullable|string',
            'category' => 'nullable|string',
            'description' => 'nullable|string',
            'is_public' => 'boolean'
        ]);
        $setting = SystemSetting::create($data);
        // optionally broadcast or clear cache
        return response()->json($setting, 201);
    }

    public function update(Request $request, $id)
    {
        $setting = SystemSetting::findOrFail($id);
        $data = $request->validate([
            'value' => 'nullable|string',
            'category' => 'nullable|string',
            'description' => 'nullable|string',
            'is_public' => 'boolean'
        ]);
        $setting->update($data);
        return response()->json($setting);
    }

    public function destroy($id)
    {
        $setting = SystemSetting::findOrFail($id);
        $setting->delete();
        return response()->json(['message' => 'Deleted']);
    }
}
