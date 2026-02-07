@extends('staff.layouts.staff')

@section('title', 'Orders Management')
@section('page-title', 'Orders')

@push('styles')
<style>
/* ============================================================================
   STAFF ORDERS - COMPLETE CSS
   ============================================================================ */

/* ============================================================================
   CARDS & CONTAINERS
   ============================================================================ */

.rounded-4 {
    border-radius: 1rem !important;
}

.shadow-sm {
    box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075) !important;
}

.card {
    border: none;
    animation: fadeIn 0.3s ease;
}

.card:hover {
    transform: translateY(-2px);
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15) !important;
}

/* Stat Cards */
.card-body {
    position: relative;
}

.card-body h4 {
    font-size: 1.75rem;
    font-weight: 700;
    color: #212529;
    margin-bottom: 0.25rem;
    line-height: 1.2;
}

.card-body small {
    font-size: 0.75rem;
    color: #6c757d;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    display: block;
}

.card-body h5 {
    font-size: 1.125rem;
    font-weight: 700;
    color: #212529;
    line-height: 1.2;
}

/* ============================================================================
   BUTTONS
   ============================================================================ */

.btn-primary {
    background-color: #3D3B6B !important;
    border-color: #3D3B6B !important;
    color: white !important;
}

.btn-primary:hover {
    background-color: #2d2850 !important;
    border-color: #2d2850 !important;
}

.btn-outline-primary {
    color: #3D3B6B !important;
    border-color: #3D3B6B !important;
}

.btn-outline-primary:hover {
    background-color: #3D3B6B !important;
    border-color: #3D3B6B !important;
    color: white !important;
}

.btn-sm {
    padding: 0.375rem 0.75rem;
    font-size: 0.875rem;
}

/* Button Group */
.btn-group-sm .btn {
    padding: 0.25rem 0.5rem;
    font-size: 0.875rem;
    border-radius: 0.25rem;
}

.btn-group .btn:first-child {
    border-top-right-radius: 0;
    border-bottom-right-radius: 0;
}

.btn-group .btn:last-child {
    border-top-left-radius: 0;
    border-bottom-left-radius: 0;
}

.btn-group .btn:not(:first-child):not(:last-child) {
    border-radius: 0;
}

/* ============================================================================
   TABLE STYLES
   ============================================================================ */

.table {
    margin-bottom: 0;
}

.table thead {
    background-color: #f8f9fa;
    border-bottom: 2px solid #dee2e6;
}

.table th {
    font-weight: 600;
    font-size: 0.875rem;
    color: #495057;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    white-space: nowrap;
}

.table td {
    vertical-align: middle;
    font-size: 0.875rem;
    color: #212529;
}

.table-hover tbody tr:hover {
    background-color: rgba(61, 59, 107, 0.05);
    cursor: pointer;
}

.table tbody tr {
    border-bottom: 1px solid #f0f0f0;
}

.table tbody tr:last-child {
    border-bottom: none;
}

/* ============================================================================
   BADGES
   ============================================================================ */

.badge {
    padding: 0.5rem 0.75rem;
    font-size: 0.75rem;
    font-weight: 600;
    border-radius: 0.375rem;
    text-transform: capitalize;
}

/* Status Badge Colors */
.badge.bg-secondary {
    background-color: #6c757d !important;
    color: white !important;
}

.badge.bg-warning {
    background-color: #ffc107 !important;
    color: #000 !important;
}

.badge.bg-info {
    background-color: #0dcaf0 !important;
    color: #000 !important;
}

.badge.bg-success {
    background-color: #198754 !important;
    color: white !important;
}

.badge.bg-danger {
    background-color: #dc3545 !important;
    color: white !important;
}

/* Light Badge */
.badge.bg-light {
    background-color: #f8f9fa !important;
    color: #495057 !important;
    border: 1px solid #dee2e6 !important;
}

/* ============================================================================
   FORMS
   ============================================================================ */

.form-control,
.form-select {
    border-radius: 0.375rem;
    border: 1px solid #ced4da;
    padding: 0.5rem 0.75rem;
    font-size: 0.875rem;
}

.form-control:focus,
.form-select:focus {
    border-color: #3D3B6B;
    box-shadow: 0 0 0 0.2rem rgba(61, 59, 107, 0.25);
    outline: none;
}

.form-control-sm,
.form-select-sm {
    padding: 0.375rem 0.5rem;
    font-size: 0.8125rem;
}

.form-label {
    font-weight: 500;
    font-size: 0.875rem;
    color: #495057;
    margin-bottom: 0.5rem;
}

/* ============================================================================
   MODAL
   ============================================================================ */

.modal-content {
    border-radius: 0.5rem;
    border: none;
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
}

