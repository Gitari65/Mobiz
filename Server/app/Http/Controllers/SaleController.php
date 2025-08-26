<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SaleController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'items'                  => 'required|array|min:1',
            'items.*.product_id'     => 'required|exists:products,id',
            'items.*.quantity'       => 'required|integer|min:1',
            'items.*.price'          => 'required|numeric|min:0',
        ]);

        DB::beginTransaction();

        try {
            // Create the sale record
            $sale = Sale::create([
                'total' => collect($validated['items'])
                    ->sum(fn($item) => $item['price'] * $item['quantity']),
            ]);

            // Create sale items & update stock
            foreach ($validated['items'] as $item) {
                SaleItem::create([
                    'sale_id'     => $sale->id,
                    'product_id'  => $item['product_id'],
                    'quantity'    => $item['quantity'],
                    'unit_price'  => $item['price'],
                    'total_price' => $item['price'] * $item['quantity'],
                ]);

                // Reduce product stock
                $product = Product::findOrFail($item['product_id']);
                if ($product->stock_quantity < $item['quantity']) {
                    throw new \Exception("Not enough stock for {$product->name}");
                }
                $product->stock_quantity -= $item['quantity'];
                $product->save();
            }

            DB::commit();

            return response()->json([
                'message' => 'Sale recorded successfully',
                'sale'    => $sale->load('saleItems.product')
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Sale failed',
                'error'   => $e->getMessage(),
            ], 400);
        }
    }
}
