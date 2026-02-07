@extends('admin.layouts.app')

@section('title', 'Order Details')
@section('page-title', 'Order #' . $order->tracking_number)

@section('content')
    <div class="row g-4">
        <!-- Left Column -->
        <div class="col-lg-8">
            <!-- Order Information -->
            <div class="table-container mb-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="mb-0">Order Information</h5>
                    <span
                        class="badge {{ $order->status === 'completed' ? 'bg-success' : ($order->status === 'cancelled' ? 'bg-danger' : 'bg-warning') }} fs-6">
                        {{ $order->status_label }}
                    </span>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="text-muted small">Tracking Number</label>
                        <div class="fw-semibold">{{ $order->tracking_number }}</div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="text-muted small">Branch</label>
                        <div><span class="badge bg-secondary">{{ $order->branch->name }}</span></div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="text-muted small">Service</label>
                        <div class="fw-semibold">
                            {{ $order->service ? $order->service->name : 'Promotion Only' }}
                            @if($order->service)
                                <small class="text-muted d-block mt-1">
                                    {{ $order->service->service_type_label }}
                                </small>
                            @endif
                        </div>
                    </div>

                    {{-- Show different fields based on pricing type --}}
                    @if($order->service && $order->service->pricing_type === 'per_kg')
                        <div class="col-md-6 mb-3">
                            <label class="text-muted small">Weight</label>
                            <div class="fw-semibold">
                                {{ number_format($order->weight, 1) }} kg
                            </div>
                        </div>
                    @elseif($order->number_of_loads)
                        <div class="col-md-6 mb-3">
                            <label class="text-muted small">
                                @if($order->service && $order->service->service_type === 'special_item')
                                    Number of Pieces
                                @else
                                    Number of Loads
                                @endif
                            </label>
                            <div class="fw-semibold">
                                {{ $order->number_of_loads }}
                                @if($order->weight)
                                    <small class="text-muted d-block mt-1">
                                        ({{ number_format($order->weight, 1) }} kg total)
                                    </small>
                                @endif
                            </div>
                        </div>
                    @endif

                    @if($order->service && $order->service->service_type === 'full_service' && $order->service->max_weight)
                        <div class="col-md-6 mb-3">
                            <label class="text-muted small">Max Weight per Load</label>
                            <div class="fw-semibold">
                                {{ number_format($order->service->max_weight, 1) }} kg
                            </div>
                        </div>
                    @endif

                    <div class="col-md-6 mb-3">
                        <label class="text-muted small">Created By</label>
                        <div>{{ $order->createdBy->name }}</div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="text-muted small">Created At</label>
                        <div>{{ $order->created_at->format('M d, Y h:i A') }}</div>
                    </div>

                    @if($order->staff)
                        <div class="col-md-6 mb-3">
                            <label class="text-muted small">Assigned Staff</label>
                            <div>{{ $order->staff->name }}</div>
                        </div>
                    @endif

                    @if($order->pickup_request_id)
                        <div class="col-md-6 mb-3">
                            <label class="text-muted small">Pickup Request</label>
                            <div>
                                <a href="{{ route('admin.pickups.show', $order->pickup_request_id) }}" class="badge bg-info text-decoration-none">
                                    #{{ $order->pickup_request_id }}
                                </a>
                            </div>
                        </div>
                    @endif
                </div>

                @if($order->notes)
                    <div class="alert alert-info mt-3">
                        <strong>Notes:</strong> {{ $order->notes }}
                    </div>
                @endif
            </div>

            <!-- Customer Information -->
            <div class="table-container mb-4">
                <h5 class="mb-3">Customer Information</h5>
                <div class="d-flex align-items-center mb-3">
                    @if($order->customer->profile_photo_url)
                        <img src="{{ $order->customer->profile_photo_url }}" alt="{{ $order->customer->name }}"
                            class="rounded-circle me-3" style="width: 60px; height: 60px; object-fit: cover;">
                    @else
                        <div class="rounded-circle me-3 d-flex align-items-center justify-content-center"
                            style="width: 60px; height: 60px; background: #E5E7EB; font-size: 1.5rem; font-weight: 600;">
                            {{ strtoupper(substr($order->customer->name, 0, 1)) }}
                        </div>
                    @endif
                    <div>
                        <h6 class="mb-0">
                            <a href="{{ route('admin.customers.show', $order->customer) }}">{{ $order->customer->name }}</a>
                        </h6>
                        <div class="text-muted small">{{ $order->customer->phone }}</div>
                        @if($order->customer->email)
                            <div class="text-muted small">{{ $order->customer->email }}</div>
                        @endif
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <label class="text-muted small">Registration Type</label>
                        <div>
                            <span class="badge bg-{{ $order->customer->isWalkIn() ? 'secondary' : 'primary' }}">
                                {{ $order->customer->registration_type_label }}
                            </span>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="text-muted small">Total Orders</label>
                        <div class="fw-semibold">{{ $order->customer->orders()->count() }}</div>
                    </div>
                </div>
            </div>

           <!-- Pricing Breakdown -->
