<?php

namespace App\Http\Controllers;

use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PurchaseController extends Controller
{
    public function store(Request $request)
    {
        try {
            DB::beginTransaction();
            
            // Validate the request
            $request->validate([
                'supplier_id' => 'nullable|exists:suppliers,id',
                'supplier_name' => 'nullable|string|max:255',
                'invoice_number' => 'nullable|string|max:100',
                'invoice_date' => 'nullable|date',
                'warehouse_id' => 'nullable|exists:warehouses,id',
                'tax_amount' => 'nullable|numeric|min:0',
                'shipping_cost' => 'nullable|numeric|min:0',
                'discount' => 'nullable|numeric|min:0',
                'notes' => 'nullable|string',
                'items' => 'required|array|min:1',
                'items.*.product_id' => 'required|exists:products,id',
                'items.*.quantity' => 'required|integer|min:1',
                'items.*.cost' => 'required|numeric|min:0',
                'items.*.notes' => 'nullable|string'
            ]);

            // Calculate subtotal from items
            $subtotal = collect($request->items)->sum(function ($item) {
                return $item['quantity'] * $item['cost'];
            });

            // Calculate total cost with tax, shipping, and discount
            $taxAmount = $request->tax_amount ?? 0;
            $shippingCost = $request->shipping_cost ?? 0;
            $discount = $request->discount ?? 0;
            $totalCost = $subtotal + $taxAmount + $shippingCost - $discount;

            // Create the purchase record
            $purchase = Purchase::create([
                'supplier_id' => $request->supplier_id,
                'supplier_name' => $request->supplier_name,
                'invoice_number' => $request->invoice_number,
                'invoice_date' => $request->invoice_date,
                'warehouse_id' => $request->warehouse_id,
                'subtotal' => $subtotal,
                'tax_amount' => $taxAmount,
                'shipping_cost' => $shippingCost,
                'discount' => $discount,
                'total_cost' => $totalCost,
                'notes' => $request->notes
            ]);

            // Create purchase items and update product stock
            foreach ($request->items as $item) {
                $totalItemCost = $item['quantity'] * $item['cost'];
                
                // Create purchase item
                PurchaseItem::create([
                    'purchase_id' => $purchase->id,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['cost'],
                    'total_price' => $totalItemCost,
                    'notes' => $item['notes'] ?? null
                ]);

                // Update product stock
                $product = Product::find($item['product_id']);
                $product->increment('stock_quantity', $item['quantity']);
                
                // Optionally update cost_price if provided
                if (isset($item['cost']) && $item['cost'] > 0) {
                    $product->update(['cost_price' => $item['cost']]);
                }
            }

            DB::commit();

            return response()->json([
                'message' => 'Purchase recorded successfully',
                'purchase' => $purchase->load('items.product')
            ], 201);

        } catch (\Exception $e) {
            DB::rollback();
            
            return response()->json([
                'message' => 'Failed to record purchase',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function index()
    {
        $purchases = Purchase::with('items.product')->latest()->get();
        return response()->json($purchases);
    }

    public function show($id)
    {
        $purchase = Purchase::with('items.product')->findOrFail($id);
        return response()->json($purchase);
    }
}