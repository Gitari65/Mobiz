<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\Product;

class InventoryController extends Controller
{
    /**
     * Handle inventory restock/purchase
     */
    public function restock(Request $request)
    {
        $user = auth()->user();
        if (!$user) {
            return response()->json(['error' => 'Not authenticated'], 401);
        }

        $validated = $request->validate([
            'supplier_id' => 'nullable|integer|exists:suppliers,id',
            'supplier_name' => 'nullable|string',
            'invoice_number' => 'required|string',
            'invoice_date' => 'required|date',
            'warehouse_id' => 'nullable|integer|exists:warehouses,id',
            'tax_amount' => 'nullable|numeric|min:0',
            'shipping_cost' => 'nullable|numeric|min:0',
            'discount' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|integer|exists:products,id',
            'items.*.quantity' => 'required|numeric|min:1',
            'items.*.cost' => 'required|numeric|min:0',
            'items.*.notes' => 'nullable|string'
        ]);

        try {
            // Create purchase record
            $purchase = Purchase::create([
                'company_id' => $user->company_id,
                'supplier_id' => $validated['supplier_id'],
                'invoice_number' => $validated['invoice_number'],
                'invoice_date' => $validated['invoice_date'],
                'warehouse_id' => $validated['warehouse_id'],
                'tax_amount' => $validated['tax_amount'] ?? 0,
                'shipping_cost' => $validated['shipping_cost'] ?? 0,
                'discount' => $validated['discount'] ?? 0,
                'notes' => $validated['notes'] ?? '',
                'total_amount' => 0, // Will be calculated
                'status' => 'completed'
            ]);

            $totalAmount = 0;

            // Create purchase items and update product stock
            foreach ($validated['items'] as $item) {
                $itemTotal = $item['quantity'] * $item['cost'];
                $totalAmount += $itemTotal;

                PurchaseItem::create([
                    'purchase_id' => $purchase->id,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'unit_cost' => $item['cost'],
                    'total' => $itemTotal,
                    'notes' => $item['notes'] ?? ''
                ]);

                // Update product stock
                $product = Product::find($item['product_id']);
                if ($product) {
                    $product->stock += $item['quantity'];
                    $product->save();
                }
            }

            // Update purchase total
            $purchase->total_amount = $totalAmount;
            $purchase->save();

            return response()->json([
                'message' => 'Restock completed successfully',
                'purchase' => $purchase,
                'items_count' => count($validated['items'])
            ], 201);

        } catch (\Exception $e) {
            \Log::error('Restock error: ' . $e->getMessage());
            return response()->json([
                'error' => 'Failed to complete restock',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
