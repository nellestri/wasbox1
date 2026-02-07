<?php

namespace App\Http\Controllers\Api;

use Log;
use App\Models\Customer;
use Illuminate\Http\Request;
use App\Models\PickupRequest;
use App\Http\Controllers\Controller;
use App\Models\Order;  // ✅ Added Order model import

class CustomerController extends Controller
{
    /**
     * Get customer profile
     *
     * GET /api/v1/profile
     */
    public function getProfile(Request $request)
    {
        $customer = $request->user();

        return response()->json([
            'success' => true,
            'data' => [
                'customer' => [
                    'id' => $customer->id,
                    'name' => $customer->name,
                    'email' => $customer->email,
                    'phone' => $customer->phone,
                    'address' => $customer->address,
                    'preferred_branch_id' => $customer->preferred_branch_id,
                    'created_at' => $customer->created_at,
                ],
            ]
        ]);
    }

    /**
     * Update customer profile
     *
     * PUT /api/v1/profile
     */
    public function updateProfile(Request $request)
    {
        $customer = $request->user();

        $request->validate([
            'name' => 'sometimes|string|max:255',
            'phone' => 'sometimes|string|max:20|unique:customers,phone,' . $customer->id,
            'address' => 'nullable|string|max:500',
            'preferred_branch_id' => 'nullable|exists:branches,id',
        ]);

        $customer->update($request->only(['name', 'phone', 'address', 'preferred_branch_id']));

        return response()->json([
            'success' => true,
            'message' => 'Profile updated successfully',
            'data' => [
                'customer' => [
                    'id' => $customer->id,
                    'name' => $customer->name,
                    'email' => $customer->email,
                    'phone' => $customer->phone,
                    'address' => $customer->address,
                    'preferred_branch_id' => $customer->preferred_branch_id,
                ],
            ]
        ]);
    }

    /**
     * Get active orders for authenticated customer
     *
     * GET /api/v1/customer/active-orders
     */
    public function getActiveOrders(Request $request)
    {
        $customer = $request->user();

        $orders = Order::where('customer_id', $customer->id)
            ->whereIn('status', ['received', 'processing', 'ready', 'paid'])
            ->with(['branch', 'service'])
            ->latest()
            ->get();

        $formattedOrders = $orders->map(function ($order) {
            return [
                'id' => $order->id,
                'tracking_number' => $order->tracking_number,
                'status' => $order->status,
                'service_name' => $order->service->name ?? 'Laundry Service',
                'branch_name' => $order->branch->name ?? 'Branch',
                'total_amount' => $order->total_amount,
                'estimated_completion' => $order->estimated_completion
                    ? $order->estimated_completion->format('M d, h:i A')
                    : 'Processing',
                'created_at' => $order->created_at->format('M d, Y'),
            ];
        });

        return response()->json([
            'success' => true,
            'data' => [
                'orders' => $formattedOrders,
            ]
        ]);
    }

    /**
     * Get latest pickup request for home screen
     *
     * GET /api/v1/customer/latest-pickup
     */
    public function getLatestPickup(Request $request)
    {
        $customer = $request->user();

        $pickup = PickupRequest::where('customer_id', $customer->id)
            ->whereIn('status', ['pending', 'confirmed', 'in_transit'])
            ->with('branch')
            ->latest()
            ->first();

        if (!$pickup) {
            return response()->json([
                'success' => true,
                'data' => ['pickup' => null]
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'pickup' => [
                    'id' => $pickup->id,
                    'status' => $pickup->status,
                    'pickup_address' => $pickup->pickup_address,
                    'scheduled_date' => $pickup->scheduled_date->format('M d, Y'),
                    'scheduled_time' => $pickup->scheduled_time,
                    'branch_name' => $pickup->branch->name ?? 'Branch',
                ],
            ]
        ]);
    }

    /**
     * Get customer statistics
     *
     * GET /api/v1/customer/stats
     */
    public function getStats(Request $request)
    {
        try {
            $customer = $request->user();

            // Get total orders
            $totalOrders = Order::where('customer_id', $customer->id)->count();

            // Get total spent (only from paid/completed orders)
            $totalSpent = Order::where('customer_id', $customer->id)
                ->whereIn('status', ['paid', 'completed'])
                ->sum('total_amount');

            // Calculate average rating (if you have reviews system)
            // For now, use a default rating
            $rating = 5.0;

            return response()->json([
                'success' => true,
                'data' => [
                    'stats' => [
                        'totalOrders' => (int) $totalOrders,
                        'totalSpent' => (float) ($totalSpent ?? 0),
                        'rating' => (float) $rating,
                    ],
                ]
            ]);
        } catch (\Exception $e) {
            \Log::error('Error in getStats: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch statistics',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get branches (public endpoint)
     *
     * GET /api/v1/branches
     */
    public function getBranches()
    {
        $branches = \App\Models\Branch::where('is_active', true)
            ->select('id', 'name', 'code', 'city', 'address', 'phone', 'email')
            ->orderBy('name')
            ->get();

        return response()->json($branches);
    }


    /**
 * Update FCM token for push notifications
 *
 * POST /api/v1/profile/fcm-token
 */
public function updateFcmToken(Request $request)
{
    $request->validate([
        'fcm_token' => 'required|string',
    ]);

    $customer = $request->user();
    $customer->update(['fcm_token' => $request->fcm_token]);

    return response()->json([
        'success' => true,
        'message' => 'FCM token updated successfully',
    ]);
}

/**
 * Update notification preferences
 *
 * PUT /api/v1/profile/notifications
 */
public function updateNotificationPreferences(Request $request)
{
    $request->validate([
        'notification_enabled' => 'required|boolean',
    ]);

    $customer = $request->user();
    $customer->update(['notification_enabled' => $request->notification_enabled]);

    return response()->json([
        'success' => true,
        'message' => 'Notification preferences updated',
        'notification_enabled' => $customer->notification_enabled,
    ]);
}
}
