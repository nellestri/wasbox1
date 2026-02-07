@extends('admin.layouts.app')

@section('title', 'Unclaimed Laundry')

@section('content')
<div class="container-fluid px-4 py-4">
    {{-- Header --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold mb-1">
                <i class="bi bi-exclamation-triangle text-warning me-2"></i>Unclaimed Laundry
            </h2>
            <p class="text-muted small mb-0">Monitor unclaimed orders across Sibulan, Dumaguete, and Bais branches</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.unclaimed.export') }}" class="btn btn-outline-success shadow-sm">
                <i class="bi bi-download me-1"></i> Export CSV
            </a>
            <a href="{{ route('admin.unclaimed.history') }}" class="btn btn-outline-secondary shadow-sm">
                <i class="bi bi-clock-history me-1"></i> Disposal History
            </a>
            <a href="{{ route('admin.unclaimed.remindAll') }}" class="btn btn-danger shadow-sm"
               onclick="return confirm('Send reminders to all customers with unclaimed laundry (3+ days)?')">
                <i class="bi bi-bell-fill me-1"></i> Remind All
            </a>
        </div>
    </div>

    {{-- Alerts --}}
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm mb-4">
            <i class="bi bi-check-circle-fill me-2"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('warning'))
        <div class="alert alert-warning alert-dismissible fade show border-0 shadow-sm mb-4">
            <i class="bi bi-exclamation-triangle-fill me-2"></i> {{ session('warning') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm mb-4">
            <i class="bi bi-x-circle-fill me-2"></i> {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- Key Metrics --}}
    <div class="row g-3 mb-4">
        {{-- Total at Risk --}}
        <div class="col-6 col-lg-3">
            <div class="card border-0 shadow-sm h-100" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                <div class="card-body text-white">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <p class="mb-1 small opacity-75">Total Value at Risk</p>
                            <h3 class="fw-bold mb-0">₱{{ number_format($stats['total_value'], 0) }}</h3>
                            <small class="opacity-75">{{ $stats['total'] }} orders</small>
                        </div>
                        <div class="rounded-circle bg-white bg-opacity-25 p-2">
                            <i class="bi bi-currency-dollar fs-4"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Critical --}}
        <div class="col-6 col-lg-3">
            <div class="card border-0 shadow-sm h-100 border-start border-4 border-danger">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <p class="text-muted small mb-1">Critical (14+ days)</p>
                            <h3 class="fw-bold text-danger mb-0">{{ $stats['critical'] }}</h3>
                            <small class="text-muted">₱{{ number_format($stats['critical_value'], 0) }}</small>
                        </div>
                        <div class="rounded-circle bg-danger bg-opacity-10 p-2">
                            <i class="bi bi-exclamation-octagon fs-4 text-danger"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Recovered This Month --}}
        <div class="col-6 col-lg-3">
            <div class="card border-0 shadow-sm h-100 border-start border-4 border-success">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <p class="text-muted small mb-1">Recovered (This Month)</p>
                            <h3 class="fw-bold text-success mb-0">₱{{ number_format($stats['recovered_this_month'], 0) }}</h3>
                            <small class="text-muted">Revenue saved</small>
                        </div>
                        <div class="rounded-circle bg-success bg-opacity-10 p-2">
                            <i class="bi bi-graph-up-arrow fs-4 text-success"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Reminders Today --}}
        <div class="col-6 col-lg-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <p class="text-muted small mb-1">Reminders Today</p>
                            <h3 class="fw-bold mb-0">{{ $stats['reminders_today'] }}</h3>
                            <small class="text-muted">Notifications sent</small>
                        </div>
                        <div class="rounded-circle bg-primary bg-opacity-10 p-2">
                            <i class="bi bi-bell fs-4 text-primary"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Urgency Breakdown --}}
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body py-3">
            <div class="row text-center">
                <div class="col">
                    <a href="{{ route('admin.unclaimed.index', ['urgency' => 'critical']) }}"
                       class="text-decoration-none d-block p-2 rounded {{ request('urgency') == 'critical' ? 'bg-danger bg-opacity-10' : '' }}">
                        <span class="badge bg-danger fs-5 mb-1">{{ $stats['critical'] }}</span>
                        <div class="small text-muted">🚨 Critical (14+)</div>
                    </a>
                </div>
                <div class="col">
                    <a href="{{ route('admin.unclaimed.index', ['urgency' => 'urgent']) }}"
                       class="text-decoration-none d-block p-2 rounded {{ request('urgency') == 'urgent' ? 'bg-warning bg-opacity-10' : '' }}">
                        <span class="badge bg-warning text-dark fs-5 mb-1">{{ $stats['urgent'] }}</span>
                        <div class="small text-muted">⚠️ Urgent (7-13)</div>
                    </a>
                </div>
                <div class="col">
                    <a href="{{ route('admin.unclaimed.index', ['urgency' => 'warning']) }}"
                       class="text-decoration-none d-block p-2 rounded {{ request('urgency') == 'warning' ? 'bg-info bg-opacity-10' : '' }}">
                        <span class="badge bg-info fs-5 mb-1">{{ $stats['warning'] }}</span>
                        <div class="small text-muted">⏰ Warning (3-6)</div>
                    </a>
                </div>
                <div class="col">
                    <a href="{{ route('admin.unclaimed.index', ['urgency' => 'pending']) }}"
                       class="text-decoration-none d-block p-2 rounded {{ request('urgency') == 'pending' ? 'bg-secondary bg-opacity-10' : '' }}">
                        <span class="badge bg-secondary fs-5 mb-1">{{ $stats['pending'] }}</span>
                        <div class="small text-muted">📌 Pending (1-2)</div>
                    </a>
                </div>
                <div class="col">
                    <a href="{{ route('admin.unclaimed.index') }}"
                       class="text-decoration-none d-block p-2 rounded {{ !request('urgency') && !request('branch_id') ? 'bg-primary bg-opacity-10' : '' }}">
                        <span class="badge bg-primary fs-5 mb-1">{{ $stats['total'] }}</span>
                        <div class="small text-muted">📊 All</div>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        {{-- Main Content --}}
        <div class="col-lg-9">
            {{-- Filters --}}
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body py-3">
                    <form method="GET" class="row g-2 align-items-end">
                        <div class="col-md-4">
                            <label class="form-label small">Search</label>
                            <input type="text" name="search" class="form-control"
                                   placeholder="Tracking #, customer, phone..."
                                   value="{{ request('search') }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small">Branch</label>
                            <select name="branch_id" class="form-select">
                                <option value="">All Branches</option>
                                @foreach($branches as $branch)
                                    <option value="{{ $branch->id }}" {{ request('branch_id') == $branch->id ? 'selected' : '' }}>
                                        {{ $branch->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small">Min Days</label>
                            <input type="number" name="min_days" class="form-control"
                                   placeholder="0" min="0" value="{{ request('min_days') }}">
                        </div>
                        <div class="col-md-3 d-flex gap-2">
                            <button type="submit" class="btn btn-primary flex-grow-1">
                                <i class="bi bi-search me-1"></i> Filter
                            </button>
                            <a href="{{ route('admin.unclaimed.index') }}" class="btn btn-outline-secondary">
                                <i class="bi bi-x-lg"></i>
                            </a>
                        </div>
                    </form>
                </div>
            </div>

            {{-- Orders Table --}}
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Unclaimed Orders</h5>
                    <button type="button" class="btn btn-sm btn-primary" id="bulkReminderBtn" disabled>
                        <i class="bi bi-send me-1"></i> Send Selected
                    </button>
                </div>
                <div class="card-body p-0">
                    <form id="bulkForm" action="{{ route('admin.unclaimed.bulk-reminders') }}" method="POST">
                        @csrf
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="bg-light">
                                    <tr>
                                        <th width="40">
                                            <input type="checkbox" class="form-check-input" id="selectAll">
                                        </th>
                                        <th>Order</th>
                                        <th>Customer</th>
                                        <th>Branch</th>
                                        <th class="text-end">Amount</th>
                                        <th class="text-center">Days</th>
                                        <th class="text-center">Status</th>
                                        <th class="text-end">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($orders as $order)
                                        @php
                                            $days = $order->days_unclaimed ?? 0;
                                            $urgency = $order->unclaimed_status ?? 'normal';
                                            $color = $order->unclaimed_color ?? 'secondary';
                                        @endphp
                                        <tr class="{{ $urgency === 'critical' ? 'table-danger' : ($urgency === 'urgent' ? 'table-warning' : '') }}">
                                            <td>
                                                <input type="checkbox" class="form-check-input order-checkbox"
                                                       name="order_ids[]" value="{{ $order->id }}">
                                            </td>
                                            <td>
                                                <a href="{{ route('admin.unclaimed.show', $order) }}" class="fw-semibold text-decoration-none">
                                                    {{ $order->tracking_number }}
                                                </a>
                                                <div class="small text-muted">
                                                    Ready: {{ $order->ready_at?->format('M d, Y') ?? 'N/A' }}
                                                </div>
                                            </td>
                                            <td>
                                                <div class="fw-semibold">{{ $order->customer->name ?? 'N/A' }}</div>
                                                <div class="small">
                                                    <a href="tel:{{ $order->customer->phone ?? '' }}" class="text-decoration-none text-muted">
                                                        <i class="bi bi-telephone me-1"></i>{{ $order->customer->phone ?? 'N/A' }}
                                                    </a>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="badge bg-secondary bg-opacity-10 text-secondary">
                                                    {{ $order->branch->name ?? 'N/A' }}
                                                </span>
                                            </td>
                                            <td class="text-end">
                                                <div class="fw-bold">₱{{ number_format($order->total_amount, 2) }}</div>
                                                @php $storageFee = $order->calculated_storage_fee ?? 0; @endphp
                                                @if($storageFee > 0)
                                                    <div class="small text-warning">
                                                        +₱{{ number_format($storageFee, 2) }} fee
                                                    </div>
                                                @endif
                                            </td>
                                            <td class="text-center">
                                                <span class="badge bg-{{ $color }} fs-6">{{ $days }}</span>
                                            </td>
                                            <td class="text-center">
                                                @switch($urgency)
                                                    @case('critical')
                                                        <span class="badge bg-danger">🚨 Critical</span>
                                                        @break
                                                    @case('urgent')
                                                        <span class="badge bg-warning text-dark">⚠️ Urgent</span>
                                                        @break
                                                    @case('warning')
                                                        <span class="badge bg-info">⏰ Warning</span>
                                                        @break
                                                    @default
                                                        <span class="badge bg-secondary">📌 Pending</span>
                                                @endswitch
                                            </td>
                                            <td class="text-end">
                                                <div class="btn-group btn-group-sm">
                                                    {{-- Call --}}
                                                    <a href="tel:{{ $order->customer->phone ?? '' }}"
                                                       class="btn btn-outline-success" title="Call">
                                                        <i class="bi bi-telephone"></i>
                                                    </a>

                                                    {{-- Send Reminder --}}
                                                    <form action="{{ route('admin.unclaimed.send-reminder', $order->id) }}"
                                                          method="POST" class="d-inline">
                                                        @csrf
                                                        <button type="submit" class="btn btn-outline-primary" title="Notify">
                                                            <i class="bi bi-bell"></i>
                                                        </button>
                                                    </form>

                                                    {{-- Mark Claimed --}}
                                                    <form action="{{ route('admin.unclaimed.mark-claimed', $order->id) }}"
                                                          method="POST" class="d-inline">
                                                        @csrf
                                                        <button type="submit" class="btn btn-success"
                                                                title="Mark Claimed"
                                                                onclick="return confirm('Mark as claimed?')">
                                                            <i class="bi bi-check-lg"></i>
                                                        </button>
                                                    </form>

                                                    {{-- Dispose (only if 30+ days) --}}
                                                    <form action="{{ route('admin.unclaimed.mark-disposed', $order->id) }}"
                                                          method="POST" class="d-inline">
                                                        @csrf
                                                        <button type="submit"
                                                                class="btn btn-outline-danger"
                                                                title="Dispose"
                                                                {{ $days < ($disposalThreshold ?? 30) ? 'disabled' : '' }}
                                                                onclick="return confirm('Dispose this order? This cannot be undone.')">
                                                            <i class="bi bi-trash"></i>
                                                        </button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="8" class="text-center py-5">
                                                <i class="bi bi-emoji-smile fs-1 text-success d-block mb-2"></i>
                                                <h5 class="text-success">Excellent!</h5>
                                                <p class="text-muted mb-0">No unclaimed laundry found.</p>
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </form>
                </div>

                @if($orders->hasPages())
                    <div class="card-footer bg-white">
                        {{ $orders->links() }}
                    </div>
                @endif
            </div>
        </div>

        {{-- Sidebar --}}
        <div class="col-lg-3">
            {{-- Branch Comparison --}}
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white py-3">
                    <h6 class="mb-0"><i class="bi bi-building me-2"></i>By Branch</h6>
                </div>
                <div class="card-body p-0">
                    @foreach($branchStats as $branch)
                        <a href="{{ route('admin.unclaimed.index', ['branch_id' => $branch['id']]) }}"
                           class="d-flex justify-content-between align-items-center p-3 border-bottom text-decoration-none {{ request('branch_id') == $branch['id'] ? 'bg-light' : '' }}">
                            <div>
                                <div class="fw-semibold text-dark">{{ $branch['name'] }}</div>
                                <small class="text-muted">₱{{ number_format($branch['value'], 0) }} at risk</small>
                            </div>
                            <div class="text-end">
                                <span class="badge bg-{{ $branch['critical'] > 0 ? 'danger' : 'secondary' }} fs-6">
                                    {{ $branch['total'] }}
                                </span>
                                @if($branch['critical'] > 0)
                                    <div class="small text-danger">{{ $branch['critical'] }} critical</div>
                                @endif
                            </div>
                        </a>
                    @endforeach
                </div>
            </div>

            {{-- Storage Fee Summary --}}
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white py-3">
                    <h6 class="mb-0"><i class="bi bi-cash-stack me-2"></i>Potential Revenue</h6>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Order Value</span>
                        <span class="fw-bold">₱{{ number_format($stats['total_value'], 2) }}</span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Storage Fees</span>
                        <span class="fw-bold text-warning">₱{{ number_format($stats['storage_fees'], 2) }}</span>
                    </div>
                    <hr>
                    <div class="d-flex justify-content-between">
                        <span class="fw-bold">Total Potential</span>
                        <span class="fw-bold text-success">₱{{ number_format($stats['potential_total'], 2) }}</span>
                    </div>
                    <small class="text-muted d-block mt-2">
                        Storage: ₱{{ config('unclaimed.storage_fee_per_day', 10) }}/day after 7 days
                    </small>
                </div>
            </div>

            {{-- This Month Summary --}}
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white py-3">
                    <h6 class="mb-0"><i class="bi bi-calendar3 me-2"></i>This Month</h6>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Recovered</span>
                        <span class="fw-bold text-success">₱{{ number_format($stats['recovered_this_month'], 0) }}</span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Disposed</span>
                        <span class="fw-bold text-secondary">{{ $stats['disposed_this_month'] }} orders</span>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span class="text-muted">Lost Revenue</span>
                        <span class="fw-bold text-danger">₱{{ number_format($stats['loss_this_month'] ?? 0, 0) }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
// Select all checkbox
document.getElementById('selectAll')?.addEventListener('change', function() {
    document.querySelectorAll('.order-checkbox').forEach(cb => {
        cb.checked = this.checked;
    });
    updateBulkButton();
});

// Individual checkboxes
document.querySelectorAll('.order-checkbox').forEach(cb => {
    cb.addEventListener('change', updateBulkButton);
});

function updateBulkButton() {
    const checked = document.querySelectorAll('.order-checkbox:checked').length;
    const btn = document.getElementById('bulkReminderBtn');
    btn.disabled = checked === 0;
    btn.innerHTML = checked > 0
        ? `<i class="bi bi-send me-1"></i> Send (${checked})`
        : `<i class="bi bi-send me-1"></i> Send Selected`;
}

// Bulk send
document.getElementById('bulkReminderBtn')?.addEventListener('click', function() {
    if (confirm('Send reminders to all selected customers?')) {
        document.getElementById('bulkForm').submit();
    }
});
</script>
@endpush
