@extends('admin.layouts.app')

@section('title', $pickup ? 'Create Order from Pickup #' . $pickup->id : 'Create New Order')

@section('content')
<div class="container-fluid px-4 py-4">
    {{-- Header --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold text-dark mb-1">
                @if($pickup)
                    Create Order from Pickup #{{ $pickup->id }}
                @else
                    Create New Order
                @endif
            </h2>
            <p class="text-muted small mb-0">
                @if($pickup)
                    Laundry has been picked up - Create the order now
                @else
                    Add a new laundry service order
                @endif
            </p>
        </div>
        <a href="{{ $pickup ? route('admin.pickups.show', $pickup) : route('admin.orders.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-2"></i>Back
        </a>
    </div>

    {{-- Success Alert --}}
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">
            <i class="bi bi-check-circle"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- Pickup Info Alert --}}
    @if($pickup)
        <div class="alert alert-success border-success mb-4">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h6 class="mb-1">
                        <i class="bi bi-truck"></i> Pickup Request #{{ $pickup->id }}
                    </h6>
                    <p class="mb-0">
                        <strong>Customer:</strong> {{ $pickup->customer->name }} |
                        <strong>Address:</strong> {{ $pickup->pickup_address }} |
                        <strong>Service Type:</strong>
                        <span class="badge bg-primary">
                            {{ $pickup->service_type == 'both' ? 'Pickup + Delivery' : ucwords(str_replace('_', ' ', $pickup->service_type)) }}
                        </span>
                    </p>
                </div>
                <div class="col-md-4 text-end">
                    <strong>Quoted Fees:</strong>
                    <span class="text-success fs-5">
                        ₱{{ number_format(($pickup->pickup_fee ?? 0) + ($pickup->delivery_fee ?? 0), 2) }}
                    </span>
                    <br>
                    <small class="text-muted">
                        (Pickup: ₱{{ number_format($pickup->pickup_fee ?? 0, 2) }} +
                        Delivery: ₱{{ number_format($pickup->delivery_fee ?? 0, 2) }})
                    </small>
                </div>
            </div>
        </div>
    @endif

    <form action="{{ route('admin.orders.store') }}" method="POST" id="orderForm">
        @csrf

        {{-- Hidden pickup data --}}
        @if($pickup)
            <input type="hidden" name="pickup_request_id" value="{{ $pickup->id }}">
        @endif

        <div class="row g-4">
            {{-- Left Column - Order Details --}}
            <div class="col-lg-8">
                {{-- Customer Selection --}}
                <div class="card border-0 shadow-sm rounded-4 mb-4">
                    <div class="card-header bg-white border-bottom py-3">
                        <h6 class="mb-0 fw-bold text-dark">
                            <i class="bi bi-person me-2" style="color: #3D3B6B;"></i>
                            Customer Information
                        </h6>
                    </div>
                    <div class="card-body p-4">
                        @if($pickup)
                            <input type="hidden" name="customer_id" value="{{ $pickup->customer_id }}">
                            <div class="alert alert-light border">
                                <div class="row">
                                    <div class="col-md-6">
                                        <strong>Name:</strong> {{ $pickup->customer->name }}
                                    </div>
                                    <div class="col-md-6">
                                        <strong>Phone:</strong> {{ $pickup->customer->phone ?? 'N/A' }}
                                    </div>
                                    <div class="col-md-12 mt-2">
                                        <strong>Pickup Address:</strong> {{ $pickup->pickup_address }}
                                    </div>
                                </div>
                            </div>
                        @else
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Select Customer <span class="text-danger">*</span></label>
                                <select name="customer_id" class="form-select @error('customer_id') is-invalid @enderror" required id="customerSelect">
                                    <option value="">Choose customer...</option>
                                    @foreach($customers as $customer)
                                        <option value="{{ $customer->id }}" {{ old('customer_id') == $customer->id ? 'selected' : '' }}
                                            data-phone="{{ $customer->phone ?? 'N/A' }}"
                                            data-address="{{ $customer->address ?? 'N/A' }}">
                                            {{ $customer->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('customer_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <div id="customerInfo" class="mt-3 p-3 bg-light rounded-3 d-none">
                                    <div class="row g-2">
                                        <div class="col-md-6">
                                            <small class="text-muted d-block">Phone</small>
                                            <strong id="customerPhone">-</strong>
                                        </div>
                                        <div class="col-md-6">
                                            <small class="text-muted d-block">Address</small>
                                            <strong id="customerAddress">-</strong>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Service & Branch --}}
                <div class="card border-0 shadow-sm rounded-4 mb-4">
                    <div class="card-header bg-white border-bottom py-3">
                        <h6 class="mb-0 fw-bold text-dark">
                            <i class="bi bi-droplet me-2" style="color: #3D3B6B;"></i>
                            Service Details
                        </h6>
                    </div>
                    <div class="card-body p-4">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Branch <span class="text-danger">*</span></label>
                                @if($pickup)
                                    <input type="hidden" name="branch_id" value="{{ $pickup->branch_id }}">
                                    <input type="text" class="form-control" value="{{ $pickup->branch->name }}" readonly>
                                @else
                                    <select name="branch_id" class="form-select @error('branch_id') is-invalid @enderror" required id="branchSelect">
                                        <option value="">Select branch...</option>
                                        @foreach($branches as $branch)
                                            <option value="{{ $branch->id }}" {{ old('branch_id') == $branch->id ? 'selected' : '' }}>
                                                {{ $branch->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('branch_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                @endif
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Service <span class="text-danger">*</span></label>
                                <select name="service_id" class="form-select @error('service_id') is-invalid @enderror" id="serviceSelect">
                                    <option value="">Select service...</option>
                                    @foreach($services as $service)
                                        <option value="{{ $service->id }}"
                                                {{ ($pickup && $pickup->service_id == $service->id) || old('service_id') == $service->id ? 'selected' : '' }}
                                                data-price-per-kg="{{ $service->price_per_kg }}"
                                                data-price-per-load="{{ $service->price_per_load }}"
                                                data-pricing-type="{{ $service->pricing_type }}"
                                                data-service-type="{{ $service->service_type }}"
                                                data-min-weight="{{ $service->min_weight }}"
                                                data-max-weight="{{ $service->max_weight }}"
                                                data-turnaround-time="{{ $service->turnaround_time }}">
                                            {{ $service->name }}
                                            @if($service->pricing_type == 'per_load')
                                                @if($service->service_type == 'special_item')
                                                    (₱{{ number_format($service->price_per_load, 2) }}/piece)
                                                @else
                                                    (₱{{ number_format($service->price_per_load, 2) }}/load{{ $service->max_weight ? ' up to ' . $service->max_weight . 'kg' : '' }})
                                                @endif
                                            @else
                                                (₱{{ number_format($service->price_per_kg, 2) }}/kg)
                                            @endif
                                        </option>
                                    @endforeach
                                </select>
                                @error('service_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <div id="serviceDescription" class="mt-2 small text-muted"></div>
                            </div>

                            {{-- Weight Input (for per_kg services) --}}
                            <div class="col-md-6" id="weightContainer">
                                <label class="form-label fw-semibold">Weight (kg) <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="number" name="weight" step="0.1" min="0.1"
                                        class="form-control @error('weight') is-invalid @enderror"
                                        value="{{ old('weight') }}"
                                        placeholder="0.0"
                                        id="weightInput">
                                    <span class="input-group-text">kg</span>
                                    @error('weight')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <small class="text-muted" id="weightHelp">Enter laundry weight</small>
                            </div>

                            {{-- Number of Loads Input (for per_load services) --}}
                            <div class="col-md-6 d-none" id="loadsContainer">
                                <label class="form-label fw-semibold">Number of Loads <span class="text-danger">*</span></label>
                                <input type="number" name="number_of_loads" min="1"
                                    class="form-control @error('number_of_loads') is-invalid @enderror"
                                    value="{{ old('number_of_loads', 1) }}"
                                    placeholder="1"
                                    id="loadsInput">
                                @error('number_of_loads')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="text-muted" id="loadsHelp">Number of loads/pieces</small>
                            </div>

                            {{-- Extra Weight Charge Warning --}}
                            <div class="col-12" id="extraWeightWarning" style="display: none;">
                                <div class="alert alert-warning py-2">
                                    <i class="bi bi-exclamation-triangle"></i>
                                    <strong>Extra Load Required:</strong>
                                    <span id="extraWeightMessage"></span>
                                    <span id="autoExtraLoad" class="text-primary fw-semibold ms-2"></span>
                                </div>
                            </div>

                            {{-- Promotion Selection --}}
                            {{-- Promotion Selection --}}
<div class="col-md-6">
    <label class="form-label fw-semibold">Apply Promotion (Optional)</label>
    <select name="promotion_id" class="form-select" id="promotionSelect">
        <option value="">Select promotion...</option>
        @foreach($promotions as $promotion)
            @php
                $promoText = $promotion->name;
                if ($promotion->application_type === 'per_load_override') {
                    $promoText .= ' - ₱' . number_format($promotion->display_price, 2) . '/load';
                } elseif ($promotion->discount_type === 'percentage') {
                    $promoText .= ' - ' . $promotion->discount_value . '% OFF';
                } elseif ($promotion->discount_type === 'fixed') {
                    $promoText .= ' - ₱' . number_format($promotion->discount_value, 2) . ' OFF';
                }
            @endphp
            <option value="{{ $promotion->id }}"
                data-application-type="{{ $promotion->application_type }}"
                data-display-price="{{ $promotion->display_price }}"
                data-discount-type="{{ $promotion->discount_type }}"
                data-discount-value="{{ $promotion->discount_value }}">
                {{ $promoText }}
            </option>
        @endforeach
    </select>
    <small class="text-muted" id="promotionDescription"></small>
</div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Assign Staff (Optional)</label>
                                <select name="staff_id" class="form-select @error('staff_id') is-invalid @enderror">
                                    <option value="">Select staff member...</option>
                                    @foreach($staff as $member)
                                        <option value="{{ $member->id }}" {{ old('staff_id') == $member->id ? 'selected' : '' }}>
                                            {{ $member->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('staff_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            {{-- Add-ons Section --}}
                            <div class="col-12 mt-3">
                                <label class="form-label fw-semibold">Add-ons (Optional)</label>
                                <p class="text-muted small mb-2">Select additional services to include in the order</p>
                                <div class="row g-2" id="addonsContainer">
                                    @foreach($addons as $addon)
                                        <div class="col-md-6 col-lg-4">
                                            <div class="form-check card border p-3 h-100 addon-item">
                                                <input class="form-check-input addon-checkbox" type="checkbox"
                                                    name="addons[]"
                                                    value="{{ $addon->id }}"
                                                    id="addon{{ $addon->id }}"
                                                    data-price="{{ $addon->price }}"
                                                    data-name="{{ $addon->name }}">
                                                <label class="form-check-label w-100" for="addon{{ $addon->id }}">
                                                    <div class="d-flex justify-content-between align-items-start">
                                                        <div>
                                                            <strong>{{ $addon->name }}</strong>
                                                            <div class="small text-muted">{{ $addon->description }}</div>
                                                        </div>
                                                        <span class="text-success fw-bold">₱{{ number_format($addon->price, 2) }}</span>
                                                    </div>
                                                </label>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Pickup & Delivery Fees Section --}}
                <div class="card border-0 shadow-sm rounded-4 mb-4 @if($pickup) border-warning border-2 @endif">
                    <div class="card-header @if($pickup) bg-warning bg-opacity-10 @else bg-white @endif border-bottom py-3">
                        <h6 class="mb-0 fw-bold text-dark">
                            <i class="bi bi-truck me-2" style="color: #3D3B6B;"></i>
                            Pickup & Delivery Fees
                            @if($pickup)
                                <span class="badge bg-warning text-dark ms-2">
                                    <i class="bi bi-exclamation-triangle"></i> ENTER FEES!
                                </span>
                            @endif
                        </h6>
                    </div>
                    <div class="card-body p-4">
                        @if($pickup)
                            {{-- BIG WARNING for pickup orders --}}
                            <div class="alert alert-warning border-warning mb-3">
                                <div class="d-flex align-items-start">
                                    <i class="bi bi-exclamation-triangle-fill fs-3 me-3 text-warning"></i>
                                    <div>
                                        <strong class="d-block mb-1">⚠️ ACTION REQUIRED: Enter pickup/delivery fees!</strong>
                                        <p class="mb-2 small">This order is from a pickup request. Please enter the appropriate fees below.</p>
                                        <div class="small">
                                            <strong>Standard Fees:</strong> Pickup: ₱50 | Delivery: ₱50 | Both: ₱100
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- Quick Fill Buttons --}}
                            <div class="mb-3">
                                <label class="form-label fw-semibold small">Quick Fill:</label>
                                <div class="btn-group btn-group-sm" role="group">
                                    <button type="button" class="btn btn-outline-primary" onclick="setFees(50, 0)">
                                        <i class="bi bi-arrow-down-circle"></i> Pickup ₱50
                                    </button>
                                    <button type="button" class="btn btn-outline-success" onclick="setFees(0, 50)">
                                        <i class="bi bi-arrow-up-circle"></i> Delivery ₱50
                                    </button>
                                    <button type="button" class="btn btn-outline-info" onclick="setFees(50, 50)">
                                        <i class="bi bi-arrow-left-right"></i> Both ₱100
                                    </button>
                                    <button type="button" class="btn btn-outline-secondary" onclick="setFees(0, 0)">
                                        <i class="bi bi-x-circle"></i> None
                                    </button>
                                </div>
                            </div>
                        @endif

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">
                                    <i class="bi bi-arrow-down-circle text-primary"></i> Pickup Fee
                                    @if($pickup) <span class="text-danger">*</span> @endif
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text">₱</span>
                                    <input type="number"
                                           name="pickup_fee"
                                           step="0.01"
                                           min="0"
                                           class="form-control form-control-lg @error('pickup_fee') is-invalid @enderror"
                                           value="{{ $pickup && $pickup->pickup_fee ? $pickup->pickup_fee : old('pickup_fee', $pickup ? 50.00 : 0) }}"
                                           placeholder="50.00"
                                           id="pickupFeeInput">
                                </div>
                                <small class="text-muted">Fee for picking up laundry from customer</small>
                                @error('pickup_fee')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold">
                                    <i class="bi bi-arrow-up-circle text-success"></i> Delivery Fee
                                    @if($pickup) <span class="text-danger">*</span> @endif
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text">₱</span>
                                    <input type="number"
                                           name="delivery_fee"
                                           step="0.01"
                                           min="0"
                                           class="form-control form-control-lg @error('delivery_fee') is-invalid @enderror"
                                           value="{{ $pickup && $pickup->delivery_fee ? $pickup->delivery_fee : old('delivery_fee', $pickup ? 50.00 : 0) }}"
                                           placeholder="50.00"
                                           id="deliveryFeeInput">
                                </div>
                                <small class="text-muted">Fee for delivering laundry to customer</small>
                                @error('delivery_fee')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        @if(!$pickup)
                            <div class="alert alert-info mt-3 mb-0">
                                <small>
                                    <i class="bi bi-info-circle"></i>
                                    <strong>Walk-in Order:</strong> Only add fees if customer requests pickup/delivery service.
                                    Leave as 0.00 for in-store transactions.
                                </small>
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Additional Notes --}}
                <div class="card border-0 shadow-sm rounded-4 mb-4">
                    <div class="card-header bg-white border-bottom py-3">
                        <h6 class="mb-0 fw-bold text-dark">
                            <i class="bi bi-chat-left-text me-2" style="color: #3D3B6B;"></i>
                            Additional Notes
                        </h6>
                    </div>
                    <div class="card-body p-4">
                        <textarea name="notes" class="form-control @error('notes') is-invalid @enderror"
                            rows="3" placeholder="Special instructions, stain notes, etc...">{{ $pickup->notes ?? old('notes') }}</textarea>
                        @error('notes')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                {{-- Action Buttons --}}
                <div class="d-flex gap-2 mb-4">
                    <button type="submit" class="btn btn-primary px-5 shadow-sm" style="background: #3D3B6B; border: none;">
                        <i class="bi bi-check-circle me-2"></i>Create Order
                    </button>
                    <a href="{{ $pickup ? route('admin.pickups.show', $pickup) : route('admin.orders.index') }}" class="btn btn-outline-secondary px-4">
                        <i class="bi bi-x-circle me-2"></i>Cancel
                    </a>
                </div>
            </div>

            {{-- Right Column - Price Preview --}}
            <div class="col-lg-4">
                <div class="sticky-top" style="top: 20px;">
                    {{-- Price Calculator --}}
                    <div class="card border-0 shadow-sm rounded-4 mb-3">
                        <div class="card-header bg-white border-bottom py-3">
                            <h6 class="mb-0 fw-bold text-dark">
                                <i class="bi bi-calculator me-2" style="color: #3D3B6B;"></i>
                                Order Summary
                            </h6>
                        </div>
                        <div class="card-body p-4">
                            {{-- Service Price Breakdown --}}
                            <h6 class="text-muted mb-3">Service Charges</h6>
                            <div id="serviceBreakdown">
                                <div class="d-flex justify-content-between mb-2">
                                    <span class="text-muted">Base Service:</span>
                                    <strong id="servicePriceDisplay">₱0.00</strong>
                                </div>
                                <div class="d-flex justify-content-between mb-2">
                                    <span class="text-muted">Quantity:</span>
                                    <strong id="quantityDisplay">0</strong>
                                </div>
                                <div class="d-flex justify-content-between mb-3">
                                    <span class="text-muted">Subtotal:</span>
                                    <strong id="serviceSubtotalDisplay">₱0.00</strong>
                                </div>
                            </div>

                            {{-- Extra Loads Calculation --}}
                            <div id="extraLoadsSection" class="mb-3" style="display: none;">
                                <h6 class="text-muted mb-2">Extra Loads</h6>
                                <div class="d-flex justify-content-between mb-1">
                                    <span class="text-muted small">Extra load(s):</span>
                                    <span class="text-muted small" id="extraLoadsCount">0</span>
                                </div>
                                <div class="d-flex justify-content-between mb-2">
                                    <span class="text-muted small">Extra charge:</span>
                                    <span class="text-danger small" id="extraLoadsCharge">₱0.00</span>
                                </div>
                            </div>

                            {{-- Add-ons Summary --}}
                            <div id="addonsSection" class="mb-3" style="display:none;">
                                <h6 class="text-muted mb-2">Add-ons</h6>
                                <div id="addonsList" class="mb-2 small"></div>
                                <div class="d-flex justify-content-between mt-2 border-top pt-2">
                                    <strong class="small">Add-ons Total:</strong>
                                    <strong id="addonsTotalDisplay" class="text-success">₱0.00</strong>
                                </div>
                            </div>

                            {{-- Promotion Discount --}}
                            <div id="promotionSection" class="mb-3" style="display:none;">
                                <h6 class="text-muted mb-2">Promotion</h6>
                                <div class="d-flex justify-content-between mb-2">
                                    <span class="text-muted small">Discount Applied:</span>
                                    <span class="text-success small" id="promotionDiscountDisplay">₱0.00</span>
                                </div>
                            </div>

                            <h6 class="text-muted mb-3 border-top pt-3">Pickup & Delivery Fees</h6>
                            <div class="d-flex justify-content-between mb-2">
                                <span class="text-muted"><i class="bi bi-arrow-down-circle text-primary"></i> Pickup Fee:</span>
                                <strong id="pickupFeeDisplay">₱0.00</strong>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span class="text-muted"><i class="bi bi-arrow-up-circle text-success"></i> Delivery Fee:</span>
                                <strong id="deliveryFeeDisplay">₱0.00</strong>
                            </div>
                            <hr>
                            <div class="d-flex justify-content-between mb-3">
                                <strong>Total Fees:</strong>
                                <strong class="text-success" id="totalFeesDisplay">₱0.00</strong>
                            </div>

                            <hr>
                            <div class="d-flex justify-content-between">
                                <span class="fw-bold">Grand Total:</span>
                                <strong class="fs-4" style="color: #3D3B6B;" id="totalDisplay">₱0.00</strong>
                            </div>
                        </div>
                    </div>

                    {{-- Quick Tips --}}
                    <div class="card border-0 shadow-sm rounded-4">
                        <div class="card-body p-4">
                            <h6 class="fw-bold mb-3">
                                <i class="bi bi-lightbulb text-warning me-2"></i>Quick Tips
                            </h6>
                            <ul class="small text-muted mb-0 ps-3">
                                <li class="mb-2">Per-load services: Price is fixed per load (e.g., ₱200/8kg)</li>
                                <li class="mb-2">Extra weight beyond max limit requires extra load(s)</li>
                                <li class="mb-2">Add-ons like detergent, fabcon are additional charges</li>
                                <li class="mb-2">Special items (comforters) are priced per piece</li>
                                <li class="mb-2">Per-kg services: Price varies with weight</li>
                                <li class="mb-0">Self-service: Customer operates machines</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize
    updatePricingFields();
    updatePrice();

    // Customer info display
    const customerSelect = document.getElementById('customerSelect');
    if (customerSelect) {
        customerSelect.addEventListener('change', function() {
            const selected = this.options[this.selectedIndex];
            const infoDiv = document.getElementById('customerInfo');

            if (this.value) {
                document.getElementById('customerPhone').textContent = selected.dataset.phone || '-';
                document.getElementById('customerAddress').textContent = selected.dataset.address || '-';
                infoDiv.classList.remove('d-none');
            } else {
                infoDiv.classList.add('d-none');
            }
        });
    }

    // Event listeners
    document.getElementById('serviceSelect').addEventListener('change', function() {
        updatePricingFields();
        updatePrice();
    });

    document.getElementById('weightInput')?.addEventListener('input', updatePrice);
    document.getElementById('loadsInput')?.addEventListener('input', updatePrice);
    document.getElementById('promotionSelect').addEventListener('change', function() {
        updatePricingFields();
        updatePrice();
    });
    document.getElementById('pickupFeeInput').addEventListener('input', updatePrice);
    document.getElementById('deliveryFeeInput').addEventListener('input', updatePrice);

    // Add-ons event listeners
    document.querySelectorAll('.addon-checkbox').forEach(checkbox => {
        checkbox.addEventListener('change', updatePrice);
    });
});

