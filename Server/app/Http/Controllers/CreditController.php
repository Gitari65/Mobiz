<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\CreditTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CreditController extends Controller
{
    /**
     * Get credit history for a customer
     */
    public function history($customerId)
    {
        try {
            $user = auth()->user();
            if (!$user) {
                return response()->json(['error' => 'Unauthenticated'], 401);
            }

            $customer = Customer::where('company_id', $user->company_id)
                ->findOrFail($customerId);

            $transactions = CreditTransaction::where('customer_id', $customerId)
                ->where('company_id', $user->company_id)
                ->with(['sale', 'user'])
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json([
                'customer' => $customer,
                'transactions' => $transactions
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching credit history: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to fetch credit history'], 500);
        }
    }

    /**
     * Record a payment
     */
    public function recordPayment(Request $request, $customerId)
    {
        try {
            $user = auth()->user();
            if (!$user) {
                return response()->json(['error' => 'Unauthenticated'], 401);
            }

            $validated = $request->validate([
                'amount' => 'required|numeric|min:0.01',
                'payment_method' => 'required|string',
                'transaction_number' => 'nullable|string',
                'notes' => 'nullable|string'
            ]);

            $customer = Customer::where('company_id', $user->company_id)
                ->findOrFail($customerId);

            if ($validated['amount'] > $customer->credit_balance) {
                return response()->json(['error' => 'Payment amount exceeds credit balance'], 422);
            }

            DB::beginTransaction();

            $balanceBefore = $customer->credit_balance;
            $customer->decrement('credit_balance', $validated['amount']);
            $balanceAfter = $customer->fresh()->credit_balance;

            CreditTransaction::create([
                'customer_id' => $customer->id,
                'company_id' => $user->company_id,
                'user_id' => $user->id,
                'type' => 'payment',
                'amount' => $validated['amount'],
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceAfter,
                'transaction_number' => $validated['transaction_number'] ?? null,
                'payment_method' => $validated['payment_method'],
                'notes' => $validated['notes'] ?? null,
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Payment recorded successfully',
                'customer' => $customer->fresh()
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error recording payment: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to record payment'], 500);
        }
    }

    /**
     * Update credit limit
     */
    public function updateCreditLimit(Request $request, $customerId)
    {
        try {
            $user = auth()->user();
            if (!$user) {
                return response()->json(['error' => 'Unauthenticated'], 401);
            }

            $validated = $request->validate([
                'credit_limit' => 'required|numeric|min:0',
                'notes' => 'nullable|string'
            ]);

            $customer = Customer::where('company_id', $user->company_id)
                ->findOrFail($customerId);

            $oldLimit = $customer->credit_limit;
            $customer->update(['credit_limit' => $validated['credit_limit']]);

            // Log the limit change as an adjustment
            CreditTransaction::create([
                'customer_id' => $customer->id,
                'company_id' => $user->company_id,
                'user_id' => $user->id,
                'type' => 'adjustment',
                'amount' => 0,
                'balance_before' => $customer->credit_balance,
                'balance_after' => $customer->credit_balance,
                'notes' => "Credit limit changed from {$oldLimit} to {$validated['credit_limit']}. " . ($validated['notes'] ?? ''),
            ]);

            return response()->json([
                'message' => 'Credit limit updated successfully',
                'customer' => $customer
            ]);
        } catch (\Exception $e) {
            Log::error('Error updating credit limit: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to update credit limit'], 500);
        }
    }

    /**
     * Manual balance adjustment
     */
    public function adjustBalance(Request $request, $customerId)
    {
        try {
            $user = auth()->user();
            if (!$user) {
                return response()->json(['error' => 'Unauthenticated'], 401);
            }

            $validated = $request->validate([
                'amount' => 'required|numeric',
                'reason' => 'required|string',
                'notes' => 'nullable|string'
            ]);

            $customer = Customer::where('company_id', $user->company_id)
                ->findOrFail($customerId);

            DB::beginTransaction();

            $balanceBefore = $customer->credit_balance;
            $newBalance = $balanceBefore + $validated['amount'];
            
            if ($newBalance < 0) {
                return response()->json(['error' => 'Adjustment would result in negative balance'], 422);
            }

            $customer->update(['credit_balance' => $newBalance]);

            CreditTransaction::create([
                'customer_id' => $customer->id,
                'company_id' => $user->company_id,
                'user_id' => $user->id,
                'type' => 'adjustment',
                'amount' => $validated['amount'],
                'balance_before' => $balanceBefore,
                'balance_after' => $newBalance,
                'notes' => "Manual adjustment: {$validated['reason']}. " . ($validated['notes'] ?? ''),
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Balance adjusted successfully',
                'customer' => $customer
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error adjusting balance: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to adjust balance'], 500);
        }
    }
}
