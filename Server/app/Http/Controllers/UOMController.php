<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\UOM;
use App\Services\UOMConversionService;

class UOMController extends Controller
{
    /**
     * Get all UOMs, with optional type filtering
     * 
     * Query Parameters:
     *   - type: Filter by type (volume, weight, length, area, count)
     * 
     * Examples:
     *   GET /uoms                    - All UOMs
     *   GET /uoms?type=volume        - Only volume UOMs (ml, L, etc.)
     *   GET /uoms?type=weight        - Only weight UOMs (kg, g, etc.)
     */
    public function index(Request $request)
    {
        $query = UOM::orderBy('is_system', 'desc')->orderBy('name');
        
        // Filter by type if provided
        if ($request->has('type')) {
            $type = $request->query('type');
            $query->where('type', $type);
        }
        
        $uoms = $query->get();
        return response()->json($uoms);
    }

    /**
     * Create a new UOM
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:50|unique:u_o_m_s,name',
            'abbreviation' => 'required|string|max:20|unique:u_o_m_s,abbreviation',
            'description' => 'nullable|string',
            'is_system' => 'boolean',
            'type' => 'nullable|string|in:volume,weight,length,area,count,other',
        ]);

        $uom = UOM::create([
            'name' => $validated['name'],
            'abbreviation' => $validated['abbreviation'],
            'description' => $validated['description'] ?? null,
            'is_system' => $validated['is_system'] ?? false,
            'type' => $validated['type'] ?? 'other',
        ]);

        return response()->json(['message' => 'UOM created successfully', 'uom' => $uom], 201);
    }

    /**
     * Update a UOM
     */
    public function update(Request $request, $id)
    {
        $uom = UOM::findOrFail($id);
        
        $validated = $request->validate([
            'name' => 'required|string|max:50|unique:u_o_m_s,name,' . $id,
            'abbreviation' => 'required|string|max:20|unique:u_o_m_s,abbreviation,' . $id,
            'description' => 'nullable|string',
            'type' => 'nullable|string|in:volume,weight,length,area,count,other',
        ]);

        $uom->update([
            'name' => $validated['name'],
            'abbreviation' => $validated['abbreviation'],
            'description' => $validated['description'],
            'type' => $validated['type'] ?? $uom->type,
        ]);

        return response()->json(['message' => 'UOM updated successfully', 'uom' => $uom]);
    }

    /**
     * Delete a UOM (only non-system UOMs)
     */
    public function destroy($id)
    {
        $uom = UOM::findOrFail($id);
        
        if ($uom->is_system) {
            return response()->json(['message' => 'Cannot delete system UOMs'], 403);
        }
        
        $uom->delete();
        return response()->json(['message' => 'UOM deleted successfully']);
    }

    /**
     * Get a specific UOM
     */
    public function show($id)
    {
        $uom = UOM::findOrFail($id);
        return response()->json($uom);
    }

    /**
     * Get conversion factor between two UOMs.
     *
     * Query params:
     * - from_uom_id (required)
     * - to_uom_id (required)
     */
    public function conversionFactor(Request $request)
    {
        $validated = $request->validate([
            'from_uom_id' => 'required|integer|exists:u_o_m_s,id',
            'to_uom_id' => 'required|integer|exists:u_o_m_s,id',
        ]);

        $fromUomId = (int) $validated['from_uom_id'];
        $toUomId = (int) $validated['to_uom_id'];

        $factor = UOMConversionService::resolveConversionFactor($fromUomId, $toUomId);

        return response()->json([
            'from_uom_id' => $fromUomId,
            'to_uom_id' => $toUomId,
            'factor' => $factor,
            'found' => $factor !== null,
        ]);
    }
}

