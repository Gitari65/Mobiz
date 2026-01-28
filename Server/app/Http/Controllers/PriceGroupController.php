<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\PriceGroup;
use Illuminate\Support\Facades\Validator;

class PriceGroupController extends Controller
{
    /**
     * Display a listing of price groups.
     */
    public function index(Request $request)
    {
        $user = $request->user();
        
        // Get system price groups and company-specific ones
        $priceGroups = PriceGroup::where(function ($query) use ($user) {
            $query->whereNull('company_id')
                  ->orWhere('company_id', $user->company_id);
        })
        ->orderBy('is_system', 'desc')
        ->orderBy('name')
        ->get();

        return response()->json($priceGroups);
    }

    /**
     * Store a newly created price group.
     */
    public function store(Request $request)
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:100',
            'code' => 'required|string|max:50|unique:price_groups,code',
            'description' => 'nullable|string',
            'discount_percentage' => 'required|numeric|min:0|max:100'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $priceGroup = PriceGroup::create([
            'name' => $request->name,
            'code' => strtoupper($request->code),
            'description' => $request->description,
            'discount_percentage' => $request->discount_percentage,
            'is_system' => false,
            'company_id' => $user->company_id
        ]);

        return response()->json([
            'message' => 'Price group created successfully',
            'data' => $priceGroup
        ], 201);
    }

    /**
     * Display the specified price group.
     */
    public function show($id)
    {
        $priceGroup = PriceGroup::with('customers')->findOrFail($id);
        return response()->json($priceGroup);
    }

    /**
     * Update the specified price group.
     */
    public function update(Request $request, $id)
    {
        $user = $request->user();
        $priceGroup = PriceGroup::findOrFail($id);

        // Prevent editing system price groups
        if ($priceGroup->is_system) {
            return response()->json([
                'message' => 'Cannot edit system price groups'
            ], 403);
        }

        // Check if user owns this price group
        if ($priceGroup->company_id !== $user->company_id) {
            return response()->json([
                'message' => 'Unauthorized'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:100',
            'code' => 'required|string|max:50|unique:price_groups,code,' . $id,
            'description' => 'nullable|string',
            'discount_percentage' => 'required|numeric|min:0|max:100'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $priceGroup->update([
            'name' => $request->name,
            'code' => strtoupper($request->code),
            'description' => $request->description,
            'discount_percentage' => $request->discount_percentage
        ]);

        return response()->json([
            'message' => 'Price group updated successfully',
            'data' => $priceGroup
        ]);
    }

    /**
     * Remove the specified price group.
     */
    public function destroy($id)
    {
        $priceGroup = PriceGroup::findOrFail($id);

        // Prevent deleting system price groups
        if ($priceGroup->is_system) {
            return response()->json([
                'message' => 'Cannot delete system price groups'
            ], 403);
        }

        // Check if price group has customers
        if ($priceGroup->customers()->count() > 0) {
            return response()->json([
                'message' => 'Cannot delete price group with assigned customers'
            ], 422);
        }

        $priceGroup->delete();

        return response()->json([
            'message' => 'Price group deleted successfully'
        ]);
    }
}
