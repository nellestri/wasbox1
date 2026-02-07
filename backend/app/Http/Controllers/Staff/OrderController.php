<?php

namespace App\Http\Controllers\Staff;

use App\Models\Order;
use App\Models\Branch;
use App\Models\Service;
use App\Models\Customer;
use App\Models\PickupRequest;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\Promotion;
use App\Models\PromotionUsage;

class OrderController extends Controller
{
     protected $notificationService;



    /**
     * Display orders list with filters (branch-specific)
     */
    public function index(Request $request)
    {
        $staff = Auth::user();

        // Safety check
        if (!$staff || !$staff->branch_id) {
            return redirect()
                ->route('staff.dashboard')
                ->with('error', 'Your account is not assigned to a branch. Please contact administrator.');
        }

        $branchId = $staff->branch_id;

        // Base query (automatically filtered by staff's branch)
        $query = Order::with(['customer', 'service', 'branch'])
            ->where('branch_id', $branchId);

        // Search filter
        if ($request->filled('search')) {
            $query->search($request->search);
        }

        // Status filter
        if ($request->filled('status')) {
            $query->byStatus($request->status);
        }

        // Service filter
        if ($request->filled('service_id')) {
            $query->where('service_id', $request->service_id);
        }

        // Date range filter
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        // Get paginated orders
        $orders = $query->latest()->paginate(20);

        // Get stats
        $stats = [
            'total' => Order::where('branch_id', $branchId)->count(),
            'received' => Order::where('branch_id', $branchId)->where('status', 'received')->count(),
            'ready' => Order::where('branch_id', $branchId)->where('status', 'ready')->count(),
            'completed' => Order::where('branch_id', $branchId)->where('status', 'completed')->count(),
            'total_revenue' => Order::where('branch_id', $branchId)->where('status', 'completed')->sum('total_amount'),
            'today_orders' => Order::where('branch_id', $branchId)->whereDate('created_at', today())->count(),
        ];

        // Get services for filter dropdown
        $services = Service::where('is_active', true)
            ->orderBy('display_order')
            ->orderBy('name')
            ->get();

        return view('staff.orders.index', compact('orders', 'stats', 'services'));
    }

    /**
     * Show create order form (with optional pickup request)
     */
    public function create(Request $request)
    {
        $staff = Auth::user();

        // Safety check
        if (!$staff || !$staff->branch_id) {
            return redirect()
                ->route('staff.dashboard')
                ->with('error', 'Your account is not assigned to a branch. Please contact administrator.');
        }

        // Check if creating order from pickup request
        $pickup = null;

        if ($request->has('pickup_id')) {
            $pickupId = $request->pickup_id;
            $pickup = PickupRequest::with(['customer', 'branch', 'service'])
                ->where('branch_id', $staff->branch_id)  // Branch security check
                ->findOrFail($pickupId);
        }

        // Get active customers from staff's branch only
        $customers = Customer::where('is_active', true)
            ->where('preferred_branch_id', $staff->branch_id)
            ->orderBy('name')
            ->get();

        // Get active services
        $services = Service::where('is_active', true)
            ->orderBy('display_order')
            ->orderBy('name')
            ->get();

        // Active promotions for quick-selection
        $promotions = Promotion::active()->get();

        // Active add-ons
        $addons = \App\Models\AddOn::where('is_active', true)->orderBy('name')->get();

        // Get current branch info
        $currentBranch = Branch::find($staff->branch_id);

        return view('staff.orders.create', compact('customers', 'services', 'currentBranch', 'pickup', 'promotions', 'addons'));
    }

