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
}
