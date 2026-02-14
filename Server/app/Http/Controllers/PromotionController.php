<?php

namespace App\Http\Controllers;

use App\Models\Promotion;
use App\Models\Product; // Import Product model
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
            'buy_products' => 'nullable|array',
            'get_products' => 'nullable|array',
            'bxgy_config' => 'nullable|array',
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

        // Validate that at least one minimum requirement is set
        if (empty($validated['minimum_purchase']) && empty($validated['minimum_quantity'])) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => [
                    'minimum_requirements' => ['At least one of minimum purchase amount or minimum quantity must be specified for the promotion to trigger.']
                ]
            ], 422);
        }

        $validated['company_id'] = $user->company_id;
        $validated['created_by'] = $user->id;
        $validated['usage_count'] = 0;

        // Always encode arrays as JSON strings
        if ($validated['type'] === 'buy_x_get_y') {
            $buyProducts = $request->input('buy_products') ?? [];
            $getProducts = $request->input('get_products') ?? [];
            
            $buyProducts = is_array($buyProducts) ? $buyProducts : [];
            $getProducts = is_array($getProducts) ? $getProducts : [];
            
            $validated['buy_products'] = json_encode(array_filter(array_map('intval', $buyProducts)));
            $validated['get_products'] = json_encode(array_filter(array_map('intval', $getProducts)));
            
            if (isset($validated['bxgy_config'])) {
                $validated['bxgy_config'] = json_encode($validated['bxgy_config']);
            }
        } else {
            $validated['buy_products'] = json_encode([]);
            $validated['get_products'] = json_encode([]);
        }

        $validated['scope_items'] = json_encode($validated['scope_items'] ?? []);
        $validated['customer_groups'] = json_encode($validated['customer_groups'] ?? []);

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
            'buy_products' => 'nullable|array',
            'get_products' => 'nullable|array',
            'bxgy_config' => 'nullable|array',
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

        // Validate that at least one minimum requirement is set
        $minPurchase = $validated['minimum_purchase'] ?? $promotion->minimum_purchase;
        $minQuantity = $validated['minimum_quantity'] ?? $promotion->minimum_quantity;
        
        if (empty($minPurchase) && empty($minQuantity)) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => [
                    'minimum_requirements' => ['At least one of minimum purchase amount or minimum quantity must be specified for the promotion to trigger.']
                ]
            ], 422);
        }

        $promoType = $validated['type'] ?? $promotion->type;
        
        // Always encode arrays as JSON strings
        if ($promoType === 'buy_x_get_y') {
            $buyProducts = $request->input('buy_products') ?? [];
            $getProducts = $request->input('get_products') ?? [];
            
            $buyProducts = is_array($buyProducts) ? $buyProducts : [];
            $getProducts = is_array($getProducts) ? $getProducts : [];
            
            $validated['buy_products'] = json_encode(array_filter(array_map('intval', $buyProducts)));
            $validated['get_products'] = json_encode(array_filter(array_map('intval', $getProducts)));
            
            if (isset($validated['bxgy_config'])) {
                $validated['bxgy_config'] = json_encode($validated['bxgy_config']);
            }
        } else {
            $validated['buy_products'] = json_encode([]);
            $validated['get_products'] = json_encode([]);
        }

        if (isset($validated['scope_items'])) {
            $validated['scope_items'] = json_encode($validated['scope_items'] ?? []);
        }
        if (isset($validated['customer_groups'])) {
            $validated['customer_groups'] = json_encode($validated['customer_groups'] ?? []);
        }

        $promotion->update($validated);

        return response()->json([
            'message' => 'Promotion updated successfully',
            'promotion' => $promotion->fresh()->load('creator')
        ]);
    }

    public function destroy($id)
    {
        $user = Auth::user();
        $promotion = Promotion::findOrFail($id);

        if ($promotion->company_id !== $user->company_id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

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

    public function getActivePromotions(Request $request)
    {
        $user = $request->user();
        $promotions = Promotion::where('company_id', $user->company_id)
            ->active()
            ->orderBy('priority', 'desc')
            ->get();

        return response()->json($promotions);
    }

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

            // Fetch product categories to handle category scope
            $productIds = array_column($cartItems, 'product_id');
            $productsKeyed = Product::whereIn('id', $productIds)
                ->where('company_id', $companyId)
                ->get()
                ->keyBy('id');

            $activePromotions = Promotion::where('company_id', $companyId)
                ->where('is_active', true)
                ->where(function ($q) {
                    $q->whereNull('start_date')->orWhere('start_date', '<=', now());
                })
                ->where(function ($q) {
                    $q->whereNull('end_date')->orWhere('end_date', '>=', now());
                })
                ->orderBy('priority', 'desc')
                ->get();

            $applicablePromotions = [];
            $totalDiscount = 0.0;

            foreach ($activePromotions as $promo) {
                // usage limits
                if ($promo->usage_limit_total && $promo->usage_count >= $promo->usage_limit_total) {
                    continue;
                }
                if ($customerId && $promo->usage_limit_per_customer) {
                    $customerUsage = DB::table('sale_promotions')
                        ->join('sales', 'sale_promotions.sale_id', '=', 'sales.id')
                        ->where('sale_promotions.promotion_id', $promo->id)
                        ->where('sales.customer_id', $customerId)
                        ->count();
                    if ($customerUsage >= $promo->usage_limit_per_customer) {
                        continue;
                    }
                }

                // normalize scope_items
                $scopeItems = is_string($promo->scope_items) ? @json_decode($promo->scope_items, true) : $promo->scope_items;
                if (!is_array($scopeItems)) {
                    $scopeItems = [];
                }

                // matched items based on scope
                $matchedItems = [];
                switch ($promo->scope) {
                    case 'all':
                        $matchedItems = $cartItems;
                        break;
                    case 'product':
                        $matchedItems = array_filter($cartItems, function ($item) use ($scopeItems) {
                            return in_array((int)$item['product_id'], array_map('intval', $scopeItems));
                        });
                        break;
                    case 'category':
                        $matchedItems = array_filter($cartItems, function ($item) use ($scopeItems, $productsKeyed) {
                            $product = $productsKeyed[$item['product_id']] ?? null;
                            return $product && in_array($product->category, $scopeItems);
                        });
                        break;
                    default:
                        $matchedItems = $cartItems;
                        break;
                }

                if (empty($matchedItems)) {
                    continue;
                }

                // parse bxgy_config and buy/get products defensively
                $bxgy = [];
                if ($promo->bxgy_config) {
                    $bxgy = is_string($promo->bxgy_config) ? @json_decode($promo->bxgy_config, true) : (array)$promo->bxgy_config;
                    if (!is_array($bxgy)) {
                        $bxgy = [];
                    }
                }

                $buyProducts = [];
                if ($promo->buy_products) {
                    $buyProducts = is_array($promo->buy_products) ? $promo->buy_products : (@json_decode($promo->buy_products, true) ?: []);
                } elseif (!empty($bxgy['buy_products'])) {
                    $buyProducts = is_array($bxgy['buy_products']) ? $bxgy['buy_products'] : (@json_decode($bxgy['buy_products'] ?? '[]', true) ?: []);
                }

                $getProducts = [];
                if ($promo->get_products) {
                    $getProducts = is_array($promo->get_products) ? $promo->get_products : (@json_decode($promo->get_products, true) ?: []);
                } elseif (!empty($bxgy['get_products'])) {
                    $getProducts = is_array($bxgy['get_products']) ? $bxgy['get_products'] : (@json_decode($bxgy['get_products'] ?? '[]', true) ?: []);
                }

                $scenario = $bxgy['scenario'] ?? ($promo->bxgy_scenario ?? null);
                $buyQty = $promo->buy_quantity ?? ($bxgy['buy_quantity'] ?? null);
                $getQty = $promo->get_quantity ?? ($bxgy['get_quantity'] ?? null);
                $minimumPurchase = $promo->minimum_purchase ?? ($bxgy['minimum_purchase'] ?? null);
                $minimumQuantity = $promo->minimum_quantity ?? ($bxgy['minimum_quantity'] ?? null);
                $tierRules = $bxgy['tier_rules'] ?? ($promo->tier_rules ?? []);

                // check minimum requirements (if configured)
                $cartQuantityTotal = array_reduce($cartItems, function ($sum, $item) {
                    return $sum + ($item['quantity'] ?? 0);
                }, 0);

                $minimumPurchaseMet = !empty($minimumPurchase) ? ($cartTotal >= $minimumPurchase) : false;
                $minimumQuantityMet = !empty($minimumQuantity) ? ($cartQuantityTotal >= $minimumQuantity) : false;

                $hasAnyMinConfigured = !empty($minimumPurchase) || !empty($minimumQuantity);
                if ($hasAnyMinConfigured && !$minimumPurchaseMet && !$minimumQuantityMet) {
                    continue;
                }

                $discount = 0.0;

                switch ($promo->type) {
                    case 'percentage':
                    case 'bulk_discount':
                        $matchedTotal = array_reduce($matchedItems, function ($sum, $item) {
                            return $sum + ($item['price'] * $item['quantity']);
                        }, 0);
                        $discount = $matchedTotal * ($promo->discount_value / 100);
                        break;

                    case 'fixed_amount':
                        if ($promo->minimum_purchase && $cartTotal < $promo->minimum_purchase) {
                            continue 2;
                        }
                        $discount = $promo->discount_value;
                        break;

                    case 'buy_x_get_y':
                        $scenario = $scenario ?: 'specific_product';
                        $expandUnits = function ($items) {
                            $units = [];
                            foreach ($items as $it) {
                                $qty = max(0, (int)($it['quantity'] ?? 0));
                                for ($i = 0; $i < $qty; $i++) {
                                    $units[] = ['product_id' => (int)$it['product_id'], 'price' => (float)$it['price']];
                                }
                            }
                            return $units;
                        };

                        if ($scenario === 'specific_product') {
                            if (empty($buyProducts)) break;
                            $eligibleBuyItems = array_filter($cartItems, function ($it) use ($buyProducts) {
                                return in_array((int)$it['product_id'], array_map('intval', $buyProducts));
                            });
                            $totalBuyUnits = array_reduce($eligibleBuyItems, function ($s, $it) {
                                return $s + ($it['quantity'] ?? 0);
                            }, 0);
                            if (empty($buyQty) || $buyQty <= 0) break;
                            $sets = floor($totalBuyUnits / $buyQty);
                            $freeUnits = $sets * ($getQty ?: 0);
                            if ($freeUnits <= 0) break;

                            // Try to find eligible get items in cart
                            $eligibleGetItems = [];
                            if (!empty($getProducts)) {
                                $eligibleGetItems = array_filter($cartItems, function ($it) use ($getProducts) {
                                    return in_array((int)$it['product_id'], array_map('intval', $getProducts));
                                });
                            }

                            $units = $expandUnits($eligibleGetItems);

                            // If not enough get items in cart, fetch missing get product prices from DB
                            $missingUnits = $freeUnits - count($units);
                            if ($missingUnits > 0 && !empty($getProducts)) {
                                $getProductPrices = \App\Models\Product::whereIn('id', $getProducts)->pluck('price', 'id')->toArray();
                                $getPrices = array_values($getProductPrices);
                                sort($getPrices);
                                for ($i = 0; $i < $missingUnits; $i++) {
                                    $price = $getPrices[0] ?? 0;
                                    $units[] = ['product_id' => $getProducts[0], 'price' => $price];
                                }
                            }

                            // If get_products not set, fallback to matchedItems in cart
                            if (empty($getProducts)) {
                                $units = $expandUnits($matchedItems);
                            }

                            usort($units, function ($a, $b) {
                                return $a['price'] <=> $b['price'];
                            });
                            for ($i = 0; $i < min($freeUnits, count($units)); $i++) {
                                $discount += $units[$i]['price'];
                            }
                        } elseif ($scenario === 'any_x_items') {
                            $units = $expandUnits($matchedItems);
                            $totalUnits = count($units);
                            if (empty($buyQty) || $buyQty <= 0) break;
                            $sets = floor($totalUnits / $buyQty);
                            $freeUnits = $sets * ($getQty ?: 0);
                            if ($freeUnits <= 0) break;
                            usort($units, function ($a, $b) {
                                return $a['price'] <=> $b['price'];
                            });
                            for ($i = 0; $i < min($freeUnits, count($units)); $i++) {
                                $discount += $units[$i]['price'];
                            }
                        } elseif ($scenario === 'spend_x_get_y') {
                            $minSpend = $minimumPurchase ?: ($bxgy['minimum_purchase'] ?? null);
                            if (empty($minSpend) || $cartTotal < $minSpend) break;
                            $freeUnits = ($getQty ?: 0);
                            if ($freeUnits <= 0) break;
                            $eligibleGetItems = [];
                            if (!empty($getProducts)) {
                                $eligibleGetItems = array_filter($cartItems, function ($it) use ($getProducts) {
                                    return in_array((int)$it['product_id'], array_map('intval', $getProducts));
                                });
                            }
                            if (empty($eligibleGetItems)) {
                                // Fetch get product prices from DB if not in cart
                                if (!empty($getProducts)) {
                                    $getProductPrices = \App\Models\Product::whereIn('id', $getProducts)->pluck('price', 'id')->toArray();
                                    $getPrices = array_values($getProductPrices);
                                    sort($getPrices);
                                    $eligibleGetItems = [];
                                    for ($i = 0; $i < $freeUnits; $i++) {
                                        $eligibleGetItems[] = ['product_id' => $getProducts[0], 'price' => $getPrices[0] ?? 0, 'quantity' => 1];
                                    }
                                } else {
                                    $eligibleGetItems = $matchedItems;
                                }
                            }
                            $units = $expandUnits($eligibleGetItems);
                            usort($units, function ($a, $b) {
                                return $a['price'] <=> $b['price'];
                            });
                            for ($i = 0; $i < min($freeUnits, count($units)); $i++) {
                                $discount += $units[$i]['price'];
                            }
                        } elseif ($scenario === 'tiered') {
                            if (!is_array($tierRules) || count($tierRules) === 0) break;
                            $units = $expandUnits($matchedItems);
                            usort($units, function ($a, $b) {
                                return $a['price'] <=> $b['price'];
                            });
                            $totalUnits = count($units);
                            foreach ($tierRules as $tier) {
                                $bq = intval($tier['buy_qty'] ?? 0);
                                $gq = intval($tier['get_qty'] ?? 0);
                                if ($bq <= 0 || $gq <= 0) continue;
                                $sets = floor($totalUnits / $bq);
                                if ($sets <= 0) continue;
                                $freeUnits = $sets * $gq;
                                for ($i = 0; $i < min($freeUnits, count($units)); $i++) {
                                    $discount += $units[$i]['price'];
                                }
                                $units = array_slice($units, min(count($units), $freeUnits));
                                $totalUnits = count($units);
                            }
                        }
                        break;
                }

                if ($discount > 0) {
                    $applicablePromotions[] = [
                        'id' => $promo->id,
                        'name' => $promo->name,
                        'type' => $promo->type,
                        'discount' => round($discount, 2),
                        'buy_quantity' => $promo->buy_quantity,
                        'get_quantity' => $promo->get_quantity,
                        'buy_products' => $buyProducts,
                        'get_products' => $getProducts,
                        'minimum_purchase' => $promo->minimum_purchase,
                        'minimum_quantity' => $promo->minimum_quantity
                    ];
                    $totalDiscount += $discount;
                    // Only break if this promo is NOT stackable and a discount was applied
                    if (!$promo->is_stackable) {
                        break;
                    }
                }
            }

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
}






