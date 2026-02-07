<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Order;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class BranchController extends Controller
{
    /**
     * Display a listing of branches with statistics
     */
    public function index()
    {
        // Get all branches with stats for the current month
        $branches = Branch::withCount(['orders as orders_mtd' => function($query) {
                $query->whereMonth('created_at', Carbon::now()->month);
            }])
            ->get()
            ->map(function($branch) {
                // Calculate MTD revenue
                $branch->revenue_mtd = Order::where('branch_id', $branch->id)
                    ->whereMonth('created_at', Carbon::now()->month)
                    ->sum('total_amount');

                // Count active staff
                $branch->active_staff = User::where('branch_id', $branch->id)
                    ->where('role', 'staff')
                    ->where('is_active', true)
                    ->count();

                return $branch;
            });

        // Calculate network-wide statistics
        $total_orders = Order::count();
        $total_revenue = Order::sum('total_amount');

        return view('admin.branches.index', compact('branches', 'total_orders', 'total_revenue'));
    }

    /**
     * Show the form for creating a new branch
     */
    public function create()
    {
        $defaultProvince = 'Negros Oriental';
        return view('admin.branches.create', compact('defaultProvince'));
    }

    /**
     * Store a newly created branch in storage
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:10|unique:branches,code',
            'address' => 'required|string',
            'city' => 'required|string|max:255',
            'province' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'email' => 'nullable|email|max:255',
            'manager' => 'nullable|string|max:255',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'operating_hours' => 'nullable|json',
            'is_active' => 'nullable|boolean',
        ]);

        // Convert operating_hours JSON string to array
        if ($request->filled('operating_hours')) {
            try {
                $validated['operating_hours'] = json_decode($request->operating_hours, true);
            } catch (\Exception $e) {
                return back()->withErrors(['operating_hours' => 'Invalid JSON format for operating hours.'])->withInput();
            }
        }

        // Set is_active (default to true if not provided)
        $validated['is_active'] = $request->has('is_active') ? true : false;

        Branch::create($validated);

        return redirect()->route('admin.branches.index')
            ->with('success', 'Branch created successfully!');
    }

    /**
     * Display the specified branch with analytics
     */
    public function show(Branch $branch)
    {
        // Load relationships for detailed analytics
        $branch->load(['orders', 'staff']);

        // Calculate branch statistics
        $stats = [
            'total_orders' => $branch->orders()->count(),
            'completed_orders' => $branch->orders()->where('status', 'completed')->count(),
            'total_revenue' => $branch->orders()->sum('total_amount'),
            'avg_order_value' => $branch->orders()->avg('total_amount') ?? 0,
            'staff_count' => $branch->staff()->count(),
            'active_staff' => $branch->staff()->where('is_active', true)->count(),
            'orders_mtd' => $branch->orders()->whereMonth('created_at', Carbon::now()->month)->count(),
            'revenue_mtd' => $branch->orders()->whereMonth('created_at', Carbon::now()->month)->sum('total_amount'),
        ];

        // Recent orders
        $recent_orders = $branch->orders()
            ->with(['customer', 'service'])
            ->latest()
            ->take(10)
            ->get();

        return view('admin.branches.show', compact('branch', 'stats', 'recent_orders'));
    }

    /**
     * Show the form for editing the specified branch
     */
    public function edit(Branch $branch)
    {
        // Convert operating_hours to pretty JSON for textarea
        if ($branch->operating_hours && is_array($branch->operating_hours)) {
            $branch->operating_hours_json = json_encode($branch->operating_hours, JSON_PRETTY_PRINT);
        } else {
            $branch->operating_hours_json = '';
        }

        // Add MTD stats for display
        $branch->orders_mtd = $branch->orders()->whereMonth('created_at', Carbon::now()->month)->count();
        $branch->revenue_mtd = $branch->orders()->whereMonth('created_at', Carbon::now()->month)->sum('total_amount');
        $branch->active_staff = User::where('branch_id', $branch->id)
            ->where('role', 'staff')
            ->where('is_active', true)
            ->count();

        return view('admin.branches.edit', compact('branch'));
    }

    /**
     * Update the specified branch in storage
     */
    public function update(Request $request, Branch $branch)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:10|unique:branches,code,' . $branch->id,
            'address' => 'required|string',
            'city' => 'required|string|max:255',
            'province' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'email' => 'nullable|email|max:255',
            'manager' => 'nullable|string|max:255',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'operating_hours' => 'nullable|json',
            'is_active' => 'nullable|boolean',
        ]);

        // Convert operating_hours JSON string to array
        if ($request->filled('operating_hours')) {
            try {
                $validated['operating_hours'] = json_decode($request->operating_hours, true);
            } catch (\Exception $e) {
                return back()->withErrors(['operating_hours' => 'Invalid JSON format for operating hours.'])->withInput();
            }
        } else {
            $validated['operating_hours'] = null;
        }

        // Update is_active status
        $validated['is_active'] = $request->has('is_active') ? true : false;

        $branch->update($validated);

        return redirect()->route('admin.branches.index')
            ->with('success', 'Branch updated successfully!');
    }

    /**
     * Remove the specified branch from storage
     */
    public function destroy(Branch $branch)
    {
        // Check if branch has any orders
        if ($branch->orders()->count() > 0) {
            return redirect()->route('admin.branches.index')
                ->with('error', 'Cannot delete branch with existing orders. Please deactivate instead.');
        }

        // Check if branch has any staff
        if ($branch->staff()->count() > 0) {
            return redirect()->route('admin.branches.index')
                ->with('error', 'Cannot delete branch with assigned staff. Please reassign or deactivate staff first.');
        }

        $branch->delete();

        return redirect()->route('admin.branches.index')
            ->with('success', 'Branch deleted successfully!');
    }

    /**
     * Toggle branch active/inactive status
     */
    public function toggleStatus(Branch $branch)
    {
        $branch->update([
            'is_active' => !$branch->is_active
        ]);

        $status = $branch->is_active ? 'activated' : 'deactivated';

        return redirect()->route('admin.branches.index')
            ->with('success', "Branch {$status} successfully!");
    }

    /**
     * Deactivate a branch
     */
    public function deactivate(Branch $branch)
    {
        $branch->update(['is_active' => false]);

        return redirect()->route('admin.branches.index')
            ->with('success', 'Branch deactivated successfully.');
    }

    /**
     * Activate a branch
     */
    public function activate(Branch $branch)
    {
        $branch->update(['is_active' => true]);

        return redirect()->route('admin.branches.index')
            ->with('success', 'Branch activated successfully.');
    }

    /**
     * Get branch staff
     */
    public function staff(Branch $branch)
    {
        $staff = User::where('branch_id', $branch->id)
            ->where('role', 'staff')
            ->with(['orders' => function($query) {
                $query->whereMonth('created_at', Carbon::now()->month);
            }])
            ->get()
            ->map(function($user) {
                $user->orders_mtd = $user->orders->count();
                $user->revenue_mtd = $user->orders->sum('total_amount');
                return $user;
            });

        return view('admin.branches.staff', compact('branch', 'staff'));
    }

    /**
     * Get branch analytics
     */
    public function analytics(Branch $branch)
    {
        // Get last 6 months of data
        $months = [];
        $ordersData = [];
        $revenueData = [];

        for ($i = 5; $i >= 0; $i--) {
            $month = Carbon::now()->subMonths($i);
            $monthName = $month->format('M Y');

            $ordersCount = $branch->orders()
                ->whereMonth('created_at', $month->month)
                ->whereYear('created_at', $month->year)
                ->count();

            $revenue = $branch->orders()
                ->whereMonth('created_at', $month->month)
                ->whereYear('created_at', $month->year)
                ->sum('total_amount');

            $months[] = $monthName;
            $ordersData[] = $ordersCount;
            $revenueData[] = $revenue;
        }

        // Get daily data for current month
        $days = [];
        $dailyOrders = [];
        $dailyRevenue = [];

        for ($i = 6; $i >= 0; $i--) {
            $day = Carbon::now()->subDays($i);
            $dayName = $day->format('D');

            $ordersCount = $branch->orders()
                ->whereDate('created_at', $day->toDateString())
                ->count();

            $revenue = $branch->orders()
                ->whereDate('created_at', $day->toDateString())
                ->sum('total_amount');

            $days[] = $dayName;
            $dailyOrders[] = $ordersCount;
            $dailyRevenue[] = $revenue;
        }

        // Top services
        $topServices = $branch->orders()
            ->with('service')
            ->selectRaw('service_id, count(*) as order_count, sum(total_amount) as revenue')
            ->groupBy('service_id')
            ->orderByDesc('order_count')
            ->take(5)
            ->get();

        return view('admin.branches.analytics', compact(
            'branch',
            'months',
            'ordersData',
            'revenueData',
            'days',
            'dailyOrders',
            'dailyRevenue',
            'topServices'
        ));
    }
}
