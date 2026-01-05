<?php
namespace App\Http\Controllers\SuperUser;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;
use App\Models\User;
use App\Models\Company;

class DashboardController extends Controller
{
    // GET /api/super/dashboard/overview
    public function overview(Request $request)
    {
        $cacheKey = 'super_dashboard_overview_v1';

        $overview = Cache::remember($cacheKey, now()->addMinutes(5), function () {
            // Totals
            $totalBusinesses = (int) Company::count();
            $activeSubscriptions = (int) DB::table('subscriptions')->where('status','active')->count();
            $totalUsers = (int) User::count();

            // Revenue / MRR (safe casts)
            $today = Carbon::today();
            // support either 'total' or legacy 'total_amount' column
            $revenueColumn = null;
            if (Schema::hasColumn('sales', 'total')) {
                $revenueColumn = 'total';
            } elseif (Schema::hasColumn('sales', 'total_amount')) {
                $revenueColumn = 'total_amount';
            }
            $dailyRevenue = $revenueColumn ? (float) DB::table('sales')->whereDate('created_at', $today)->sum($revenueColumn) : 0.0;
            $mrr = (float) DB::table('subscriptions')->where('status','active')->sum('monthly_fee');

            // System health
            $queuedJobs = (int) DB::table('jobs')->count();
            $failedJobs = (int) DB::table('failed_jobs')->count();

            // Monthly signups (last 12 months) -> labels & data
            $monthlyRaw = User::selectRaw("DATE_FORMAT(created_at, '%Y-%m') as month, count(*) as count")
                ->where('created_at', '>=', Carbon::now()->subMonths(11)->startOfMonth())
                ->groupBy('month')
                ->orderBy('month')
                ->get()
                ->pluck('count','month')
                ->toArray();

            // Ensure last 12 months present
            $months = [];
            $signupData = [];
            for ($i = 11; $i >= 0; $i--) {
                $m = Carbon::now()->subMonths($i)->format('Y-m');
                $months[] = $m;
                $signupData[] = isset($monthlyRaw[$m]) ? (int)$monthlyRaw[$m] : 0;
            }

            // Active businesses per month
            $businessRaw = Company::selectRaw("DATE_FORMAT(created_at, '%Y-%m') as month, count(*) as count")
                ->where('created_at', '>=', Carbon::now()->subMonths(11)->startOfMonth())
                ->groupBy('month')
                ->orderBy('month')
                ->get()
                ->pluck('count','month')
                ->toArray();

            $businessData = [];
            foreach ($months as $m) {
                $businessData[] = isset($businessRaw[$m]) ? (int)$businessRaw[$m] : 0;
            }

            // Latest activities (audit logs) - fetch structured rows
            $latestActivities = DB::table('audit_logs')
                ->select('id','action','auditable_type','auditable_id','user_id','user_name','ip_address','created_at','notes')
                ->orderBy('created_at','desc')
                ->limit(20)
                ->get();

            return [
                'totals' => [
                    'total_businesses' => $totalBusinesses,
                    'active_subscriptions' => $activeSubscriptions,
                    'total_users' => $totalUsers,
                    'daily_revenue' => $dailyRevenue,
                    'mrr' => $mrr,
                ],
                'system_health' => [
                    'queued_jobs' => $queuedJobs,
                    'failed_jobs' => $failedJobs,
                ],
                'charts' => [
                    'monthly_signups' => [
                        'labels' => $months,
                        'data' => $signupData
                    ],
                    'active_businesses' => [
                        'labels' => $months,
                        'data' => $businessData
                    ]
                ],
                'latest_activities' => $latestActivities,
            ];
        });

        return response()->json($overview);
    }
}
