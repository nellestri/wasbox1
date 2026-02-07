<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Customer;
use App\Models\PickupRequest;
use App\Models\Branch;
use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardController extends Controller
{
    /**
     * Display the staff dashboard with analytics
     */
    public function index(Request $request)
    {
        // ====================================================================
        // CRITICAL FIX: Get staff's branch (not from request!)
        // ====================================================================
        $staff = auth()->user();

        // Safety check - staff must have a branch
        if (!$staff || !$staff->branch_id) {
            return redirect()
                ->route('staff.dashboard')
                ->with('error', 'Your account is not assigned to a branch. Please contact administrator.');
        }

        // Staff can ONLY see their own branch
        $branchId = $staff->branch_id;

        // Get filter parameters (date range only, no branch selection for staff)
        $dateRange = $request->get('date_range', 'last_30_days');

        // Calculate date range
        $dates = $this->getDateRange($dateRange);
        $startDate = $dates['start'];
        $endDate = $dates['end'];

        // Get dashboard data
        $data = [
            // KPIs
            'kpis' => $this->getKPIs($startDate, $endDate, $branchId),

            // Revenue metrics
            'revenue' => $this->getRevenueMetrics($startDate, $endDate, $branchId),

            // Order metrics
            'orders' => $this->getOrderMetrics($startDate, $endDate, $branchId),

            // Customer metrics
            'customers' => $this->getCustomerMetrics($startDate, $endDate, $branchId),

            // Pickup metrics
            'pickups' => $this->getPickupMetrics($branchId),

            // Pickup locations (customers' pinned locations) for map display
            'pickupLocations' => PickupRequest::with('customer:id,name,phone')
                ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
                ->whereIn('status', ['pending', 'en_route'])
                ->whereNotNull('latitude')
                ->whereNotNull('longitude')
                ->get(),

            // Unclaimed laundry
            'unclaimed' => $this->getUnclaimedLaundry($branchId),

            // Branch performance (only staff's branch)
            'branches' => $this->getBranchPerformance($startDate, $endDate, $branchId),

            // Order status pipeline
            'pipeline' => $this->getOrderPipeline($branchId),

            // Recent orders (FIXED: now uses branchId)
            'recent_orders' => $this->getRecentOrders($branchId, 10),

            // Alerts
            'alerts' => $this->getAlerts($branchId),

            // Charts data
            'charts' => $this->getChartsData($startDate, $endDate, $branchId),

            // Filters (staff can only see their branch)
            'branches' => Branch::where('id', $branchId)->get(),
            'current_filters' => [
                'date_range' => $dateRange,
                'branch_id' => $branchId,
            ],
        ];

        return view('staff.dashboard', $data);
    }

    /**
     * Get Key Performance Indicators
     */
    private function getKPIs($startDate, $endDate, $branchId = null)
    {
        $query = Order::whereBetween('created_at', [$startDate, $endDate]);
        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        // Today's revenue
        $todayRevenue = (clone $query)
            ->whereDate('created_at', today())
            ->where('status', 'completed')
            ->sum('total_amount');

        // Yesterday's revenue
        $yesterdayRevenue = Order::whereDate('created_at', today()->subDay())
            ->where('status', 'completed')
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->sum('total_amount');

        // Monthly revenue
        $monthlyRevenue = (clone $query)
            ->whereMonth('created_at', now()->month)
            ->where('status', 'completed')
            ->sum('total_amount');

        // Last month revenue
        $lastMonthRevenue = Order::whereMonth('created_at', now()->subMonth()->month)
            ->where('status', 'completed')
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->sum('total_amount');

        return [
            'today_revenue' => [
                'value' => $todayRevenue,
                'change' => $yesterdayRevenue > 0
                    ? (($todayRevenue - $yesterdayRevenue) / $yesterdayRevenue) * 100
                    : 0,
                'vs' => 'yesterday',
            ],
            'monthly_revenue' => [
                'value' => $monthlyRevenue,
                'change' => $lastMonthRevenue > 0
                    ? (($monthlyRevenue - $lastMonthRevenue) / $lastMonthRevenue) * 100
                    : 0,
                'vs' => 'last month',
            ],
            'active_orders' => [
                'value' => Order::whereIn('status', ['received', 'ready', 'paid'])
                    ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
                    ->count(),
            ],
            'ready_for_pickup' => [
                'value' => Order::where('status', 'ready')
                    ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
                    ->count(),
                'avg_wait_days' => Order::where('status', 'ready')
                    ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
                    ->selectRaw('AVG(DATEDIFF(NOW(), updated_at)) as avg_days')
                    ->value('avg_days') ?? 0,
            ],
            'completed_today' => [
                'value' => Order::whereDate('updated_at', today())
                    ->where('status', 'completed')
                    ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
                    ->count(),
            ],
            'total_customers' => [
                'value' => Customer::count(),
                'new_this_month' => Customer::whereMonth('created_at', now()->month)->count(),
            ],
            'pending_pickups' => [
                'value' => PickupRequest::where('status', 'pending')
                    ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
                    ->count(),
            ],
        ];
    }

    /**
     * Get revenue metrics
     */
    private function getRevenueMetrics($startDate, $endDate, $branchId = null)
    {
        $revenue = Order::whereBetween('created_at', [$startDate, $endDate])
            ->where('status', 'completed')
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->selectRaw('
                SUM(total_amount) as total,
                AVG(total_amount) as average,
                MAX(total_amount) as highest,
                COUNT(*) as orders
            ')
            ->first();

        return [
            'total' => $revenue->total ?? 0,
            'average' => $revenue->average ?? 0,
            'highest' => $revenue->highest ?? 0,
            'orders' => $revenue->orders ?? 0,
            'per_day' => $revenue->total > 0
                ? $revenue->total / max(1, $startDate->diffInDays($endDate))
                : 0,
        ];
    }

    /**
     * Get order metrics
     */
    private function getOrderMetrics($startDate, $endDate, $branchId = null)
    {
        $query = Order::whereBetween('created_at', [$startDate, $endDate]);
        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        return [
            'total' => $query->count(),
            'by_status' => Order::whereBetween('created_at', [$startDate, $endDate])
                ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
                ->select('status', DB::raw('count(*) as count'))
                ->groupBy('status')
                ->pluck('count', 'status')
                ->toArray(),
            // FIX: Specify table name for created_at to avoid ambiguity
            'by_service' => Order::whereBetween('orders.created_at', [$startDate, $endDate])
                ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
                ->join('services', 'orders.service_id', '=', 'services.id')
                ->select('services.name', DB::raw('count(*) as count'))
                ->groupBy('services.name')
                ->pluck('count', 'name')
                ->toArray(),
        ];
    }

    /**
     * Get customer metrics
     */
    private function getCustomerMetrics($startDate, $endDate, $branchId = null)
    {
        return [
            'total' => Customer::count(),
            'new' => Customer::whereBetween('created_at', [$startDate, $endDate])->count(),
            'active' => Customer::whereHas('orders', function($q) use ($startDate, $endDate, $branchId) {
                $q->whereBetween('created_at', [$startDate, $endDate]);
                if ($branchId) {
                    $q->where('branch_id', $branchId);
                }
            })->count(),
            'top_customers' => Order::whereBetween('created_at', [$startDate, $endDate])
                ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
                ->select('customer_id', DB::raw('count(*) as order_count, sum(total_amount) as total_spent'))
                ->groupBy('customer_id')
                ->orderByDesc('total_spent')
                ->with('customer:id,name,email')
                ->limit(5)
                ->get(),
        ];
    }

    /**
     * Get pickup request metrics
     */
    private function getPickupMetrics($branchId = null)
    {
        $query = PickupRequest::query();
        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        return [
            'pending' => (clone $query)->where('status', 'pending')->count(),
            'en_route' => (clone $query)->where('status', 'en_route')->count(),
            'completed_today' => PickupRequest::whereDate('picked_up_at', today())
                ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
                ->count(),
            'recent' => PickupRequest::with(['customer:id,name,phone', 'branch:id,name'])
                ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
                ->whereIn('status', ['pending', 'en_route'])
                ->orderBy('created_at', 'desc')
                ->limit(5)
                ->get(),
        ];
    }

    /**
     * Get unclaimed laundry
     */
    private function getUnclaimedLaundry($branchId = null)
    {
        $unclaimedOrders = Order::where('status', 'ready')
            ->where('updated_at', '<', now()->subDays(3))
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->with(['customer:id,name,phone', 'branch:id,name'])
            ->orderBy('updated_at')
            ->get();

        $totalValue = $unclaimedOrders->sum('total_amount');

        // Categorize by days unclaimed
        $categorized = [
            'day3' => $unclaimedOrders->where('updated_at', '>', now()->subDays(5))->count(),
            'day5' => $unclaimedOrders->where('updated_at', '<=', now()->subDays(5))
                                      ->where('updated_at', '>', now()->subDays(7))->count(),
            'day7' => $unclaimedOrders->where('updated_at', '<=', now()->subDays(7))->count(),
        ];

        return [
            'total_count' => $unclaimedOrders->count(),
            'total_value' => $totalValue,
            'categorized' => $categorized,
            'orders' => $unclaimedOrders->take(10),
            'oldest_days' => $unclaimedOrders->first()
                ? now()->diffInDays($unclaimedOrders->first()->updated_at)
                : 0,
        ];
    }

    /**
     * Get branch performance comparison (FIXED: staff only sees their branch)
     */
    private function getBranchPerformance($startDate, $endDate, $branchId = null)
    {
        // If branchId is provided (staff user), only get that branch
        $query = Branch::query();

        if ($branchId) {
            $query->where('id', $branchId);
        }

        $branches = $query->withCount([
            'orders as orders_count' => function($q) use ($startDate, $endDate) {
                $q->whereBetween('created_at', [$startDate, $endDate]);
            }
        ])
        ->withSum([
            'orders as revenue' => function($q) use ($startDate, $endDate) {
                $q->whereBetween('created_at', [$startDate, $endDate])
                  ->where('status', 'completed');
            }
        ], 'total_amount')
        ->orderByDesc('revenue')
        ->get();

        $totalRevenue = $branches->sum('revenue');

        return $branches->map(function($branch) use ($totalRevenue) {
            return [
                'id' => $branch->id,
                'name' => $branch->name,
                'orders' => $branch->orders_count,
                'revenue' => $branch->revenue ?? 0,
                'percentage' => $totalRevenue > 0
                    ? round(($branch->revenue / $totalRevenue) * 100, 1)
                    : 100, // 100% if only one branch
            ];
        });
    }

    /**
     * Get order status pipeline
     */
    private function getOrderPipeline($branchId = null)
    {
        $statuses = ['received', 'ready', 'paid', 'completed'];
        $pipeline = [];

        foreach ($statuses as $status) {
            $count = Order::where('status', $status)
                ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
                ->when($status === 'completed', fn($q) => $q->whereDate('updated_at', today()))
                ->count();

            $pipeline[$status] = $count;
        }

        return $pipeline;
    }

    /**
     * Get recent orders (FIXED: now properly filters by branch)
     */
    private function getRecentOrders($branchId = null, $limit = 10)
    {
        return Order::with(['customer:id,name', 'branch:id,name', 'service:id,name'])
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get dashboard alerts
     */
    private function getAlerts($branchId = null)
    {
        $alerts = [];

        // Ready for pickup alert
        $readyCount = Order::where('status', 'ready')
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->count();

        if ($readyCount > 0) {
            $avgWaitDays = Order::where('status', 'ready')
                ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
                ->selectRaw('AVG(DATEDIFF(NOW(), updated_at)) as avg_days')
                ->value('avg_days');

            $alerts[] = [
                'type' => 'warning',
                'icon' => 'shopping-bag',
                'title' => "$readyCount Orders Ready for Pickup",
                'message' => "Average wait time: " . round($avgWaitDays, 1) . " days. Consider sending reminders.",
                'action' => route('staff.orders.index', ['status' => 'ready']),
                'action_text' => 'View Orders',
            ];
        }

        // Unclaimed laundry alert
        $unclaimedCount = Order::where('status', 'ready')
            ->where('updated_at', '<', now()->subDays(3))
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->count();

        if ($unclaimedCount > 0) {
            $unclaimedValue = Order::where('status', 'ready')
                ->where('updated_at', '<', now()->subDays(3))
                ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
                ->sum('total_amount');

            $oldestDays = Order::where('status', 'ready')
                ->where('updated_at', '<', now()->subDays(3))
                ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
                ->selectRaw('DATEDIFF(NOW(), MIN(updated_at)) as days')
                ->value('days');

            $alerts[] = [
                'type' => 'danger',
                'icon' => 'alert-triangle',
                'title' => 'Unclaimed Laundry Alert',
                'message' => "$unclaimedCount orders unclaimed (₱" . number_format($unclaimedValue, 2) . " at risk). Oldest: $oldestDays days.",
                'action' => route('staff.unclaimed.index'),
                'action_text' => 'View Details',
            ];
        }

        // Revenue increase alert
        $todayRevenue = Order::whereDate('created_at', today())
            ->where('status', 'completed')
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->sum('total_amount');

        $yesterdayRevenue = Order::whereDate('created_at', today()->subDay())
            ->where('status', 'completed')
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->sum('total_amount');

        if ($todayRevenue > $yesterdayRevenue && $yesterdayRevenue > 0) {
            $increase = (($todayRevenue - $yesterdayRevenue) / $yesterdayRevenue) * 100;
            if ($increase >= 10) {
                $alerts[] = [
                    'type' => 'success',
                    'icon' => 'trending-up',
                    'title' => 'Revenue Increase!',
                    'message' => "Today's revenue up " . round($increase, 1) . "% compared to yesterday (₱" . number_format($todayRevenue, 2) . ")",
                ];
            }
        }

        // Pending pickups alert
        $pendingPickups = PickupRequest::where('status', 'pending')
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->count();

        if ($pendingPickups > 5) {
            $alerts[] = [
                'type' => 'warning',
                'icon' => 'truck',
                'title' => "$pendingPickups Pending Pickup Requests",
                'message' => "Multiple pickup requests are waiting for confirmation.",
                'action' => route('staff.pickups.index'),
                'action_text' => 'View Requests',
            ];
        }

        return $alerts;
    }

    /**
     * Get charts data for visualizations
     */
    private function getChartsData($startDate, $endDate, $branchId = null)
    {
        // Revenue trend (daily for last 30 days)
        $revenueTrend = Order::whereBetween('created_at', [$startDate, $endDate])
            ->where('status', 'completed')
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->selectRaw('DATE(created_at) as date, SUM(total_amount) as revenue')
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->mapWithKeys(fn($item) => [$item->date => $item->revenue]);

        // Fill missing dates with 0
        $dates = [];
        $current = clone $startDate;
        while ($current <= $endDate) {
            $dateStr = $current->format('Y-m-d');
            $dates[$dateStr] = $revenueTrend[$dateStr] ?? 0;
            $current->addDay();
        }

        // FIX: Specify table name for created_at in service distribution
        return [
            'revenue_trend' => $dates,
            'service_distribution' => Order::whereBetween('orders.created_at', [$startDate, $endDate])
                ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
                ->join('services', 'orders.service_id', '=', 'services.id')
                ->select('services.name', DB::raw('count(*) as count'))
                ->groupBy('services.name')
                ->pluck('count', 'name'),
        ];
    }

    /**
     * Get date range based on filter
     */
    private function getDateRange($range)
    {
        $end = now();

        switch ($range) {
            case 'today':
                $start = now()->startOfDay();
                break;
            case 'yesterday':
                $start = now()->subDay()->startOfDay();
                $end = now()->subDay()->endOfDay();
                break;
            case 'last_7_days':
                $start = now()->subDays(7)->startOfDay();
                break;
            case 'last_30_days':
            default:
                $start = now()->subDays(30)->startOfDay();
                break;
            case 'this_month':
                $start = now()->startOfMonth();
                break;
            case 'last_month':
                $start = now()->subMonth()->startOfMonth();
                $end = now()->subMonth()->endOfMonth();
                break;
        }

        return ['start' => $start, 'end' => $end];
    }

    /**
     * Export dashboard data
     */
    public function export(Request $request)
    {
        $staff = auth()->user();

        if (!$staff || !$staff->branch_id) {
            return back()->with('error', 'Your account is not assigned to a branch.');
        }

        // TODO: Add export functionality
        return back()->with('info', 'Export feature coming soon!');
    }
}