.modal-header {
    background-color: #f8f9fa;
    border-bottom: 1px solid #dee2e6;
    border-radius: 0.5rem 0.5rem 0 0;
    padding: 1rem 1.5rem;
}

.modal-title {
    font-weight: 600;
    font-size: 1.125rem;
    color: #212529;
}

.modal-body {
    padding: 1.5rem;
}

.modal-footer {
    background-color: #f8f9fa;
    border-top: 1px solid #dee2e6;
    border-radius: 0 0 0.5rem 0.5rem;
    padding: 1rem 1.5rem;
}

.btn-close {
    opacity: 0.5;
}

.btn-close:hover {
    opacity: 1;
}

/* ============================================================================
   STAT CARDS - ICON BACKGROUNDS
   ============================================================================ */

.bg-primary.bg-opacity-10 {
    background-color: rgba(13, 110, 253, 0.1) !important;
    opacity: 1 !important;
}

.bg-warning.bg-opacity-10 {
    background-color: rgba(255, 193, 7, 0.1) !important;
    opacity: 1 !important;
}

.bg-info.bg-opacity-10 {
    background-color: rgba(13, 202, 240, 0.1) !important;
    opacity: 1 !important;
}

.bg-success.bg-opacity-10 {
    background-color: rgba(25, 135, 84, 0.1) !important;
    opacity: 1 !important;
}

.bg-dark.bg-opacity-10 {
    background-color: rgba(33, 37, 41, 0.1) !important;
    opacity: 1 !important;
}

/* Icons */
.bi {
    vertical-align: middle;
    line-height: 1;
}

.fs-4 {
    font-size: 1.5rem !important;
}

.rounded-3 {
    border-radius: 0.5rem !important;
}

/* ============================================================================
   TEXT UTILITIES
   ============================================================================ */

.text-primary {
    color: #3D3B6B !important;
}

.text-muted {
    color: #6c757d !important;
}

.text-dark {
    color: #212529 !important;
}

.fw-bold {
    font-weight: 700 !important;
}

.fw-semibold {
    font-weight: 600 !important;
}

/* ============================================================================
   PAGINATION
   ============================================================================ */

.pagination {
    margin-bottom: 0;
}

.page-link {
    color: #3D3B6B;
    border: 1px solid #dee2e6;
    padding: 0.5rem 0.75rem;
    font-size: 0.875rem;
}

.page-link:hover {
    background-color: #f8f9fa;
    border-color: #3D3B6B;
    color: #2d2850;
}

.page-item.active .page-link {
    background-color: #3D3B6B;
    border-color: #3D3B6B;
    color: white;
}

/* ============================================================================
   EMPTY STATE
   ============================================================================ */

.table tbody td[colspan] {
    text-align: center;
    padding: 3rem 1rem;
}

.table tbody td[colspan] i {
    font-size: 3rem;
    opacity: 0.2;
    color: #6c757d;
}

.table tbody td[colspan] p {
    color: #6c757d;
    margin-top: 1rem;
    margin-bottom: 1rem;
}

/* ============================================================================
   RESPONSIVE
   ============================================================================ */

@media (max-width: 768px) {
    .table-responsive {
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }

    .card-body {
        padding: 1rem !important;
    }

    .px-4 {
        padding-left: 1rem !important;
        padding-right: 1rem !important;
    }

    .py-4 {
        padding-top: 1rem !important;
        padding-bottom: 1rem !important;
    }

    /* Stack stats cards on mobile */
    .row.g-3 {
        --bs-gutter-x: 0.5rem;
        --bs-gutter-y: 0.5rem;
    }

    /* Mobile button group */
    .btn-group {
        display: flex;
        flex-direction: column;
    }

    .btn-group .btn {
        border-radius: 0.25rem !important;
        margin-bottom: 0.25rem;
    }
}

/* ============================================================================
   ADDITIONAL UTILITIES
   ============================================================================ */

/* Spacing */
.g-3 {
    --bs-gutter-x: 1rem;
    --bs-gutter-y: 1rem;
}

.mb-4 {
    margin-bottom: 1.5rem !important;
}

.mt-2 {
    margin-top: 0.5rem !important;
}

.me-2 {
    margin-right: 0.5rem !important;
}

.me-1 {
    margin-right: 0.25rem !important;
}

/* Border Utilities */
.border-start {
    border-left: 4px solid !important;
}

.border-success {
    border-color: #198754 !important;
}

.border-4 {
    border-width: 4px !important;
}

/* Height Utilities */
.h-100 {
    height: 100% !important;
}

/* Display Utilities */
.d-flex {
    display: flex !important;
}

.d-inline-block {
    display: inline-block !important;
}