<div class="table-container mb-4">
    <h5 class="mb-3">Pricing Breakdown</h5>
    <table class="table table-borderless mb-0">
        @if($order->service)
            @if($order->service->pricing_type === 'per_kg')
                <tr>
                    <td>
                        {{ $order->service->name }}
                        ({{ number_format($order->weight, 1) }} kg × ₱{{ number_format($order->price_per_kg, 2) }}/kg)
                    </td>
                    <td class="text-end fw-semibold">₱{{ number_format($order->subtotal, 2) }}</td>
                </tr>
            @else
                <tr>
                    <td>
                        {{ $order->service->name }}
                        @if($order->service->service_type === 'special_item')
                            ({{ $order->number_of_loads }} piece{{ $order->number_of_loads > 1 ? 's' : '' }} × ₱{{ number_format($order->service->price_per_load, 2) }}/piece)
                        @else
                            ({{ $order->number_of_loads }} load{{ $order->number_of_loads > 1 ? 's' : '' }} × ₱{{ number_format($order->service->price_per_load, 2) }}/load)
                        @endif
                        @if($order->weight && $order->service->service_type === 'full_service')
                            <br>
                            <small class="text-muted">
                                {{ number_format($order->weight, 1) }} kg total
                                @if($order->service->max_weight)
                                    ({{ number_format($order->service->max_weight, 1) }} kg per load)
                                @endif
                            </small>
                        @endif
                    </td>
                    <td class="text-end fw-semibold">₱{{ number_format($order->subtotal, 2) }}</td>
                </tr>
            @endif
        @elseif($order->promotion)
            <tr>
                <td>
                    {{ $order->promotion->name }} (Promotion Only)
                    @if($order->number_of_loads)
                        ({{ $order->number_of_loads }} load{{ $order->number_of_loads > 1 ? 's' : '' }})
                    @endif
                </td>
                <td class="text-end fw-semibold">₱{{ number_format($order->subtotal, 2) }}</td>
            </tr>
        @endif

        {{-- Add-ons --}}
        @if($order->addons && $order->addons->count())
            <tr>
                <td colspan="2" class="pt-3">
                    <strong>Add-ons:</strong>
                </td>
            </tr>
            @foreach($order->addons as $addon)
                <tr class="small">
                    <td>
                        <i class="bi bi-plus-circle text-success me-1"></i>
                        {{ $addon->name }}
                        <span class="text-muted">({{ $addon->pivot->quantity }} × ₱{{ number_format($addon->pivot->price_at_purchase, 2) }})</span>
                    </td>
                    <td class="text-end">₱{{ number_format($addon->pivot->price_at_purchase * $addon->pivot->quantity, 2) }}</td>
                </tr>
            @endforeach
            <tr class="small border-top">
                <td class="text-end"><strong>Add-ons Total:</strong></td>
                <td class="text-end fw-semibold">₱{{ number_format($order->addons_total, 2) }}</td>
            </tr>
        @endif

        {{-- Pickup & Delivery Fees --}}
        @if($order->pickup_fee > 0 || $order->delivery_fee > 0)
            <tr class="border-top">
                <td colspan="2" class="pt-3">
                    <strong>Pickup & Delivery:</strong>
                </td>
            </tr>
            @if($order->pickup_fee > 0)
                <tr class="small">
                    <td><i class="bi bi-truck text-primary me-1"></i> Pickup Fee</td>
                    <td class="text-end">₱{{ number_format($order->pickup_fee, 2) }}</td>
                </tr>
            @endif
            @if($order->delivery_fee > 0)
                <tr class="small">
                    <td><i class="bi bi-truck text-success me-1"></i> Delivery Fee</td>
                    <td class="text-end">₱{{ number_format($order->delivery_fee, 2) }}</td>
                </tr>
            @endif
        @endif

        @if($order->promotion)
            <tr class="text-success">
                <td>
                    <i class="bi bi-tag"></i> Promotion: {{ $order->promotion->name }}
                    @if($order->promotion->promo_code)
                        <small class="text-muted">({{ $order->promotion->promo_code }})</small>
                    @endif
                </td>
                <td class="text-end fw-semibold">-₱{{ number_format($order->discount_amount, 2) }}</td>
            </tr>
        @endif

        <tr class="border-top">
            <td class="fs-5 fw-bold">Total Amount</td>
            <td class="text-end fs-5 fw-bold text-primary">₱{{ number_format($order->total_amount, 2) }}</td>
        </tr>

        @if($order->payment_status === 'paid')
            <tr class="text-success">
                <td>
                    <i class="bi bi-check-circle"></i> Payment Status
                    <small class="text-muted d-block">Paid via {{ $order->payment_method }}</small>
                </td>
                <td class="text-end fw-semibold">
                    ₱{{ number_format($order->total_amount, 2) }}
                    @if($order->paid_at)
                        <small class="text-muted d-block">{{ $order->paid_at->format('M d, Y h:i A') }}</small>
                    @endif
                </td>
            </tr>
        @else
            <tr class="text-danger">
                <td><i class="bi bi-exclamation-circle"></i> Payment Status</td>
                <td class="text-end fw-semibold">Unpaid</td>
            </tr>
        @endif
    </table>
