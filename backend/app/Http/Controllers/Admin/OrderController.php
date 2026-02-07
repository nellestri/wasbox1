<?php

namespace App\Http\Controllers\Admin;

use App\Models\User;
use App\Models\Order;
use App\Models\AddOn;
use App\Models\Branch;
use App\Models\Service;
use App\Models\Customer;
use App\Models\Promotion;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Models\PickupRequest;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class OrderController extends Controller
{
    /**
     * Display a listing of orders
     */
    public function index(Request $request)
    {
        $query = Order::with(['customer', 'service', 'branch', 'staff']);

        // Filter by status
        if ($request->filled('status')) {
            $query->byStatus($request->status);
        }

        // Filter by branch
        if ($request->filled('branch_id')) {
            $query->byBranch($request->branch_id);
        }

        // Filter by service
        if ($request->filled('service_id')) {
            $query->where('service_id', $request->service_id);
        }

        // Filter by staff
        if ($request->filled('staff_id')) {
            $query->byStaff($request->staff_id);
        }

        // Filter by date range
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        // Search
        if ($request->filled('search')) {
            $query->search($request->search);
        }

        // Sort
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        $orders = $query->paginate(15);

        // Get filter options
        $services = Service::active()->get();
        $branches = Branch::active()->get();
        $staff = User::staff()->active()->get();

        // Statistics
        $stats = [
            'total' => Order::count(),
            'pending' => Order::where('status', 'received')->count(),
            'processing' => Order::where('status', 'processing')->count(),
            'ready' => Order::where('status', 'ready')->count(),
            'completed' => Order::where('status', 'completed')->count(),
            'total_revenue' => Order::where('status', 'completed')->sum('total_amount'),
            'today_orders' => Order::whereDate('created_at', today())->count(),
        ];

        return view('admin.orders.index', compact('orders', 'services', 'branches', 'staff', 'stats'));
    }

    /**
     * Show the form for creating a new order (with optional pickup request)
     */
    public function create(Request $request)
    {
        $pickup = null;
        if ($request->has('pickup_id')) {
            $pickup = PickupRequest::with(['customer', 'branch'])->find($request->pickup_id);
        }

        // Fetch promotions with correct types
        $promotions = Promotion::active()
            ->valid()
            ->where(function($query) use ($pickup) {
                $query->whereNull('branch_id')
                      ->orWhere('branch_id', $pickup?->branch_id);
            })
            ->orderBy('display_order')
            ->get();

        return view('admin.orders.create', [
            'pickup' => $pickup,
            'customers' => Customer::active()->get(),
            'branches' => Branch::active()->get(),
            'services' => Service::active()->get(),
            'addons' => AddOn::active()->get(),
            'staff' => User::staff()->active()->get(),
            'promotions' => $promotions,
        ]);
    }

    /**
     * Store a newly created order in storage
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'branch_id' => 'required|exists:branches,id',
            'service_id' => 'nullable|exists:services,id',
            'staff_id' => 'nullable|exists:users,id',
            'weight' => 'nullable|numeric|min:0.1',
            'number_of_loads' => 'nullable|integer|min:1',
            'notes' => 'nullable|string|max:1000',
            'pickup_request_id' => 'nullable|exists:pickup_requests,id',
            'pickup_fee' => 'nullable|numeric|min:0',
            'delivery_fee' => 'nullable|numeric|min:0',
            'promotion_id' => 'nullable|exists:promotions,id',
            'addons' => 'nullable|array',
            'addons.*' => 'exists:add_ons,id',
        ]);

        DB::beginTransaction();

        try {
            $pickupRequest = null;
            if ($validated['pickup_request_id'] ?? null) {
                $pickupRequest = PickupRequest::find($validated['pickup_request_id']);
            }

            // Get service
            $service = null;
            $serviceSubtotal = 0;
            $pricePerKg = 0;
            $loads = 1;
            $weight = 0;

            if ($validated['service_id'] ?? null) {
                $service = Service::findOrFail($validated['service_id']);

                if ($service->pricing_type === 'per_kg') {
                    $weight = $validated['weight'] ?? 0;
                    $serviceSubtotal = $weight * $service->price_per_kg;
                    $pricePerKg = $service->price_per_kg;
                } else {
                    $loads = $validated['number_of_loads'] ?? 1;
                    $serviceSubtotal = $loads * $service->price_per_load;
                }
            }

            // Handle promotion
            $promotion = null;
            $promotionDiscount = 0;
            $promotionOverrideTotal = null;
            $promotionPricePerLoad = null;
            $finalServiceSubtotal = $serviceSubtotal;

            if ($validated['promotion_id'] ?? null) {
                $promotion = Promotion::find($validated['promotion_id']);

                if ($promotion && $promotion->isValid) {
                    // Check if promotion is applicable
                    $orderData = [
                        'subtotal' => $serviceSubtotal,
                        'service_id' => $service?->id,
                        'branch_id' => $validated['branch_id'],
                        'weight' => $weight,
                        'loads' => $loads,
                    ];

                    if ($promotion->isApplicableTo($orderData)) {
                        // Calculate promotion effect
                        $promotionEffect = $promotion->calculateEffect($serviceSubtotal, $loads);

                        $promotionDiscount = $promotionEffect['discount_amount'];
                        $promotionOverrideTotal = $promotionEffect['override_total'];
                        $promotionPricePerLoad = $promotionEffect['price_per_load'];
                        $finalServiceSubtotal = $promotionEffect['final_subtotal'];
                    }
                }
            }

            // Calculate add-ons total
            $addonsTotal = 0;
            $addonData = [];

            if ($request->has('addons')) {
                foreach ($request->input('addons') as $addonId) {
                    $addon = AddOn::findOrFail($addonId);
                    $addonsTotal += $addon->price;

                    $addonData[$addonId] = [
                        'price_at_purchase' => $addon->price,
                        'quantity' => 1
                    ];
                }
            }

            // Calculate fees
            $pickupFee = $request->filled('pickup_fee')
                ? $validated['pickup_fee']
                : ($pickupRequest->pickup_fee ?? 0);

            $deliveryFee = $request->filled('delivery_fee')
                ? $validated['delivery_fee']
                : ($pickupRequest->delivery_fee ?? 0);

            // Calculate total amount
            $totalAmount = $finalServiceSubtotal + $addonsTotal + $pickupFee + $deliveryFee;
            $trackingNumber = $this->generateTrackingNumber();

            // Create the order
            $order = Order::create([
                'tracking_number' => $trackingNumber,
                'customer_id' => $validated['customer_id'],
                'branch_id' => $validated['branch_id'],
                'service_id' => $service?->id,
                'staff_id' => $validated['staff_id'] ?? null,
                'created_by' => Auth::id(),
                'weight' => $weight,
                'number_of_loads' => $loads,
                'price_per_kg' => $pricePerKg,
                'subtotal' => $serviceSubtotal,
                'addons_total' => $addonsTotal,
                'discount_amount' => $promotionDiscount,
                'promotion_id' => $promotion?->id,
                'promotion_override_total' => $promotionOverrideTotal,
                'promotion_price_per_load' => $promotionPricePerLoad,
                'pickup_fee' => $pickupFee,
                'delivery_fee' => $deliveryFee,
                'total_amount' => $totalAmount,
                'status' => 'received',
                'received_at' => now(),
                'notes' => $validated['notes'] ?? null,
            ]);

            // Attach add-ons if any
            if (!empty($addonData)) {
                $order->addons()->attach($addonData);
            }

            // Create status history
            $order->statusHistories()->create([
                'status' => 'received',
                'changed_by' => Auth::id(),
                'notes' => 'Order created' . ($pickupRequest ? ' from pickup #' . $pickupRequest->id : '') .
                           ($addonsTotal > 0 ? ' with ' . count($addonData) . ' add-on(s)' : ''),
            ]);

            // Record promotion usage if applied
            if ($promotion) {
                \App\Models\PromotionUsage::create([
                    'promotion_id' => $promotion->id,
                    'order_id' => $order->id,
                    'user_id' => Auth::id(),
                    'discount_amount' => $promotionDiscount,
                    'original_amount' => $serviceSubtotal,
                    'final_amount' => $finalServiceSubtotal,
                    'code_used' => $promotion->promo_code,
                    'applied_at' => now(),
                ]);

                $promotion->incrementUsage();
            }

            // Update pickup request if applicable
            if ($pickupRequest) {
                $pickupRequest->update([
                    'order_id' => $order->id,
                    'pickup_fee' => $pickupFee,
                    'delivery_fee' => $deliveryFee,
                    'status' => 'picked_up',
                    'picked_up_at' => now()
                ]);
            }

            DB::commit();

            return redirect()->route('admin.orders.show', $order)
                ->with('success', 'Order created successfully! Tracking: ' . $trackingNumber);

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Error creating order: ' . $e->getMessage())->withInput();
        }
    }

    /**
     * Display the specified order
     */
    public function show(Order $order)
    {
        $order->load([
            'customer',
            'service',
            'branch',
            'staff',
            'createdBy',
            'statusHistories.changedBy',
            'payment',
            'addons'
        ]);

        return view('admin.orders.show', compact('order'));
    }

    /**
     * Show the form for editing the specified order
     */
    public function edit(Order $order)
    {
        $customers = Customer::all();
        $services = Service::active()->get();
        $branches = Branch::active()->get();
        $staff = User::staff()->active()->get();

        return view('admin.orders.edit', compact('order', 'customers', 'services', 'branches', 'staff'));
    }

    /**
     * Update the specified order in storage
     */
    public function update(Request $request, Order $order)
    {
        $validated = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'branch_id' => 'required|exists:branches,id',
            'service_id' => 'required_without:number_of_loads|exists:services,id',
            'number_of_loads' => 'nullable|integer|min:1',
            'staff_id' => 'nullable|exists:users,id',
            'weight' => 'nullable|numeric|min:0.1',
            'notes' => 'nullable|string|max:1000',
        ]);

        // Recalculate if service, weight, or number_of_loads changed
        $pricePerKg = $order->price_per_kg;
        $subtotal = $order->subtotal;
        $discountAmount = $order->discount_amount ?? 0;

        if ($validated['number_of_loads'] ?? null) {
            // Per-load promo update
            $promo = $order->promotion;
            if ($promo && $promo->application_type === 'per_load_override') {
                $fixedPrice = $promo->display_price;
                $subtotal = $fixedPrice * $validated['number_of_loads'];
                $pricePerKg = 0;
                $discountAmount = 0;
            }
        } elseif ($order->service_id != $validated['service_id'] || $order->weight != ($validated['weight'] ?? $order->weight)) {
            // Regular service update
            $service = Service::findOrFail($validated['service_id']);
            $pricePerKg = $service->price_per_kg;
            $subtotal = ($validated['weight'] ?? 0) * $pricePerKg;
        }

        $pickupFee = $order->pickup_fee ?? 0;
        $deliveryFee = $order->delivery_fee ?? 0;
        $totalAmount = $subtotal - $discountAmount + $pickupFee + $deliveryFee + $order->addons_total;

        $validated['price_per_kg'] = $pricePerKg;
        $validated['subtotal'] = $subtotal;
        $validated['total_amount'] = $totalAmount;

        $order->update($validated);

        return redirect()->route('admin.orders.show', $order)
            ->with('success', 'Order updated successfully!');
    }

    /**
     * Update order status
     */
    public function updateStatus(Request $request, Order $order)
    {
        $validated = $request->validate([
            'status' => 'required|in:received,processing,ready,paid,completed,cancelled',
            'notes' => 'nullable|string|max:500',
        ]);

        $order->updateStatus(
            $validated['status'],
            Auth::user(),
            $validated['notes'] ?? null
        );

        return redirect()->route('admin.orders.show', $order)
            ->with('success', 'Order status updated to: ' . ucfirst($validated['status']));
    }

    /**
     * Assign staff to order
     */
    public function assignStaff(Request $request, Order $order)
    {
        $validated = $request->validate([
            'staff_id' => 'required|exists:users,id',
        ]);

        $order->assignToStaff($validated['staff_id']);
        $staff = User::find($validated['staff_id']);

        return redirect()->route('admin.orders.show', $order)
            ->with('success', 'Order assigned to: ' . $staff->name);
    }

    /**
     * Remove the specified order from storage
     */
    public function destroy(Order $order)
    {
        if ($order->status !== 'cancelled') {
            return redirect()->route('admin.orders.index')
                ->with('error', 'Only cancelled orders can be deleted. Please cancel the order first.');
        }

        $trackingNumber = $order->tracking_number;
        $order->delete();

        return redirect()->route('admin.orders.index')
            ->with('success', "Order {$trackingNumber} deleted successfully!");
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

    /**
     * Print receipt/invoice
     */
    public function receipt(Order $order)
    {
        $order->load(['customer', 'service', 'branch', 'staff']);
        return view('admin.orders.receipt', compact('order'));
    }

    /**
     * Record payment for order
     */
    public function recordPayment(Request $request, Order $order)
    {
        DB::transaction(function () use ($request, $order) {
            // Create payment record
            \App\Models\Payment::create([
                'order_id' => $order->id,
                'method' => 'cash',
                'amount' => $order->total_amount,
                'receipt_number' => 'REC-' . now()->format('Ymd') . '-' . $order->id,
                'received_by' => Auth::id(),
                'notes' => $request->notes ?? 'Paid at counter',
            ]);

            // Update order
            $order->update([
                'payment_status' => 'paid',
                'payment_method' => 'cash',
                'paid_at' => now(),
                'status' => 'paid'
            ]);

            // Log history
            $order->statusHistories()->create([
                'status' => 'paid',
                'changed_by' => Auth::id(),
                'notes' => 'Payment recorded successfully'
            ]);
        });

        return redirect()->route('admin.orders.show', $order)
            ->with('success', 'Payment recorded and Order updated!');
    }
}
