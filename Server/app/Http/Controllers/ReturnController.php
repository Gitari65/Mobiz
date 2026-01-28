<?php

namespace App\Http\Controllers;

use App\Models\ReturnModel;
use App\Models\ReturnItem;
use App\Models\Sale;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ReturnController extends Controller
{
    /**
     * List all returns
     */
    public function index(Request $request)
    {
        try {
            $user = auth()->user();
            if (!$user) {
                return response()->json(['error' => 'Unauthenticated'], 401);
            }

            $query = ReturnModel::where('company_id', $user->company_id)
                ->with(['customer', 'items', 'user', 'sale']);

            // Filter by status
            if ($request->has('status') && $request->status !== 'all') {
                $query->where('status', $request->status);
            }

            // Search
            if ($request->has('search')) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('return_number', 'like', "%{$search}%")
                      ->orWhereHas('customer', function($q) use ($search) {
                          $q->where('name', 'like', "%{$search}%");
                      });
                });
            }

            $returns = $query->orderBy('return_date', 'desc')->get();

            return response()->json($returns);
        } catch (\Exception $e) {
            Log::error('Error fetching returns: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to fetch returns'], 500);
        }
    }

    /**
     * Get a single return
     */
    public function show($id)
    {
        try {
            $user = auth()->user();
            if (!$user) {
                return response()->json(['error' => 'Unauthenticated'], 401);
            }

            $return = ReturnModel::where('company_id', $user->company_id)
                ->with(['customer', 'items.product', 'user', 'sale', 'approver'])
                ->findOrFail($id);

            return response()->json($return);
        } catch (\Exception $e) {
            Log::error('Error fetching return: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to fetch return'], 500);
        }
    }

    /**
     * Create a new return
     */
    public function store(Request $request)
    {
        try {
            $user = auth()->user();
            if (!$user) {
                return response()->json(['error' => 'Unauthenticated'], 401);
            }

            $validated = $request->validate([
                'sale_id' => 'nullable|exists:sales,id',
                'customer_id' => 'required|exists:customers,id',
                'return_date' => 'required|date',
                'refund_method' => 'required|in:cash,credit,original_payment',
                'items' => 'required|array|min:1',
                'items.*.product_id' => 'required|exists:products,id',
                'items.*.quantity' => 'required|integer|min:1',
                'items.*.unit_price' => 'required|numeric|min:0',
                'items.*.reason' => 'nullable|string',
                'reason' => 'required|string',
                'notes' => 'nullable|string',
            ]);

            DB::beginTransaction();

            // Calculate refund amount
            $refundAmount = 0;
            foreach ($validated['items'] as $item) {
                $refundAmount += $item['quantity'] * $item['unit_price'];
            }

            // Generate return number
            $returnNumber = $this->generateReturnNumber($user->company_id);

            // Create return
            $return = ReturnModel::create([
                'sale_id' => $validated['sale_id'] ?? null,
                'customer_id' => $validated['customer_id'],
                'company_id' => $user->company_id,
                'user_id' => $user->id,
                'return_number' => $returnNumber,
                'return_date' => $validated['return_date'],
                'refund_amount' => $refundAmount,
                'refund_method' => $validated['refund_method'],
                'status' => 'pending',
                'reason' => $validated['reason'],
                'notes' => $validated['notes'] ?? null,
            ]);

            // Create return items
            foreach ($validated['items'] as $item) {
                $product = Product::find($item['product_id']);
                ReturnItem::create([
                    'return_id' => $return->id,
                    'product_id' => $item['product_id'],
                    'product_name' => $product->name,
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'subtotal' => $item['quantity'] * $item['unit_price'],
                    'reason' => $item['reason'] ?? null,
                ]);
            }

            DB::commit();

            return response()->json([
                'message' => 'Return created successfully',
                'return' => $return->load('items', 'customer')
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error creating return: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to create return'], 500);
        }
    }

    /**
     * Approve a return
     */
    public function approve($id)
    {
        try {
            $user = auth()->user();
            if (!$user) {
                return response()->json(['error' => 'Unauthenticated'], 401);
            }

            $return = ReturnModel::where('company_id', $user->company_id)
                ->findOrFail($id);

            if ($return->status !== 'pending') {
                return response()->json(['error' => 'Only pending returns can be approved'], 422);
            }

            DB::beginTransaction();

            $return->update([
                'status' => 'approved',
                'approved_by' => $user->id,
                'approved_at' => now(),
            ]);

            // Restore inventory
            foreach ($return->items as $item) {
                $product = Product::find($item->product_id);
                if ($product) {
                    $product->increment('quantity', $item->quantity);
                }
            }

            // Handle refund
            $customer = $return->customer;
            if ($return->refund_method === 'credit') {
                // Add to customer credit balance
                $balanceBefore = $customer->credit_balance;
                $customer->increment('credit_balance', $return->refund_amount);
                
                // Log credit transaction
                \App\Models\CreditTransaction::create([
                    'customer_id' => $customer->id,
                    'company_id' => $user->company_id,
                    'user_id' => $user->id,
                    'type' => 'credit',
                    'amount' => $return->refund_amount,
                    'balance_before' => $balanceBefore,
                    'balance_after' => $customer->fresh()->credit_balance,
                    'notes' => "Return {$return->return_number} approved and added as credit",
                ]);
            }

            DB::commit();

            return response()->json([
                'message' => 'Return approved successfully',
                'return' => $return->fresh()->load('items', 'customer')
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error approving return: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to approve return'], 500);
        }
    }

    /**
     * Reject a return
     */
    public function reject(Request $request, $id)
    {
        try {
            $user = auth()->user();
            if (!$user) {
                return response()->json(['error' => 'Unauthenticated'], 401);
            }

            $return = ReturnModel::where('company_id', $user->company_id)
                ->findOrFail($id);

            if ($return->status !== 'pending') {
                return response()->json(['error' => 'Only pending returns can be rejected'], 422);
            }

            $validated = $request->validate([
                'notes' => 'required|string',
            ]);

            $return->update([
                'status' => 'rejected',
                'approved_by' => $user->id,
                'approved_at' => now(),
                'notes' => ($return->notes ?? '') . "\n\nREJECTED: " . $validated['notes'],
            ]);

            return response()->json([
                'message' => 'Return rejected successfully',
                'return' => $return->fresh()->load('items', 'customer')
            ]);
        } catch (\Exception $e) {
            Log::error('Error rejecting return: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to reject return'], 500);
        }
    }

    /**
     * Complete a return (mark as completed after refund is issued)
     */
    public function complete($id)
    {
        try {
            $user = auth()->user();
            if (!$user) {
                return response()->json(['error' => 'Unauthenticated'], 401);
            }

            $return = ReturnModel::where('company_id', $user->company_id)
                ->findOrFail($id);

            if ($return->status !== 'approved') {
                return response()->json(['error' => 'Only approved returns can be completed'], 422);
            }

            $return->update(['status' => 'completed']);

            return response()->json([
                'message' => 'Return completed successfully',
                'return' => $return
            ]);
        } catch (\Exception $e) {
            Log::error('Error completing return: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to complete return'], 500);
        }
    }

    /**
     * Delete a return
     */
    public function destroy($id)
    {
        try {
            $user = auth()->user();
            if (!$user) {
                return response()->json(['error' => 'Unauthenticated'], 401);
            }

            $return = ReturnModel::where('company_id', $user->company_id)
                ->findOrFail($id);

            // Only allow deleting pending or rejected returns
            if (!in_array($return->status, ['pending', 'rejected'])) {
                return response()->json(['error' => 'Only pending or rejected returns can be deleted'], 422);
            }

            $return->delete();

            return response()->json(['message' => 'Return deleted successfully']);
        } catch (\Exception $e) {
            Log::error('Error deleting return: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to delete return'], 500);
        }
    }

    /**
     * Generate unique return number
     */
    private function generateReturnNumber($companyId)
    {
        $prefix = 'RET-';
        $year = date('Y');
        $lastReturn = ReturnModel::where('company_id', $companyId)
            ->whereYear('created_at', $year)
            ->orderBy('id', 'desc')
            ->first();

        if ($lastReturn) {
            $lastNumber = (int) substr($lastReturn->return_number, -6);
            $newNumber = str_pad($lastNumber + 1, 6, '0', STR_PAD_LEFT);
        } else {
            $newNumber = '000001';
        }

        return $prefix . $year . '-' . $newNumber;
    }
}