// Toggle between weight and loads based on service type
function updatePricingFields() {
    const serviceSelect = document.getElementById('serviceSelect');
    const selected = serviceSelect.options[serviceSelect.selectedIndex];
    const promotionSelect = document.getElementById('promotionSelect');
    const promotionSelected = promotionSelect.options[promotionSelect.selectedIndex];
    const isPerLoadOverride = promotionSelected && promotionSelected.value &&
                              promotionSelected.dataset.applicationType === 'per_load_override';

    // If per-load override is selected, we need to show loads input
    if (isPerLoadOverride) {
        // Hide weight, show loads
        document.getElementById('weightContainer').classList.add('d-none');
        document.getElementById('loadsContainer').classList.remove('d-none');
        document.getElementById('loadsInput').required = true;
        document.getElementById('weightInput').required = false;

        // Update labels
        document.querySelector('#loadsContainer label').textContent = 'Number of Loads *';
        document.getElementById('loadsHelp').textContent = 'Number of loads (e.g., 2 loads)';

        // Hide service description if no service selected
        if (!selected.value) {
            document.getElementById('serviceDescription').textContent = 'Per-load promotion selected - No service needed';
        }

        // Set default loads if empty
        if (!document.getElementById('loadsInput').value) {
            document.getElementById('loadsInput').value = '1';
        }

        return;
    }

    // Normal service selection logic
    if (!selected.value) {
        // Reset everything
        document.getElementById('weightContainer').classList.add('d-none');
        document.getElementById('loadsContainer').classList.add('d-none');
        document.getElementById('extraWeightWarning').style.display = 'none';
        document.getElementById('serviceDescription').textContent = '';
        return;
    }

    const pricingType = selected.dataset.pricingType;
    const serviceType = selected.dataset.serviceType;
    const maxWeight = parseFloat(selected.dataset.maxWeight) || 0;
    const minWeight = parseFloat(selected.dataset.minWeight) || 0;
    const turnaround = selected.dataset.turnaroundTime || 24;

    // Update service description
    let description = '';
    if (serviceType === 'full_service') {
        description = 'Full Service Package';
    } else if (serviceType === 'self_service') {
        description = 'Self Service - Customer operated';
    } else if (serviceType === 'special_item') {
        description = 'Special Item - Priced per piece';
    } else if (serviceType === 'addon') {
        description = 'Add-on Service';
    }
    document.getElementById('serviceDescription').textContent = description + ' | Turnaround: ' + turnaround + ' hours';

    if (pricingType === 'per_kg') {
        // Show weight input, hide loads input
        document.getElementById('weightContainer').classList.remove('d-none');
        document.getElementById('loadsContainer').classList.add('d-none');

        // Update weight help text
        let helpText = 'Enter laundry weight in kilograms';
        if (minWeight > 0 && maxWeight > 0) {
            helpText = `Weight range: ${minWeight}kg to ${maxWeight}kg`;
        } else if (minWeight > 0) {
            helpText = `Minimum weight: ${minWeight}kg`;
        } else if (maxWeight > 0) {
            helpText = `Maximum weight: ${maxWeight}kg`;
        }
        document.getElementById('weightHelp').textContent = helpText;

        // Set default value
        if (!document.getElementById('weightInput').value) {
            document.getElementById('weightInput').value = minWeight > 0 ? minWeight : '5.0';
        }
        document.getElementById('weightInput').required = true;
        document.getElementById('loadsInput').required = false;

        // Hide extra weight warning for per_kg services
        document.getElementById('extraWeightWarning').style.display = 'none';

    } else {
        // Show loads input, hide weight input
        document.getElementById('weightContainer').classList.add('d-none');
        document.getElementById('loadsContainer').classList.remove('d-none');

        // Update labels based on service type
        if (serviceType === 'special_item') {
            document.querySelector('#loadsContainer label').textContent = 'Number of Pieces *';
            document.getElementById('loadsHelp').textContent = 'Number of pieces (e.g., 2 comforters)';
        } else {
            document.querySelector('#loadsContainer label').textContent = 'Number of Loads *';
            document.getElementById('loadsHelp').textContent = 'Number of loads (e.g., 2 loads)';
        }

        if (!document.getElementById('loadsInput').value) {
            document.getElementById('loadsInput').value = '1';
        }
        document.getElementById('loadsInput').required = true;
        document.getElementById('weightInput').required = false;

        // Show weight input for extra calculation (hidden)
        document.getElementById('weightContainer').classList.remove('d-none');
        document.getElementById('weightContainer').classList.add('d-none');
        if (!document.getElementById('weightInput').value) {
            document.getElementById('weightInput').value = maxWeight || '8.0';
        }
    }

    // Trigger price update
    updatePrice();
}

