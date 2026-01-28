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
        $cacheKey = 'dashboard_stats_v1';
        $stats = \Cache::remember($cacheKey, now()->addSeconds(30), function () {
            $today = Carbon::today();
            $yesterday = Carbon::yesterday();
            $todaySales = Sale::whereDate('created_at', $today)->sum('total');
            $yesterdaySales = Sale::whereDate('created_at', $yesterday)->sum('total');
            $lowStock = Product::where('stock_quantity', '<', 5)->count();
            $outOfStock = Product::where('stock_quantity', '<=', 0)->count();
            $recentSales = Sale::with('saleItems.product')
                ->orderBy('created_at', 'desc')
                ->take(5)
                ->get();
            return [
                'today_sales' => $todaySales,
                'yesterday_sales' => $yesterdaySales,
                'low_stock' => $lowStock,
                'out_of_stock' => $outOfStock,
                'recent_sales' => $recentSales
            ];
        });
        return response()->json($stats);
    }
}
