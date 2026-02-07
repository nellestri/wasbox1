<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class OrderController extends Controller
{
    /**
     * Get all orders for authenticated customer
     *
     * GET /api/v1/orders
     */
    public function index(Request $request)
    {
        try {
            $customer = $request->user();

            $orders = Order::where('customer_id', $customer->id)
                ->with(['branch', 'service'])
                ->orderBy('created_at', 'desc')
                ->get();

            $formattedOrders = $orders->map(function ($order) {
                return [
                    'id' => $order->id,
                    'tracking_number' => $order->tracking_number,
                    'status' => $order->status,
                    'service_name' => $order->service->name ?? 'Laundry Service',
                    'branch_name' => $order->branch->name ?? 'Branch',
                    'weight' => (float) $order->weight,
                    'total_amount' => (float) $order->total_amount,
                    'estimated_completion' => $order->delivery_date
                        ? $order->delivery_date
                        : $order->created_at->addDays(2)->toIso8601String(),
                    'created_at' => $order->created_at->toIso8601String(),
                    'updated_at' => $order->updated_at->toIso8601String(),
                ];
            });

            return response()->json([
                'success' => true,
                'data' => [
                    'orders' => $formattedOrders,
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching orders: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch orders',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get specific order details
     *
     * GET /api/v1/orders/{id}
     */
    public function show(Request $request, $id)
    {
        try {
            $customer = $request->user();

            // Try to find by tracking number first, then by ID
            $order = Order::where('customer_id', $customer->id)
                ->where(function ($query) use ($id) {
                    $query->where('tracking_number', $id)
                          ->orWhere('id', $id);
                })
                ->with(['branch', 'service'])
                ->first();

            if (!$order) {
                return response()->json([
                    'success' => false,
                    'message' => 'Order not found',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'order' => [
                        'id' => $order->id,
                        'tracking_number' => $order->tracking_number,
                        'status' => $order->status,
                        'service_name' => $order->service->name ?? 'Laundry Service',
                        'branch_name' => $order->branch->name ?? 'Branch',
                        'branch_address' => $order->branch->address ?? null,
                        'branch_phone' => $order->branch->phone ?? null,
                        'weight' => (float) $order->weight,
                        'price_per_kg' => (float) $order->price_per_kg,
                        'subtotal' => (float) $order->subtotal,
                        'discount_amount' => (float) ($order->discount_amount ?? 0),
                        'total_amount' => (float) $order->total_amount,
                        'notes' => $order->notes,
                        'estimated_completion' => $order->delivery_date
                            ? $order->delivery_date
                            : $order->created_at->addDays(2)->toIso8601String(),
                        'created_at' => $order->created_at->toIso8601String(),
                        'updated_at' => $order->updated_at->toIso8601String(),
                    ],
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching order: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch order',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Create new order (self-service)
     *
     * POST /api/v1/orders
     */
    public function store(Request $request)
    {
        try {
            $customer = $request->user();

            $validated = $request->validate([
                'branch_id' => 'required|exists:branches,id',
                'service_id' => 'required|exists:services,id',
                'weight' => 'required|numeric|min:0.1|max:1000',
                'pickup_address' => 'nullable|string|max:500',
                'delivery_address' => 'nullable|string|max:500',
                'pickup_date' => 'nullable|date|after_or_equal:today',
                'delivery_date' => 'nullable|date|after_or_equal:today',
                'notes' => 'nullable|string|max:1000',
            ]);

            // Get service for pricing
            $service = \App\Models\Service::findOrFail($validated['service_id']);

            // Calculate pricing
            $pricePerKg = $service->price_per_kg;
            $weight = $validated['weight'];
            $subtotal = $pricePerKg * $weight;
            $discountAmount = 0; // TODO: Apply promotions if any
            $totalAmount = $subtotal - $discountAmount;

            // Generate unique tracking number
            $trackingNumber = $this->generateTrackingNumber();

            // Create order
            $order = Order::create([
                'customer_id' => $customer->id,
                'branch_id' => $validated['branch_id'],
                'service_id' => $validated['service_id'],
                'tracking_number' => $trackingNumber,
                'weight' => $weight,
                'price_per_kg' => $pricePerKg,
                'subtotal' => $subtotal,
                'discount_amount' => $discountAmount,
                'total_amount' => $totalAmount,
                'status' => 'received',
                'received_at' => now(),
                'pickup_address' => $validated['pickup_address'] ?? null,
                'delivery_address' => $validated['delivery_address'] ?? null,
                'pickup_date' => $validated['pickup_date'] ?? null,
                'delivery_date' => $validated['delivery_date'] ?? null,
                'notes' => $validated['notes'] ?? null,
            ]);

            // Load relationships
            $order->load(['service', 'branch']);

            // TODO: Send notification to customer
            // TODO: Send notification to branch staff

            return response()->json([
                'success' => true,
                'message' => 'Order created successfully',
                'data' => [
                    'order' => [
                        'id' => $order->id,
                        'tracking_number' => $order->tracking_number,
                        'status' => $order->status,
                        'service_name' => $order->service->name,
                        'branch_name' => $order->branch->name,
                        'total_amount' => (float) $order->total_amount,
                        'created_at' => $order->created_at->toIso8601String(),
                    ],
                ]
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error creating order: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to create order',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Cancel order
     *
     * PUT /api/v1/orders/{id}/cancel
     */
    public function cancel(Request $request, $id)
    {
        try {
            $customer = $request->user();

            $order = Order::where('customer_id', $customer->id)
                ->where('id', $id)
                ->first();

            if (!$order) {
                return response()->json([
                    'success' => false,
                    'message' => 'Order not found',
                ], 404);
            }

            // Only allow cancellation of certain statuses
            if (!in_array($order->status, ['received', 'processing', 'washing'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot cancel order in current status. Order is already being processed.',
                ], 400);
            }

            $order->update([
                'status' => 'cancelled',
            ]);

            // TODO: Send cancellation notification to customer
            // TODO: Send cancellation notification to branch

            return response()->json([
                'success' => true,
                'message' => 'Order cancelled successfully',
            ]);
        } catch (\Exception $e) {
            Log::error('Error cancelling order: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to cancel order',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Generate unique tracking number
     */
    private function generateTrackingNumber(): string
    {
        do {
            $tracking = 'WB-' . date('Ymd') . '-' . strtoupper(Str::random(4));
        } while (Order::where('tracking_number', $tracking)->exists());

        return $tracking;
    }
}