// Main price calculation function
function updatePrice() {
    const serviceSelect = document.getElementById('serviceSelect');
    const selected = serviceSelect.options[serviceSelect.selectedIndex];
    const promotionSelect = document.getElementById('promotionSelect');
    const promotionSelected = promotionSelect.options[promotionSelect.selectedIndex];

    // Check if per-load override is selected without service
    const isPerLoadOverride = promotionSelected && promotionSelected.value &&
                              promotionSelected.dataset.applicationType === 'per_load_override';

    if (!selected.value && !isPerLoadOverride) {
        resetPriceDisplay();
        return;
    }

    const pricingType = selected.dataset.pricingType;
    const serviceType = selected.dataset.serviceType;
    const pricePerKg = parseFloat(selected.dataset.pricePerKg) || 0;
    const pricePerLoad = parseFloat(selected.dataset.pricePerLoad) || 0;
    const maxWeight = parseFloat(selected.dataset.maxWeight) || 0;
    let basePrice = 0;
    let quantity = 0;
    let serviceSubtotal = 0;
    let extraLoads = 0;
    let extraCharge = 0;
    let loads = 1;
    let weight = 0;

    // Calculate service subtotal
    if (isPerLoadOverride && !selected.value) {
        // Only per-load promotion, no service selected
        loads = parseInt(document.getElementById('loadsInput').value) || 1;
        serviceSubtotal = 0; // Will be overridden by promotion price
        quantity = loads;

        // Update display
        document.getElementById('servicePriceDisplay').textContent = 'No Service';
        document.getElementById('quantityDisplay').textContent = loads + (loads === 1 ? ' load' : ' loads');

        // Hide extra loads section
        document.getElementById('extraLoadsSection').style.display = 'none';

    } else if (pricingType === 'per_kg') {
        // Per kg pricing
        weight = parseFloat(document.getElementById('weightInput').value) || 0;
        quantity = weight;
        basePrice = pricePerKg;
        serviceSubtotal = weight * pricePerKg;

        // Update display
        document.getElementById('servicePriceDisplay').textContent = '₱' + pricePerKg.toFixed(2) + '/kg';
        document.getElementById('quantityDisplay').textContent = weight.toFixed(1) + ' kg';

        // Hide extra loads section
        document.getElementById('extraLoadsSection').style.display = 'none';

    } else {
        // Per load pricing
        loads = parseInt(document.getElementById('loadsInput').value) || 1;

        if (serviceType === 'full_service' && maxWeight > 0) {
            // For full service packages, check if we need extra loads
            weight = parseFloat(document.getElementById('weightInput').value) || maxWeight;
            const maxLoadWeight = maxWeight;

            // Calculate required loads based on weight
            const requiredLoads = Math.ceil(weight / maxLoadWeight);

            if (requiredLoads > loads) {
                // Auto-adjust loads
                loads = requiredLoads;
                document.getElementById('loadsInput').value = loads;

                // Show warning
                document.getElementById('extraWeightWarning').style.display = 'block';
                document.getElementById('extraWeightMessage').textContent =
                    `Weight (${weight.toFixed(1)}kg) exceeds ${maxWeight}kg per load.`;
                document.getElementById('autoExtraLoad').textContent =
                    `Auto-adjusted to ${loads} load(s).`;

                // Calculate extra loads
                extraLoads = loads - 1; // Original load is 1
                extraCharge = extraLoads * pricePerLoad;

                // Show extra loads section
                document.getElementById('extraLoadsSection').style.display = 'block';
                document.getElementById('extraLoadsCount').textContent = extraLoads + ' extra load(s)';
                document.getElementById('extraLoadsCharge').textContent = '₱' + extraCharge.toFixed(2);

            } else {
                // Hide warning
                document.getElementById('extraWeightWarning').style.display = 'none';
                document.getElementById('extraLoadsSection').style.display = 'none';
            }
        } else {
            // For other per-load services
            document.getElementById('extraWeightWarning').style.display = 'none';
            document.getElementById('extraLoadsSection').style.display = 'none';
        }

        quantity = loads;
        basePrice = pricePerLoad;
        serviceSubtotal = loads * pricePerLoad;

        // Update display
        if (serviceType === 'special_item') {
            document.getElementById('servicePriceDisplay').textContent = '₱' + pricePerLoad.toFixed(2) + '/piece';
            document.getElementById('quantityDisplay').textContent = loads + (loads === 1 ? ' piece' : ' pieces');
        } else {
            document.getElementById('servicePriceDisplay').textContent = '₱' + pricePerLoad.toFixed(2) + '/load';
            document.getElementById('quantityDisplay').textContent = loads + (loads === 1 ? ' load' : ' loads');
        }
    }

    // Calculate add-ons total
    let addonsTotal = 0;
    const addonsListEl = document.getElementById('addonsList');
    addonsListEl.innerHTML = '';

    document.querySelectorAll('.addon-checkbox:checked').forEach(checkbox => {
        const price = parseFloat(checkbox.dataset.price) || 0;
        const name = checkbox.dataset.name || 'Add-on';
        addonsTotal += price;

        const item = document.createElement('div');
        item.className = 'd-flex justify-content-between mb-1';
        item.innerHTML = `
            <span class="text-muted small">${name}:</span>
            <span class="text-muted small">₱${price.toFixed(2)}</span>
        `;
        addonsListEl.appendChild(item);
    });

    // Show/hide addons section
    const addonsSectionEl = document.getElementById('addonsSection');
    if (addonsTotal > 0) {
        addonsSectionEl.style.display = 'block';
        document.getElementById('addonsTotalDisplay').textContent = '₱' + addonsTotal.toFixed(2);
    } else {
        addonsSectionEl.style.display = 'none';
    }

    // Calculate promotion discount or override
    let promotionDiscount = 0;
    let promotionOverrideTotal = serviceSubtotal;
    let promotionDisplayText = '';
    let isOverride = false;
    const promotionSection = document.getElementById('promotionSection');

    if (promotionSelected && promotionSelected.value) {
        const applicationType = promotionSelected.dataset.applicationType;
        const discountType = promotionSelected.dataset.discountType;
        const discountValue = parseFloat(promotionSelected.dataset.discountValue) || 0;
        const displayPrice = parseFloat(promotionSelected.dataset.displayPrice) || 0;

        if (applicationType === 'per_load_override') {
            // PER LOAD OVERRIDE: Set fixed price per load
            isOverride = true;

            // Calculate new total based on override price
            promotionOverrideTotal = loads * displayPrice;

            // The "discount" is what the customer saves
            promotionDiscount = Math.max(0, serviceSubtotal - promotionOverrideTotal);
            promotionDisplayText = '₱' + displayPrice.toFixed(2) + '/load';

            // Update description
            document.getElementById('promotionDescription').textContent =
                `Fixed price: ₱${displayPrice.toFixed(2)} per load`;

        } else {
            // REGULAR DISCOUNT
            if (discountType === 'percentage') {
                promotionDiscount = (serviceSubtotal * discountValue) / 100;
                promotionDisplayText = discountValue + '% OFF';

                document.getElementById('promotionDescription').textContent =
                    `${discountValue}% discount applied`;
            } else {
                promotionDiscount = discountValue;
                promotionDisplayText = '₱' + discountValue.toFixed(2) + ' OFF';

                document.getElementById('promotionDescription').textContent =
                    `Fixed discount of ₱${discountValue.toFixed(2)} applied`;
            }

            // Ensure discount doesn't exceed subtotal
            promotionDiscount = Math.min(promotionDiscount, serviceSubtotal);
            promotionOverrideTotal = serviceSubtotal - promotionDiscount;
        }

        // Update promotion section
        promotionSection.style.display = 'block';
        document.getElementById('promotionDiscountDisplay').textContent = promotionDisplayText;

    } else {
        promotionSection.style.display = 'none';
        document.getElementById('promotionDescription').textContent = '';
    }

    // Calculate fees
    const pickupFee = parseFloat(document.getElementById('pickupFeeInput').value) || 0;
    const deliveryFee = parseFloat(document.getElementById('deliveryFeeInput').value) || 0;
    const totalFees = pickupFee + deliveryFee;

    // Calculate grand total based on promotion type
    let grandTotal = 0;

    if (isOverride) {
        // For overrides: use the override total, not original - discount
        grandTotal = promotionOverrideTotal + extraCharge + addonsTotal + totalFees;
    } else {
        // For discounts: original - discount + extras
        grandTotal = serviceSubtotal - promotionDiscount + extraCharge + addonsTotal + totalFees;
    }

    // Update all displays
    document.getElementById('serviceSubtotalDisplay').textContent = '₱' + serviceSubtotal.toFixed(2);
    document.getElementById('pickupFeeDisplay').textContent = '₱' + pickupFee.toFixed(2);
    document.getElementById('deliveryFeeDisplay').textContent = '₱' + deliveryFee.toFixed(2);
    document.getElementById('totalFeesDisplay').textContent = '₱' + totalFees.toFixed(2);
    document.getElementById('totalDisplay').textContent = '₱' + grandTotal.toFixed(2);
}

