<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Purchase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class ReportController extends Controller
{
    /**
     * Get a simple overview of sales for the current month.
     * This provides key insights into the store's performance.
     */
    public function salesOverview()
    {
        $thisMonth = Carbon::now()->startOfMonth();

        // Calculate sales metrics for the current month
        $totalSales = Sale::whereDate('created_at', '>=', $thisMonth)->sum('total');
        $totalTransactions = Sale::whereDate('created_at', '>=', $thisMonth)->count();
        $averageTransactionValue = $totalTransactions > 0 ? $totalSales / $totalTransactions : 0;
        
        // Calculate the number of items sold
        $totalItemsSold = SaleItem::whereHas('sale', function ($query) use ($thisMonth) {
            $query->whereDate('created_at', '>=', $thisMonth);
        })->sum('quantity');

        // Fetch recent sales to display on the report page
        $recentSales = Sale::with('items.product')
            ->whereDate('created_at', '>=', $thisMonth)
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();
            
        return response()->json([
            'total_sales_month' => $totalSales,
            'total_transactions_month' => $totalTransactions,
            'average_transaction_value' => round($averageTransactionValue, 2),
            'total_items_sold_month' => $totalItemsSold,
            'recent_sales' => $recentSales,
        ]);
    }

    /**
     * Get a summary of the current inventory status.
     * This report highlights low stock and out-of-stock products for reordering.
     */
    public function inventorySummary()
    {
        // Get overall inventory statistics
        $totalProducts = Product::count();
        $inStock = Product::where('stock_quantity', '>', 10)->count();
        $lowStock = Product::whereBetween('stock_quantity', [1, 10])->count();
        $outOfStock = Product::where('stock_quantity', 0)->count();

        // Get the list of products that need attention
        $lowStockProducts = Product::where('stock_quantity', '<=', 10)
            ->where('stock_quantity', '>', 0)
            ->orderBy('stock_quantity', 'asc')
            ->get();

        $outOfStockProducts = Product::where('stock_quantity', 0)->get();

        // Calculate total inventory value
        $totalInventoryValue = Product::sum(DB::raw('price * stock_quantity'));

        return response()->json([
            'total_products' => $totalProducts,
            'in_stock' => $inStock,
            'low_stock' => $lowStock,
            'out_of_stock' => $outOfStock,
            'total_inventory_value' => $totalInventoryValue,
            'low_stock_products' => $lowStockProducts,
            'out_of_stock_products' => $outOfStockProducts,
        ]);
    }

    /**
     * Get the top 10 selling products based on quantity sold.
     * This provides insights into which products are most popular.
     */
    public function topSellingProducts()
    {
        // Use a broader date range (e.g., last 12 months) for more meaningful data
        $lastYear = Carbon::now()->subMonths(12);

        $topProducts = SaleItem::select('product_id', 'products.name', DB::raw('SUM(quantity) as total_sold'))
            ->join('products', 'sale_items.product_id', '=', 'products.id')
            ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->whereBetween('sales.created_at', [$lastYear, Carbon::now()])
            ->groupBy('product_id', 'products.name')
            ->orderByDesc('total_sold')
            ->limit(10)
            ->get();
            
        return response()->json([
            'top_products' => $topProducts,
        ]);
    }

    /**
     * Get sales report with filters for date range
     * Filtered by authenticated user's company
     */
    public function salesReport(Request $request)
    {
        Log::info('🔍 SALES REPORT REQUEST', [
            'user_id' => auth()->id(),
            'company_id' => auth()->user()?->company_id,
            'start_date' => $request->input('start_date'),
            'end_date' => $request->input('end_date'),
        ]);

        $user = auth()->user();
        if (!$user || !$user->company_id) {
            Log::warning('❌ Sales Report - Unauthorized or no company', [
                'user_id' => $user?->id,
                'company_id' => $user?->company_id,
            ]);
            return response()->json(['error' => 'Unauthorized or no company associated'], 403);
        }

        $startDate = $request->input('start_date', Carbon::now()->subDays(30)->toDateString());
        $endDate = $request->input('end_date', Carbon::now()->toDateString());

        Log::info('📅 Date Range', [
            'start' => $startDate,
            'end' => $endDate,
            'company_id' => $user->company_id,
        ]);

        // Get sales for the company
        $sales = Sale::where('company_id', $user->company_id)
            ->whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
            ->with(['items.product', 'customer', 'user'])
            ->orderBy('created_at', 'desc')
            ->get();

        Log::info('📊 Sales Query Results', [
            'total_sales_found' => $sales->count(),
            'company_id' => $user->company_id,
            'date_range' => "{$startDate} to {$endDate}",
        ]);

        if ($sales->count() === 0) {
            Log::warning('⚠️ No sales found for date range', [
                'company_id' => $user->company_id,
                'start_date' => $startDate,
                'end_date' => $endDate,
            ]);
        }

        $totalRevenue = $sales->sum(function ($s) { return (float)$s->total; });
        $totalDiscount = $sales->sum(function ($s) { return (float)$s->discount; });
        $totalTax = $sales->sum(function ($s) { return (float)$s->tax; });
        $totalTransactions = $sales->count();
        $avgTransaction = $totalTransactions > 0 ? $totalRevenue / $totalTransactions : 0;
        $totalItems = $sales->sum(function ($sale) {
            return $sale->items->sum('quantity');
        });

        Log::info('💰 Sales Metrics Calculated', [
            'total_revenue' => $totalRevenue,
            'total_discount' => $totalDiscount,
            'total_tax' => $totalTax,
            'total_transactions' => $totalTransactions,
            'avg_transaction' => $avgTransaction,
            'total_items' => $totalItems,
        ]);

        $transactions = $sales->map(function ($sale) {
            return [
                'id' => $sale->id,
                'total' => (float)$sale->total,
                'discount' => (float)$sale->discount,
                'tax' => (float)$sale->tax,
                'items_count' => $sale->items->sum('quantity'),
                'customer_name' => $sale->customer ? $sale->customer->name : 'Walk-in',
                'payment_method' => $sale->payment_method,
                'cashier' => $sale->user ? $sale->user->name : 'Unknown',
                'created_at' => $sale->created_at->toISOString(),
            ];
        });

        $response = [
            'total_revenue' => round($totalRevenue, 2),
            'total_discount' => round($totalDiscount, 2),
            'total_tax' => round($totalTax, 2),
            'total_transactions' => $totalTransactions,
            'avg_transaction' => round($avgTransaction, 2),
            'total_items' => $totalItems,
            'transactions' => $transactions,
        ];

        Log::info('✅ Sales Report Response Ready', [
            'response_keys' => array_keys($response),
            'transaction_count' => count($transactions),
        ]);

        return response()->json($response);
    }

    /**
     * Get transfers report with date filters
     * Filtered by authenticated user's company
     */
    public function transfersReport(Request $request)
    {
        $user = auth()->user();
        if (!$user || !$user->company_id) {
            return response()->json(['error' => 'Unauthorized or no company associated'], 403);
        }

        $startDate = $request->input('start_date', Carbon::now()->subDays(30)->toDateString());
        $endDate = $request->input('end_date', Carbon::now()->toDateString());

        $transfers = \App\Models\WarehouseTransfer::where('company_id', $user->company_id)
            ->whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
            ->with(['product', 'fromWarehouse', 'toWarehouse'])
            ->orderBy('created_at', 'desc')
            ->get();

        $totalCount = $transfers->count();
        $itemsIn = $transfers->where('transfer_type', 'in')->sum('quantity');
        $itemsOut = $transfers->where('transfer_type', 'out')->sum('quantity');

        $list = $transfers->map(function ($transfer) {
            return [
                'id' => $transfer->id,
                'from_location' => $transfer->fromWarehouse ? $transfer->fromWarehouse->name : $transfer->external_target,
                'to_location' => $transfer->toWarehouse ? $transfer->toWarehouse->name : $transfer->external_target,
                'items_count' => $transfer->quantity,
                'created_at' => $transfer->created_at->toISOString(),
            ];
        });

        return response()->json([
            'total_count' => $totalCount,
            'items_in' => $itemsIn,
            'items_out' => $itemsOut,
            'list' => $list,
        ]);
    }

    /**
     * Get analytics report - inventory and top products
     * Filtered by authenticated user's company
     */
    public function analyticsReport(Request $request)
    {
        $user = auth()->user();
        if (!$user || !$user->company_id) {
            return response()->json(['error' => 'Unauthorized or no company associated'], 403);
        }

        $startDate = $request->input('start_date', Carbon::now()->subDays(30)->toDateString());
        $endDate = $request->input('end_date', Carbon::now()->toDateString());

        // Get inventory stats for the company
        $totalProducts = Product::where('company_id', $user->company_id)->count();
        $inStock = Product::where('company_id', $user->company_id)->where('stock_quantity', '>', 10)->count();
        $lowStock = Product::where('company_id', $user->company_id)->whereBetween('stock_quantity', [1, 10])->count();
        $outOfStock = Product::where('company_id', $user->company_id)->where('stock_quantity', 0)->count();

        // Get top selling products for the date range
        $topProducts = SaleItem::select('product_id', 'products.name', DB::raw('SUM(sale_items.quantity) as total_sold'))
            ->join('products', 'sale_items.product_id', '=', 'products.id')
            ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->where(function($query) use ($user) {
                $query->where('sales.company_id', $user->company_id)
                      ->orWhereNull('sales.company_id');
            })
            ->whereBetween('sales.created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
            ->groupBy('product_id', 'products.name')
            ->orderByDesc('total_sold')
            ->limit(10)
            ->get()
            ->map(function ($item) {
                return [
                    'id' => $item->product_id,
                    'name' => $item->name,
                    'total_sold' => $item->total_sold,
                ];
            });

        // Get low stock items
        $lowStockItems = Product::where('company_id', $user->company_id)
            ->where('stock_quantity', '<=', 10)
            ->where('stock_quantity', '>', 0)
            ->orderBy('stock_quantity', 'asc')
            ->limit(10)
            ->get()
            ->map(function ($product) {
                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'stock_quantity' => $product->stock_quantity,
                ];
            });

        return response()->json([
            'total_products' => $totalProducts,
            'in_stock' => $inStock,
            'low_stock' => $lowStock,
            'out_of_stock' => $outOfStock,
            'top_products' => $topProducts,
            'low_stock_items' => $lowStockItems,
        ]);
    }

    /**
     * Get promotions report
     * Filtered by authenticated user's company
     */
    public function promotionsReport(Request $request)
    {
        $user = auth()->user();
        if (!$user || !$user->company_id) {
            return response()->json(['error' => 'Unauthorized or no company associated'], 403);
        }

        $startDate = $request->input('start_date', Carbon::now()->subDays(30)->toDateString());
        $endDate = $request->input('end_date', Carbon::now()->toDateString());

        // Get promotions for the company
        $promotions = \App\Models\Promotion::where('company_id', $user->company_id)->get();

        $activeCount = $promotions->where('is_active', true)->count();
        
        // Calculate discount and usage within date range
        $totalDiscount = 0;
        $totalUsage = 0;

        $list = $promotions->map(function ($promo) use ($startDate, $endDate, &$totalDiscount, &$totalUsage) {
            // Get usages for this promotion within date range
            $usages = \App\Models\PromotionUsage::where('promotion_id', $promo->id)
                ->whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
                ->get();
            
            $usageCount = $usages->count();
            $discountSum = $usages->sum('discount_amount');
            
            $totalDiscount += $discountSum;
            $totalUsage += $usageCount;
            
            return [
                'id' => $promo->id,
                'name' => $promo->name,
                'type' => $promo->type,
                'scope' => $promo->scope,
                'usage_count' => $usageCount,
                'total_discount' => round($discountSum, 2),
                'is_active' => $promo->is_active,
            ];
        });

        return response()->json([
            'active_count' => $activeCount,
            'total_discount' => round($totalDiscount, 2),
            'total_usage' => $totalUsage,
            'list' => $list,
        ]);
    }

    /**
     * Get customers report
     * Filtered by authenticated user's company
     */
    public function customersReport(Request $request)
    {
        $user = auth()->user();
        if (!$user || !$user->company_id) {
            return response()->json(['error' => 'Unauthorized or no company associated'], 403);
        }

        $startDate = $request->input('start_date', Carbon::now()->subDays(30)->toDateString());
        $endDate = $request->input('end_date', Carbon::now()->toDateString());

        // Get all customers for this company
        $totalUnique = \App\Models\Customer::where('company_id', $user->company_id)->count();

        // Get sales with and without customers in date range
        $salesInPeriod = Sale::where(function($query) use ($user) {
                $query->where('company_id', $user->company_id)
                      ->orWhereNull('company_id');
            })
            ->whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
            ->get();

        $totalServed = $salesInPeriod->whereNotNull('customer_id')->unique('customer_id')->count();
        $walkIns = $salesInPeriod->whereNull('customer_id')->count();
        
        $totalSpent = $salesInPeriod->whereNotNull('customer_id')->sum('total');
        $avgSpend = $totalServed > 0 ? $totalSpent / $totalServed : 0;

        // Get top customers by purchase count and total spent
        $customerIds = $salesInPeriod->whereNotNull('customer_id')->pluck('customer_id')->unique();
        
        $topCustomers = \App\Models\Customer::where('company_id', $user->company_id)
            ->whereIn('id', $customerIds)
            ->get()
            ->map(function ($customer) use ($startDate, $endDate) {
                $sales = Sale::where('customer_id', $customer->id)
                    ->whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
                    ->get();
                
                $totalSpent = $sales->sum('total');
                $purchaseCount = $sales->count();
                $avgPurchase = $purchaseCount > 0 ? $totalSpent / $purchaseCount : 0;
                $lastPurchase = $sales->max('created_at');

                return [
                    'id' => $customer->id,
                    'name' => $customer->name,
                    'purchase_count' => $purchaseCount,
                    'total_spent' => round($totalSpent, 2),
                    'avg_purchase' => round($avgPurchase, 2),
                    'last_purchase_date' => $lastPurchase ? Carbon::parse($lastPurchase)->toISOString() : null,
                ];
            })
            ->sortByDesc('total_spent')
            ->take(10)
            ->values();

        return response()->json([
            'total_unique' => $totalUnique,
            'total_served' => $totalServed,
            'walk_ins' => $walkIns,
            'avg_spend' => round($avgSpend, 2),
            'top_customers' => $topCustomers,
        ]);
    }

    /**
     * Export DSRS (Daily Sales Report Summary) as CSV (Excel-compatible)
     */
    public function exportDSRS(Request $request)
    {
        Log::info('📥 DSRS EXPORT REQUEST', [
            'user_id' => auth()->id(),
            'company_id' => auth()->user()?->company_id,
            'start_date' => $request->input('start_date'),
            'end_date' => $request->input('end_date'),
        ]);

        try {
            $user = auth()->user();
            if (!$user || !$user->company_id) {
                Log::error('❌ DSRS Export - Unauthorized', [
                    'user_id' => $user?->id,
                ]);
                return response()->json(['error' => 'Unauthorized'], 403);
            }

            $startDate = $request->input('start_date', Carbon::now()->subDays(30)->toDateString());
            $endDate = $request->input('end_date', Carbon::now()->toDateString());

            Log::info('🔎 Fetching sales for DSRS', [
                'company_id' => $user->company_id,
                'date_range' => "{$startDate} to {$endDate}",
            ]);

            // Fetch sales data with all relationships
            $sales = Sale::where('company_id', $user->company_id)
                ->whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
                ->with(['items.product', 'customer', 'user', 'taxConfiguration'])
                ->orderBy('created_at', 'asc')
                ->get();

            Log::info('📊 DSRS Sales Data', [
                'sales_count' => $sales->count(),
                'company_name' => $user->company?->name,
            ]);

            // Generate CSV content
            $csvOutput = fopen('php://temp', 'r+');
            
            if (!$csvOutput) {
                throw new \Exception('Failed to create temporary CSV file');
            }
            
            // Add BOM for UTF-8
            fwrite($csvOutput, chr(0xEF).chr(0xBB).chr(0xBF));
            
            // ===== HEADER SECTION =====
            fputcsv($csvOutput, ['Daily Sales Report Summary (DSRS)']);
            fputcsv($csvOutput, []);
            fputcsv($csvOutput, ['Company:', $user->company ? $user->company->name : 'N/A']);
            fputcsv($csvOutput, ['Period:', "{$startDate} to {$endDate}"]);
            fputcsv($csvOutput, ['Generated:', now()->format('Y-m-d H:i:s')]);
            fputcsv($csvOutput, []);

            // ===== DETAILED TRANSACTIONS SECTION =====
            fputcsv($csvOutput, ['DETAILED SALES TRANSACTIONS']);
            fputcsv($csvOutput, ['Sale ID', 'Date & Time', 'Cashier/Admin', 'Customer Name', 'Product', 'Qty', 'Unit Price', 'Subtotal', 'Discount', 'Tax', 'Total', 'Payment Method', 'Balance Due']);

            $transactionDetails = [];
            $totalGrossSales = 0;
            $totalDiscount = 0;
            $totalTax = 0;
            $totalNetSales = 0;
            $totalTransactions = 0;
            $totalItems = 0;

            foreach ($sales as $sale) {
                $saleDate = $sale->created_at->format('Y-m-d H:i:s');
                $cashierName = $sale->user ? $sale->user->name : 'System';
                $customerName = $sale->customer ? $sale->customer->name : 'Walk-in';
                $paymentMethod = $sale->payment_method ?? 'Not Specified';
                
                // Get sale items with product details
                $saleItems = $sale->items()->with('product')->get();
                
                if ($saleItems->count() > 0) {
                    // Write one row per item in the sale
                    foreach ($saleItems as $index => $item) {
                        $subtotal = $item->total_price;
                        
                        // Distribute discount and tax across items proportionally
                        $totalItemsPrice = $sale->saleItems()->sum('total_price');
                        $itemDiscount = $totalItemsPrice > 0 ? ($item->total_price / $totalItemsPrice) * $sale->discount : 0;
                        $itemTax = $totalItemsPrice > 0 ? ($item->total_price / $totalItemsPrice) * $sale->tax : 0;
                        $itemTotal = $subtotal - $itemDiscount + $itemTax;
                        
                        // Only show sale header on first item
                        $displaySaleId = $index === 0 ? $sale->id : '';
                        $displayDate = $index === 0 ? $saleDate : '';
                        $displayCashier = $index === 0 ? $cashierName : '';
                        $displayCustomer = $index === 0 ? $customerName : '';
                        $displayPayment = $index === 0 ? $paymentMethod : '';
                        $displayBalance = $index === 0 ? $sale->balance_due : '';
                        
                        fputcsv($csvOutput, [
                            $displaySaleId,
                            $displayDate,
                            $displayCashier,
                            $displayCustomer,
                            $item->product ? $item->product->name : 'Unknown',
                            $item->quantity,
                            number_format($item->unit_price, 2),
                            number_format($subtotal, 2),
                            number_format($itemDiscount, 2),
                            number_format($itemTax, 2),
                            number_format($itemTotal, 2),
                            $displayPayment,
                            $displayBalance ? number_format($displayBalance, 2) : ''
                        ]);
                    }
                } else {
                    // No items (edge case)
                    fputcsv($csvOutput, [
                        $sale->id,
                        $saleDate,
                        $cashierName,
                        $customerName,
                        'No items',
                        0,
                        0,
                        0,
                        number_format($sale->discount, 2),
                        number_format($sale->tax, 2),
                        number_format($sale->total, 2),
                        $paymentMethod,
                        number_format($sale->balance_due, 2)
                    ]);
                }
                
                $totalTransactions++;
                $totalItems += $saleItems->sum('quantity');
                $totalGrossSales += ($sale->total + $sale->discount);
                $totalDiscount += $sale->discount;
                $totalTax += $sale->tax;
                $totalNetSales += $sale->total;
            }

            // ===== DAILY SUMMARY SECTION =====
            fputcsv($csvOutput, []);
            fputcsv($csvOutput, ['DAILY SALES BREAKDOWN']);
            fputcsv($csvOutput, ['Date', 'Transactions', 'Total Items', 'Gross Sales', 'Discount', 'Tax', 'Net Sales', 'Avg Transaction']);

            $salesByDate = $sales->groupBy(function ($sale) {
                return $sale->created_at->format('Y-m-d');
            });

            Log::info('📅 Daily Breakdown', [
                'days_with_sales' => $salesByDate->count(),
            ]);

            foreach ($salesByDate as $date => $dateSales) {
                $dayTransactions = $dateSales->count();
                $dayItems = $dateSales->sum(function ($s) { return $s->items->sum('quantity'); });
                $dayGross = $dateSales->sum(function ($s) { return (float)$s->total + (float)$s->discount; });
                $dayDiscount = $dateSales->sum(function ($s) { return (float)$s->discount; });
                $dayTax = $dateSales->sum(function ($s) { return (float)$s->tax; });
                $dayNet = $dateSales->sum(function ($s) { return (float)$s->total; });
                $dayAvg = $dayTransactions > 0 ? $dayNet / $dayTransactions : 0;

                Log::debug("📊 Daily Stats: {$date}", [
                    'transactions' => $dayTransactions,
                    'items' => $dayItems,
                    'gross' => $dayGross,
                    'discount' => $dayDiscount,
                    'tax' => $dayTax,
                    'net' => $dayNet,
                ]);

                fputcsv($csvOutput, [
                    $date,
                    $dayTransactions,
                    $dayItems,
                    number_format($dayGross, 2),
                    number_format($dayDiscount, 2),
                    number_format($dayTax, 2),
                    number_format($dayNet, 2),
                    number_format($dayAvg, 2)
                ]);
            }

            // ===== SUMMARY METRICS SECTION =====
            fputcsv($csvOutput, []);
            fputcsv($csvOutput, ['SUMMARY METRICS']);
            fputcsv($csvOutput, ['Total Gross Sales', number_format($totalGrossSales, 2)]);
            fputcsv($csvOutput, ['Total Discounts', number_format($totalDiscount, 2)]);
            fputcsv($csvOutput, ['Total Tax', number_format($totalTax, 2)]);
            fputcsv($csvOutput, ['Total Net Sales', number_format($totalNetSales, 2)]);
            fputcsv($csvOutput, ['Total Transactions', $totalTransactions]);
            fputcsv($csvOutput, ['Total Items Sold', $totalItems]);
            fputcsv($csvOutput, ['Average Transaction Value', number_format($totalTransactions > 0 ? $totalNetSales / $totalTransactions : 0, 2)]);
            fputcsv($csvOutput, ['Discount Rate (%)', number_format($totalGrossSales > 0 ? ($totalDiscount / $totalGrossSales) * 100 : 0, 2)]);
            fputcsv($csvOutput, ['Tax Rate (%)', number_format($totalNetSales > 0 ? ($totalTax / $totalNetSales) * 100 : 0, 2)]);

            // ===== PAYMENT METHOD BREAKDOWN =====
            fputcsv($csvOutput, []);
            fputcsv($csvOutput, ['PAYMENT METHOD BREAKDOWN']);
            fputcsv($csvOutput, ['Payment Method', 'Transactions', 'Total Amount', 'Avg Transaction']);
            $paymentBreakdown = $sales->groupBy('payment_method');
            
            Log::info('💳 Payment Methods', [
                'method_count' => $paymentBreakdown->count(),
            ]);

            foreach ($paymentBreakdown as $method => $methodSales) {
                $methodCount = $methodSales->count();
                $methodTotal = $methodSales->sum(function ($s) { return (float)$s->total; });
                $methodAvg = $methodCount > 0 ? $methodTotal / $methodCount : 0;
                $methodName = $method ?? 'Unknown';
                
                Log::debug("💳 Payment: {$methodName}", [
                    'count' => $methodCount,
                    'total' => $methodTotal,
                ]);

                fputcsv($csvOutput, [$methodName, $methodCount, number_format($methodTotal, 2), number_format($methodAvg, 2)]);
            }

            // ===== CASHIER/ADMIN PERFORMANCE =====
            fputcsv($csvOutput, []);
            fputcsv($csvOutput, ['CASHIER/ADMIN PERFORMANCE']);
            fputcsv($csvOutput, ['Staff Name', 'Transactions', 'Total Sales', 'Avg Transaction', 'Items Sold', 'Discount Given']);
            $cashierStats = $sales->groupBy(function ($s) { return $s->user ? $s->user->name : 'Unknown'; });
            
            Log::info('👤 Staff Members', [
                'staff_count' => $cashierStats->count(),
            ]);

            foreach ($cashierStats as $cashier => $cashierSales) {
                $cashierCount = $cashierSales->count();
                $cashierTotal = $cashierSales->sum(function ($s) { return (float)$s->total; });
                $cashierAvg = $cashierCount > 0 ? $cashierTotal / $cashierCount : 0;
                $cashierItems = $cashierSales->sum(function ($s) { return $s->items->sum('quantity'); });
                $cashierDiscount = $cashierSales->sum(function ($s) { return (float)$s->discount; });
                
                Log::debug("👤 Staff: {$cashier}", [
                    'transactions' => $cashierCount,
                    'total_sales' => $cashierTotal,
                    'items_sold' => $cashierItems,
                ]);

                fputcsv($csvOutput, [$cashier, $cashierCount, number_format($cashierTotal, 2), number_format($cashierAvg, 2), $cashierItems, number_format($cashierDiscount, 2)]);
            }

            // ===== TOP SELLING PRODUCTS =====
            fputcsv($csvOutput, []);
            fputcsv($csvOutput, ['TOP SELLING PRODUCTS']);
            fputcsv($csvOutput, ['Product Name', 'Category', 'Quantity Sold', 'Total Revenue', 'Avg Unit Price', 'Profit Margin']);
            $topItems = DB::table('sale_items')
                ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
                ->join('products', 'sale_items.product_id', '=', 'products.id')
                ->where('sales.company_id', $user->company_id)
                ->whereBetween('sales.created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
                ->selectRaw('products.id, products.name, products.category, products.cost_price, SUM(sale_items.quantity) as total_qty, SUM(sale_items.total_price) as total_revenue, AVG(sale_items.unit_price) as avg_price')
                ->groupBy('products.id', 'products.name', 'products.category', 'products.cost_price')
                ->orderByDesc('total_qty')
                ->limit(20)
                ->get();

            Log::info('🏆 Top Products', [
                'product_count' => $topItems->count(),
            ]);

            foreach ($topItems as $item) {
                $profitMargin = $item->cost_price > 0 ? (($item->avg_price - $item->cost_price) / $item->cost_price) * 100 : 0;
                
                Log::debug("🏆 Product: {$item->name}", [
                    'quantity' => $item->total_qty,
                    'revenue' => $item->total_revenue,
                    'avg_price' => $item->avg_price,
                    'profit_margin' => $profitMargin,
                ]);

                fputcsv($csvOutput, [
                    $item->name,
                    $item->category ?? 'Uncategorized',
                    $item->total_qty,
                    number_format($item->total_revenue, 2),
                    number_format($item->avg_price, 2),
                    number_format($profitMargin, 2) . '%'
                ]);
            }

            // ===== CUSTOMER ANALYSIS =====
            fputcsv($csvOutput, []);
            fputcsv($csvOutput, ['CUSTOMER ANALYSIS']);
            fputcsv($csvOutput, ['Customer Name', 'Transactions', 'Total Spent', 'Avg Purchase', 'Last Purchase Date']);
            
            $topCustomers = DB::table('sales')
                ->join('customers', 'sales.customer_id', '=', 'customers.id')
                ->where('sales.company_id', $user->company_id)
                ->whereBetween('sales.created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
                ->whereNotNull('sales.customer_id')
                ->selectRaw('customers.name, COUNT(sales.id) as transaction_count, SUM(sales.total) as total_spent, AVG(sales.total) as avg_purchase, MAX(sales.created_at) as last_purchase')
                ->groupBy('customers.id', 'customers.name')
                ->orderByDesc('total_spent')
                ->limit(20)
                ->get();

            foreach ($topCustomers as $customer) {
                fputcsv($csvOutput, [
                    $customer->name,
                    $customer->transaction_count,
                    number_format($customer->total_spent, 2),
                    number_format($customer->avg_purchase, 2),
                    Carbon::parse($customer->last_purchase)->format('Y-m-d H:i:s')
                ]);
            }

            rewind($csvOutput);
            $csvData = stream_get_contents($csvOutput);
            fclose($csvOutput);

            Log::info('✅ DSRS CSV Generated', [
                'file_size' => strlen($csvData),
                'company_id' => $user->company_id,
                'sales_count' => $sales->count(),
                'transactions' => $totalTransactions,
            ]);

            $filename = 'DSRS_' . $startDate . '_' . $endDate . '.csv';
            return response($csvData, 200)
                ->header('Content-Type', 'text/csv; charset=utf-8')
                ->header('Content-Disposition', 'attachment; filename="' . $filename . '"')
                ->header('Cache-Control', 'no-cache, no-store, must-revalidate')
                ->header('Pragma', 'no-cache')
                ->header('Expires', '0');

        } catch (\Exception $e) {
            Log::error('❌ DSRS Export Error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => auth()->id(),
            ]);

            return response()->json([
                'error' => 'Failed to generate DSRS export',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Export Sales Summary as Excel with transactions and totals
     */
    public function exportSalesSummaryExcel(Request $request)
    {
        Log::info('📄 SALES SUMMARY EXCEL REQUEST', [
            'user_id' => auth()->id(),
            'company_id' => auth()->user()?->company_id,
        ]);

        try {
            $user = auth()->user();
            if (!$user || !$user->company_id) {
                Log::error('❌ Sales Summary - Unauthorized');
                return response()->json(['error' => 'Unauthorized'], 403);
            }

            $startDate = $request->input('start_date', Carbon::now()->subDays(30)->toDateString());
            $endDate = $request->input('end_date', Carbon::now()->toDateString());

            Log::info('📊 Fetching data for Sales Summary', [
                'date_range' => "{$startDate} to {$endDate}",
                'company_id' => $user->company_id,
            ]);

            // Fetch sales data with eager loading
            $sales = Sale::where('company_id', $user->company_id)
                ->whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
                ->with([
                    'items' => function($query) {
                        $query->with('product');
                    },
                    'customer',
                    'user'
                ])
                ->orderBy('created_at', 'asc')
                ->get();

            Log::info('📈 Sales Summary Data Loaded', [
                'sales_count' => $sales->count(),
                'company_id' => $user->company_id,
            ]);

            // Generate CSV using temp file (same proven method as DSRS)
            $csvOutput = fopen('php://temp', 'r+');
            
            if (!$csvOutput) {
                throw new \Exception('Failed to create temporary CSV file');
            }
            
            // Add BOM for UTF-8
            fwrite($csvOutput, chr(0xEF).chr(0xBB).chr(0xBF));
            
            // ===== HEADER SECTION =====
            fputcsv($csvOutput, ['Sales Summary Report']);
            fputcsv($csvOutput, []);
            fputcsv($csvOutput, ['Company:', $user->company ? $user->company->name : 'N/A']);
            fputcsv($csvOutput, ['Period:', "{$startDate} to {$endDate}"]);
            fputcsv($csvOutput, ['Generated:', now()->format('Y-m-d H:i:s')]);
            fputcsv($csvOutput, []);

            // ===== SALES TRANSACTIONS SECTION =====
            fputcsv($csvOutput, ['SALES TRANSACTIONS']);
            fputcsv($csvOutput, ['Sale ID', 'Date', 'Time', 'Cashier', 'Customer', 'Product', 'Qty', 'Unit Price', 'Subtotal', 'Discount', 'Tax', 'Total', 'Payment Method']);

            $totalGrossSales = 0;
            $totalDiscount = 0;
            $totalTax = 0;
            $totalNetSales = 0;
            $totalTransactions = 0;
            $totalItems = 0;

            // Process each sale
            foreach ($sales as $sale) {
                $saleDate = $sale->created_at->format('Y-m-d');
                $saleTime = $sale->created_at->format('H:i:s');
                $cashierName = $sale->user ? $sale->user->name : 'System';
                $customerName = $sale->customer ? $sale->customer->name : 'Walk-in';
                $paymentMethod = $sale->payment_method ?? 'Not Specified';
                
                $saleItems = $sale->items;
                
                if ($saleItems && $saleItems->count() > 0) {
                    $totalItemsPrice = $saleItems->sum('total_price');
                    
                    foreach ($saleItems as $index => $item) {
                        $subtotal = (float)$item->total_price;
                        
                        // Distribute discount and tax proportionally
                        if ($totalItemsPrice > 0) {
                            $proportion = $subtotal / $totalItemsPrice;
                            $itemDiscount = $proportion * (float)$sale->discount;
                            $itemTax = $proportion * (float)$sale->tax;
                        } else {
                            $itemDiscount = 0;
                            $itemTax = 0;
                        }
                        
                        $itemTotal = $subtotal - $itemDiscount + $itemTax;
                        
                        $displaySaleId = $index === 0 ? $sale->id : '';
                        $displayDate = $index === 0 ? $saleDate : '';
                        $displayTime = $index === 0 ? $saleTime : '';
                        $displayCashier = $index === 0 ? $cashierName : '';
                        $displayCustomer = $index === 0 ? $customerName : '';
                        $displayPayment = $index === 0 ? $paymentMethod : '';
                        
                        $productName = $item->product ? $item->product->name : 'Unknown Product';
                        $unitPrice = (float)$item->unit_price;
                        $quantity = (int)$item->quantity;
                        
                        fputcsv($csvOutput, [
                            $displaySaleId,
                            $displayDate,
                            $displayTime,
                            $displayCashier,
                            $displayCustomer,
                            $productName,
                            $quantity,
                            number_format($unitPrice, 2),
                            number_format($subtotal, 2),
                            number_format($itemDiscount, 2),
                            number_format($itemTax, 2),
                            number_format($itemTotal, 2),
                            $displayPayment
                        ]);
                    }
                } else {
                    // Sale with no items (edge case)
                    fputcsv($csvOutput, [
                        $sale->id,
                        $saleDate,
                        $saleTime,
                        $cashierName,
                        $customerName,
                        'No items',
                        0,
                        '0.00',
                        '0.00',
                        number_format($sale->discount, 2),
                        number_format($sale->tax, 2),
                        number_format($sale->total, 2),
                        $paymentMethod
                    ]);
                }
                
                $totalTransactions++;
                $totalItems += $saleItems ? $saleItems->sum('quantity') : 0;
                $totalGrossSales += ((float)$sale->total + (float)$sale->discount);
                $totalDiscount += (float)$sale->discount;
                $totalTax += (float)$sale->tax;
                $totalNetSales += (float)$sale->total;
            }

            // ===== TOTALS SECTION =====
            fputcsv($csvOutput, []);
            fputcsv($csvOutput, ['TOTALS']);
            fputcsv($csvOutput, ['Metric', 'Value']);
            fputcsv($csvOutput, ['Total Gross Sales', number_format($totalGrossSales, 2)]);
            fputcsv($csvOutput, ['Total Discounts', number_format($totalDiscount, 2)]);
            fputcsv($csvOutput, ['Total Tax', number_format($totalTax, 2)]);
            fputcsv($csvOutput, ['Total Net Sales', number_format($totalNetSales, 2)]);
            fputcsv($csvOutput, ['Total Transactions', $totalTransactions]);
            fputcsv($csvOutput, ['Total Items Sold', $totalItems]);
            fputcsv($csvOutput, []);
            
            // ===== KEY METRICS =====
            fputcsv($csvOutput, ['KEY METRICS']);
            fputcsv($csvOutput, ['Metric', 'Value']);
            fputcsv($csvOutput, ['Average Transaction Value', number_format($totalTransactions > 0 ? $totalNetSales / $totalTransactions : 0, 2)]);
            fputcsv($csvOutput, ['Discount Rate (%)', number_format($totalGrossSales > 0 ? ($totalDiscount / $totalGrossSales) * 100 : 0, 2)]);
            fputcsv($csvOutput, ['Tax Rate (%)', number_format($totalNetSales > 0 ? ($totalTax / $totalNetSales) * 100 : 0, 2)]);
            fputcsv($csvOutput, ['Items Per Transaction', number_format($totalTransactions > 0 ? $totalItems / $totalTransactions : 0, 2)]);

            // Read CSV data from temp file
            rewind($csvOutput);
            $csvData = stream_get_contents($csvOutput);
            fclose($csvOutput);

            if (!$csvData || strlen($csvData) === 0) {
                throw new \Exception('Failed to generate CSV data - output is empty');
            }

            Log::info('✅ Sales Summary Excel Generated', [
                'company_id' => $user->company_id,
                'sales_count' => $sales->count(),
                'total_transactions' => $totalTransactions,
                'file_size' => strlen($csvData),
            ]);

            $filename = 'Sales_Summary_' . $startDate . '_' . $endDate . '.csv';
            
            // Return response with proper headers for CSV
            return response($csvData, 200)
                ->header('Content-Type', 'text/csv; charset=utf-8')
                ->header('Content-Disposition', 'attachment; filename="' . $filename . '"')
                ->header('Cache-Control', 'no-cache, no-store, must-revalidate')
                ->header('Pragma', 'no-cache')
                ->header('Expires', '0');

        } catch (\Exception $e) {
            Log::error('❌ Sales Summary Excel Error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => auth()->id(),
            ]);

            return response()->json(
                [
                    'error' => 'Failed to generate Sales Summary',
                    'message' => $e->getMessage(),
                ],
                500
            );
        }
    }

    /**
     * Export Transfers as Excel (CSV)
     */
    public function exportTransfersExcel(Request $request)
    {
        $user = auth()->user();
        if (!$user || !$user->company_id) {
            return response()->json(['error' => 'Unauthorized or no company associated'], 403);
        }

        $startDate = $request->input('start_date', Carbon::now()->subDays(30)->toDateString());
        $endDate = $request->input('end_date', Carbon::now()->toDateString());

        $transfers = \App\Models\WarehouseTransfer::where('company_id', $user->company_id)
            ->whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
            ->with(['product', 'fromWarehouse', 'toWarehouse', 'user'])
            ->orderBy('created_at', 'desc')
            ->get();

        // Prepare CSV
        $csvOutput = fopen('php://temp', 'r+');
        fwrite($csvOutput, chr(0xEF).chr(0xBB).chr(0xBF)); // UTF-8 BOM

        // Header
        fputcsv($csvOutput, ['Transfers Report']);
        fputcsv($csvOutput, []);
        fputcsv($csvOutput, ['Company:', $user->company ? $user->company->name : 'N/A']);
        fputcsv($csvOutput, ['Period:', "{$startDate} to {$endDate}"]);
        fputcsv($csvOutput, ['Generated:', now()->format('Y-m-d H:i:s')]);
        fputcsv($csvOutput, []);

        // Table header
        fputcsv($csvOutput, [
            'Transfer ID', 'Date', 'Time', 'Product', 'Quantity', 'From', 'To', 'Type', 'Initiated By'
        ]);

        foreach ($transfers as $transfer) {
            fputcsv($csvOutput, [
                $transfer->id,
                $transfer->created_at ? $transfer->created_at->format('Y-m-d') : '',
                $transfer->created_at ? $transfer->created_at->format('H:i:s') : '',
                $transfer->product ? $transfer->product->name : '',
                $transfer->quantity,
                $transfer->fromWarehouse ? $transfer->fromWarehouse->name : ($transfer->external_source ?? ''),
                $transfer->toWarehouse ? $transfer->toWarehouse->name : ($transfer->external_target ?? ''),
                ucfirst($transfer->transfer_type),
                $transfer->user ? $transfer->user->name : '',
            ]);
        }

        rewind($csvOutput);
        $csvData = stream_get_contents($csvOutput);
        fclose($csvOutput);

        $filename = 'Transfers_' . $startDate . '_' . $endDate . '.csv';
        return response($csvData, 200)
            ->header('Content-Type', 'text/csv; charset=utf-8')
            ->header('Content-Disposition', 'attachment; filename="' . $filename . '"')
            ->header('Cache-Control', 'no-cache, no-store, must-revalidate')
            ->header('Pragma', 'no-cache')
            ->header('Expires', '0');
    }

    /**
     * Export Promotions as CSV
     */
    public function exportPromotionsCsv(Request $request)
    {
        $user = auth()->user();
        if (!$user || !$user->company_id) {
            return response()->json(['error' => 'Unauthorized or no company associated'], 403);
        }

        $startDate = $request->input('start_date', Carbon::now()->subDays(30)->toDateString());
        $endDate = $request->input('end_date', Carbon::now()->toDateString());

        $promotions = \App\Models\Promotion::where('company_id', $user->company_id)->get();

        $csvOutput = fopen('php://temp', 'r+');
        fwrite($csvOutput, chr(0xEF).chr(0xBB).chr(0xBF)); // UTF-8 BOM

        fputcsv($csvOutput, ['Promotions Report']);
        fputcsv($csvOutput, []);
        fputcsv($csvOutput, ['Company:', $user->company ? $user->company->name : 'N/A']);
        fputcsv($csvOutput, ['Period:', "{$startDate} to {$endDate}"]);
        fputcsv($csvOutput, ['Generated:', now()->format('Y-m-d H:i:s')]);
        fputcsv($csvOutput, []);

        fputcsv($csvOutput, [
            'Promotion Name', 'Type', 'Scope', 'Usage Count', 'Total Discount', 'Status'
        ]);

        foreach ($promotions as $promo) {
            $usages = \App\Models\PromotionUsage::where('promotion_id', $promo->id)
                ->whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
                ->get();
            $usageCount = $usages->count();
            $discountSum = $usages->sum('discount_amount');
            fputcsv($csvOutput, [
                $promo->name,
                $promo->type,
                $promo->scope,
                $usageCount,
                number_format($discountSum, 2),
                $promo->is_active ? 'Active' : 'Inactive'
            ]);
        }

        rewind($csvOutput);
        $csvData = stream_get_contents($csvOutput);
        fclose($csvOutput);

        $filename = 'Promotions_' . $startDate . '_' . $endDate . '.csv';
        return response($csvData, 200)
            ->header('Content-Type', 'text/csv; charset=utf-8')
            ->header('Content-Disposition', 'attachment; filename="' . $filename . '"')
            ->header('Cache-Control', 'no-cache, no-store, must-revalidate')
            ->header('Pragma', 'no-cache')
            ->header('Expires', '0');
    }

    /**
     * Export Customers as CSV
     */
    public function exportCustomersCsv(Request $request)
    {
        $user = auth()->user();
        if (!$user || !$user->company_id) {
            return response()->json(['error' => 'Unauthorized or no company associated'], 403);
        }

        $startDate = $request->input('start_date', Carbon::now()->subDays(30)->toDateString());
        $endDate = $request->input('end_date', Carbon::now()->toDateString());

        $salesInPeriod = \App\Models\Sale::where(function($query) use ($user) {
                $query->where('company_id', $user->company_id)
                      ->orWhereNull('company_id');
            })
            ->whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
            ->get();

        $customerIds = $salesInPeriod->whereNotNull('customer_id')->pluck('customer_id')->unique();

        $customers = \App\Models\Customer::where('company_id', $user->company_id)
            ->whereIn('id', $customerIds)
            ->get();

        $csvOutput = fopen('php://temp', 'r+');
        fwrite($csvOutput, chr(0xEF).chr(0xBB).chr(0xBF)); // UTF-8 BOM

        fputcsv($csvOutput, ['Customers Report']);
        fputcsv($csvOutput, []);
        fputcsv($csvOutput, ['Company:', $user->company ? $user->company->name : 'N/A']);
        fputcsv($csvOutput, ['Period:', "{$startDate} to {$endDate}"]);
        fputcsv($csvOutput, ['Generated:', now()->format('Y-m-d H:i:s')]);
        fputcsv($csvOutput, []);

        fputcsv($csvOutput, [
            'Customer Name', 'Purchases', 'Total Spent', 'Avg Purchase', 'Last Purchase Date'
        ]);

        foreach ($customers as $customer) {
            $sales = \App\Models\Sale::where('customer_id', $customer->id)
                ->whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
                ->get();
            $totalSpent = $sales->sum('total');
            $purchaseCount = $sales->count();
            $avgPurchase = $purchaseCount > 0 ? $totalSpent / $purchaseCount : 0;
            $lastPurchase = $sales->max('created_at');
            fputcsv($csvOutput, [
                $customer->name,
                $purchaseCount,
                number_format($totalSpent, 2),
                number_format($avgPurchase, 2),
                $lastPurchase ? \Carbon\Carbon::parse($lastPurchase)->format('Y-m-d H:i:s') : ''
            ]);
        }

        rewind($csvOutput);
        $csvData = stream_get_contents($csvOutput);
        fclose($csvOutput);

        $filename = 'Customers_' . $startDate . '_' . $endDate . '.csv';
        return response($csvData, 200)
            ->header('Content-Type', 'text/csv; charset=utf-8')
            ->header('Content-Disposition', 'attachment; filename="' . $filename . '"')
            ->header('Cache-Control', 'no-cache, no-store, must-revalidate')
            ->header('Pragma', 'no-cache')
            ->header('Expires', '0');
    }

    /**
     * Get suppliers report with details and metrics
     */
    public function suppliersReport(Request $request)
    {
        $user = auth()->user();
        if (!$user || !$user->company_id) {
            return response()->json(['error' => 'Unauthorized or no company associated'], 403);
        }

        $startDate = $request->input('start_date', Carbon::now()->subDays(30)->toDateString());
        $endDate = $request->input('end_date', Carbon::now()->toDateString());

        $suppliers = \App\Models\Supplier::where('company_id', $user->company_id)->get();

        $totalSuppliers = $suppliers->count();
        $totalProductsSupplied = $suppliers->pluck('products_supplied')->filter()->flatMap(function($v) {
            return array_map('trim', explode(',', $v));
        })->unique()->count();

        // Count recent purchases for all suppliers (by supplier_id or supplier_name)
        $recentPurchasesCount = 0;
        foreach ($suppliers as $supplier) {
            $recentPurchasesCount += \App\Models\Purchase::where(function($q) use ($supplier) {
                $q->where('supplier_id', $supplier->id)
                  ->orWhere(function($q2) use ($supplier) {
                      $q2->whereNull('supplier_id')
                         ->where('supplier_name', $supplier->name);
                  });
            })
            ->whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
            ->count();
        }

        $list = $suppliers->map(function($s) use ($startDate, $endDate) {
            // Count purchases for this supplier (by id or name)
            $purchaseCount = \App\Models\Purchase::where(function($q) use ($s) {
                $q->where('supplier_id', $s->id)
                  ->orWhere(function($q2) use ($s) {
                      $q2->whereNull('supplier_id')
                         ->where('supplier_name', $s->name);
                  });
            })
            ->whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
            ->count();

            return [
                'id' => $s->id,
                'name' => $s->name,
                'contact_person' => $s->contact_person,
                'email' => $s->email,
                'phone' => $s->phone,
                'products_supplied' => $s->products_supplied,
                'notes' => $s->notes,
                'purchase_count' => $purchaseCount,
            ];
        });

        return response()->json([
            'total_suppliers' => $totalSuppliers,
            'total_products_supplied' => $totalProductsSupplied,
            'recent_purchases_count' => $recentPurchasesCount,
            'list' => $list,
        ]);
    }

    /**
     * Export Suppliers as CSV
     */
    public function exportSuppliersCsv(Request $request)
    {
        $user = auth()->user();
        if (!$user || !$user->company_id) {
            return response()->json(['error' => 'Unauthorized or no company associated'], 403);
        }

        $startDate = $request->input('start_date', Carbon::now()->subDays(30)->toDateString());
        $endDate = $request->input('end_date', Carbon::now()->toDateString());

        $suppliers = \App\Models\Supplier::where('company_id', $user->company_id)->get();

        $csvOutput = fopen('php://temp', 'r+');
        fwrite($csvOutput, chr(0xEF).chr(0xBB).chr(0xBF)); // UTF-8 BOM

        fputcsv($csvOutput, ['Suppliers Report']);
        fputcsv($csvOutput, []);
        fputcsv($csvOutput, ['Company:', $user->company ? $user->company->name : 'N/A']);
        fputcsv($csvOutput, ['Period:', "{$startDate} to {$endDate}"]);
        fputcsv($csvOutput, ['Generated:', now()->format('Y-m-d H:i:s')]);
        fputcsv($csvOutput, []);

        fputcsv($csvOutput, [
            'Name', 'Contact Person', 'Email', 'Phone', 'Products Supplied', 'Notes'
        ]);

        foreach ($suppliers as $s) {
            fputcsv($csvOutput, [
                $s->name,
                $s->contact_person,
                $s->email,
                $s->phone,
                $s->products_supplied,
                $s->notes,
            ]);
        }

        rewind($csvOutput);
        $csvData = stream_get_contents($csvOutput);
        fclose($csvOutput);

        $filename = 'Suppliers_' . $startDate . '_' . $endDate . '.csv';
        return response($csvData, 200)
            ->header('Content-Type', 'text/csv; charset=utf-8')
            ->header('Content-Disposition', 'attachment; filename="' . $filename . '"')
            ->header('Cache-Control', 'no-cache, no-store, must-revalidate')
            ->header('Pragma', 'no-cache')
            ->header('Expires', '0');
    }
}
