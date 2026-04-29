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
                'items.*.quantity' => 'required|numeric|min:0.0001',
                'items.*.cost' => 'required|numeric|min:0',
                'items.*.uom_id' => 'nullable|exists:u_o_m_s,id',
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
                    'uom_id' => $item['uom_id'] ?? null,
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['cost'],
                    'total_price' => $totalItemCost,
                    'notes' => $item['notes'] ?? null
                ]);

                // Update product stock
                $product = Product::with('empties')->find($item['product_id']);
                $purchaseUomId = $item['uom_id'] ?? $product->purchase_uom_id ?? $product->getBaseUomId();
                $product->addStockForUom((float) $item['quantity'], $purchaseUomId);
                
                // Auto-increase linked returnables/empties based on quantity ratio
                foreach ($product->empties as $empty) {
                    // Calculate how many empties to add based on the ratio
                    $emptyQuantityToAdd = (float) $item['quantity'] * $empty->pivot->quantity;
                    
                    // Add stock to the returnable/empty product
                    $emptyProduct = Product::find($empty->id);
                    if ($emptyProduct) {
                        // Add stock using the empty's purchase UOM (usually it's the same as base UOM for returnables)
                        $emptyPurchaseUomId = $emptyProduct->purchase_uom_id ?? $emptyProduct->getBaseUomId();
                        $emptyProduct->addStockForUom($emptyQuantityToAdd, $emptyPurchaseUomId);
                    }
                }
                
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