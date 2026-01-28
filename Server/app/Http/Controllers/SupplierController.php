<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class SupplierController extends Controller
{
    /**
     * Display a listing of suppliers.
     */
    public function index(Request $request)
    {
        try {
            $user = $request->user();
            if (!$user) {
                return response()->json(['error' => 'Unauthenticated'], 401);
            }

            $suppliers = Supplier::where('company_id', $user->company_id)
                ->with(['creator'])
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json([
                'data' => $suppliers
            ], 200);

        } catch (\Exception $e) {
            Log::error('Error fetching suppliers: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to fetch suppliers'], 500);
        }
    }

    /**
     * Store a newly created supplier.
     */
    public function store(Request $request)
    {
        try {
            $user = $request->user();
            if (!$user) {
                return response()->json(['error' => 'Unauthenticated'], 401);
            }

            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'contact_person' => 'nullable|string|max:255',
                'email' => 'nullable|email|max:255',
                'phone' => 'nullable|string|max:20',
                'address' => 'nullable|string|max:500',
                'products_supplied' => 'nullable|string|max:500',
                'notes' => 'nullable|string|max:1000',
            ]);

            $supplier = Supplier::create([
                ...$validated,
                'company_id' => $user->company_id,
                'created_by' => $user->id,
            ]);

            $supplier->load('creator');

            return response()->json([
                'message' => 'Supplier created successfully',
                'data' => $supplier
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['error' => 'Invalid input', 'details' => $e->errors()], 422);
        } catch (\Exception $e) {
            Log::error('Error creating supplier: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to create supplier'], 500);
        }
    }

    /**
     * Display the specified supplier.
     */
    public function show($id)
    {
        try {
            $user = Auth::user();
            if (!$user) {
                return response()->json(['error' => 'Unauthenticated'], 401);
            }

            $supplier = Supplier::where('company_id', $user->company_id)
                ->with(['creator', 'purchases'])
                ->findOrFail($id);

            return response()->json($supplier, 200);

        } catch (\Exception $e) {
            Log::error('Error showing supplier: ' . $e->getMessage());
            return response()->json(['error' => 'Supplier not found'], 404);
        }
    }

    /**
     * Update the specified supplier.
     */
    public function update(Request $request, $id)
    {
        try {
            $user = $request->user();
            if (!$user) {
                return response()->json(['error' => 'Unauthenticated'], 401);
            }

            $supplier = Supplier::where('company_id', $user->company_id)->findOrFail($id);

            $validated = $request->validate([
                'name' => 'sometimes|required|string|max:255',
                'contact_person' => 'nullable|string|max:255',
                'email' => 'nullable|email|max:255',
                'phone' => 'nullable|string|max:20',
                'address' => 'nullable|string|max:500',
                'products_supplied' => 'nullable|string|max:500',
                'notes' => 'nullable|string|max:1000',
            ]);

            $supplier->update($validated);
            $supplier->load('creator');

            return response()->json([
                'message' => 'Supplier updated successfully',
                'data' => $supplier
            ], 200);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['error' => 'Invalid input', 'details' => $e->errors()], 422);
        } catch (\Exception $e) {
            Log::error('Error updating supplier: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to update supplier'], 500);
        }
    }

    /**
     * Remove the specified supplier.
     */
    public function destroy($id)
    {
        try {
            $user = Auth::user();
            if (!$user) {
                return response()->json(['error' => 'Unauthenticated'], 401);
            }

            $supplier = Supplier::where('company_id', $user->company_id)->findOrFail($id);
            $supplier->delete();

            return response()->json([
                'message' => 'Supplier deleted successfully'
            ], 200);

        } catch (\Exception $e) {
            Log::error('Error deleting supplier: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to delete supplier'], 500);
        }
    }

    /**
     * Batch import suppliers.
     */
    public function batchImport(Request $request)
    {
        try {
            $user = $request->user();
            if (!$user) {
                return response()->json(['error' => 'Unauthenticated'], 401);
            }

            $request->validate([
                'data' => 'required|array|min:1',
                'data.*.name' => 'required|string|max:255',
                'data.*.contact_person' => 'nullable|string|max:255',
                'data.*.email' => 'nullable|email|max:255',
                'data.*.phone' => 'nullable|string|max:20',
                'data.*.address' => 'nullable|string|max:500',
                'data.*.products_supplied' => 'nullable|string|max:500',
                'data.*.notes' => 'nullable|string|max:1000',
            ]);

            DB::beginTransaction();

            $suppliers = [];
            foreach ($request->data as $supplierData) {
                $supplier = Supplier::create([
                    'name' => $supplierData['name'],
                    'contact_person' => $supplierData['contact_person'] ?? null,
                    'email' => $supplierData['email'] ?? null,
                    'phone' => $supplierData['phone'] ?? null,
                    'address' => $supplierData['address'] ?? null,
                    'products_supplied' => $supplierData['products_supplied'] ?? null,
                    'notes' => $supplierData['notes'] ?? null,
                    'company_id' => $user->company_id,
                    'created_by' => $user->id,
                ]);
                $suppliers[] = $supplier;
            }

            DB::commit();

            return response()->json([
                'message' => 'Suppliers imported successfully',
                'data' => $suppliers,
                'count' => count($suppliers)
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            return response()->json(['error' => 'Invalid input', 'details' => $e->errors()], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error importing suppliers: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to import suppliers'], 500);
        }
    }
}
