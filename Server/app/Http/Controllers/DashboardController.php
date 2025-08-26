<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Sale;
use App\Models\Product;
use Carbon\Carbon;
use App\Models\SaleItem;
use DB;

class DashboardController extends Controller
{
    public function getStats()
    {
        $today = Carbon::today();
        $yesterday = Carbon::yesterday();

        // Today's sales total
        $todaySales = Sale::whereDate('created_at', $today)
            ->sum('total');

        // Yesterday's sales total
        $yesterdaySales = Sale::whereDate('created_at', $yesterday)
            ->sum('total');

        // Low stock count
        $lowStock = Product::where('stock_quantity', '<', 5)->count();

        // Out of stock count
        $outOfStock = Product::where('stock_quantity', '<=', 0)->count();

        //recent 5 sales from sales table
        $recentSales = Sale::with('saleItems.product')
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get();

        return response()->json([
            'today_sales' => $todaySales,
            'yesterday_sales' => $yesterdaySales,
            'low_stock' => $lowStock,
            'out_of_stock' => $outOfStock,
            'recent_sales' => $recentSales
        ]);
    }
}
