@extends('staff.layouts.staff')

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
                        <div class="fw-semibold">{{ $order->service->name }}</div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="text-muted small">Weight</label>
                        <div class="fw-semibold">{{ $order->formatted_weight }}</div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="text-muted small">Created By</label>
                        <div>{{ $order->createdBy->name }}</div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="text-muted small">Created At</label>
                        <div>{{ $order->created_at->format('M d, Y h:i A') }}</div>
                    </div>
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
                        <h6 class="mb-0">{{ $order->customer->name }}</h6>
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
                    <tr>
                        <td>Subtotal ({{ $order->formatted_weight }} × ₱{{ number_format($order->price_per_kg, 2) }}/kg)
                        </td>
                        <td class="text-end fw-semibold">{{ $order->formatted_subtotal }}</td>
                    </tr>
                    @if($order->promotion)
                        <tr class="text-success">
                            <td>
                                <i class="bi bi-tag"></i> Promotion: {{ $order->promotion->name }}
                                <br><small class="text-muted">{{ $order->promotion->type_label }}</small>
                            </td>
                            <td class="text-end fw-semibold">-{{ $order->formatted_discount }}</td>
                        </tr>
                    @endif
                    <tr class="border-top">
                        <td class="fs-5 fw-bold">Total Amount</td>
                        <td class="text-end fs-5 fw-bold text-primary">{{ $order->formatted_total }}</td>
                    </tr>
                </table>
            </div>

            <!-- Payment Information -->
            @if($order->payment)
                <div class="table-container mb-4">
                    <h5 class="mb-3">Payment Information</h5>
                    <div class="row">
                        <div class="col-md-4">
                            <label class="text-muted small">Payment Method</label>
                            <div class="fw-semibold">Cash</div>
                        </div>
                        <div class="col-md-4">
                            <label class="text-muted small">Receipt Number</label>
                            <div class="fw-semibold">{{ $order->payment->receipt_number }}</div>
                        </div>
                        <div class="col-md-4">
                            <label class="text-muted small">Received By</label>
                            <div>{{ $order->payment->receivedBy->name }}</div>
                        </div>
                        <div class="col-md-4 mt-3">
                            <label class="text-muted small">Amount Paid</label>
                            <div class="fw-semibold text-success">{{ $order->payment->formatted_amount }}</div>
                        </div>
                        <div class="col-md-8 mt-3">
                            <label class="text-muted small">Payment Date</label>
                            <div>{{ $order->payment->created_at->format('M d, Y h:i A') }}</div>
                        </div>
                    </div>
                </div>
            @endif

            <!-- Activity Log -->
            <div class="table-container">
                <h5 class="mb-3">Status History</h5>
                <div class="timeline">
                    @forelse($order->statusHistories as $history)
                        <div class="d-flex mb-3 pb-3 border-bottom">
                            <div class="me-3">
                                <div class="rounded-circle d-flex align-items-center justify-content-center"
                                    style="width: 40px; height: 40px; background: #E5E7EB;">
                                    <i
                                        class="bi bi-{{ $history->status === 'received' ? 'inbox' : ($history->status === 'ready' ? 'check-circle' : ($history->status === 'paid' ? 'currency-dollar' : 'check-all')) }}"></i>
                                </div>
                            </div>
                            <div class="flex-grow-1">
                                <div class="fw-semibold">{{ ucfirst($history->status) }}</div>
                                <div class="text-muted small">
                                    {{ $history->changedBy ? 'by ' . $history->changedBy->name : 'System' }} •
                                    {{ $history->created_at->format('M d, Y h:i A') }}
                                </div>
                                @if($history->notes)
                                    <div class="mt-1 small">{{ $history->notes }}</div>
                                @endif
                            </div>
                        </div>
                    @empty
                        <p class="text-muted">No status history available.</p>
                    @endforelse
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

                    @foreach(['received', 'ready', 'paid', 'completed'] as $stage)
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
            <!-- Order Stats -->
            <div class="table-container">
                <h5 class="mb-3">Quick Stats</h5><!-- Quick Actions -->
                <div class="table-container mb-4">
                    <h5 class="mb-3">Quick Actions</h5>
                    <div class="d-grid gap-2">

                        {{-- Status: RECEIVED → Mark as Ready --}}
                        @if($order->status === 'received')
                            <form action="{{ route('staff.orders.update-status', $order) }}" method="POST">
                                @csrf
                                <input type="hidden" name="status" value="ready">
                                <button type="submit" class="btn btn-success w-100">
                                    <i class="bi bi-check-circle"></i> Mark as Ready
                                </button>
                            </form>
                        @endif

                        {{-- Status: READY → Record Payment (Link - same as admin) --}}
                        @if($order->status === 'ready')
                            <a href="{{ route('staff.orders.record-payment', $order) }}" class="btn btn-primary w-100">
                                <i class="bi bi-currency-dollar"></i> Record Payment
                            </a>
                        @endif

                        {{-- Status: PAID → Mark as Completed --}}
                        @if($order->status === 'paid')
                            <form action="{{ route('staff.orders.update-status', $order) }}" method="POST">
                                @csrf
                                <input type="hidden" name="status" value="completed">
                                <button type="submit" class="btn btn-success w-100">
                                    <i class="bi bi-check-all"></i> Mark as Completed
                                </button>
                            </form>
                        @endif

                        {{-- View Receipt --}}
                        <a href="{{ route('staff.orders.receipt', $order) }}" class="btn btn-outline-primary w-100"
                            target="_blank">
                            <i class="bi bi-receipt"></i> View Receipt
                        </a>

                        {{-- Cancel Order Button --}}
                        @if(!in_array($order->status, ['completed', 'cancelled']))
                            <button type="button" class="btn btn-outline-danger w-100" data-bs-toggle="modal"
                                data-bs-target="#cancelModal">
                                <i class="bi bi-x-circle"></i> Cancel Order
                            </button>
                        @endif
                    </div>
                </div>
                <div class="mb-2">
                    <small class="text-muted">Days Since Created</small>
                    <div class="fw-semibold {{ $order->days_unclaimed >= 3 ? 'text-danger' : '' }}">
                        {{ $order->days_unclaimed }} days
                    </div>
                </div>
                @if($order->promotion)
                    <div class="mb-2">
                        <small class="text-muted">Discount Applied</small>
                        <div class="fw-semibold text-success">{{ $order->formatted_discount }}</div>
                    </div>
                @endif
                <div>
                    <small class="text-muted">Order Age</small>
                    <div class="fw-semibold">{{ $order->created_at->diffForHumans() }}</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Cancel Order Modal -->
    <div class="modal fade" id="cancelModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="{{ route('staff.orders.update-status', $order) }}" method="POST">
                    @csrf
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
            padding: 1.5rem;
            border-radius: 0.75rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }
    </style>
@endpush
