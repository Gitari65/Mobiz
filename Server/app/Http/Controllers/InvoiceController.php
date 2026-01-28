<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class InvoiceController extends Controller
{
    /**
     * List all invoices
     */
    public function index(Request $request)
    {
        try {
            $user = auth()->user();
            if (!$user) {
                return response()->json(['error' => 'Unauthenticated'], 401);
            }

            $query = Invoice::where('company_id', $user->company_id)
                ->with(['customer', 'items', 'user']);

            // Filter by status
            if ($request->has('status') && $request->status !== 'all') {
                $query->where('status', $request->status);
            }

            // Filter by customer
            if ($request->has('customer_id')) {
                $query->where('customer_id', $request->customer_id);
            }

            // Search
            if ($request->has('search')) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('invoice_number', 'like', "%{$search}%")
                      ->orWhereHas('customer', function($q) use ($search) {
                          $q->where('name', 'like', "%{$search}%");
                      });
                });
            }

            $invoices = $query->orderBy('invoice_date', 'desc')->get();

            return response()->json($invoices);
        } catch (\Exception $e) {
            Log::error('Error fetching invoices: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to fetch invoices'], 500);
        }
    }

    /**
     * Get a single invoice
     */
    public function show($id)
    {
        try {
            $user = auth()->user();
            if (!$user) {
                return response()->json(['error' => 'Unauthenticated'], 401);
            }

            $invoice = Invoice::where('company_id', $user->company_id)
                ->with(['customer', 'items.product', 'user'])
                ->findOrFail($id);

            return response()->json($invoice);
        } catch (\Exception $e) {
            Log::error('Error fetching invoice: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to fetch invoice'], 500);
        }
    }

    /**
     * Create a new invoice
     */
    public function store(Request $request)
    {
        try {
            $user = auth()->user();
            if (!$user) {
                return response()->json(['error' => 'Unauthenticated'], 401);
            }

            $validated = $request->validate([
                'customer_id' => 'required|exists:customers,id',
                'invoice_date' => 'required|date',
                'due_date' => 'required|date|after_or_equal:invoice_date',
                'items' => 'required|array|min:1',
                'items.*.product_id' => 'required|exists:products,id',
                'items.*.quantity' => 'required|integer|min:1',
                'items.*.unit_price' => 'required|numeric|min:0',
                'tax' => 'nullable|numeric|min:0',
                'notes' => 'nullable|string',
            ]);

            // Verify customer belongs to company
            $customer = Customer::where('company_id', $user->company_id)
                ->findOrFail($validated['customer_id']);

            DB::beginTransaction();

            // Calculate totals
            $subtotal = 0;
            foreach ($validated['items'] as $item) {
                $subtotal += $item['quantity'] * $item['unit_price'];
            }

            $tax = $validated['tax'] ?? 0;
            $total = $subtotal + $tax;

            // Generate invoice number
            $invoiceNumber = $this->generateInvoiceNumber($user->company_id);

            // Create invoice
            $invoice = Invoice::create([
                'customer_id' => $customer->id,
                'company_id' => $user->company_id,
                'user_id' => $user->id,
                'invoice_number' => $invoiceNumber,
                'invoice_date' => $validated['invoice_date'],
                'due_date' => $validated['due_date'],
                'subtotal' => $subtotal,
                'tax' => $tax,
                'total' => $total,
                'paid_amount' => 0,
                'balance' => $total,
                'status' => 'draft',
                'notes' => $validated['notes'] ?? null,
            ]);

            // Create invoice items
            foreach ($validated['items'] as $item) {
                $product = \App\Models\Product::find($item['product_id']);
                InvoiceItem::create([
                    'invoice_id' => $invoice->id,
                    'product_id' => $item['product_id'],
                    'product_name' => $product->name,
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'subtotal' => $item['quantity'] * $item['unit_price'],
                ]);
            }

            DB::commit();

            return response()->json([
                'message' => 'Invoice created successfully',
                'invoice' => $invoice->load('items', 'customer')
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error creating invoice: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to create invoice'], 500);
        }
    }

    /**
     * Update an invoice
     */
    public function update(Request $request, $id)
    {
        try {
            $user = auth()->user();
            if (!$user) {
                return response()->json(['error' => 'Unauthenticated'], 401);
            }

            $invoice = Invoice::where('company_id', $user->company_id)
                ->findOrFail($id);

            // Only allow editing draft invoices
            if ($invoice->status !== 'draft') {
                return response()->json(['error' => 'Only draft invoices can be edited'], 422);
            }

            $validated = $request->validate([
                'invoice_date' => 'sometimes|date',
                'due_date' => 'sometimes|date|after_or_equal:invoice_date',
                'items' => 'sometimes|array|min:1',
                'items.*.product_id' => 'required|exists:products,id',
                'items.*.quantity' => 'required|integer|min:1',
                'items.*.unit_price' => 'required|numeric|min:0',
                'tax' => 'nullable|numeric|min:0',
                'notes' => 'nullable|string',
                'status' => 'sometimes|in:draft,sent,paid,overdue,cancelled',
            ]);

            DB::beginTransaction();

            // Update items if provided
            if (isset($validated['items'])) {
                // Delete old items
                $invoice->items()->delete();

                // Calculate new totals
                $subtotal = 0;
                foreach ($validated['items'] as $item) {
                    $subtotal += $item['quantity'] * $item['unit_price'];
                    
                    $product = \App\Models\Product::find($item['product_id']);
                    InvoiceItem::create([
                        'invoice_id' => $invoice->id,
                        'product_id' => $item['product_id'],
                        'product_name' => $product->name,
                        'quantity' => $item['quantity'],
                        'unit_price' => $item['unit_price'],
                        'subtotal' => $item['quantity'] * $item['unit_price'],
                    ]);
                }

                $tax = $validated['tax'] ?? $invoice->tax;
                $total = $subtotal + $tax;

                $invoice->update([
                    'subtotal' => $subtotal,
                    'tax' => $tax,
                    'total' => $total,
                    'balance' => $total - $invoice->paid_amount,
                ]);
            }

            // Update other fields
            $invoice->update(array_filter($validated, function($key) {
                return !in_array($key, ['items', 'tax']);
            }, ARRAY_FILTER_USE_KEY));

            DB::commit();

            return response()->json([
                'message' => 'Invoice updated successfully',
                'invoice' => $invoice->load('items', 'customer')
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error updating invoice: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to update invoice'], 500);
        }
    }

    /**
     * Delete an invoice
     */
    public function destroy($id)
    {
        try {
            $user = auth()->user();
            if (!$user) {
                return response()->json(['error' => 'Unauthenticated'], 401);
            }

            $invoice = Invoice::where('company_id', $user->company_id)
                ->findOrFail($id);

            // Only allow deleting draft invoices
            if ($invoice->status !== 'draft') {
                return response()->json(['error' => 'Only draft invoices can be deleted'], 422);
            }

            $invoice->delete();

            return response()->json(['message' => 'Invoice deleted successfully']);
        } catch (\Exception $e) {
            Log::error('Error deleting invoice: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to delete invoice'], 500);
        }
    }

    /**
     * Record a payment for an invoice
     */
    public function recordPayment(Request $request, $id)
    {
        try {
            $user = auth()->user();
            if (!$user) {
                return response()->json(['error' => 'Unauthenticated'], 401);
            }

            $invoice = Invoice::where('company_id', $user->company_id)
                ->findOrFail($id);

            $validated = $request->validate([
                'amount' => 'required|numeric|min:0.01',
                'payment_method' => 'required|string',
                'transaction_number' => 'nullable|string',
                'notes' => 'nullable|string',
                'apply_to_credit' => 'boolean',
            ]);

            if ($validated['amount'] > $invoice->balance) {
                return response()->json(['error' => 'Payment amount exceeds invoice balance'], 422);
            }

            DB::beginTransaction();

            $invoice->increment('paid_amount', $validated['amount']);
            $invoice->decrement('balance', $validated['amount']);
            $invoice = $invoice->fresh();

            // Update status
            if ($invoice->balance <= 0) {
                $invoice->update(['status' => 'paid']);
            } elseif ($invoice->status === 'draft') {
                $invoice->update(['status' => 'sent']);
            }

            // If customer has credit and apply_to_credit is true, reduce their credit
            if ($validated['apply_to_credit'] ?? false) {
                $customer = $invoice->customer;
                if ($customer->credit_balance > 0) {
                    $creditToApply = min($validated['amount'], $customer->credit_balance);
                    
                    $balanceBefore = $customer->credit_balance;
                    $customer->decrement('credit_balance', $creditToApply);
                    
                    // Log credit transaction
                    \App\Models\CreditTransaction::create([
                        'customer_id' => $customer->id,
                        'company_id' => $user->company_id,
                        'user_id' => $user->id,
                        'type' => 'payment',
                        'amount' => $creditToApply,
                        'balance_before' => $balanceBefore,
                        'balance_after' => $customer->fresh()->credit_balance,
                        'transaction_number' => $validated['transaction_number'] ?? "INV-{$invoice->invoice_number}",
                        'payment_method' => 'credit_application',
                        'notes' => "Applied to invoice {$invoice->invoice_number}. " . ($validated['notes'] ?? ''),
                    ]);
                }
            }

            DB::commit();

            return response()->json([
                'message' => 'Payment recorded successfully',
                'invoice' => $invoice->load('items', 'customer')
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error recording payment: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to record payment'], 500);
        }
    }

    /**
     * Generate unique invoice number
     */
    private function generateInvoiceNumber($companyId)
    {
        $prefix = 'INV-';
        $year = date('Y');
        $lastInvoice = Invoice::where('company_id', $companyId)
            ->whereYear('created_at', $year)
            ->orderBy('id', 'desc')
            ->first();

        if ($lastInvoice) {
            $lastNumber = (int) substr($lastInvoice->invoice_number, -6);
            $newNumber = str_pad($lastNumber + 1, 6, '0', STR_PAD_LEFT);
        } else {
            $newNumber = '000001';
        }

        return $prefix . $year . '-' . $newNumber;
    }
}
