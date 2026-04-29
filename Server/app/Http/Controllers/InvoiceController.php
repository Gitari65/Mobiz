<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\InvoicePayment;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class InvoiceController extends Controller
{
    public function index(Request $request)
    {
        try {
            $user = Auth::user();
            
            // Check if user is authenticated and is admin
            if (!$user) {
                return response()->json([
                    'error' => 'Unauthorized',
                    'message' => 'User not authenticated'
                ], 401);
            }
            
            if (!$user->role || !in_array(strtolower($user->role->name), ['admin', 'administrator'])) {
                return response()->json([
                    'error' => 'Unauthorized',
                    'message' => 'Only admins can access invoices'
                ], 403);
            }
            
            if (!$user->company_id) {
                return response()->json([
                    'error' => 'Invalid user',
                    'message' => 'User has no company assigned'
                ], 400);
            }
            
            $query = Invoice::with(['supplier', 'customer', 'company', 'items.product', 'items.uom', 'creator'])
                ->where('company_id', $user->company_id);
            
            if ($request->has('type')) {
                $query->where('type', $request->type);
            }
            if ($request->has('status')) {
                $query->where('status', $request->status);
            }
            if ($request->has('search')) {
                $search = $request->input('search');
                $query->where(function($q) use ($search) {
                    $q->where('invoice_number', 'like', "%{$search}%")
                      ->orWhereHas('customer', fn($q) => $q->where('name', 'like', "%{$search}%"))
                      ->orWhereHas('supplier', fn($q) => $q->where('name', 'like', "%{$search}%"));
                });
            }
            
            $perPage = max(10, min((int) $request->input('per_page', 20), 200));
            return response()->json($query->orderByDesc('created_at')->paginate($perPage));
        } catch (\Exception $e) {
            Log::error('Invoice index error', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'error' => 'Failed to fetch invoices',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function show($id)
    {
        try {
            $user = Auth::user();
            
            // Check authorization
            if (!$user || !$user->role || !in_array(strtolower($user->role->name), ['admin', 'administrator'])) {
                return response()->json([
                    'error' => 'Unauthorized',
                    'message' => 'Only admins can view invoices'
                ], 403);
            }
            
            $invoice = Invoice::with(['supplier', 'customer', 'company', 'items.product', 'items.uom', 'creator'])
                ->where('company_id', $user->company_id)
                ->findOrFail($id);
            
            return response()->json($invoice);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'error' => 'Invoice not found',
                'message' => 'The requested invoice does not exist'
            ], 404);
        } catch (\Exception $e) {
            Log::error('Invoice show error', [
                'user_id' => Auth::id(),
                'invoice_id' => $id,
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'error' => 'Failed to fetch invoice',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function store(Request $request)
    {
        $user = Auth::user();
        
        // Check authorization
        if (!$user || !$user->role || !in_array(strtolower($user->role->name), ['admin', 'administrator'])) {
            return response()->json([
                'error' => 'Unauthorized',
                'message' => 'Only admins can create invoices'
            ], 403);
        }
        
        $validated = $request->validate([
            'type' => 'required|string|in:purchase,sale,service,other',
            'supplier_id' => 'nullable|exists:suppliers,id',
            'customer_id' => 'nullable|exists:customers,id',
            'invoice_date' => 'nullable|date',
            'due_date' => 'nullable|date',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'nullable|exists:products,id',
            'items.*.description' => 'nullable|string',
            'items.*.quantity' => 'required|numeric|min:0.0001',
            'items.*.uom_id' => 'nullable|exists:u_o_m_s,id',
            'items.*.unit_price' => 'required|numeric|min:0',
            'tax' => 'nullable|numeric|min:0',
            'discount' => 'nullable|numeric|min:0',
            'paid_amount' => 'nullable|numeric|min:0',
        ]);

        DB::beginTransaction();
        try {
            $companyId = $user->company_id;
            
            if (!$companyId) {
                return response()->json([
                    'error' => 'Failed to create invoice',
                    'message' => 'No company associated with user'
                ], 400);
            }

            // Generate invoice number
            $invoiceNumber = Invoice::generateInvoiceNumber();

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
                'user_id' => $user->id,
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
                    'uom_id' => $item['uom_id'] ?? null,
                    'quantity' => $item['quantity'],
                    'unit_price' => (float)$item['unit_price'],
                    'total' => (float)$item['quantity'] * (float)$item['unit_price'],
                ]);
                
                // Update stock for purchase/sale
                if ($item['product_id']) {
                    $product = Product::find($item['product_id']);
                    if ($product) {
                        $itemUomId = $item['uom_id'] ?? ($invoice->type === 'purchase'
                            ? ($product->purchase_uom_id ?? $product->getBaseUomId())
                            : $product->getDefaultSaleUomId());

                        if ($invoice->type === 'purchase') {
                            $product->addStockForUom((float) $item['quantity'], $itemUomId);
                        } elseif ($invoice->type === 'sale') {
                            if ($product->hasEnoughStockForQuantity((float) $item['quantity'], $itemUomId)) {
                                $product->subtractStockForUom((float) $item['quantity'], $itemUomId);
                            }
                        }
                    }
                }
            }

            $invoice->load(['supplier', 'customer', 'company', 'items.product', 'items.uom']);

            DB::commit();

            // Audit log
            Log::info('Invoice created', [
                'user_id' => $user->id,
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
        try {
            $user = Auth::user();
            
            if (!$user || !$user->role || !in_array(strtolower($user->role->name), ['admin', 'administrator'])) {
                return response()->json([
                    'error' => 'Unauthorized',
                    'message' => 'Only admins can update invoices'
                ], 403);
            }
            
            $invoice = Invoice::where('company_id', $user->company_id)->findOrFail($id);
            
            $validated = $request->validate([
                'status' => 'nullable|string|in:draft,sent,paid,cancelled,reversed',
                'due_date' => 'nullable|date',
                'notes' => 'nullable|string',
                'payment_method' => 'nullable|string|max:255',
                'mpesa_receipt_number' => 'nullable|string|max:255',
                'mpesa_phone_number' => 'nullable|string|max:20',
            ]);
            $invoice->update($validated);

            Log::info('Invoice updated', [
                'user_id' => Auth::id(),
                'invoice_id' => $invoice->id,
                'changes' => $validated
            ]);

            return response()->json($invoice);
        } catch (\Exception $e) {
            Log::error('Invoice update error', ['user_id' => Auth::id(), 'error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to update invoice', 'message' => $e->getMessage()], 500);
        }
    }

    public function reverse(Request $request, $id)
    {
        try {
            $user = Auth::user();

            if (!$user || !$user->role || !in_array(strtolower($user->role->name), ['admin', 'administrator'])) {
                return response()->json([
                    'error' => 'Unauthorized',
                    'message' => 'Only admins can reverse invoices'
                ], 403);
            }

            $invoice = Invoice::with('items')
                ->where('company_id', $user->company_id)
                ->findOrFail($id);

            if ($invoice->status === 'reversed') {
                return response()->json([
                    'error' => 'Already reversed',
                    'message' => 'This invoice has already been reversed'
                ], 422);
            }

            $reason = trim((string) $request->input('reason', ''));

            DB::beginTransaction();

            foreach ($invoice->items as $item) {
                if (!$item->product_id) {
                    continue;
                }

                $qty = (float) $item->quantity;
                if ($qty <= 0) {
                    continue;
                }

                $product = Product::where('company_id', $user->company_id)
                    ->where('id', $item->product_id)
                    ->lockForUpdate()
                    ->first();

                if (!$product) {
                    continue;
                }

                $itemUomId = $item->uom_id ?: ($invoice->type === 'purchase'
                    ? ($product->purchase_uom_id ?? $product->getBaseUomId())
                    : $product->getDefaultSaleUomId());

                if ($invoice->type === 'sale') {
                    // Reverse sale: returned items go back to stock.
                    $product->addStockForUom($qty, $itemUomId);
                } elseif ($invoice->type === 'purchase') {
                    // Reverse purchase: remove previously added stock.
                    if (!$product->hasEnoughStockForQuantity($qty, $itemUomId)) {
                        DB::rollBack();
                        return response()->json([
                            'error' => 'Insufficient stock to reverse purchase invoice',
                            'message' => "Cannot reverse invoice: product {$product->name} has insufficient stock"
                        ], 422);
                    }
                    $product->subtractStockForUom($qty, $itemUomId);
                }
            }

            // Remove payment records and reset payable fields so money values reflect reversal.
            InvoicePayment::where('invoice_id', $invoice->id)->delete();

            $reversalNote = 'Reversed by ' . ($user->name ?? 'Admin') . ' on ' . now()->toDateTimeString();
            if ($reason !== '') {
                $reversalNote .= '. Reason: ' . $reason;
            }

            $existingNotes = trim((string) ($invoice->notes ?? ''));
            $invoice->status = 'reversed';
            $invoice->paid_amount = 0;
            $invoice->balance = 0;
            $invoice->notes = $existingNotes !== ''
                ? ($existingNotes . "\n" . $reversalNote)
                : $reversalNote;
            $invoice->save();

            DB::commit();

            Log::info('Invoice reversed', [
                'user_id' => $user->id,
                'invoice_id' => $invoice->id,
                'company_id' => $user->company_id,
                'reason' => $reason
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Invoice reversed successfully',
                'data' => $invoice->fresh(['items.product', 'customer', 'supplier'])
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'error' => 'Invoice not found',
                'message' => 'The requested invoice does not exist'
            ], 404);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Invoice reverse error', [
                'user_id' => Auth::id(),
                'invoice_id' => $id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'error' => 'Failed to reverse invoice',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $user = Auth::user();
            
            if (!$user || !$user->role || !in_array(strtolower($user->role->name), ['admin', 'administrator'])) {
                return response()->json([
                    'error' => 'Unauthorized',
                    'message' => 'Only admins can delete invoices'
                ], 403);
            }
            
            $invoice = Invoice::where('company_id', $user->company_id)->findOrFail($id);
            $invoiceId = $invoice->id;
            $userId = Auth::id();

            $invoice->items()->delete();
            $invoice->delete();

            Log::info('Invoice deleted', [
                'user_id' => $userId,
                'invoice_id' => $invoiceId
            ]);

            return response()->json(['success' => true, 'message' => 'Invoice deleted successfully']);
        } catch (\Exception $e) {
            Log::error('Invoice delete error', ['user_id' => Auth::id(), 'error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to delete invoice', 'message' => $e->getMessage()], 500);
        }
    }
}