// Reset price display
function resetPriceDisplay() {
    document.getElementById('servicePriceDisplay').textContent = '₱0.00';
    document.getElementById('quantityDisplay').textContent = '0';
    document.getElementById('serviceSubtotalDisplay').textContent = '₱0.00';
    document.getElementById('pickupFeeDisplay').textContent = '₱0.00';
    document.getElementById('deliveryFeeDisplay').textContent = '₱0.00';
    document.getElementById('totalFeesDisplay').textContent = '₱0.00';
    document.getElementById('totalDisplay').textContent = '₱0.00';
    document.getElementById('extraLoadsSection').style.display = 'none';
    document.getElementById('addonsSection').style.display = 'none';
    document.getElementById('promotionSection').style.display = 'none';
}

// Quick fill fees function
function setFees(pickup, delivery) {
    document.getElementById('pickupFeeInput').value = pickup.toFixed(2);
    document.getElementById('deliveryFeeInput').value = delivery.toFixed(2);
    updatePrice();
}

// Form submission validation
document.getElementById('orderForm').addEventListener('submit', function(e) {
    const pickup = parseFloat(document.getElementById('pickupFeeInput').value) || 0;
    const delivery = parseFloat(document.getElementById('deliveryFeeInput').value) || 0;
    const serviceSelect = document.getElementById('serviceSelect');
    const promotionSelect = document.getElementById('promotionSelect');
    const promotionSelected = promotionSelect.options[promotionSelect.selectedIndex];

    const isPerLoadOverride = promotionSelected && promotionSelected.value &&
                              promotionSelected.dataset.applicationType === 'per_load_override';

    @if($pickup)
        // For pickup orders, warn if no fees entered
        if (pickup === 0 && delivery === 0) {
            if (!confirm('⚠️ No pickup/delivery fees entered!\n\nAre you sure this order should have NO fees?')) {
                e.preventDefault();
                document.getElementById('pickupFeeInput').classList.add('border-danger');
                document.getElementById('deliveryFeeInput').classList.add('border-danger');
                document.getElementById('pickupFeeInput').focus();
                return false;
            }
        }
    @endif

    // Validate service selection (not required for per-load override promotions)
    if (!serviceSelect.value && !isPerLoadOverride) {
        alert('Please select a service or choose a per-load promotion');
        e.preventDefault();
        serviceSelect.focus();
        return false;
    }

    // Validate loads for per-load promotions
    if (isPerLoadOverride) {
        const loads = parseInt(document.getElementById('loadsInput').value) || 0;
        if (loads <= 0) {
            alert('Please enter a valid number of loads for the per-load promotion');
            e.preventDefault();
            document.getElementById('loadsInput').focus();
            return false;
        }
    }

    // Validate weight/loads for normal services
    if (serviceSelect.value && !isPerLoadOverride) {
        const selected = serviceSelect.options[serviceSelect.selectedIndex];
        const pricingType = selected.dataset.pricingType;

        if (pricingType === 'per_kg') {
            const weight = parseFloat(document.getElementById('weightInput').value) || 0;
            const minWeight = parseFloat(selected.dataset.minWeight) || 0;
            const maxWeight = parseFloat(selected.dataset.maxWeight) || 9999;

            if (weight <= 0) {
                alert('Please enter a valid weight');
                e.preventDefault();
                document.getElementById('weightInput').focus();
                return false;
            }

            if (minWeight > 0 && weight < minWeight) {
                alert(`Minimum weight for this service is ${minWeight}kg`);
                e.preventDefault();
                document.getElementById('weightInput').focus();
                return false;
            }

            if (maxWeight > 0 && weight > maxWeight) {
                alert(`Maximum weight for this service is ${maxWeight}kg. Please split into multiple orders.`);
                e.preventDefault();
                document.getElementById('weightInput').focus();
                return false;
            }
        } else {
            const loads = parseInt(document.getElementById('loadsInput').value) || 0;
            if (loads <= 0) {
                alert('Please enter a valid number of loads/pieces');
                e.preventDefault();
                document.getElementById('loadsInput').focus();
                return false;
            }
        }
    }
});

