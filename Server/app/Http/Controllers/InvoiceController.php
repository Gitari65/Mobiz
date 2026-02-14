<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class InvoiceController extends Controller
{
    public function index(Request $request)
    {
        $query = Invoice::with(['supplier', 'customer', 'company', 'items.product']);
        if ($request->has('type')) {
            $query->where('type', $request->type);
        }
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }
        return response()->json($query->orderByDesc('created_at')->paginate(20));
    }

    public function show($id)
    {
        $invoice = Invoice::with(['supplier', 'customer', 'company', 'items.product'])->findOrFail($id);
        return response()->json($invoice);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'type' => 'required|string|in:purchase,sale,service,other',
            'supplier_id' => 'nullable|exists:suppliers,id',
            'customer_id' => 'nullable|exists:customers,id',
            'company_id' => 'nullable|exists:companies,id',
            'invoice_date' => 'nullable|date',
            'due_date' => 'nullable|date',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'nullable|exists:products,id',
            'items.*.description' => 'nullable|string',
            'items.*.quantity' => 'required|numeric|min:1',
            'items.*.unit_price' => 'required|numeric|min:0',
            'tax' => 'nullable|numeric|min:0',
            'discount' => 'nullable|numeric|min:0',
            'paid_amount' => 'nullable|numeric|min:0',
        ]);

        DB::beginTransaction();
        try {
            $user = Auth::user();
            $userId = $user ? $user->id : 1;
            
            // Use authenticated user's company or provided company_id
            $companyId = $validated['company_id'] ?? ($user ? $user->company_id : null);
            
            if (!$companyId) {
                return response()->json([
                    'error' => 'Failed to create invoice',
                    'message' => 'No company associated with user'
                ], 400);
            }

            // Generate invoice number
            $invoiceNumber = $request->input('invoice_number') ?? Invoice::generateInvoiceNumber();

            // Calculate totals
            $subtotal = 0;
            foreach ($validated['items'] as $item) {
                $subtotal += (float)$item['quantity'] * (float)$item['unit_price'];
            }
            
            $tax = (float)($validated['tax'] ?? 0);
            $discount = (float)($validated['discount'] ?? 0);
            $total = $subtotal + $tax - $discount;
            $paid = (float)($validated['paid_amount'] ?? 0);
            $balance = $total - $paid;

            // Validate supplier_id for purchase invoices
            if ($validated['type'] === 'purchase' && !empty($validated['supplier_id'])) {
                $supplierExists = \App\Models\Supplier::where('id', $validated['supplier_id'])
                    ->where('company_id', $companyId)
                    ->exists();
                
                if (!$supplierExists) {
                    return response()->json([
                        'error' => 'Failed to create invoice',
                        'message' => 'Supplier not found for this company'
                    ], 422);
                }
            }

            // Validate customer_id for sale invoices
            if ($validated['type'] === 'sale' && !empty($validated['customer_id'])) {
                $customerExists = \App\Models\Customer::where('id', $validated['customer_id'])
                    ->where('company_id', $companyId)
                    ->exists();
                
                if (!$customerExists) {
                    return response()->json([
                        'error' => 'Failed to create invoice',
                        'message' => 'Customer not found for this company'
                    ], 422);
                }
            }

            // Create invoice
            $invoice = Invoice::create([
                'invoice_number' => $invoiceNumber,
                'type' => $validated['type'],
                'supplier_id' => $validated['supplier_id'] ?? null,
                'customer_id' => $validated['customer_id'] ?? null,
                'company_id' => $companyId,
                'user_id' => $userId,
                'invoice_date' => $validated['invoice_date'] ?? now()->toDateString(),
                'due_date' => $validated['due_date'] ?? null,
                'subtotal' => $subtotal,
                'tax' => $tax,
                'discount' => $discount,
                'total' => $total,
                'paid_amount' => $paid,
                'balance' => $balance,
                'status' => $balance <= 0 ? 'paid' : 'draft',
                'notes' => $validated['notes'] ?? null,
            ]);

            // Create invoice items
            foreach ($validated['items'] as $item) {
                InvoiceItem::create([
                    'invoice_id' => $invoice->id,
                    'product_id' => $item['product_id'] ?? null,
                    'description' => $item['description'] ?? null,
                    'quantity' => (int)$item['quantity'],
                    'unit_price' => (float)$item['unit_price'],
                    'total_price' => (float)$item['quantity'] * (float)$item['unit_price'],
                ]);
                
                // Update stock for purchase/sale
                if ($item['product_id']) {
                    $product = Product::find($item['product_id']);
                    if ($product) {
                        if ($invoice->type === 'purchase') {
                            $product->increment('stock_quantity', (int)$item['quantity']);
                        } elseif ($invoice->type === 'sale') {
                            if ($product->stock_quantity >= $item['quantity']) {
                                $product->decrement('stock_quantity', (int)$item['quantity']);
                            }
                        }
                    }
                }
            }

            $invoice->load(['supplier', 'customer', 'company', 'items.product']);

            DB::commit();

            // Audit log
            Log::info('Invoice created', [
                'user_id' => $userId,
                'invoice_id' => $invoice->id,
                'type' => $invoice->type,
                'supplier_id' => $invoice->supplier_id,
                'customer_id' => $invoice->customer_id,
                'company_id' => $companyId,
                'total' => $invoice->total
            ]);

            return response()->json([
                'message' => 'Invoice created successfully',
                'data' => $invoice
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            Log::error('Invoice validation error', [
                'errors' => $e->errors(),
                'user_id' => Auth::id()
            ]);
            return response()->json([
                'error' => 'Validation failed',
                'details' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Invoice creation failed', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'error' => 'Failed to create invoice',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        $invoice = Invoice::findOrFail($id);
        $validated = $request->validate([
            'status' => 'nullable|string|in:draft,sent,paid,cancelled',
            'due_date' => 'nullable|date',
            'notes' => 'nullable|string',
        ]);
        $invoice->update($validated);

        // Audit log
        Log::info('Invoice updated', [
            'user_id' => Auth::id(),
            'invoice_id' => $invoice->id,
            'changes' => $validated
        ]);

        return response()->json($invoice);
    }

    public function destroy($id)
    {
        $invoice = Invoice::findOrFail($id);
        $invoiceId = $invoice->id;
        $userId = Auth::id();

        // Optionally: reverse stock changes if needed
        $invoice->delete();

        // Audit log
        Log::info('Invoice deleted', [
            'user_id' => $userId,
            'invoice_id' => $invoiceId
        ]);

        return response()->json(['success' => true]);
    }
}
