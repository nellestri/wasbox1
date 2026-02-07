<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Service;

class ServiceController extends Controller
{
    // List all services
    public function index()
    {
        $services = Service::all();
        return view('admin.services.index', compact('services'));
    }

    // Show form to create a new service
    public function create()
    {
        return view('admin.services.create');
    }

    // Store a new service
   public function store(Request $request)
{
    $request->validate([
        'name' => 'required|string|max:255',
        'description' => 'nullable|string',
        'price_per_kg' => 'nullable|numeric',
        'per_load_price' => 'required|numeric',
        'min_weight' => 'required|numeric',
        'max_weight' => 'required|numeric',
        'service_type' => 'required|string',
        'icon_path' => 'nullable|string',
        'is_active' => 'boolean',
    ]);

    Service::create([
        'name' => $request->name,
        'description' => $request->description,
        'price_per_kg' => $request->price_per_kg,
        'per_load_price' => $request->per_load_price,
        'min_weight' => $request->min_weight,
        'max_weight' => $request->max_weight,
        'service_type' => $request->service_type,
        'icon_path' => $request->icon_path,
        'is_active' => $request->is_active ?? true,
    ]);

    return redirect()->route('services.index')->with('success', 'Service created successfully!');
}

    // Show a single service
    public function show(Service $service)
    {
        return view('admin.services.show', compact('service'));
    }

    // Show form to edit a service
    public function edit(Service $service)
    {
        return view('admin.services.edit', compact('service'));
    }

    // Update a service
    public function update(Request $request, Service $service)
    {
        $service->update($request->all());
        return redirect()->route('admin.services.index')->with('success', 'Service updated!');
    }

    // Delete a service
    public function destroy(Service $service)
    {
        $service->delete();
        return redirect()->route('admin.services.index')->with('success', 'Service deleted!');
    }

    
}
