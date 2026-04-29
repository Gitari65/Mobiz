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
use App\Models\MpesaTransaction;
use App\Services\PriceGroupService;

class SaleController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'items'                  => 'required|array|min:1',
            'items.*.product_id'     => 'required|exists:products,id',
            'items.*.quantity'       => 'required|numeric|min:0.0001',
            'items.*.price'          => 'required|numeric|min:0',
            'items.*.uom_id'         => 'nullable|exists:u_o_m_s,id',
            'customer_id'            => 'nullable|integer|exists:customers,id',
            'payment_method'         => 'nullable|string|max:255',
            'tax'                    => 'nullable|numeric|min:0',
            'discount'               => 'nullable|numeric|min:0',
            'tax_configuration_id'   => 'nullable|integer|exists:tax_configurations,id',
            'amount_paid'            => 'nullable|numeric|min:0',
            'apply_credit'           => 'nullable|boolean',
            'mpesa_phone_number'     => 'nullable|string|max:20',
            'mpesa_checkout_request_id' => 'nullable|string|max:255',
            'mpesa_receipt_number'   => 'nullable|string|max:255',
        ]);

        $user = $request->user();
        $companyId = $user ? $user->company_id : null;
        $customer = null;
        $mpesaTransaction = null;

        if (!empty($validated['customer_id']) && $companyId) {
            $customer = Customer::where('company_id', $companyId)
                ->with('priceGroup')
                ->find($validated['customer_id']);

            if (!$customer) {
                return response()->json([
                    'message' => 'Sale failed',
                    'error' => 'Customer not found for this company.',
                ], 400);
            }
        }

        DB::beginTransaction();

        try {
            $allSaleItems = [];
            $mainItems = $validated['items'];

            $this->validateGroupedPricingForSale($mainItems, $customer, $companyId);
            
            // Check if user is admin to allow returnable/empty sales
            $isAdmin = false;
            if ($user) {
                $user->load('role');
                $roleName = strtolower($user->role->name ?? '');
                $isAdmin = in_array($roleName, ['admin', 'administrator', 'superuser']);
            }
            
            // Process each item and auto-include empties (only for admins)
            foreach ($validated['items'] as $item) {
                // Add the main product
                $allSaleItems[] = $item;
                
                // Load empties linked to this product - only auto-add for admins
                if ($isAdmin) {
                    $product = Product::with('empties')->findOrFail($item['product_id']);
                    
                    // Auto-add empties to the sale only if admin
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
            }
            
            // Calculate promotions on main items only
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

            if ($this->isMpesaPaymentMethod($validated['payment_method'] ?? 'Cash')) {
                if ($amountPaid <= 0) {
                    throw new \Exception('Amount paid must be greater than zero for M-Pesa payments.');
                }

                if (empty($validated['mpesa_phone_number']) || empty($validated['mpesa_checkout_request_id'])) {
                    throw new \Exception('Complete the M-Pesa STK payment flow before processing the sale.');
                }

                $mpesaTransaction = MpesaTransaction::where('company_id', $companyId)
                    ->where('checkout_request_id', $validated['mpesa_checkout_request_id'])
                    ->first();

                if (! $mpesaTransaction) {
                    throw new \Exception('M-Pesa transaction not found for this business.');
                }

                if ((float) $mpesaTransaction->amount !== (float) $amountPaid) {
                    throw new \Exception('M-Pesa amount does not match the amount paid. Re-initiate the payment and try again.');
                }

                if ($mpesaTransaction->status !== 'success') {
                    throw new \Exception('M-Pesa payment is not yet confirmed. Check payment status before completing the sale.');
                }
            }

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
                if (!$customer || $customer->id !== (int) $customerId) {
                    $customer = Customer::where('company_id', $companyId)->with('priceGroup')->find($customerId);
                }
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
                'mpesa_transaction_id' => $mpesaTransaction?->id,
                'mpesa_phone_number' => $validated['mpesa_phone_number'] ?? null,
                'mpesa_checkout_request_id' => $validated['mpesa_checkout_request_id'] ?? null,
                'mpesa_receipt_number' => $validated['mpesa_receipt_number'] ?? $mpesaTransaction?->mpesa_receipt_number,
            ]);

            if ($mpesaTransaction) {
                $mpesaTransaction->update([
                    'sale_id' => $sale->id,
                ]);
            }

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
                    'uom_id'      => $item['uom_id'] ?? null,
                    'quantity'    => $item['quantity'],
                    'unit_price'  => $item['price'],
                    'total_price' => $item['price'] * $item['quantity'],
                ]);

                // Reduce product stock (only if it's not a deposit-only empty)
                $product = Product::findOrFail($item['product_id']);
                
                // Only reduce stock for actual products, not deposit-only empties
                if (!isset($item['is_empty']) || $item['price'] > 0) {
                    $stockUomId = $item['uom_id'] ?? $product->getDefaultSaleUomId();
                    if (!$product->hasEnoughStockForQuantity((float) $item['quantity'], $stockUomId)) {
                        throw new \Exception("Not enough stock for {$product->name}");
                    }
                    $product->subtractStockForUom((float) $item['quantity'], $stockUomId);
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

            // Create invoice record for this sale
            $invoice = \App\Models\Invoice::create([
                'type' => 'sale',
                'company_id' => $companyId,
                'customer_id' => $validated['customer_id'] ?? null,
                'user_id' => $user ? $user->id : null,
                'invoice_number' => \App\Models\Invoice::generateInvoiceNumber(),
                'invoice_date' => now()->toDateString(),
                'due_date' => now()->addDays(30)->toDateString(),
                'subtotal' => $baseAmount,
                'discount' => $totalDiscount,
                'tax' => $taxAmount,
                'total' => $netTotal,
                'paid_amount' => $amountPaid,
                'balance' => $balanceDue,
                'status' => $amountPaid >= $netTotal ? 'paid' : 'sent',
                'payment_method' => $validated['payment_method'] ?? 'Cash',
                'mpesa_receipt_number' => $validated['mpesa_receipt_number'] ?? $mpesaTransaction?->mpesa_receipt_number,
                'mpesa_phone_number' => $validated['mpesa_phone_number'] ?? null,
                'notes' => 'Auto-generated from POS sale #' . $sale->id
                    . (($validated['payment_method'] ?? '') === 'M-Pesa' && !empty($validated['mpesa_receipt_number'])
                        ? ' | M-Pesa Receipt: ' . $validated['mpesa_receipt_number']
                        : ''),
            ]);

            // Create invoice items from sale items (only non-empty products)
            foreach ($sale->saleItems as $saleItem) {
                $product = $saleItem->product;
                if (!isset($saleItem->is_empty) || $saleItem->is_empty === false) {
                    \App\Models\InvoiceItem::create([
                        'invoice_id' => $invoice->id,
                        'product_id' => $saleItem->product_id,
                        'description' => $product ? $product->name : 'Item',
                        'uom_id' => $saleItem->uom_id,
                        'quantity' => $saleItem->quantity,
                        'unit_price' => $saleItem->unit_price,
                        'total' => $saleItem->total_price,
                    ]);
                }
            }

            DB::commit();

            return response()->json([
                'message' => 'Sale recorded successfully',
                'sale'    => $sale->load('saleItems.product', 'taxConfiguration'),
                'invoice' => $invoice->fresh()->load('items'),
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

    private function isMpesaPaymentMethod(?string $paymentMethod): bool
    {
        $normalized = strtolower(trim((string) $paymentMethod));

        return in_array($normalized, ['m-pesa', 'mpesa', 'm pesa'], true);
    }

    private function validateGroupedPricingForSale(array $items, ?Customer $customer, ?int $companyId): void
    {
        if (!$customer || !$companyId) {
            return;
        }

        PriceGroupService::ensureDefaultsForCompany($companyId);

        $priceGroup = $customer->priceGroup;
        if (!$priceGroup || PriceGroupService::isRetailDefault($priceGroup)) {
            return;
        }

        if (!$priceGroup->is_enabled) {
            throw new \Exception("{$priceGroup->name} pricing group is disabled for this company. Enable it or reassign the customer.");
        }

        $productIds = collect($items)->pluck('product_id')->map(fn ($id) => (int) $id)->unique()->values();
        $products = Product::with('prices')
            ->where('company_id', $companyId)
            ->whereIn('id', $productIds)
            ->get()
            ->keyBy('id');

        foreach ($items as $item) {
            $product = $products->get((int) $item['product_id']);
            if (!$product) {
                throw new \Exception('One or more products do not belong to this company.');
            }

            $hasExplicitPrice = $product->prices->contains(function ($price) use ($priceGroup) {
                return (int) $price->price_group_id === (int) $priceGroup->id
                    && $price->uom_id === null;
            });

            if (!$hasExplicitPrice) {
                throw new \Exception("{$product->name} has no price set for {$priceGroup->name}. Set a {$priceGroup->name} price before selling to this customer.");
            }
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
