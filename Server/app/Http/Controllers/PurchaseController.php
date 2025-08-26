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
                'supplier_name' => 'nullable|string|max:255',
                'notes' => 'nullable|string',
                'items' => 'required|array|min:1',
                'items.*.product_id' => 'required|exists:products,id',
                'items.*.quantity' => 'required|integer|min:1',
                'items.*.cost' => 'required|numeric|min:0'
            ]);

            // Calculate total cost
            $totalCost = collect($request->items)->sum('cost');

            // Create the purchase record
            $purchase = Purchase::create([
                'supplier_name' => $request->supplier_name,
                'notes' => $request->notes,
                'total_cost' => $totalCost
            ]);

            // Create purchase items and update product stock
            foreach ($request->items as $item) {
                // Calculate unit price from total cost and quantity
                $unitPrice = $item['cost'] / $item['quantity'];
                
                // Create purchase item
                PurchaseItem::create([
                    'purchase_id' => $purchase->id,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $unitPrice,
                    'total_price' => $item['cost']
                ]);

                // Update product stock
                $product = Product::find($item['product_id']);
                $product->increment('stock_quantity', $item['quantity']);
                
                // Update product price if needed (optional)
                // $product->update(['price' => $unitPrice]);
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