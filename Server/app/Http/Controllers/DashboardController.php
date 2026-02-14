<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;
use App\Models\Product;

class DashboardController extends Controller
{
    public function getDashboardStats()
    {
        try {
            $today = Carbon::today();
            $yesterday = Carbon::yesterday();
            
            // Initialize default values
            $todaySales = 0;
            $yesterdaySales = 0;
            $recentSales = [];
            
            // Check if sales table exists and get sales data
            if (Schema::hasTable('sales')) {
                // Try different possible column names for total
                $totalColumn = 'total';
                if (!Schema::hasColumn('sales', 'total') && Schema::hasColumn('sales', 'total_amount')) {
                    $totalColumn = 'total_amount';
                } elseif (!Schema::hasColumn('sales', 'total') && Schema::hasColumn('sales', 'amount')) {
                    $totalColumn = 'amount';
                }
                
                if (Schema::hasColumn('sales', $totalColumn)) {
                    $todaySales = (float) DB::table('sales')
                        ->whereDate('created_at', $today)
                        ->sum($totalColumn);
                    
                    $yesterdaySales = (float) DB::table('sales')
                        ->whereDate('created_at', $yesterday)
                        ->sum($totalColumn);
                    
                    // Get recent sales (last 5)
                    $recentSales = DB::table('sales')
                        ->select('id', $totalColumn . ' as total', 'created_at')
                        ->orderBy('created_at', 'desc')
                        ->limit(5)
                        ->get()
                        ->map(function($sale) {
                            return [
                                'id' => $sale->id,
                                'total' => (float) $sale->total,
                                'created_at' => $sale->created_at
                            ];
                        });
                }
            }
            
            // Get stock information
            $lowStock = 0;
            $outOfStock = 0;
            
            if (Schema::hasTable('products')) {
                // Count products with low stock (assuming low_stock_threshold or default threshold)
                $lowStockThreshold = 10; // default threshold
                
                $lowStock = Product::where('stock_quantity', '>', 0)
                    ->where('stock_quantity', '<=', $lowStockThreshold)
                    ->count();
                
                $outOfStock = Product::where('stock_quantity', '<=', 0)->count();
            }
            
            return response()->json([
                'today_sales' => $todaySales,
                'yesterday_sales' => $yesterdaySales,
                'low_stock' => $lowStock,
                'out_of_stock' => $outOfStock,
                'recent_sales' => $recentSales
            ]);
            
        } catch (\Exception $e) {
            \Log::error('Dashboard stats error: ' . $e->getMessage());
            
            // Return default values if there's any error
            return response()->json([
                'today_sales' => 0,
                'yesterday_sales' => 0,
                'low_stock' => 0,
                'out_of_stock' => 0,
                'recent_sales' => []
            ]);
        }
    }
}
