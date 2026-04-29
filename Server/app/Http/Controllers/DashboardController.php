<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;
use App\Models\Product;

class DashboardController extends Controller
{
    public function getDashboardStats(Request $request)
    {
        try {
            $user = $request->user() ?: auth('sanctum')->user() ?: auth()->user();
            if (!$user || !$user->company_id) {
                return response()->json([
                    'scope' => 'guest',
                    'role' => null,
                    'today_sales' => 0,
                    'yesterday_sales' => 0,
                    'today_transactions' => 0,
                    'yesterday_transactions' => 0,
                    'low_stock' => 0,
                    'out_of_stock' => 0,
                    'recent_sales' => [],
                    'cashier_performance' => []
                ]);
            }

            $user->loadMissing('role');
            $roleName = strtolower($user->role->name ?? '');
            $isAdminRole = in_array($roleName, ['admin', 'administrator', 'superuser', 'super_user', 'super user'], true);
            $isCashierRole = $roleName === 'cashier';

            $today = Carbon::today();
            $yesterday = Carbon::yesterday();

            // Initialize default values
            $todaySales = 0;
            $yesterdaySales = 0;
            $recentSales = [];
            $todayTransactions = 0;
            $yesterdayTransactions = 0;
            $cashierPerformance = [];

            // Check if sales table exists and get sales data
            if (Schema::hasTable('sales')) {

                $salesBase = DB::table('sales')->where('company_id', $user->company_id);
                if ($isCashierRole) {
                    $salesBase->where('user_id', $user->id);
                }

                $todaySales = (float) (clone $salesBase)
                    ->whereDate('created_at', $today)
                    ->sum('total');

                $yesterdaySales = (float) (clone $salesBase)
                    ->whereDate('created_at', $yesterday)
                    ->sum('total');

                $todayTransactions = (int) (clone $salesBase)
                    ->whereDate('created_at', $today)
                    ->count();

                $yesterdayTransactions = (int) (clone $salesBase)
                    ->whereDate('created_at', $yesterday)
                    ->count();

                // Get recent sales (last 5)
                $recentSales = (clone $salesBase)
                    ->select('id', 'total', 'created_at', 'user_id')
                    ->orderBy('created_at', 'desc')
                    ->limit(5)
                    ->get()
                    ->map(function ($sale) {
                        return [
                            'id' => $sale->id,
                            'total' => (float) $sale->total,
                            'created_at' => $sale->created_at,
                            'user_id' => $sale->user_id,
                        ];
                    })
                    ->values();

                if ($isAdminRole) {
                    $cashierPerformance = DB::table('sales')
                        ->join('users', 'users.id', '=', 'sales.user_id')
                        ->where('sales.company_id', $user->company_id)
                        ->whereDate('sales.created_at', $today)
                        ->groupBy('sales.user_id', 'users.name')
                        ->selectRaw('sales.user_id, users.name as cashier_name, COUNT(*) as transactions, SUM(sales.total) as total_sales, AVG(sales.total) as avg_sale, MIN(sales.created_at) as first_sale_at, MAX(sales.created_at) as last_sale_at')
                        ->orderByDesc('total_sales')
                        ->get()
                        ->map(function ($row) {
                            return [
                                'user_id' => (int) $row->user_id,
                                'cashier_name' => $row->cashier_name,
                                'transactions' => (int) $row->transactions,
                                'total_sales' => round((float) $row->total_sales, 2),
                                'avg_sale' => round((float) $row->avg_sale, 2),
                                'first_sale_at' => $row->first_sale_at,
                                'last_sale_at' => $row->last_sale_at,
                            ];
                        })
                        ->values();
                }
            }

            // Get stock information
            $lowStock = 0;
            $outOfStock = 0;

            if ($isAdminRole && Schema::hasTable('products')) {
                // Count products with low stock (assuming low_stock_threshold or default threshold)
                $lowStockThreshold = 10; // default threshold

                $lowStock = Product::where('stock_quantity', '>', 0)
                    ->where('company_id', $user->company_id)
                    ->where('stock_quantity', '<=', $lowStockThreshold)
                    ->count();

                $outOfStock = Product::where('company_id', $user->company_id)
                    ->where('stock_quantity', '<=', 0)
                    ->count();
            }

            return response()->json([
                'scope' => $isCashierRole ? 'personal' : 'company',
                'role' => $roleName,
                'today_sales' => $todaySales,
                'yesterday_sales' => $yesterdaySales,
                'today_transactions' => $todayTransactions,
                'yesterday_transactions' => $yesterdayTransactions,
                'low_stock' => $lowStock,
                'out_of_stock' => $outOfStock,
                'recent_sales' => $recentSales,
                'cashier_performance' => $cashierPerformance
            ]);

        } catch (\Exception $e) {
            \Log::error('Dashboard stats error: ' . $e->getMessage());

            // Return default values if there's any error
            return response()->json([
                'scope' => 'error',
                'role' => null,
                'today_sales' => 0,
                'yesterday_sales' => 0,
                'today_transactions' => 0,
                'yesterday_transactions' => 0,
                'low_stock' => 0,
                'out_of_stock' => 0,
                'recent_sales' => [],
                'cashier_performance' => []
            ]);
        }
    }
}
