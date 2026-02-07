<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Service;
use App\Models\Branch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ServiceController extends Controller
{
    /**
     * Display a listing of services
     */
    public function index(Request $request)
    {
        $query = Service::query();

        // Filter by status
        if ($request->filled('status')) {
            if ($request->status === 'active') {
                $query->where('is_active', true);
            } elseif ($request->status === 'inactive') {
                $query->where('is_active', false);
            }
        }

        // Search by name or description
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Sort
        $sortBy = $request->get('sort_by', 'name');
        $sortOrder = $request->get('sort_order', 'asc');
        $query->orderBy($sortBy, $sortOrder);

        $services = $query->withCount('orders')->paginate(12);

        // Statistics
        $stats = [
            'total' => Service::count(),
            'active' => Service::where('is_active', true)->count(),
            'inactive' => Service::where('is_active', false)->count(),
            'total_orders' => Service::withCount('orders')->get()->sum('orders_count'),
        ];

        return view('admin.services.index', compact('services', 'stats'));
    }

    /**
     * Show the form for creating a new service
     */
    public function create()
    {
        $branches = Branch::active()->get();
        return view('admin.services.create', compact('branches'));
    }

    /**
     * Store a newly created service in storage
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'price_per_kg' => 'required|numeric|min:0',
            'min_weight' => 'nullable|numeric|min:0',
            'max_weight' => 'nullable|numeric|min:0',
            'turnaround_time' => 'nullable|integer|min:0',
            'service_type' => 'nullable|string|max:100',
            'icon' => 'nullable|image|mimes:jpeg,jpg,png,svg|max:2048',
            'is_active' => 'nullable|boolean',
        ]);

        // Handle icon upload
        if ($request->hasFile('icon')) {
            $validated['icon_path'] = $request->file('icon')->store('services', 'public');
        }

        $validated['is_active'] = $request->has('is_active') ? true : false;

        Service::create($validated);

        return redirect()->route('admin.services.index')
            ->with('success', 'Service created successfully!');
    }

    /**
     * Display the specified service
     */
    public function show(Service $service)
    {
        // Load relationships
        $service->load(['orders' => function($query) {
            $query->latest()->take(10);
        }]);

        // Calculate statistics
        $stats = [
            'total_orders' => $service->orders()->count(),
            'completed_orders' => $service->orders()->where('status', 'completed')->count(),
            'total_revenue' => $service->orders()->where('status', 'completed')->sum('total_amount'),
            'avg_order_value' => $service->orders()->where('status', 'completed')->avg('total_amount') ?? 0,
            'total_weight' => $service->orders()->sum('weight') ?? 0,
        ];

        // Recent orders
        $recent_orders = $service->orders()
            ->with(['customer', 'branch'])
            ->latest()
            ->take(10)
            ->get();

        return view('admin.services.show', compact('service', 'stats', 'recent_orders'));
    }

    /**
     * Show the form for editing the specified service
     */
    public function edit(Service $service)
    {
        $branches = Branch::active()->get();
        return view('admin.services.edit', compact('service', 'branches'));
    }

    /**
     * Update the specified service in storage
     */
    public function update(Request $request, Service $service)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'price_per_kg' => 'required|numeric|min:0',
            'min_weight' => 'nullable|numeric|min:0',
            'max_weight' => 'nullable|numeric|min:0',
            'turnaround_time' => 'nullable|integer|min:0',
            'service_type' => 'nullable|string|max:100',
            'icon' => 'nullable|image|mimes:jpeg,jpg,png,svg|max:2048',
            'is_active' => 'nullable|boolean',
        ]);

        // Handle icon upload
        if ($request->hasFile('icon')) {
            // Delete old icon
            if ($service->icon_path) {
                Storage::disk('public')->delete($service->icon_path);
            }
            $validated['icon_path'] = $request->file('icon')->store('services', 'public');
        }

        $validated['is_active'] = $request->has('is_active') ? true : false;

        $service->update($validated);

        return redirect()->route('admin.services.index')
            ->with('success', 'Service updated successfully!');
    }

    /**
     * Remove the specified service from storage
     */
    public function destroy(Service $service)
    {
        // Check if service has any orders
        if ($service->orders()->count() > 0) {
            return redirect()->route('admin.services.index')
                ->with('error', 'Cannot delete service with existing orders. Please deactivate instead.');
        }

        // Delete icon if exists
        if ($service->icon_path) {
            Storage::disk('public')->delete($service->icon_path);
        }

        $service->delete();

        return redirect()->route('admin.services.index')
            ->with('success', 'Service deleted successfully!');
    }

    /**
     * Toggle service active/inactive status
     */
    public function toggleStatus(Service $service)
    {
        $service->update([
            'is_active' => !$service->is_active
        ]);

        $status = $service->is_active ? 'activated' : 'deactivated';

        return redirect()->route('admin.services.index')
            ->with('success', "Service {$status} successfully!");
    }
}