    /**
     * Store new order
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            // allow creating an order without selecting a service when applying a per-load promo
            'service_id' => 'nullable|exists:services,id',
            'weight' => 'nullable|numeric|min:0.1|max:1000',
            'pickup_date' => 'nullable|date|after_or_equal:today',
            'delivery_date' => 'nullable|date|after_or_equal:today',
            'notes' => 'nullable|string|max:1000',

            // Pickup/delivery fee fields (optional)
            'pickup_request_id' => 'nullable|exists:pickup_requests,id',
            'pickup_fee' => 'nullable|numeric|min:0',
            'delivery_fee' => 'nullable|numeric|min:0',

            // Add-ons (optional)
            'addons' => 'nullable|array',
            'addons.*' => 'integer|exists:add_ons,id',

            // Promotion code or selected promotion (optional)
            'promo_code' => 'nullable|string|exists:promotions,promo_code',
            'promotion_id' => 'nullable|integer|exists:promotions,id',
        ]);

        $staff = Auth::user();

        // Safety check
        if (!$staff || !$staff->branch_id) {
            return back()->with('error', 'Your account is not assigned to a branch.');
        }

        // If neither service nor a promo is provided, require service
        if (empty($validated['service_id'] ?? null) && empty($validated['promotion_id'] ?? null) && empty($validated['promo_code'] ?? null)) {
            return back()->withErrors(['service_id' => 'Service is required unless applying a per-load promotion.'])->withInput();
        }

        // If no service provided but a promotion is supplied, ensure the promotion is a per-load override
        if (empty($validated['service_id'] ?? null) && (!empty($validated['promotion_id'] ?? null) || !empty($validated['promo_code'] ?? null))) {
            $q = Promotion::query();
            if (!empty($validated['promotion_id'] ?? null)) {
                $q->where('id', $validated['promotion_id']);
            } else {
                $q->where('promo_code', $validated['promo_code']);
            }
            $promoCheck = $q->first();
            if (!$promoCheck || $promoCheck->application_type !== 'per_load_override') {
                return back()->withErrors(['promotion_id' => 'Selected promotion cannot be applied without selecting a service.'])->withInput();
            }
        }

        $service = ($validated['service_id'] ?? null) ? Service::findOrFail($validated['service_id']) : null;

        // Calculate laundry amounts (support per_load pricing)
        if ($service) {
            if ($service->isPerLoad()) {
                // For per-load services, weight is optional and subtotal is the fixed per-load price
                $pricePerKg = 0;
                $subtotal = (float) $service->price_per_load;
            } else {
                // Per-kg pricing requires weight
                if (empty($validated['weight'])) {
                    return back()->withErrors(['weight' => 'Weight is required for per-kg services.'])->withInput();
                }
                $pricePerKg = $service->price_per_kg;
                $subtotal = $validated['weight'] * $pricePerKg;
            }
        } else {
            $pricePerKg = 0;
            $subtotal = 0; // will be set by promo in promo-only flow
        }

        // Compute add-ons total (if any)
        $addonsTotal = 0;
        $selectedAddons = [];
        if (!empty($validated['addons'])) {
            $selectedAddons = \App\Models\AddOn::whereIn('id', $validated['addons'])->get();
            $addonsTotal = $selectedAddons->sum(function ($a) {
                return (float) $a->price;
            });
        }

        // Promotion application (if provided)
        $discountAmount = 0;
        $appliedPromotion = null;

        // Apply promotion if provided (either selected promotion_id or promo_code)
        if (!empty($validated['promotion_id']) || !empty($validated['promo_code'])) {
            DB::transaction(function() use (&$discountAmount, &$appliedPromotion, $validated, &$subtotal, &$pricePerKg, $service, $staff) {
                $query = Promotion::query();
                if (!empty($validated['promotion_id'])) {
                    $query->where('id', $validated['promotion_id']);
                } else {
                    $query->where('promo_code', $validated['promo_code']);
                }

                $promo = $query->lockForUpdate()->first();

                if ($promo && $promo->isApplicableTo((object)[
                    'subtotal' => $subtotal,
                    'service' => $service,
                    'branch_id' => $staff->branch_id,
                    'weight' => $validated['weight'],
                ])) {
                    // If promo applies as per-load override, compute override total and derive discount
                    if ($promo->application_type === 'per_load_override') {
                        $info = $promo->computeOverrideTotal($validated['weight']);
                        $overrideTotal = $info['override_total'];

                        if ($service) {
                            // With a service selected, discount is the difference
                            $discountAmount = max(0, $subtotal - $overrideTotal);
                        } else {
                            // Promo-only order: promo defines the subtotal
                            $discountAmount = 0;
                            $subtotal = $overrideTotal;
                            $pricePerKg = 0; // price not applicable
                        }
                    } else {
                        $calc = $promo->calculateDiscountValue($subtotal, $validated['weight'] ?? null);
                        $discountAmount = min($calc, $subtotal);
                    }

                    $appliedPromotion = $promo;
                }
            });
        }

        // Add pickup/delivery fees if provided
        $pickupFee = $validated['pickup_fee'] ?? 0;
        $deliveryFee = $validated['delivery_fee'] ?? 0;

        // Calculate total (laundry + addons + fees - discount)
        $totalAmount = $subtotal + $addonsTotal - $discountAmount + $pickupFee + $deliveryFee;

        // Generate tracking number
        $trackingNumber = $this->generateTrackingNumber();

        // Create order
        $order = Order::create([
            'tracking_number' => $trackingNumber,
            'customer_id' => $validated['customer_id'],
            'branch_id' => $staff->branch_id,
            'service_id' => $validated['service_id'] ?? null,
            'staff_id' => $staff->id,
            'created_by' => $staff->id,
            'weight' => $validated['weight'] ?? null,
            'price_per_kg' => $pricePerKg,
            'subtotal' => $subtotal,
            'addons_total' => $addonsTotal,
            'discount_amount' => $discountAmount,
            'promotion_id' => $appliedPromotion->id ?? null,
            'pickup_fee' => $pickupFee,
            'delivery_fee' => $deliveryFee,
            'total_amount' => $totalAmount,
            'pickup_date' => $validated['pickup_date'] ?? null,
            'delivery_date' => $validated['delivery_date'] ?? null,
            'notes' => $validated['notes'] ?? null,
            'status' => 'received',
            'received_at' => now(),
            'pickup_request_id' => $validated['pickup_request_id'] ?? null,
        ]);

        // Create status history
        $order->statusHistories()->create([
            'status' => 'received',
            'changed_by' => $staff->id,
            'notes' => 'Order created by staff',
        ]);

        // If created from pickup request, link the order to pickup
        if ($validated['pickup_request_id'] ?? null) {
            $pickup = PickupRequest::find($validated['pickup_request_id']);
            $pickup->update(['order_id' => $order->id]);
        }

        // If a promotion was applied, record usage and increment counters safely
        if ($appliedPromotion) {
            DB::transaction(function() use ($appliedPromotion, $order, $validated, $discountAmount, $subtotal) {
                PromotionUsage::create([
                    'promotion_id' => $appliedPromotion->id,
                    'order_id' => $order->id,
                    'user_id' => $validated['customer_id'],
                    'discount_amount' => $discountAmount,
                    'original_amount' => $subtotal,
                    'final_amount' => max(0, $subtotal - $discountAmount),
                    'code_used' => $appliedPromotion->promo_code,
                    'applied_at' => now(),
                ]);

                $appliedPromotion->increment('usage_count');
            });
        }

        // Attach add-ons (if any)
        if (!empty($selectedAddons)) {
            foreach ($selectedAddons as $addon) {
                $order->addons()->attach($addon->id, [
                    'price_at_purchase' => $addon->price,
                    'quantity' => 1,
                ]);
            }
        }

        return redirect()
            ->route('staff.orders.show', $order)
            ->with('success', 'Order created successfully! Tracking #: ' . $trackingNumber);
    }

    /**
     * Display order details
     */
    public function show(Order $order)
    {
        $staff = Auth::user();

        // Safety check
        if (!$staff || !$staff->branch_id) {
            return redirect()
                ->route('staff.dashboard')
                ->with('error', 'Your account is not assigned to a branch.');
        }

        // Verify staff can access this order
        if ($order->branch_id != $staff->branch_id) {
            abort(403, 'Unauthorized: This order belongs to a different branch.');
        }

        // Load relationships
        $order->load(['customer', 'service', 'branch', 'staff', 'pickupRequest']);

        return view('staff.orders.show', compact('order'));
    }