</div>
            <!-- Activity Log -->
            <div class="table-container">
                <h5 class="mb-3">Status History</h5>
                <div class="timeline">
                    @foreach($order->statusHistories as $history)
                        <div class="d-flex mb-3 pb-3 border-bottom">
                            <div class="me-3">
                                <div class="rounded-circle d-flex align-items-center justify-content-center"
                                    style="width: 40px; height: 40px; background: #E5E7EB;">
                                    @php
                                        $icon = match($history->status) {
                                            'received' => 'inbox',
                                            'processing' => 'gear',
                                            'ready' => 'check-circle',
                                            'paid' => 'currency-dollar',
                                            'completed' => 'check-all',
                                            'cancelled' => 'x-circle',
                                            default => 'clock'
                                        };
                                    @endphp
                                    <i class="bi bi-{{ $icon }}"></i>
                                </div>
                            </div>
                            <div class="flex-grow-1">
                                <div class="fw-semibold">
                                    {{ ucfirst($history->status) }}
                                    @if($history->status === 'paid')
                                        <span class="badge bg-success ms-2">Paid</span>
                                    @endif
                                </div>
                                <div class="text-muted small">
                                    {{ $history->changedBy ? 'by ' . $history->changedBy->name : 'System' }} •
                                    {{ $history->created_at->format('M d, Y h:i A') }}
                                </div>
                                @if($history->notes)
                                    <div class="mt-1 small text-muted">{{ $history->notes }}</div>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <!-- Right Column -->
        <div class="col-lg-4">
            <!-- Timeline -->
            <div class="table-container mb-4">
                <h5 class="mb-3">Order Timeline</h5>
                <div class="timeline-vertical">
                    @php
                        $timeline = $order->getTimeline();
                        $currentReached = false;
                    @endphp

                    @foreach(['received', 'processing', 'ready', 'paid', 'completed'] as $stage)
                        @php
                            $isActive = $timeline[$stage] !== null;
                            if (!$isActive)
                                $currentReached = true;
                        @endphp
                        <div class="timeline-item {{ $isActive ? 'active' : ($currentReached ? 'pending' : '') }}">
                            <div class="timeline-marker {{ $isActive ? 'bg-success' : 'bg-secondary' }}">
                                <i class="bi bi-{{ $isActive ? 'check' : 'circle' }}"></i>
                            </div>
                            <div class="timeline-content">
                                <div class="fw-semibold">{{ ucfirst($stage) }}</div>
                                @if($isActive)
                                    <small class="text-muted">{{ $timeline[$stage]->format('M d, Y h:i A') }}</small>
                                @else
                                    <small class="text-muted">Pending</small>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="table-container mb-4">
                <h5 class="mb-3">Quick Actions</h5>
                <div class="d-grid gap-2">
                    @if($order->status === 'received')
                        <form action="{{ route('admin.orders.update-status', $order) }}" method="POST">
                            @csrf
                            @method('PUT')
                            <input type="hidden" name="status" value="processing">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="bi bi-play-circle"></i> Start Processing
                            </button>
                        </form>
                    @endif

                    @if($order->status === 'processing')
                        <form action="{{ route('admin.orders.update-status', $order) }}" method="POST">
                            @csrf
                            @method('PUT')
                            <input type="hidden" name="status" value="ready">
                            <button type="submit" class="btn btn-success w-100">
                                <i class="bi bi-check-circle"></i> Mark as Ready
                            </button>
                        </form>
                    @endif

                    @if($order->status === 'ready' && $order->payment_status !== 'paid')
                        <form action="{{ route('admin.orders.record-payment', $order) }}" method="POST">
                            @csrf
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="bi bi-currency-dollar"></i> Record Payment
                            </button>
                        </form>
                    @endif

                    @if($order->status === 'paid')
                        <form action="{{ route('admin.orders.update-status', $order) }}" method="POST">
                            @csrf
                            @method('PUT')
                            <input type="hidden" name="status" value="completed">
                            <button type="submit" class="btn btn-success w-100">
                                <i class="bi bi-check-all"></i> Mark as Completed
                            </button>
                        </form>
                    @endif

                    <a href="{{ route('admin.receipts.show', $order) }}" class="btn btn-outline-primary w-100"
                        target="_blank">
                        <i class="bi bi-receipt"></i> View Receipt
                    </a>

                    <a href="{{ route('admin.orders.edit', $order) }}" class="btn btn-outline-secondary w-100">
                        <i class="bi bi-pencil"></i> Edit Order
                    </a>

                    @if(!in_array($order->status, ['completed', 'cancelled']))
                        <button type="button" class="btn btn-outline-danger w-100" data-bs-toggle="modal"
                            data-bs-target="#cancelModal">
                            <i class="bi bi-x-circle"></i> Cancel Order
                        </button>
                    @endif

                    {{-- Print label button --}}
                        <i class="bi bi-printer"></i> Print Label
                    </a>
                </div>
            </div>

            <!-- Order Stats -->
            <div class="table-container">
                <h5 class="mb-3">Order Summary</h5>
                <div class="mb-3">
                    <small class="text-muted">Service Type</small>
                    <div class="fw-semibold">
                        @if($order->service)
                            @php
                                $typeColors = [
                                    'full_service' => 'primary',
                                    'self_service' => 'success',
                                    'special_item' => 'warning',
                                    'addon' => 'info'
                                ];
                            @endphp
                            <span class="badge bg-{{ $typeColors[$order->service->service_type] ?? 'secondary' }}">
                                {{ $order->service->service_type_label }}
                            </span>
                        @else
                            <span class="badge bg-secondary">Promotion</span>
                        @endif
                    </div>
                </div>

                <div class="mb-3">
                    <small class="text-muted">Pricing Type</small>
                    <div class="fw-semibold">
                        @if($order->service)
                            {{ $order->service->pricing_type === 'per_load' ? 'Per Load' : 'Per Kilogram' }}
                        @else
                            Promotion Fixed Price
                        @endif
                    </div>
                </div>

                <div class="mb-3">
                    <small class="text-muted">Turnaround Time</small>
                    <div class="fw-semibold">
                        @if($order->service)
                            {{ $order->service->turnaround_time }} hours
                        @else
                            N/A
                        @endif
                    </div>
                </div>

                @if($order->promotion)
                    <div class="mb-3">
                        <small class="text-muted">Promotion Applied</small>
                        <div class="fw-semibold text-success">
                            {{ $order->promotion->name }}
                            @if($order->promotion->promo_code)
                                <div class="small text-muted">({{ $order->promotion->promo_code }})</div>
                            @endif
                        </div>
                    </div>
                @endif

                <div class="mb-3">
                    <small class="text-muted">Order Age</small>
                    <div class="fw-semibold">{{ $order->created_at->diffForHumans() }}</div>
                </div>

                @if($order->payment_status !== 'paid')
                    <div class="mb-3">
                        <small class="text-muted">Days Unclaimed</small>
                        <div class="fw-semibold {{ $order->days_unclaimed >= 3 ? 'text-danger' : '' }}">
                            {{ $order->days_unclaimed }} days
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Cancel Order Modal -->
    <div class="modal fade" id="cancelModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="{{ route('admin.orders.update-status', $order) }}" method="POST">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="status" value="cancelled">

                    <div class="modal-header">
                        <h5 class="modal-title">Cancel Order</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="alert alert-warning">
                            <i class="bi bi-exclamation-triangle"></i> This action cannot be undone.
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Cancellation Reason <span class="text-danger">*</span></label>
                            <textarea name="notes" class="form-control" rows="3" required
                                placeholder="Please provide a reason for cancellation"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-danger">Cancel Order</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('styles')
    <style>
        .timeline-vertical {
            position: relative;
        }

        .timeline-item {
            display: flex;
            align-items: flex-start;
            margin-bottom: 1.5rem;
            position: relative;
        }

        .timeline-item:not(:last-child)::after {
            content: '';
            position: absolute;
            left: 15px;
            top: 35px;
            width: 2px;
            height: calc(100% - 5px);
            background: #E5E7EB;
        }

        .timeline-item.active:not(:last-child)::after {
            background: #10B981;
        }

        .timeline-marker {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 1rem;
            color: white;
            flex-shrink: 0;
        }

        .timeline-content {
            flex: 1;
            padding-top: 4px;
        }

        .table-container {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
    </style>
@endpush
