<?php

namespace App\Http\Controllers\Admin;

use App\Models\AddOn;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Log;


class AddOnController extends Controller
{
    /**
     * Store a newly created add-on
     */
    public function store(Request $request)
    {
        // Debug: Check what data is being sent
        Log::info('AddOn Store Request:', $request->all());

        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'slug' => 'required|string|max:255|unique:add_ons,slug',
                'description' => 'nullable|string',
                'price' => 'required|numeric|min:0',
                'is_active' => 'sometimes|boolean'
            ]);

            $addon = AddOn::create([
                'name' => $validated['name'],
                'slug' => $validated['slug'],
                'description' => $validated['description'] ?? null,
                'price' => $validated['price'],
                'is_active' => $validated['is_active'] ?? true
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Add-on created successfully!',
                'addon' => $addon
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('AddOn Validation Error:', $e->errors());
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('AddOn Store Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error creating add-on: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update the specified add-on
     */
    public function update(Request $request, AddOn $addon)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'slug' => 'required|string|max:255|unique:add_ons,slug,' . $addon->id,
                'description' => 'nullable|string',
                'price' => 'required|numeric|min:0',
                'is_active' => 'sometimes|boolean'
            ]);

            $addon->update($validated);

            return response()->json([
                'success' => true,
                'message' => 'Add-on updated successfully!',
                'addon' => $addon
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('AddOn Update Validation Error:', $e->errors());
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('AddOn Update Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error updating add-on: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified add-on
     */
    public function destroy(AddOn $addon)
    {
        try {
            // Check if add-on is used in any orders
            if ($addon->orders()->count() > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete add-on that is used in existing orders.'
                ], 422);
            }

            $addon->delete();

            return response()->json([
                'success' => true,
                'message' => 'Add-on deleted successfully!'
            ]);

        } catch (\Exception $e) {
            Log::error('AddOn Delete Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error deleting add-on: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Toggle add-on status
     */
    public function toggleStatus(Request $request, AddOn $addon)
    {
        try {
            $validated = $request->validate([
                'is_active' => 'required|boolean'
            ]);

            $addon->update(['is_active' => $validated['is_active']]);

            return response()->json([
                'success' => true,
                'message' => 'Add-on status updated!',
                'is_active' => $addon->is_active
            ]);

        } catch (\Exception $e) {
            Log::error('AddOn Toggle Status Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error updating status'
            ], 500);
        }
    }
}