    /**
     * Show edit order form
     */
    public function edit(Order $order)
    {
        $staff = Auth::user();

        // Safety check
        if (!$staff || !$staff->branch_id) {
            return redirect()
                ->route('staff.dashboard')
                ->with('error', 'Your account is not assigned to a branch.');
        }

        // Verify staff can access this order
        if ($order->branch_id != $staff->branch_id) {
            abort(403, 'Unauthorized: This order belongs to a different branch.');
        }

        // Only allow editing if order is not completed or cancelled
        if (in_array($order->status, ['completed', 'cancelled'])) {
            return redirect()
                ->route('staff.orders.show', $order)
                ->with('error', 'Cannot edit completed or cancelled orders.');
        }

        $customers = Customer::where('is_active', true)->orderBy('name')->get();
        $services = Service::where('is_active', true)
            ->orderBy('display_order')
            ->orderBy('name')
            ->get();

        return view('staff.orders.edit', compact('order', 'customers', 'services'));
    }

    /**
     * Update order
     */
    public function update(Request $request, Order $order)
    {
        $staff = Auth::user();

        // Safety check
        if (!$staff || !$staff->branch_id) {
            return back()->with('error', 'Your account is not assigned to a branch.');
        }

        // Verify staff can access this order
        if ($order->branch_id != $staff->branch_id) {
            abort(403, 'Unauthorized: This order belongs to a different branch.');
        }

        // Only allow editing if order is not completed or cancelled
        if (in_array($order->status, ['completed', 'cancelled'])) {
            return redirect()
                ->route('staff.orders.show', $order)
                ->with('error', 'Cannot edit completed or cancelled orders.');
        }

        $validated = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'service_id' => 'required|exists:services,id',
            'weight' => 'required|numeric|min:0.1|max:1000',
            'pickup_date' => 'nullable|date',
            'delivery_date' => 'nullable|date',
            'notes' => 'nullable|string|max:1000',
        ]);

        $service = Service::findOrFail($validated['service_id']);

        // Recalculate amounts
        $pricePerKg = $service->price_per_kg;
        $subtotal = $validated['weight'] * $pricePerKg;
        $discountAmount = $order->discount_amount ?? 0;

        // Keep existing pickup/delivery fees
        $pickupFee = $order->pickup_fee ?? 0;
        $deliveryFee = $order->delivery_fee ?? 0;

        $totalAmount = $subtotal - $discountAmount + $pickupFee + $deliveryFee;

        // Update order
        $order->update([
            'customer_id' => $validated['customer_id'],
            'service_id' => $validated['service_id'],
            'weight' => $validated['weight'],
            'price_per_kg' => $pricePerKg,
            'subtotal' => $subtotal,
            'total_amount' => $totalAmount,
            'pickup_date' => $validated['pickup_date'] ?? null,
            'delivery_date' => $validated['delivery_date'] ?? null,
            'notes' => $validated['notes'] ?? null,
        ]);

        return redirect()
            ->route('staff.orders.show', $order)
            ->with('success', 'Order updated successfully!');
    }

    /**
     * Update order status
     */
    public function updateStatus(Request $request, Order $order)
    {
        $staff = Auth::user();

        // Safety check
        if (!$staff || !$staff->branch_id) {
            return back()->with('error', 'Your account is not assigned to a branch.');
        }

        // Verify staff can access this order
        if ($order->branch_id != $staff->branch_id) {
            abort(403, 'Unauthorized: This order belongs to a different branch.');
        }

        $validated = $request->validate([
            'status' => 'required|in:received,processing,ready,completed',
            'notes' => 'nullable|string|max:500',
        ]);

        $oldStatus = $order->status;
        $newStatus = $validated['status'];

        // Update order status
        $order->update([
            'status' => $newStatus,
        ]);

        // Update timestamp based on status
        if ($newStatus === 'processing' && !$order->processing_at) {
            $order->update(['processing_at' => now()]);
        } elseif ($newStatus === 'ready' && !$order->ready_at) {
            $order->update(['ready_at' => now()]);
        } elseif ($newStatus === 'completed' && !$order->completed_at) {
            $order->update(['completed_at' => now()]);
        }

        // Create status history
        $order->statusHistories()->create([
            'status' => $newStatus,
            'changed_by' => $staff->id,
            'notes' => $validated['notes'] ?? "Status changed from {$oldStatus} to {$newStatus}",
        ]);

        return back()->with('success', 'Order status updated to: ' . ucfirst($newStatus));
    }

    /**
     * Generate receipt for printing
     */
    public function receipt(Order $order)
    {
        $staff = Auth::user();

        // Safety check
        if (!$staff || !$staff->branch_id) {
            return redirect()
                ->route('staff.dashboard')
                ->with('error', 'Your account is not assigned to a branch.');
        }

        // Verify staff can access this order
        if ($order->branch_id != $staff->branch_id) {
            abort(403, 'Unauthorized: This order belongs to a different branch.');
        }

        // Load relationships
        $order->load(['customer', 'service', 'branch', 'staff', 'pickupRequest']);

        return view('staff.orders.receipt', compact('order'));
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
     * Record payment for an order
     */
    public function recordPayment(Request $request, Order $order)
    {
        $staff = Auth::user();

        // Branch Security Check
        if (!$staff || !$staff->branch_id) {
            return redirect()->route('staff.orders.show', $order)
                ->with('error', 'Your account is not assigned to a branch.');
        }

        if ($order->branch_id != $staff->branch_id) {
            abort(403, 'Unauthorized: This order belongs to a different branch.');
        }

        // Update order status to paid
        $order->updateStatus('paid', $staff, 'Payment recorded');

        return redirect()->route('staff.orders.show', $order)
            ->with('success', 'Payment recorded successfully!');
    }
}
