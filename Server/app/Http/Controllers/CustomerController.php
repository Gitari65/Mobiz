<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class CustomerController extends Controller
{
    /**
     * Display a listing of customers.
     */
    public function index(Request $request)
    {
        try {
            $user = $request->user();
            if (!$user) {
                return response()->json(['error' => 'Unauthenticated'], 401);
            }

            $customers = Customer::where('company_id', $user->company_id)
                ->with(['creator', 'priceGroup'])
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json([
                'data' => $customers
            ], 200);

        } catch (\Exception $e) {
            Log::error('Error fetching customers: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to fetch customers'], 500);
        }
    }

    /**
     * Store a newly created customer.
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
                'email' => 'nullable|email|max:255',
                'phone' => 'nullable|string|max:20',
                'address' => 'nullable|string|max:500',
                'tax_id' => 'nullable|string|max:50',
                'notes' => 'nullable|string|max:1000',
                'price_group_id' => 'nullable|exists:price_groups,id',
            ]);

            $customer = Customer::create([
                ...$validated,
                'company_id' => $user->company_id,
                'created_by' => $user->id,
                'total_orders' => 0,
            ]);

            $customer->load('creator', 'priceGroup');

            return response()->json([
                'message' => 'Customer created successfully',
                'data' => $customer
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['error' => 'Invalid input', 'details' => $e->errors()], 422);
        } catch (\Exception $e) {
            Log::error('Error creating customer: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to create customer'], 500);
        }
    }

    /**
     * Display the specified customer.
     */
    public function show($id)
    {
        try {
            $user = Auth::user();
            if (!$user) {
                return response()->json(['error' => 'Unauthenticated'], 401);
            }

            $customer = Customer::where('company_id', $user->company_id)
                ->with(['creator', 'sales', 'priceGroup'])
                ->findOrFail($id);

            return response()->json($customer, 200);

        } catch (\Exception $e) {
            Log::error('Error showing customer: ' . $e->getMessage());
            return response()->json(['error' => 'Customer not found'], 404);
        }
    }

    /**
     * Update the specified customer.
     */
    public function update(Request $request, $id)
    {
        try {
            $user = $request->user();
            if (!$user) {
                return response()->json(['error' => 'Unauthenticated'], 401);
            }

            $customer = Customer::where('company_id', $user->company_id)->findOrFail($id);

            $validated = $request->validate([
                'name' => 'sometimes|required|string|max:255',
                'email' => 'nullable|email|max:255',
                'phone' => 'nullable|string|max:20',
                'address' => 'nullable|string|max:500',
                'tax_id' => 'nullable|string|max:50',
                'notes' => 'nullable|string|max:1000',
                'price_group_id' => 'nullable|exists:price_groups,id',
            ]);

            $customer->update($validated);
            $customer->load('creator', 'priceGroup');

            return response()->json([
                'message' => 'Customer updated successfully',
                'data' => $customer
            ], 200);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['error' => 'Invalid input', 'details' => $e->errors()], 422);
        } catch (\Exception $e) {
            Log::error('Error updating customer: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to update customer'], 500);
        }
    }

    /**
     * Remove the specified customer.
     */
    public function destroy($id)
    {
        try {
            $user = Auth::user();
            if (!$user) {
                return response()->json(['error' => 'Unauthenticated'], 401);
            }

            $customer = Customer::where('company_id', $user->company_id)->findOrFail($id);
            $customer->delete();

            return response()->json([
                'message' => 'Customer deleted successfully'
            ], 200);

        } catch (\Exception $e) {
            Log::error('Error deleting customer: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to delete customer'], 500);
        }
    }

    /**
     * Batch import customers.
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
                'data.*.email' => 'nullable|email|max:255',
                'data.*.phone' => 'nullable|string|max:20',
                'data.*.address' => 'nullable|string|max:500',
                'data.*.tax_id' => 'nullable|string|max:50',
                'data.*.notes' => 'nullable|string|max:1000',
                'data.*.price_group_id' => 'nullable|exists:price_groups,id',
            ]);

            DB::beginTransaction();

            $customers = [];
            foreach ($request->data as $customerData) {
                $customer = Customer::create([
                    'name' => $customerData['name'],
                    'email' => $customerData['email'] ?? null,
                    'phone' => $customerData['phone'] ?? null,
                    'address' => $customerData['address'] ?? null,
                    'tax_id' => $customerData['tax_id'] ?? null,
                    'notes' => $customerData['notes'] ?? null,
                    'price_group_id' => $customerData['price_group_id'] ?? null,
                    'company_id' => $user->company_id,
                    'created_by' => $user->id,
                    'total_orders' => 0,
                ]);
                $customers[] = $customer;
            }

            DB::commit();

            return response()->json([
                'message' => 'Customers imported successfully',
                'data' => $customers,
                'count' => count($customers)
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            return response()->json(['error' => 'Invalid input', 'details' => $e->errors()], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error importing customers: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to import customers'], 500);
        }
    }
}
