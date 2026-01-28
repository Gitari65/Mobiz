<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\BusinessCategory;
use App\Models\FeatureToggle;
use App\Models\Announcement;
use App\Models\Warehouse;
use App\Models\PaymentMethod;

class GlobalSettingsController extends Controller
{
    // Business Categories
    public function listCategories() {
        return response()->json(BusinessCategory::pluck('name'));
    }
    public function addCategory(Request $request) {
        $request->validate(['name' => 'required|string|max:100']);
        BusinessCategory::firstOrCreate(['name' => $request->name]);
        return response()->json(['success' => true]);
    }
    public function removeCategory($name) {
        BusinessCategory::where('name', $name)->delete();
        return response()->json(['success' => true]);
    }

    // Feature Toggles
    public function listFeatures() {
        return response()->json(FeatureToggle::all(['key', 'name', 'enabled']));
    }
    public function setFeature(Request $request) {
        $request->validate(['key' => 'required', 'enabled' => 'required|boolean']);
        $feature = FeatureToggle::where('key', $request->key)->first();
        if ($feature) {
            $feature->enabled = $request->enabled;
            $feature->save();
        }
        return response()->json(['success' => true]);
    }

    // Announcements
    public function getAnnouncement() {
        $a = Announcement::first();
        return response()->json($a ? $a->text : '');
    }
    public function setAnnouncement(Request $request) {
        $request->validate(['text' => 'required|string']);
        $a = Announcement::first();
        if (!$a) $a = new Announcement();
        $a->text = $request->text;
        $a->save();
        return response()->json(['success' => true]);
    }

    // Warehouses
    public function listWarehouses() {
        return response()->json(Warehouse::all(['id', 'name', 'type', 'user_id']));
    }
    public function addWarehouse(Request $request) {
        $request->validate([
            'name' => 'required|string|max:100',
            'type' => 'nullable|string|max:50',
        ]);
        $warehouse = Warehouse::create([
            'name' => $request->name,
            'type' => $request->type,
            'user_id' => auth()->id() ?? null,
        ]);
        return response()->json($warehouse);
    }
    public function deleteWarehouse($id) {
        Warehouse::where('id', $id)->delete();
        return response()->json(['success' => true]);
    }

    // Payment Methods
    public function listPaymentMethods() {
        return response()->json(PaymentMethod::where('is_active', true)->pluck('name'));
    }
    public function addPaymentMethod(Request $request) {
        $request->validate(['name' => 'required|string|max:100']);
        PaymentMethod::firstOrCreate(['name' => $request->name]);
        return response()->json(['success' => true]);
    }
    public function deletePaymentMethod($name) {
        PaymentMethod::where('name', $name)->delete();
        return response()->json(['success' => true]);
    }
}