// Auto-calculate weight when loads change for full service packages
document.getElementById('loadsInput')?.addEventListener('change', function() {
    const serviceSelect = document.getElementById('serviceSelect');
    const selected = serviceSelect.options[serviceSelect.selectedIndex];
    const serviceType = selected.dataset.serviceType;
    const maxWeight = parseFloat(selected.dataset.maxWeight) || 0;

    if (serviceType === 'full_service' && maxWeight > 0) {
        const loads = parseInt(this.value) || 1;
        const estimatedWeight = loads * maxWeight;
        document.getElementById('weightInput').value = estimatedWeight.toFixed(1);
        updatePrice();
    }
});
</script>

<style>
.form-check .card {
    transition: all 0.2s ease;
    cursor: pointer;
    min-height: 90px;
}

.form-check .card:hover {
    border-color: #3D3B6B;
    transform: translateY(-2px);
}

.form-check-input:checked + .form-check-label .card {
    border-color: #3D3B6B;
    background-color: rgba(61, 59, 107, 0.05);
    box-shadow: 0 0 0 2px rgba(61, 59, 107, 0.2);
}

.addon-item {
    transition: all 0.3s ease;
}

.addon-item.selected {
    border-color: #28a745;
    background-color: rgba(40, 167, 69, 0.05);
}

#addonsList div {
    padding: 0.5rem;
    border-radius: 0.375rem;
    margin-bottom: 0.25rem;
    background-color: #f8f9fa;
    border-left: 3px solid #3D3B6B;
}
</style>
@endpush
@endsection
