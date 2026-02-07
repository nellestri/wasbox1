<?php

namespace App\Http\Controllers\Admin;

use Hash;
use App\Models\User;
use App\Models\Branch;
use App\Models\Customer;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class CustomerController extends Controller
{
    // List all customers
   public function index(Request $request)
{
    // Prepare filter variables
    $query = Customer::query()->with(['preferredBranch', 'orders']);

    // Handle Filters from your Blade form
    if ($request->filled('registration_type')) {
        $query->where('registration_type', $request->registration_type);
    }

    if ($request->filled('branch_id')) {
        $query->where('preferred_branch_id', $request->branch_id);
    }

    if ($request->filled('status')) {
        $query->where('is_active', $request->status === 'active');
    }

    if ($request->filled('search')) {
        $search = $request->search;
        $query->where(function($q) use ($search) {
            $q->where('name', 'like', "%{$search}%")
              ->orWhere('phone', 'like', "%{$search}%")
              ->orWhere('email', 'like', "%{$search}%");
        });
    }

    // Calculate dynamic stats for the top row
    $stats = [
        'total'           => Customer::count(),
        'walk_in'         => Customer::where('registration_type', 'walk_in')->count(),
        'self_registered' => Customer::where('registration_type', 'self_registered')->count(),
        'new_today'       => Customer::whereDate('created_at', today())->count(),
    ];

    // Get branches for the filter dropdown
    $branches = Branch::all();

    // Final paginated result
    $customers = $query->latest()->paginate(20)->withQueryString();

    return view('admin.customers.index', compact('customers', 'stats', 'branches'));
}
    // Show create form
    public function create()
    {
        $branches = Branch::all();
        $staff = User::where('role', 'staff')->get();
        return view('admin.customers.create', compact('branches', 'staff'));
    }

    // Store new customer
    public function store(Request $request)
{
    $request->validate([
        'name' => 'required|string|max:255',
        'phone' => 'required|string|unique:customers,phone',
        'email' => 'nullable|email|unique:customers,email',
        'preferred_branch_id' => 'required|exists:branches,id',
        'address' => 'nullable|string',
    ]);

    // Create the customer with hidden/default values
    \App\Models\Customer::create([
        'name' => $request->name,
        'phone' => $request->phone,
        'email' => $request->email,
        'address' => $request->address,
        'preferred_branch_id' => $request->preferred_branch_id,
        'registration_type' => 'walk_in', // CRITICAL: Your model uses this in scopes
        'password' => \Hash::make($request->phone), // Use phone as default password
        'registered_by' => auth()->id(), // Track which staff in Sibulan/Bais did the work
        'is_active' => true,
    ]);

    return redirect()->route('admin.customers.index')
                     ->with('success', 'Customer registered for ' . Branch::find($request->preferred_branch_id)->name);
}

    // Show customer details
    public function show($id)
    {
        $customer = Customer::with(['preferredBranch', 'registeredBy', 'orders', 'pickupRequests', 'ratings'])->findOrFail($id);
        return view('admin.customers.show', compact('customer'));
    }

    // Show edit form
    public function edit($id)
    {
        $customer = Customer::findOrFail($id);
        $branches = Branch::all();
        $staff = User::where('role', 'staff')->get();
        return view('admin.customers.edit', compact('customer', 'branches', 'staff'));
    }

    // Update customer
    public function update(Request $request, $id)
{
    $customer = Customer::findOrFail($id);

    $validated = $request->validate([
        'name' => 'required|string|max:255',
        'phone' => 'required|string|max:20|unique:customers,phone,' . $customer->id,
        'email' => 'nullable|email|unique:customers,email,' . $customer->id,
        'address' => 'nullable|string',
        'preferred_branch_id' => 'required|exists:branches,id',
        'is_active' => 'required|boolean', // Matches the select name in your Blade
    ]);

    // Handle Password only if provided (optional for Admin edits)
    if ($request->filled('password')) {
        $validated['password'] = Hash::make($request->password);
    }

    $customer->update($validated);

    return redirect()->route('admin.customers.show', $customer->id)
                     ->with('success', 'Customer profile updated successfully.');
}

    // Delete customer
    public function destroy($id)
    {
        $customer = Customer::findOrFail($id);
        $customer->delete();

        return redirect()->route('admin.customers.index')->with('success', 'Customer deleted successfully.');
    }
}
