<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Customer;
use App\Models\Branch;
use App\Models\Service;
use App\Models\Promotion;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class AnalyticsController extends Controller
{
    /**
     * Display the analytics dashboard.
     *
     * This page shows comprehensive analytics including:
     * - Revenue trends (daily, weekly, monthly)
     * - Order statistics and trends
     * - Branch performance comparison
     * - Service popularity
     * - Customer growth
     * - Promotion effectiveness
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        // Get date range from request or default to last 30 days
        $startDate = $request->input('start_date', now()->subDays(30));
        $endDate = $request->input('end_date', now());

        // Ensure dates are Carbon instances
        $startDate = Carbon::parse($startDate)->startOfDay();
        $endDate = Carbon::parse($endDate)->endOfDay();

        // ===================================================
        // REVENUE ANALYTICS
        // ===================================================

        $revenueAnalytics = $this->getRevenueAnalytics($startDate, $endDate);

        // ===================================================
        // ORDER ANALYTICS
        // ===================================================

        $orderAnalytics = $this->getOrderAnalytics($startDate, $endDate);

        // ===================================================
        // BRANCH PERFORMANCE
        // ===================================================

        $branchPerformance = $this->getBranchPerformance($startDate, $endDate);

        // ===================================================
        // SERVICE POPULARITY
        // ===================================================

        $servicePopularity = $this->getServicePopularity($startDate, $endDate);

        // ===================================================
        // CUSTOMER ANALYTICS
        // ===================================================

        $customerAnalytics = $this->getCustomerAnalytics($startDate, $endDate);

        // ===================================================
        // PROMOTION EFFECTIVENESS
        // ===================================================

        $promotionEffectiveness = $this->getPromotionEffectiveness($startDate, $endDate);

        // ===================================================
        // RETURN VIEW
        // ===================================================

        return view('admin.analytics.index', [
            'startDate' => $startDate->format('Y-m-d'),
            'endDate' => $endDate->format('Y-m-d'),
            'revenueAnalytics' => $revenueAnalytics,
            'orderAnalytics' => $orderAnalytics,
            'branchPerformance' => $branchPerformance,
            'servicePopularity' => $servicePopularity,
            'customerAnalytics' => $customerAnalytics,
            'promotionEffectiveness' => $promotionEffectiveness,
        ]);
    }

    /**
     * Get revenue analytics.
     *
     * @param  Carbon  $startDate
     * @param  Carbon  $endDate
     * @return array
     */
    protected function getRevenueAnalytics($startDate, $endDate)
    {
        // Total Revenue
        $totalRevenue = Order::whereBetween('created_at', [$startDate, $endDate])
            ->whereIn('status', ['paid', 'completed'])
            ->sum('total_amount');

        // Average Order Value
        $averageOrderValue = Order::whereBetween('created_at', [$startDate, $endDate])
            ->whereIn('status', ['paid', 'completed'])
            ->avg('total_amount');

        // Revenue by Day (for chart)
        $revenueByDay = Order::whereBetween('created_at', [$startDate, $endDate])
            ->whereIn('status', ['paid', 'completed'])
            ->selectRaw('DATE(created_at) as date, SUM(total_amount) as revenue')
            ->groupBy('date')
            ->orderBy('date', 'asc')
            ->get();

        $revenueLabels = $revenueByDay->pluck('date')->map(function($date) {
            return Carbon::parse($date)->format('M d');
        })->toArray();

        $revenueData = $revenueByDay->pluck('revenue')->map(function($revenue) {
            return (float) $revenue;
        })->toArray();

        // Revenue Growth (compared to previous period)
        $periodDays = $startDate->diffInDays($endDate);
        $previousStartDate = $startDate->copy()->subDays($periodDays);
        $previousEndDate = $startDate->copy()->subDay();

        $previousRevenue = Order::whereBetween('created_at', [$previousStartDate, $previousEndDate])
            ->whereIn('status', ['paid', 'completed'])
            ->sum('total_amount');

        $revenueGrowth = $previousRevenue > 0
            ? (($totalRevenue - $previousRevenue) / $previousRevenue) * 100
            : 0;

        return [
            'total' => (float) $totalRevenue,
            'average_order_value' => (float) $averageOrderValue,
            'growth_percentage' => round($revenueGrowth, 2),
            'labels' => $revenueLabels,
            'data' => $revenueData,
        ];
    }

    /**
     * Get order analytics.
     *
     * @param  Carbon  $startDate
     * @param  Carbon  $endDate
     * @return array
     */
    protected function getOrderAnalytics($startDate, $endDate)
    {
        // Total Orders
        $totalOrders = Order::whereBetween('created_at', [$startDate, $endDate])->count();

        // Orders by Status
        $ordersByStatus = Order::whereBetween('created_at', [$startDate, $endDate])
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->get();

        $statusLabels = $ordersByStatus->pluck('status')->map(function($status) {
            return ucfirst($status);
        })->toArray();

        $statusData = $ordersByStatus->pluck('count')->toArray();

        // Completion Rate
        $completedOrders = Order::whereBetween('created_at', [$startDate, $endDate])
            ->where('status', 'completed')
            ->count();

        $completionRate = $totalOrders > 0 ? ($completedOrders / $totalOrders) * 100 : 0;

        // Average Processing Time (in hours)
        $avgProcessingTime = Order::whereBetween('created_at', [$startDate, $endDate])
            ->whereNotNull('completed_at')
            ->selectRaw('AVG(TIMESTAMPDIFF(HOUR, created_at, completed_at)) as avg_hours')
            ->value('avg_hours');

        return [
            'total' => $totalOrders,
            'completed' => $completedOrders,
            'completion_rate' => round($completionRate, 2),
            'avg_processing_time_hours' => round($avgProcessingTime ?? 0, 2),
            'status_labels' => $statusLabels,
            'status_data' => $statusData,
        ];
    }

    /**
     * Get branch performance analytics.
     *
     * @param  Carbon  $startDate
     * @param  Carbon  $endDate
     * @return array
     */
    protected function getBranchPerformance($startDate, $endDate)
    {
        $branches = Branch::withCount(['orders' => function($query) use ($startDate, $endDate) {
            $query->whereBetween('created_at', [$startDate, $endDate]);
        }])
        ->with(['orders' => function($query) use ($startDate, $endDate) {
            $query->whereBetween('created_at', [$startDate, $endDate])
                ->whereIn('status', ['paid', 'completed']);
        }])
        ->get();

        $branchData = [];

        foreach ($branches as $branch) {
            $revenue = $branch->orders->sum('total_amount');

            $branchData[] = [
                'name' => $branch->name,
                'code' => $branch->code,
                'orders' => $branch->orders_count,
                'revenue' => (float) $revenue,
            ];
        }

        // Sort by revenue (descending)
        usort($branchData, function($a, $b) {
            return $b['revenue'] <=> $a['revenue'];
        });

        $branchLabels = array_column($branchData, 'code');
        $branchOrderData = array_column($branchData, 'orders');
        $branchRevenueData = array_column($branchData, 'revenue');

        return [
            'branches' => $branchData,
            'labels' => $branchLabels,
            'order_data' => $branchOrderData,
            'revenue_data' => $branchRevenueData,
        ];
    }

    /**
     * Get service popularity analytics.
     *
     * @param  Carbon  $startDate
     * @param  Carbon  $endDate
     * @return array
     */
    protected function getServicePopularity($startDate, $endDate)
    {
        $services = Service::withCount(['orders' => function($query) use ($startDate, $endDate) {
            $query->whereBetween('created_at', [$startDate, $endDate]);
        }])
        ->with(['orders' => function($query) use ($startDate, $endDate) {
            $query->whereBetween('created_at', [$startDate, $endDate])
                ->whereIn('status', ['paid', 'completed']);
        }])
        ->get();

        $serviceData = [];

        foreach ($services as $service) {
            $revenue = $service->orders->sum('total_amount');

            $serviceData[] = [
                'name' => $service->name,
                'orders' => $service->orders_count,
                'revenue' => (float) $revenue,
            ];
        }

        // Sort by orders (descending)
        usort($serviceData, function($a, $b) {
            return $b['orders'] <=> $a['orders'];
        });

        $serviceLabels = array_column($serviceData, 'name');
        $serviceOrderData = array_column($serviceData, 'orders');
        $serviceRevenueData = array_column($serviceData, 'revenue');

        return [
            'services' => $serviceData,
            'labels' => $serviceLabels,
            'order_data' => $serviceOrderData,
            'revenue_data' => $serviceRevenueData,
        ];
    }

    /**
     * Get customer analytics.
     *
     * @param  Carbon  $startDate
     * @param  Carbon  $endDate
     * @return array
     */
    protected function getCustomerAnalytics($startDate, $endDate)
    {
        // Total Customers
        $totalCustomers = Customer::count();

        // New Customers in Period
        $newCustomers = Customer::whereBetween('created_at', [$startDate, $endDate])->count();

        // Customer Growth by Day
        $customerGrowth = Customer::whereBetween('created_at', [$startDate, $endDate])
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date', 'asc')
            ->get();

        $growthLabels = $customerGrowth->pluck('date')->map(function($date) {
            return Carbon::parse($date)->format('M d');
        })->toArray();

        $growthData = $customerGrowth->pluck('count')->toArray();

        // Average Orders Per Customer
        $avgOrdersPerCustomer = Order::whereBetween('created_at', [$startDate, $endDate])
            ->selectRaw('customer_id, COUNT(*) as order_count')
            ->groupBy('customer_id')
            ->get()
            ->avg('order_count');

        // Top Customers (by revenue)
        $topCustomers = Customer::withSum(['orders' => function($query) use ($startDate, $endDate) {
            $query->whereBetween('created_at', [$startDate, $endDate])
                ->whereIn('status', ['paid', 'completed']);
        }], 'total_amount')
        ->orderBy('orders_sum_total_amount', 'desc')
        ->take(10)
        ->get();

        return [
            'total' => $totalCustomers,
            'new' => $newCustomers,
            'avg_orders_per_customer' => round($avgOrdersPerCustomer ?? 0, 2),
            'growth_labels' => $growthLabels,
            'growth_data' => $growthData,
            'top_customers' => $topCustomers,
        ];
    }

    /**
     * Get promotion effectiveness analytics.
     *
     * @param  Carbon  $startDate
     * @param  Carbon  $endDate
     * @return array
     */
    protected function getPromotionEffectiveness($startDate, $endDate)
    {
        $promotions = Promotion::withCount(['orders' => function($query) use ($startDate, $endDate) {
            $query->whereBetween('created_at', [$startDate, $endDate]);
        }])
        ->with(['orders' => function($query) use ($startDate, $endDate) {
            $query->whereBetween('created_at', [$startDate, $endDate])
                ->whereIn('status', ['paid', 'completed']);
        }])
        ->where('start_date', '<=', $endDate)
        ->where('end_date', '>=', $startDate)
        ->get();

        $promotionData = [];

        foreach ($promotions as $promotion) {
            $revenue = $promotion->orders->sum('total_amount');
            $discount = $promotion->orders->sum('discount_amount');

            $promotionData[] = [
                'name' => $promotion->name,
                'type' => $promotion->type,
                'usage_count' => $promotion->orders_count,
                'revenue' => (float) $revenue,
                'total_discount' => (float) $discount,
                'is_active' => $promotion->is_active,
            ];
        }

        // Sort by usage (descending)
        usort($promotionData, function($a, $b) {
            return $b['usage_count'] <=> $a['usage_count'];
        });

        $promotionLabels = array_column($promotionData, 'name');
        $promotionUsageData = array_column($promotionData, 'usage_count');

        return [
            'promotions' => $promotionData,
            'labels' => $promotionLabels,
            'usage_data' => $promotionUsageData,
        ];
    }
}
