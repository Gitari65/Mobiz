<?php

namespace App\Http\Controllers;

use App\Models\Promotion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class PromotionController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        
        $query = Promotion::where('company_id', $user->company_id)
            ->with(['creator'])
            ->orderBy('priority', 'desc')
            ->orderBy('created_at', 'desc');

        // Filter by status
        if ($request->has('status')) {
            if ($request->status === 'active') {
                $query->active();
            } elseif ($request->status === 'inactive') {
                $query->where('is_active', false);
            }
        }

        $promotions = $query->get();
        
        return response()->json($promotions);
    }

    public function store(Request $request)
    {
        $user = $request->user();
        
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'required|in:percentage,fixed_amount,buy_x_get_y,spend_save,bulk_discount',
            'discount_value' => 'nullable|numeric|min:0',
            'buy_quantity' => 'nullable|integer|min:1',
            'get_quantity' => 'nullable|integer|min:1',
            'minimum_purchase' => 'nullable|numeric|min:0',
            'minimum_quantity' => 'nullable|integer|min:1',
            'scope' => 'required|in:all,category,product,customer_group',
            'scope_items' => 'nullable|array',
            'first_time_only' => 'boolean',
            'customer_groups' => 'nullable|array',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'usage_limit_total' => 'nullable|integer|min:1',
            'usage_limit_per_customer' => 'nullable|integer|min:1',
            'is_active' => 'boolean',
            'priority' => 'nullable|integer|min:0',
            'is_stackable' => 'boolean',
        ]);

        $validated['company_id'] = $user->company_id;
        $validated['created_by'] = $user->id;
        $validated['usage_count'] = 0;

        $promotion = Promotion::create($validated);

        return response()->json([
            'message' => 'Promotion created successfully',
            'promotion' => $promotion->load('creator')
        ], 201);
    }

    public function show($id)
    {
        $promotion = Promotion::with(['creator', 'usages'])->findOrFail($id);
        return response()->json($promotion);
    }

    public function update(Request $request, $id)
    {
        $user = $request->user();
        $promotion = Promotion::findOrFail($id);

        if ($promotion->company_id !== $user->company_id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'type' => 'sometimes|in:percentage,fixed_amount,buy_x_get_y,spend_save,bulk_discount',
            'discount_value' => 'nullable|numeric|min:0',
            'buy_quantity' => 'nullable|integer|min:1',
            'get_quantity' => 'nullable|integer|min:1',
            'minimum_purchase' => 'nullable|numeric|min:0',
            'minimum_quantity' => 'nullable|integer|min:1',
            'scope' => 'sometimes|in:all,category,product,customer_group',
            'scope_items' => 'nullable|array',
            'first_time_only' => 'boolean',
            'customer_groups' => 'nullable|array',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'usage_limit_total' => 'nullable|integer|min:1',
            'usage_limit_per_customer' => 'nullable|integer|min:1',
            'is_active' => 'boolean',
            'priority' => 'nullable|integer|min:0',
            'is_stackable' => 'boolean',
        ]);

        $promotion->update($validated);

        return response()->json([
            'message' => 'Promotion updated successfully',
            'promotion' => $promotion->fresh()->load('creator')
        ]);
    }

    public function destroy($id)
    {
        $promotion = Promotion::findOrFail($id);
        
        // Check if promotion has been used
        if ($promotion->usage_count > 0) {
            return response()->json([
                'error' => 'Cannot delete promotion that has been used. Consider deactivating it instead.'
            ], 409);
        }

        $promotion->delete();
        
        return response()->json(['message' => 'Promotion deleted successfully']);
    }

    public function toggleStatus($id)
    {
        $promotion = Promotion::findOrFail($id);
        $promotion->is_active = !$promotion->is_active;
        $promotion->save();

        return response()->json([
            'message' => 'Promotion status updated',
            'promotion' => $promotion
        ]);
    }

    // Get active promotions for POS
    public function getActivePromotions(Request $request)
    {
        $user = $request->user();
        
        $promotions = Promotion::where('company_id', $user->company_id)
            ->active()
            ->orderBy('priority', 'desc')
            ->get();

        return response()->json($promotions);
    }

    // Calculate applicable discount for a cart
    public function calculateDiscount(Request $request)
    {
        $request->validate([
            'cart_total' => 'required|numeric',
            'cart_items' => 'required|array',
            'cart_items.*.product_id' => 'required|integer',
            'cart_items.*.quantity' => 'required|integer|min:1',
            'cart_items.*.price' => 'required|numeric',
            'customer_id' => 'nullable|integer'
        ]);

        try {
            $companyId = Auth::user()->company_id;
            $cartTotal = $request->cart_total;
            $cartItems = $request->cart_items;
            $customerId = $request->customer_id;

            // Get active promotions for this company
            $activePromotions = Promotion::where('company_id', $companyId)
                ->where('is_active', true)
                ->where('start_date', '<=', now())
                ->where(function($q) {
                    $q->whereNull('end_date')->orWhere('end_date', '>=', now());
                })
                ->orderBy('priority', 'desc')
                ->get();

            Log::info('🎯 Promo Calculation Started', [
                'company_id' => $companyId,
                'cart_total' => $cartTotal,
                'cart_items_count' => count($cartItems),
                'active_promos_found' => $activePromotions->count()
            ]);

            $applicablePromotions = [];
            $totalDiscount = 0;

            foreach ($activePromotions as $promo) {
                // Check usage limits
                if ($promo->usage_limit_total && $promo->usage_count >= $promo->usage_limit_total) {
                    Log::info("⏭️ Skipping promo {$promo->name}: total usage limit reached");
                    continue;
                }

                if ($customerId && $promo->usage_limit_per_customer) {
                    $customerUsage = DB::table('sale_promotions')
                        ->join('sales', 'sale_promotions.sale_id', '=', 'sales.id')
                        ->where('sale_promotions.promotion_id', $promo->id)
                        ->where('sales.customer_id', $customerId)
                        ->count();
                    
                    if ($customerUsage >= $promo->usage_limit_per_customer) {
                        Log::info("⏭️ Skipping promo {$promo->name}: customer usage limit reached");
                        continue;
                    }
                }

                $discount = 0;
                $matchedItems = [];

                // Decode scope_items (it's stored as JSON)
                $scopeItems = is_string($promo->scope_items) 
                    ? json_decode($promo->scope_items, true) 
                    : $promo->scope_items;
                
                if (!is_array($scopeItems)) {
                    $scopeItems = [];
                }

                Log::info("🔍 Checking promo: {$promo->name}", [
                    'type' => $promo->type,
                    'scope' => $promo->scope,
                    'scope_items' => $scopeItems
                ]);

                switch ($promo->scope) {
                    case 'all':
                        $matchedItems = $cartItems;
                        break;

                    case 'product':
                        // Match specific product IDs
                        $matchedItems = array_filter($cartItems, function($item) use ($scopeItems) {
                            $productId = (int)$item['product_id'];
                            $match = in_array($productId, array_map('intval', $scopeItems));
                            if ($match) {
                                Log::info("✅ Product {$productId} matched in scope_items");
                            }
                            return $match;
                        });
                        break;

                    case 'category':
                        // Get products in matching categories
                        $products = \App\Models\Product::whereIn('id', array_column($cartItems, 'product_id'))
                            ->whereIn('category', $scopeItems)
                            ->pluck('id')
                            ->toArray();
                        
                        $matchedItems = array_filter($cartItems, function($item) use ($products) {
                            $match = in_array($item['product_id'], $products);
                            if ($match) {
                                Log::info("✅ Product {$item['product_id']} matched by category");
                            }
                            return $match;
                        });
                        break;
                }

                Log::info("📊 Matched items for {$promo->name}", [
                    'matched_count' => count($matchedItems),
                    'matched_product_ids' => array_column($matchedItems, 'product_id')
                ]);

                if (empty($matchedItems)) {
                    Log::info("⏭️ No matching items for promo: {$promo->name}");
                    continue;
                }

                // Calculate discount based on promotion type
                switch ($promo->type) {
                    case 'percentage':
                        $matchedTotal = array_reduce($matchedItems, function($sum, $item) {
                            return $sum + ($item['price'] * $item['quantity']);
                        }, 0);

                        // Check minimum quantity
                        $totalQty = array_reduce($matchedItems, function($sum, $item) {
                            return $sum + $item['quantity'];
                        }, 0);

                        if ($promo->minimum_quantity && $totalQty < $promo->minimum_quantity) {
                            Log::info("⏭️ Minimum quantity not met: need {$promo->minimum_quantity}, have {$totalQty}");
                            continue 2;
                        }

                        $discount = $matchedTotal * ($promo->discount_value / 100);
                        Log::info("💰 Percentage discount calculated", [
                            'matched_total' => $matchedTotal,
                            'percentage' => $promo->discount_value,
                            'discount' => $discount
                        ]);
                        break;

                    case 'fixed_amount':
                        // Check minimum purchase
                        if ($promo->minimum_purchase && $cartTotal < $promo->minimum_purchase) {
                            Log::info("⏭️ Minimum purchase not met: need {$promo->minimum_purchase}, have {$cartTotal}");
                            continue 2;
                        }
                        $discount = $promo->discount_value;
                        break;

                    case 'buy_x_get_y':
                        // For each matched item, calculate how many free items they get
                        foreach ($matchedItems as $item) {
                            $quantity = $item['quantity'];
                            $buyQty = $promo->buy_quantity;
                            $getQty = $promo->get_quantity;
                            
                            // How many complete sets can we make?
                            $sets = floor($quantity / $buyQty);
                            $freeItems = $sets * $getQty;
                            
                            if ($freeItems > 0) {
                                // Discount is the price of free items
                                $discount += $item['price'] * $freeItems;
                                
                                Log::info("🎁 Buy X Get Y applied", [
                                    'product_id' => $item['product_id'],
                                    'quantity' => $quantity,
                                    'buy' => $buyQty,
                                    'get' => $getQty,
                                    'sets' => $sets,
                                    'free_items' => $freeItems,
                                    'discount' => $item['price'] * $freeItems
                                ]);
                            }
                        }
                        break;
                }

                if ($discount > 0) {
                    $applicablePromotions[] = [
                        'id' => $promo->id,
                        'name' => $promo->name,
                        'type' => $promo->type,
                        'discount' => round($discount, 2)
                    ];
                    $totalDiscount += $discount;

                    Log::info("✅ Promotion applied: {$promo->name}", [
                        'discount' => $discount,
                        'total_discount_so_far' => $totalDiscount
                    ]);
                }

                // If not stackable and we have a discount, stop here
                if (!$promo->is_stackable && $discount > 0) {
                    Log::info("🛑 Stopping at non-stackable promotion: {$promo->name}");
                    break;
                }
            }

            Log::info('🎉 Promo Calculation Complete', [
                'applicable_promotions' => count($applicablePromotions),
                'total_discount' => $totalDiscount
            ]);

            return response()->json([
                'applicable_promotions' => $applicablePromotions,
                'total_discount' => round($totalDiscount, 2)
            ]);

        } catch (\Exception $e) {
            Log::error('❌ Promotion calculation error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'applicable_promotions' => [],
                'total_discount' => 0,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    private function calculatePromotionDiscount($promotion, $data)
    {
        $cartTotal = $data['cart_total'];
        $cartItems = $data['cart_items'];

        // Build a quick lookup for items by product_id and sum totals
        $itemsByProduct = [];
        foreach ($cartItems as $item) {
            $pid = (int) $item['product_id'];
            if (!isset($itemsByProduct[$pid])) {
                $itemsByProduct[$pid] = ['quantity' => 0, 'price' => (float) $item['price']];
            }
            $itemsByProduct[$pid]['quantity'] += (int) $item['quantity'];
            // If differing prices appear, use the lowest for conservative discount
            $itemsByProduct[$pid]['price'] = min($itemsByProduct[$pid]['price'], (float) $item['price']);
        }

        // Apply scope filtering
        $scopedItems = $itemsByProduct;
        if ($promotion->scope !== 'all') {
            $scopedItems = [];
            $scopeItems = (array) ($promotion->scope_items ?? []);

            if ($promotion->scope === 'product') {
                $allowedIds = array_map('intval', $scopeItems);
                foreach ($itemsByProduct as $pid => $info) {
                    if (in_array($pid, $allowedIds, true)) {
                        $scopedItems[$pid] = $info;
                    }
                }
            } elseif ($promotion->scope === 'category') {
                // scope_items contains category names; fetch product categories
                $productIds = array_keys($itemsByProduct);
                $products = \App\Models\Product::whereIn('id', $productIds)->select('id','category')->get();
                $allowedCategories = array_map('strval', $scopeItems);
                foreach ($products as $p) {
                    if ($p->category && in_array($p->category, $allowedCategories, true)) {
                        $scopedItems[$p->id] = $itemsByProduct[$p->id];
                    }
                }
            } elseif ($promotion->scope === 'customer_group') {
                // Not implemented here; default to all items
                $scopedItems = $itemsByProduct;
            }
        }

        // Check minimum purchase
        if ($promotion->minimum_purchase && $cartTotal < $promotion->minimum_purchase) {
            return 0;
        }

        // Check minimum quantity based on scoped items
        if ($promotion->minimum_quantity) {
            $totalQty = 0;
            foreach ($scopedItems as $info) { $totalQty += (int) $info['quantity']; }
            if ($totalQty < $promotion->minimum_quantity) { return 0; }
        }

        switch ($promotion->type) {
            case 'percentage':
                // Apply percentage only to scoped subtotal
                $scopedSubtotal = 0.0;
                foreach ($scopedItems as $info) { $scopedSubtotal += $info['quantity'] * $info['price']; }
                return ($scopedSubtotal * (float) $promotion->discount_value) / 100.0;

            case 'fixed_amount':
                // Cap fixed discount to scoped subtotal
                $scopedSubtotal = 0.0;
                foreach ($scopedItems as $info) { $scopedSubtotal += $info['quantity'] * $info['price']; }
                return min((float) $promotion->discount_value, $scopedSubtotal);

            case 'spend_save':
                // Based on scoped subtotal meeting minimum
                $scopedSubtotal = 0.0;
                foreach ($scopedItems as $info) { $scopedSubtotal += $info['quantity'] * $info['price']; }
                if ($promotion->minimum_purchase && $scopedSubtotal < (float) $promotion->minimum_purchase) {
                    return 0;
                }
                return ($scopedSubtotal * (float) $promotion->discount_value) / 100.0;

            case 'bulk_discount':
                $scopedQty = 0;
                $scopedSubtotal = 0.0;
                foreach ($scopedItems as $info) { 
                    $scopedQty += (int) $info['quantity'];
                    $scopedSubtotal += $info['quantity'] * $info['price'];
                }
                if ($scopedQty >= (int) $promotion->minimum_quantity) {
                    return ($scopedSubtotal * (float) $promotion->discount_value) / 100.0;
                }
                return 0.0;

            case 'buy_x_get_y':
                $buy = (int) ($promotion->buy_quantity ?? 0);
                $get = (int) ($promotion->get_quantity ?? 0);
                if ($buy <= 0 || $get <= 0) { return 0.0; }
                $discount = 0.0;
                // Calculate per product within scope
                foreach ($scopedItems as $info) {
                    $q = (int) $info['quantity'];
                    if ($q < $buy) { continue; }
                    // Each full group of BUY grants GET items free (no need to also purchase the free units)
                    $freeGroups = intdiv($q, $buy);
                    $freeItems = $freeGroups * $get;
                    $discount += $freeItems * (float) $info['price'];
                }
                return $discount;

            default:
                return 0;
        }
    }
}
