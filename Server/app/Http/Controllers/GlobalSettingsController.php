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
        // Get root categories with their children
        return response()->json(
            BusinessCategory::whereNull('parent_id')
                ->with('children')
                ->orderBy('display_order', 'asc')
                ->orderBy('name', 'asc')
                ->get()
        );
    }
    public function addCategory(Request $request) {
        $request->validate([
            'name' => 'required|string|max:100',
            'parent_id' => 'nullable|exists:business_categories,id',
            'description' => 'nullable|string',
            'icon' => 'nullable|string',
            'display_order' => 'nullable|integer|min:0'
        ]);
        BusinessCategory::firstOrCreate(
            ['name' => $request->name, 'parent_id' => $request->parent_id],
            [
                'description' => $request->description,
                'icon' => $request->icon,
                'display_order' => $request->display_order ?? 0
            ]
        );
        return response()->json(['success' => true]);
    }
    public function removeCategory($name) {
        BusinessCategory::where('name', $name)->delete();
        return response()->json(['success' => true]);
    }
    
    public function importCategoriesCsv(Request $request) {
        $request->validate(['csv_file' => 'required|file|mimes:csv,txt']);
        
        $file = $request->file('csv_file');
        $stream = fopen($file->getRealPath(), 'r');
        $imported = 0;
        
        // Skip header row if it exists
        $header = fgetcsv($stream);
        
        while (($row = fgetcsv($stream)) !== false) {
            if (!empty($row[0])) {
                $name = trim($row[0]);
                $parent_name = isset($row[1]) ? trim($row[1]) : null;
                $parent_id = null;
                
                // If parent category name is provided, find its ID
                if ($parent_name) {
                    $parent = BusinessCategory::where('name', $parent_name)->first();
                    $parent_id = $parent ? $parent->id : null;
                }
                
                BusinessCategory::firstOrCreate(
                    ['name' => $name],
                    ['parent_id' => $parent_id]
                );
                $imported++;
            }
        }
        
        fclose($stream);
        
        return response()->json([
            'success' => true,
            'imported' => $imported,
            'message' => "Successfully imported $imported categories"
        ]);
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
