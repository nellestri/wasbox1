<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Customer;
use App\Models\Branch;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ReportController extends Controller
{
    /**
     * Display reports dashboard
     */
    public function index()
    {
        $stats = [
            'total_revenue' => Order::where('payment_status', 'paid')->sum('total_amount'),
            'total_orders' => Order::count(),
            'total_customers' => Customer::count(),
            'active_branches' => Branch::where('is_active', true)->count(),
        ];

        // Revenue by month (last 12 months)
        $monthlyRevenue = Order::where('payment_status', 'paid')
            ->where('created_at', '>=', now()->subMonths(12))
            ->select(
                DB::raw('DATE_FORMAT(created_at, "%Y-%m") as month'),
                DB::raw('SUM(total_amount) as revenue'),
                DB::raw('COUNT(*) as orders')
            )
            ->groupBy('month')
            ->orderBy('month', 'desc')
            ->get();

        // Top customers
        $topCustomers = Customer::withCount('orders')
            ->with(['orders' => function($query) {
                $query->where('payment_status', 'paid');
            }])
            ->having('orders_count', '>', 0)
            ->orderBy('orders_count', 'desc')
            ->limit(10)
            ->get();

        // Branch performance
        $branchStats = Branch::withCount('orders')
            ->with(['orders' => function($query) {
                $query->where('payment_status', 'paid');
            }])
            ->get()
            ->map(function($branch) {
                return [
                    'name' => $branch->name,
                    'orders_count' => $branch->orders_count,
                    'revenue' => $branch->orders()->where('payment_status', 'paid')->sum('total_amount'),
                ];
            });

        return view('admin.reports.index', compact(
            'stats',
            'monthlyRevenue',
            'topCustomers',
            'branchStats'
        ));
    }

    /**
     * Revenue report
     */
    public function revenue(Request $request)
    {
        $startDate = $request->get('start_date', now()->startOfMonth());
        $endDate = $request->get('end_date', now()->endOfMonth());

        $data = Order::where('payment_status', 'paid')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('SUM(total_amount) as revenue'),
                DB::raw('COUNT(*) as orders')
            )
            ->groupBy('date')
            ->orderBy('date', 'asc')
            ->get();

        return view('admin.reports.revenue', compact('data', 'startDate', 'endDate'));
    }

    /**
     * Orders report
     */
    public function orders(Request $request)
    {
        $startDate = $request->get('start_date', now()->startOfMonth());
        $endDate = $request->get('end_date', now()->endOfMonth());

        $orders = Order::whereBetween('created_at', [$startDate, $endDate])
            ->with(['customer', 'branch', 'assignedStaff'])
            ->orderBy('created_at', 'desc')
            ->paginate(50);

        $summary = [
            'total_orders' => Order::whereBetween('created_at', [$startDate, $endDate])->count(),
            'completed_orders' => Order::whereBetween('created_at', [$startDate, $endDate])
                ->where('status', 'completed')->count(),
            'pending_orders' => Order::whereBetween('created_at', [$startDate, $endDate])
                ->whereIn('status', ['pending', 'processing'])->count(),
            'total_revenue' => Order::whereBetween('created_at', [$startDate, $endDate])
                ->where('payment_status', 'paid')->sum('total_amount'),
        ];

        return view('admin.reports.orders', compact('orders', 'summary', 'startDate', 'endDate'));
    }

    /**
     * Customers report
     */
    public function customers(Request $request)
    {
        $customers = Customer::withCount(['orders' => function($query) use ($request) {
            if ($request->has('start_date') && $request->has('end_date')) {
                $query->whereBetween('created_at', [$request->start_date, $request->end_date]);
            }
        }])
        ->with(['orders' => function($query) use ($request) {
            $query->where('payment_status', 'paid');
            if ($request->has('start_date') && $request->has('end_date')) {
                $query->whereBetween('created_at', [$request->start_date, $request->end_date]);
            }
        }])
        ->paginate(50);

        return view('admin.reports.customers', compact('customers'));
    }

    /**
     * Branches report
     */
    public function branches(Request $request)
    {
        $startDate = $request->get('start_date', now()->startOfMonth());
        $endDate = $request->get('end_date', now()->endOfMonth());

        $branches = Branch::with(['orders' => function($query) use ($startDate, $endDate) {
            $query->whereBetween('created_at', [$startDate, $endDate]);
        }])
        ->withCount(['orders' => function($query) use ($startDate, $endDate) {
            $query->whereBetween('created_at', [$startDate, $endDate]);
        }])
        ->get()
        ->map(function($branch) {
            return [
                'id' => $branch->id,
                'name' => $branch->name,
                'code' => $branch->code,
                'orders_count' => $branch->orders_count,
                'revenue' => $branch->orders()->where('payment_status', 'paid')->sum('total_amount'),
                'avg_order_value' => $branch->orders_count > 0
                    ? $branch->orders()->where('payment_status', 'paid')->sum('total_amount') / $branch->orders_count
                    : 0,
            ];
        });

        return view('admin.reports.branches', compact('branches', 'startDate', 'endDate'));
    }

    /**
     * Export report
     */
    public function export(Request $request)
    {
        $type = $request->get('type', 'orders');
        $format = $request->get('format', 'csv');

        // For now, just return CSV
        // You can add Excel export later using Laravel Excel package

        $filename = "{$type}_report_" . now()->format('Y-m-d') . ".{$format}";
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        switch ($type) {
            case 'orders':
                return $this->exportOrders($request, $headers);
            case 'revenue':
                return $this->exportRevenue($request, $headers);
            case 'customers':
                return $this->exportCustomers($request, $headers);
            default:
                return redirect()->back()->with('error', 'Invalid export type');
        }
    }

    /**
     * Export orders to CSV
     */
    private function exportOrders(Request $request, array $headers)
    {
        $startDate = $request->get('start_date', now()->startOfMonth());
        $endDate = $request->get('end_date', now()->endOfMonth());

        $orders = Order::whereBetween('created_at', [$startDate, $endDate])
            ->with(['customer', 'branch'])
            ->get();

        $callback = function() use ($orders) {
            $file = fopen('php://output', 'w');

            // Header row
            fputcsv($file, [
                'Order ID',
                'Tracking Number',
                'Customer',
                'Branch',
                'Status',
                'Payment Status',
                'Amount',
                'Date'
            ]);

            // Data rows
            foreach ($orders as $order) {
                fputcsv($file, [
                    $order->id,
                    $order->tracking_number,
                    $order->customer->name ?? 'N/A',
                    $order->branch->name ?? 'N/A',
                    $order->status,
                    $order->payment_status,
                    $order->total_amount,
                    $order->created_at->format('Y-m-d H:i:s'),
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Export revenue to CSV
     */
    private function exportRevenue(Request $request, array $headers)
    {
        $startDate = $request->get('start_date', now()->startOfMonth());
        $endDate = $request->get('end_date', now()->endOfMonth());

        $data = Order::where('payment_status', 'paid')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('SUM(total_amount) as revenue'),
                DB::raw('COUNT(*) as orders')
            )
            ->groupBy('date')
            ->orderBy('date', 'asc')
            ->get();

        $callback = function() use ($data) {
            $file = fopen('php://output', 'w');

            fputcsv($file, ['Date', 'Revenue', 'Orders']);

            foreach ($data as $row) {
                fputcsv($file, [
                    $row->date,
                    $row->revenue,
                    $row->orders,
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Export customers to CSV
     */
    private function exportCustomers(Request $request, array $headers)
    {
        $customers = Customer::withCount('orders')
            ->with(['orders' => function($query) {
                $query->where('payment_status', 'paid');
            }])
            ->get();

        $callback = function() use ($customers) {
            $file = fopen('php://output', 'w');

            fputcsv($file, [
                'Customer ID',
                'Name',
                'Email',
                'Phone',
                'Total Orders',
                'Total Spent',
                'Registration Date'
            ]);

            foreach ($customers as $customer) {
                fputcsv($file, [
                    $customer->id,
                    $customer->name,
                    $customer->email,
                    $customer->phone,
                    $customer->orders_count,
                    $customer->orders()->where('payment_status', 'paid')->sum('total_amount'),
                    $customer->created_at->format('Y-m-d'),
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
