<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\UOM;

class UOMController extends Controller
{
    /**
     * Get all UOMs
     */
    public function index()
    {
        $uoms = UOM::orderBy('is_system', 'desc')->orderBy('name')->get();
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
        ]);

        $uom = UOM::create([
            'name' => $validated['name'],
            'abbreviation' => $validated['abbreviation'],
            'description' => $validated['description'] ?? null,
            'is_system' => $validated['is_system'] ?? false,
        ]);

        return response()->json(['message' => 'UOM created successfully', 'uom' => $uom], 201);
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
     * Update a UOM
     */
    public function update(Request $request, $id)
    {
        $uom = UOM::findOrFail($id);

        // Prevent updating system UOMs
        if ($uom->is_system) {
            return response()->json(['error' => 'System UOMs cannot be modified'], 403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:50|unique:u_o_m_s,name,' . $id,
            'abbreviation' => 'required|string|max:20|unique:u_o_m_s,abbreviation,' . $id,
            'description' => 'nullable|string',
        ]);

        $uom->update($validated);
        return response()->json(['message' => 'UOM updated successfully', 'uom' => $uom]);
    }

    /**
     * Delete a UOM
     */
    public function destroy($id)
    {
        $uom = UOM::findOrFail($id);

        // Prevent deleting system UOMs
        if ($uom->is_system) {
            return response()->json(['error' => 'System UOMs cannot be deleted'], 403);
        }

        // Check if UOM is in use
        if ($uom->products()->count() > 0) {
            return response()->json(['error' => 'Cannot delete UOM that is in use'], 409);
        }

        $uom->delete();
        return response()->json(['message' => 'UOM deleted successfully']);
    }
}
