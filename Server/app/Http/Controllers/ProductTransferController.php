<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\WarehouseTransfer;
use App\Models\WarehouseTransferItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class ProductTransferController extends Controller
{
    /**
     * Create a stock transfer
     */
    public function store(Request $request)
    {
        try {
            $request->validate([
                'product_id' => 'required|exists:products,id',
                'from_warehouse_id' => 'required|exists:warehouses,id',
                'quantity' => 'required|numeric|min:0.0001',
                'destination_type' => 'required|in:warehouse,supplier_return,write_off,adjustment_out',
                'to_warehouse_id' => 'nullable|exists:warehouses,id',
                'external_target' => 'nullable|string|max:255',
                'reason' => 'nullable|string|max:255',
                'reference' => 'nullable|string|max:100',
                'note' => 'nullable|string'
            ]);

            DB::beginTransaction();

            // Verify product has sufficient stock
            $product = Product::findOrFail($request->product_id);
            
            if (!$product->hasEnoughStockForQuantity($request->quantity)) {
                return response()->json([
                    'error' => 'Insufficient stock',
                    'message' => "Not enough stock available. Available: {$product->stock_quantity}"
                ], 400);
            }

            // Create transfer record
            $transfer = WarehouseTransfer::create([
                'transfer_number' => $this->generateTransferNumber(),
                'product_id' => $request->product_id,
                'from_warehouse_id' => $request->from_warehouse_id,
                'to_warehouse_id' => $request->destination_type === 'warehouse' ? $request->to_warehouse_id : null,
                'transfer_type' => $request->destination_type,
                'quantity' => $request->quantity,
                'external_target' => $request->external_target,
                'reason' => $request->reason,
                'reference' => $request->reference,
                'note' => $request->note,
                'user_id' => Auth::id(),
            ]);

            // Update product stock
            // For warehouse transfers, stock stays in system (just changes warehouse)
            // For other types, stock is reduced from inventory
            if ($request->destination_type !== 'warehouse') {
                $product->subtractStockForUom((float) $request->quantity);
            }

            DB::commit();

            return response()->json([
                'message' => 'Stock transfer completed successfully',
                'transfer' => $transfer->load('product', 'fromWarehouse', 'toWarehouse', 'user')
            ], 201);

        } catch (\Exception $e) {
            DB::rollback();
            \Log::error('Product transfer error:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'error' => 'Failed to process transfer',
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Get transfer history
     */
    public function index()
    {
        try {
            $transfers = WarehouseTransfer::with('product', 'fromWarehouse', 'toWarehouse', 'user')
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(function ($transfer) {
                    return [
                        'id' => $transfer->id,
                        'transfer_number' => $transfer->transfer_number,
                        'product_id' => $transfer->product_id,
                        'product_name' => $transfer->product?->name,
                        'from_warehouse_id' => $transfer->from_warehouse_id,
                        'from_warehouse_name' => $transfer->fromWarehouse?->name,
                        'to_warehouse_id' => $transfer->to_warehouse_id,
                        'to_warehouse_name' => $transfer->toWarehouse?->name,
                        'transfer_type' => $transfer->transfer_type,
                        'quantity' => $transfer->quantity,
                        'external_target' => $transfer->external_target,
                        'reason' => $transfer->reason,
                        'reference' => $transfer->reference,
                        'note' => $transfer->note,
                        'user_id' => $transfer->user_id,
                        'user_name' => $transfer->user?->name,
                        'created_at' => $transfer->created_at,
                    ];
                });

            return response()->json($transfers);

        } catch (\Exception $e) {
            \Log::error('Failed to fetch transfer history:', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'error' => 'Failed to fetch transfer history',
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Generate unique transfer number
     */
    private function generateTransferNumber()
    {
        $prefix = 'TRF';
        $timestamp = now()->format('ymdHi');
        $random = strtoupper(substr(md5(time() . mt_rand()), 0, 4));
        
        return "{$prefix}-{$timestamp}-{$random}";
    }
}