.justify-content-between {
    justify-content: space-between !important;
}

.justify-content-center {
    justify-content: center !important;
}

.align-items-center {
    align-items: center !important;
}

.text-center {
    text-align: center !important;
}

/* ============================================================================
   ANIMATIONS
   ============================================================================ */

@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Smooth transitions - FIXED: Only specific elements */
.card, .btn, .badge, .table tr, .form-control, .form-select {
    transition: all 0.2s ease;
}

/* ============================================================================
   PRINT STYLES
   ============================================================================ */

@media print {
    .btn,
    .modal,
    .pagination,
    .card-footer,
    .d-flex.justify-content-between {
        display: none !important;
    }

    .table {
        border: 1px solid #000;
    }

    .table th,
    .table td {
        border: 1px solid #000;
        padding: 0.5rem;
    }
}
</style>
@endpush

@section('content')
<div class="container-fluid px-4 py-4">
    {{-- Header --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold text-dark mb-1">Orders Management</h2>
            <p class="text-muted small mb-0">Track and manage all laundry orders</p>
        </div>
        <a href="{{ route('staff.orders.create') }}" class="btn btn-primary shadow-sm">
            <i class="bi bi-plus-circle me-2"></i>New Order
        </a>
    </div>

    {{-- Stats Overview --}}
    <div class="row g-3 mb-4">
        <div class="col-md-2">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-body p-3 text-center">
                    <div class="bg-primary bg-opacity-10 p-2 rounded-3 d-inline-block mb-2">
                        <i class="bi bi-box-seam fs-4 text-primary"></i>
                    </div>
                    <h4 class="fw-bold mb-0">{{ $stats['total'] ?? 0 }}</h4>
                    <small class="text-muted">Total Orders</small>
                </div>
            </div>
        </div>

        <div class="col-md-2">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-body p-3 text-center">
                    <div class="bg-warning bg-opacity-10 p-2 rounded-3 d-inline-block mb-2">
                        <i class="bi bi-clock fs-4 text-warning"></i>
                    </div>
                    <h4 class="fw-bold mb-0">{{ $stats['pending'] ?? 0 }}</h4>
                    <small class="text-muted">Pending</small>
                </div>
            </div>
        </div>

        <div class="col-md-2">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-body p-3 text-center">
                    <div class="bg-info bg-opacity-10 p-2 rounded-3 d-inline-block mb-2">
                        <i class="bi bi-arrow-repeat fs-4 text-info"></i>
                    </div>
                    <h4 class="fw-bold mb-0">{{ $stats['processing'] ?? 0 }}</h4>
                    <small class="text-muted">Processing</small>
                </div>
            </div>
        </div>

        <div class="col-md-2">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-body p-3 text-center">
                    <div class="bg-success bg-opacity-10 p-2 rounded-3 d-inline-block mb-2">
                        <i class="bi bi-check-circle fs-4 text-success"></i>
                    </div>
                    <h4 class="fw-bold mb-0">{{ $stats['ready'] ?? 0 }}</h4>
                    <small class="text-muted">Ready</small>
                </div>
            </div>
        </div>

        <div class="col-md-2">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-body p-3 text-center">
                    <div class="bg-dark bg-opacity-10 p-2 rounded-3 d-inline-block mb-2">
                        <i class="bi bi-check-all fs-4 text-dark"></i>
                    </div>
                    <h4 class="fw-bold mb-0">{{ $stats['completed'] ?? 0 }}</h4>
                    <small class="text-muted">Completed</small>
                </div>
            </div>
        </div>

        <div class="col-md-2">
            <div class="card border-0 shadow-sm rounded-4 h-100 border-start border-success border-4">
                <div class="card-body p-3 text-center">
                    <div class="bg-success bg-opacity-10 p-2 rounded-3 d-inline-block mb-2">
                        <i class="bi bi-cash-stack fs-4 text-success"></i>
                    </div>
                    <h5 class="fw-bold mb-0 small">₱{{ number_format($stats['total_revenue'] ?? 0, 0) }}</h5>
                    <small class="text-muted">Revenue</small>
                </div>
            </div>
        </div>
    </div>

    {{-- Filters --}}
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-body p-3">
            <form method="GET" class="row g-2 align-items-center">
                <div class="col-md-3">
                    <input type="text" name="search" class="form-control form-control-sm"
                        placeholder="Search..." value="{{ request('search') }}">
                </div>
                <div class="col-md-2">
                    <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="">All Status</option>
                        <option value="received" {{ request('status') === 'received' ? 'selected' : '' }}>Received</option>
                        <option value="processing" {{ request('status') === 'processing' ? 'selected' : '' }}>Processing</option>
                        <option value="ready" {{ request('status') === 'ready' ? 'selected' : '' }}>Ready</option>
                        <option value="paid" {{ request('status') === 'paid' ? 'selected' : '' }}>Paid</option>
                        <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>Completed</option>
                        <option value="cancelled" {{ request('status') === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="service_id" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="">All Services</option>
                        @foreach($services as $service)
                            <option value="{{ $service->id }}" {{ request('service_id') == $service->id ? 'selected' : '' }}>
                                {{ $service->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <input type="date" name="date_from" class="form-control form-control-sm"
                        value="{{ request('date_from') }}" onchange="this.form.submit()">
                </div>
                <div class="col-md-2">
                    <input type="date" name="date_to" class="form-control form-control-sm"
                        value="{{ request('date_to') }}" onchange="this.form.submit()">
                </div>
                <div class="col-md-1">
                    <a href="{{ route('staff.orders.index') }}" class="btn btn-sm btn-light border w-100">
                        Clear
                    </a>
                </div>
            </form>
        </div>
    </div>

    {{-- Orders Table --}}
    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="px-4 py-3">Tracking #</th>
                            <th class="px-4 py-3">Customer</th>
                            <th class="px-4 py-3">Service</th>
                            <th class="px-4 py-3">Weight</th>
                            <th class="px-4 py-3">Amount</th>
                            <th class="px-4 py-3">Status</th>
                            <th class="px-4 py-3">Date</th>
                            <th class="px-4 py-3 text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($orders as $order)
                        <tr>
                            <td class="px-4 py-3">
                                <strong class="text-primary">{{ $order->tracking_number ?? '#' . $order->id }}</strong>
                            </td>
                            <td class="px-4 py-3">
                                <div class="fw-semibold">{{ $order->customer->name ?? 'N/A' }}</div>
                                <small class="text-muted">{{ $order->customer->phone ?? '' }}</small>
                            </td>
                            <td class="px-4 py-3">{{ $order->service->name ?? 'N/A' }}</td>
                            <td class="px-4 py-3">{{ number_format($order->weight, 2) }} kg</td>
                            <td class="px-4 py-3">
                                <strong>₱{{ number_format($order->total_amount, 2) }}</strong>
                            </td>
                            <td class="px-4 py-3">
                                <span class="badge bg-{{
                                    $order->status === 'completed' ? 'success' :
                                    ($order->status === 'ready' ? 'info' :
                                    ($order->status === 'processing' ? 'warning' :
                                    ($order->status === 'cancelled' ? 'danger' : 'secondary')))
                                }}">
                                    {{ ucfirst($order->status) }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <small>{{ $order->created_at->format('M d, Y') }}</small>
                            </td>
                            <td class="px-4 py-3 text-center">
                                <div class="btn-group btn-group-sm" role="group">
                                    <a href="{{ route('staff.orders.show', $order) }}" class="btn btn-outline-primary">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    @if(!in_array($order->status, ['completed', 'cancelled']))
                                    <button type="button" class="btn btn-outline-success"
                                            onclick="showStatusModal({{ $order->id }}, '{{ $order->status }}')">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    @endif
                                    <a href="{{ route('staff.orders.receipt', $order) }}" class="btn btn-outline-secondary" target="_blank">
                                        <i class="bi bi-printer"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="text-center py-5">
                                <i class="bi bi-inbox" style="font-size: 3rem; opacity: 0.2;"></i>
                                <p class="text-muted mt-2">No orders found</p>
                                <a href="{{ route('staff.orders.create') }}" class="btn btn-primary btn-sm">
                                    <i class="bi bi-plus-circle me-1"></i>Create First Order
                                </a>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @if($orders->hasPages())
        <div class="card-footer bg-white border-top">
            <div class="d-flex justify-content-center">
                {{ $orders->links() }}
            </div>
        </div>
        @endif
    </div>
</div>

{{-- Status Update Modal --}}
<div class="modal fade" id="statusModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Update Order Status</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="statusForm" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">New Status</label>
                        <select name="status" class="form-select" id="newStatus" required>
                            <option value="received">Received</option>
                            <option value="processing">Processing</option>
                            <option value="ready">Ready for Pickup</option>
                            <option value="completed">Completed</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Notes (Optional)</label>
                        <textarea name="notes" class="form-control" rows="3"
                                  placeholder="Add any notes..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Status</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function showStatusModal(orderId, currentStatus) {
    const modal = new bootstrap.Modal(document.getElementById('statusModal'));
    const form = document.getElementById('statusForm');
    const statusSelect = document.getElementById('newStatus');

    form.action = `/staff/orders/${orderId}/status`;
    statusSelect.value = currentStatus;
    modal.show();
}
</script>
@endpush
