<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Promotion;
use App\Models\PromotionUsage;
use App\Models\TaxConfiguration;
use App\Models\Customer;

class SaleController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'items'                  => 'required|array|min:1',
            'items.*.product_id'     => 'required|exists:products,id',
            'items.*.quantity'       => 'required|integer|min:1',
            'items.*.price'          => 'required|numeric|min:0',
            'customer_id'            => 'nullable|integer|exists:customers,id',
            'payment_method'         => 'nullable|string|max:255',
            'tax'                    => 'nullable|numeric|min:0',
            'discount'               => 'nullable|numeric|min:0',
            'tax_configuration_id'   => 'nullable|integer|exists:tax_configurations,id',
            'amount_paid'            => 'nullable|numeric|min:0',
            'apply_credit'           => 'nullable|boolean',
        ]);

        DB::beginTransaction();

        try {
            $allSaleItems = [];
            $mainItems = $validated['items'];
            
            // Process each item and auto-include empties
            foreach ($validated['items'] as $item) {
                // Add the main product
                $allSaleItems[] = $item;
                
                // Load empties linked to this product
                $product = Product::with('empties')->findOrFail($item['product_id']);
                
                // Auto-add empties to the sale
                foreach ($product->empties as $empty) {
                    $emptyQuantity = $item['quantity'] * $empty->pivot->quantity;
                    
                    $allSaleItems[] = [
                        'product_id' => $empty->id,
                        'quantity' => $emptyQuantity,
                        'price' => $empty->pivot->deposit_amount, // Use deposit amount as price
                        'is_empty' => true // Flag to identify empty items
                    ];
                }
            }
            
            // Calculate promotions on main items only
            $user = $request->user();
            $cartTotal = collect($mainItems)->sum(fn($i) => $i['price'] * $i['quantity']);
            $cartItems = $mainItems; // already in required shape
            $totalDiscount = 0.0;
            $appliedPromotions = [];
            if ($user) {
                $promotions = Promotion::where('company_id', $user->company_id)
                    ->active()
                    ->orderBy('priority', 'desc')
                    ->get();

                foreach ($promotions as $promotion) {
                    $discount = $this->calculatePromotionDiscountForSale($promotion, $cartTotal, $cartItems, $validated['customer_id'] ?? null);
                    if ($discount > 0) {
                        $appliedPromotions[] = [
                            'id' => $promotion->id,
                            'name' => $promotion->name,
                            'discount' => $discount,
                            'type' => $promotion->type,
                        ];

                        if (!$promotion->is_stackable) {
                            $totalDiscount = max($totalDiscount, $discount);
                            // if non-stackable, prefer highest and stop further stacking
                            break;
                        } else {
                            $totalDiscount += $discount;
                        }
                    }
                }
            }

            $grossTotal = collect($allSaleItems)->sum(fn($item) => $item['price'] * $item['quantity']);
            $manualDiscount = $validated['discount'] ?? 0;
            $totalDiscount += $manualDiscount;

            // Cap total discount to gross total to prevent negative sales
            if ($totalDiscount > $grossTotal) {
                $totalDiscount = $grossTotal;
            }

            $baseAmount = max(0, $grossTotal - $totalDiscount);

            // Get authenticated user for company_id and user_id
            $user = auth()->user();
            $companyId = $user ? $user->company_id : null;

            // Resolve tax configuration scoped to the company (including global defaults)
            $taxConfig = null;
            if (!empty($validated['tax_configuration_id'])) {
                $taxConfig = TaxConfiguration::forCompany($companyId)->find($validated['tax_configuration_id']);
                if (!$taxConfig) {
                    throw new \Exception('Invalid tax configuration for this company');
                }
            } else {
                $taxConfig = TaxConfiguration::forCompany($companyId)->where('is_default', true)->first();
                if (!$taxConfig) {
                    $taxConfig = TaxConfiguration::forCompany($companyId)->where('is_system_default', true)->first();
                }
            }

            $taxAmount = $taxConfig ? $taxConfig->calculateTax($baseAmount) : 0;
            $netTotal = $taxConfig && !$taxConfig->is_inclusive
                ? max(0, $baseAmount + $taxAmount)
                : $baseAmount;

            $amountPaid = max(0, $validated['amount_paid'] ?? 0);
            $balanceDue = max(0, $netTotal - $amountPaid);

            $customer = null;
            if ($balanceDue > 0) {
                // Check if Credit/Invoice payment method is enabled for this company
                $creditPaymentEnabled = \App\Models\PaymentMethod::whereHas('companies', function($q) use ($companyId) {
                    $q->where('company_id', $companyId)
                      ->where('is_enabled', true);
                })->where('name', 'Credit/Invoice')->exists();

                if (!$creditPaymentEnabled) {
                    throw new \Exception('Credit/Invoice payments are not enabled for your business. Please collect full payment.');
                }

                $customerId = $validated['customer_id'] ?? null;
                if (!$customerId) {
                    throw new \Exception('Payment is insufficient. Select a customer or pay full amount.');
                }
                if (!($validated['apply_credit'] ?? false)) {
                    throw new \Exception('Payment is insufficient. Confirm applying balance as customer credit.');
                }
                $customer = Customer::where('company_id', $companyId)->find($customerId);
                if (!$customer) {
                    throw new \Exception('Customer not found for this company.');
                }
                
                $balanceBefore = $customer->credit_balance;
                $customer->increment('credit_balance', $balanceDue);
                $customer = $customer->fresh();
            }

            // Create the sale record (store net total)
            $sale = Sale::create([
                'total' => $netTotal,
                'company_id' => $companyId,
                'customer_id' => $validated['customer_id'] ?? null,
                'user_id' => $user ? $user->id : null,
                'payment_method' => $validated['payment_method'] ?? 'Cash',
                'discount' => $totalDiscount,
                'tax' => $taxAmount,
                'tax_configuration_id' => $taxConfig?->id,
                'amount_paid' => $amountPaid,
                'balance_due' => $balanceDue,
            ]);

            // Create credit transaction if balance was added to credit
            if ($balanceDue > 0 && $customer) {
                \App\Models\CreditTransaction::create([
                    'customer_id' => $customer->id,
                    'company_id' => $companyId,
                    'sale_id' => $sale->id,
                    'user_id' => $user ? $user->id : null,
                    'type' => 'credit',
                    'amount' => $balanceDue,
                    'balance_before' => $balanceBefore,
                    'balance_after' => $customer->credit_balance,
                    'transaction_number' => $sale->id,
                    'payment_method' => $validated['payment_method'] ?? 'Cash',
                    'notes' => "Credit from sale - Payment short by " . number_format($balanceDue, 2),
                ]);
            }

            // Create sale items & update stock
            foreach ($allSaleItems as $item) {
                SaleItem::create([
                    'sale_id'     => $sale->id,
                    'product_id'  => $item['product_id'],
                    'quantity'    => $item['quantity'],
                    'unit_price'  => $item['price'],
                    'total_price' => $item['price'] * $item['quantity'],
                ]);

                // Reduce product stock (only if it's not a deposit-only empty)
                $product = Product::findOrFail($item['product_id']);
                
                // Only reduce stock for actual products, not deposit-only empties
                if (!isset($item['is_empty']) || $item['price'] > 0) {
                    if ($product->stock_quantity < $item['quantity']) {
                        throw new \Exception("Not enough stock for {$product->name}");
                    }
                    $product->stock_quantity -= $item['quantity'];
                    $product->save();
                }
            }

            // Record promotion usage if any
            if (!empty($appliedPromotions)) {
                foreach ($appliedPromotions as $ap) {
                    $promo = Promotion::find($ap['id']);
                    if ($promo) {
                        PromotionUsage::create([
                            'promotion_id' => $promo->id,
                            'customer_id' => $validated['customer_id'] ?? null,
                            'sale_id' => $sale->id,
                            'discount_amount' => $ap['discount'],
                            'used_at' => now(),
                        ]);
                        $promo->incrementUsage();
                    }
                }
            }

            DB::commit();

            return response()->json([
                'message' => 'Sale recorded successfully',
                'sale'    => $sale->load('saleItems.product', 'taxConfiguration'),
                'discount' => $totalDiscount,
                'tax' => $taxAmount,
                'applied_promotions' => $appliedPromotions,
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Sale failed',
                'error'   => $e->getMessage(),
            ], 400);
        }
    }

    private function calculatePromotionDiscountForSale(Promotion $promotion, $cartTotal, array $cartItems, $customerId = null)
    {
        // Customer usage constraint already handled in PromotionController.canBeUsedBy
        // Build items map (product_id => [quantity, price])
        $itemsByProduct = [];
        foreach ($cartItems as $item) {
            $pid = (int) $item['product_id'];
            if (!isset($itemsByProduct[$pid])) {
                $itemsByProduct[$pid] = ['quantity' => 0, 'price' => (float) $item['price']];
            }
            $itemsByProduct[$pid]['quantity'] += (int) $item['quantity'];
            $itemsByProduct[$pid]['price'] = min($itemsByProduct[$pid]['price'], (float) $item['price']);
        }

        // Validate minimums
        if ($promotion->minimum_purchase && $cartTotal < (float) $promotion->minimum_purchase) {
            return 0.0;
        }

        // Scope filtering
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
                $productIds = array_keys($itemsByProduct);
                $products = Product::whereIn('id', $productIds)->select('id','category')->get();
                $allowedCategories = array_map('strval', $scopeItems);
                foreach ($products as $p) {
                    if ($p->category && in_array($p->category, $allowedCategories, true)) {
                        $scopedItems[$p->id] = $itemsByProduct[$p->id];
                    }
                }
            }
        }

        // Minimum quantity on scoped items
        if ($promotion->minimum_quantity) {
            $totalQty = 0;
            foreach ($scopedItems as $info) { $totalQty += (int) $info['quantity']; }
            if ($totalQty < (int) $promotion->minimum_quantity) {
                return 0.0;
            }
        }

        switch ($promotion->type) {
            case 'percentage':
                $scopedSubtotal = 0.0;
                foreach ($scopedItems as $info) { $scopedSubtotal += $info['quantity'] * $info['price']; }
                return ($scopedSubtotal * (float) $promotion->discount_value) / 100.0;

            case 'fixed_amount':
                $scopedSubtotal = 0.0;
                foreach ($scopedItems as $info) { $scopedSubtotal += $info['quantity'] * $info['price']; }
                return min((float) $promotion->discount_value, $scopedSubtotal);

            case 'spend_save':
                $scopedSubtotal = 0.0;
                foreach ($scopedItems as $info) { $scopedSubtotal += $info['quantity'] * $info['price']; }
                if ($promotion->minimum_purchase && $scopedSubtotal < (float) $promotion->minimum_purchase) {
                    return 0.0;
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
                foreach ($scopedItems as $info) {
                    $q = (int) $info['quantity'];
                    if ($q < $buy) { continue; }
                    // Each full group of BUY grants GET items free
                    $freeGroups = intdiv($q, $buy);
                    $freeItems = $freeGroups * $get;
                    $discount += $freeItems * (float) $info['price'];
                }
                return $discount;

            default:
                return 0.0;
        }
    }
}
