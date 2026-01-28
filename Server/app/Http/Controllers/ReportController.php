<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Purchase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
        $user = auth()->user();
        if (!$user || !$user->company_id) {
            return response()->json(['error' => 'Unauthorized or no company associated'], 403);
        }

        $startDate = $request->input('start_date', Carbon::now()->subDays(30)->toDateString());
        $endDate = $request->input('end_date', Carbon::now()->toDateString());

        // Get sales for the company (or all if no company_id in old data)
        $sales = Sale::where(function($query) use ($user) {
                $query->where('company_id', $user->company_id)
                      ->orWhereNull('company_id');
            })
            ->whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
            ->with(['items.product', 'customer'])
            ->orderBy('created_at', 'desc')
            ->get();

        $totalRevenue = $sales->sum('total');
        $totalTransactions = $sales->count();
        $avgTransaction = $totalTransactions > 0 ? $totalRevenue / $totalTransactions : 0;
        $totalItems = $sales->sum(function ($sale) {
            return $sale->items->sum('quantity');
        });

        $transactions = $sales->map(function ($sale) {
            return [
                'id' => $sale->id,
                'total' => $sale->total,
                'items_count' => $sale->items->sum('quantity'),
                'customer_name' => $sale->customer ? $sale->customer->name : null,
                'created_at' => $sale->created_at->toISOString(),
            ];
        });

        return response()->json([
            'total_revenue' => round($totalRevenue, 2),
            'total_transactions' => $totalTransactions,
            'avg_transaction' => round($avgTransaction, 2),
            'total_items' => $totalItems,
            'transactions' => $transactions,
        ]);
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
}
