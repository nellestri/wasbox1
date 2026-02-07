@extends('staff.layouts.staff')

@section('title', 'Unclaimed Laundry')
@section('page-title', 'Unclaimed Laundry')

@section('content')
<div class="container-fluid">

    {{-- Header --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold mb-1">
                <i class="bi bi-exclamation-triangle text-warning me-2"></i>Unclaimed Laundry
            </h4>
            <p class="text-muted mb-0">Manage and follow up on unclaimed orders to recover revenue</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('staff.unclaimed.export') }}" class="btn btn-outline-success">
                <i class="bi bi-download me-1"></i> Export CSV
            </a>
            <button type="button" class="btn btn-primary" id="bulkReminderBtn" disabled>
                <i class="bi bi-send me-1"></i> Send Selected
            </button>
        </div>
    </div>

    {{-- Stats Cards --}}
    <div class="row g-3 mb-4">
        {{-- Total Unclaimed --}}
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body text-center py-3">
                    <div class="rounded-circle bg-warning bg-opacity-10 d-inline-flex align-items-center justify-content-center mb-2" style="width: 48px; height: 48px;">
                        <i class="bi bi-inbox fs-4 text-warning"></i>
                    </div>
                    <h3 class="fw-bold mb-0">{{ $stats['total'] }}</h3>
                    <small class="text-muted">Total Unclaimed</small>
                </div>
            </div>
        </div>

        {{-- Critical (14+ days) --}}
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm h-100 border-start border-4 border-danger">
                <div class="card-body text-center py-3">
                    <div class="rounded-circle bg-danger bg-opacity-10 d-inline-flex align-items-center justify-content-center mb-2" style="width: 48px; height: 48px;">
                        <i class="bi bi-exclamation-octagon fs-4 text-danger"></i>
                    </div>
                    <h3 class="fw-bold mb-0 text-danger">{{ $stats['critical'] }}</h3>
                    <small class="text-muted">Critical (14+ days)</small>
                </div>
            </div>
        </div>

        {{-- Value at Risk --}}
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body text-center py-3">
                    <div class="rounded-circle bg-primary bg-opacity-10 d-inline-flex align-items-center justify-content-center mb-2" style="width: 48px; height: 48px;">
                        <i class="bi bi-currency-dollar fs-4 text-primary"></i>
                    </div>
                    <h3 class="fw-bold mb-0">₱{{ number_format($stats['total_value'], 0) }}</h3>
                    <small class="text-muted">Value at Risk</small>
                </div>
            </div>
        </div>

        {{-- Reminders Today --}}
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body text-center py-3">
                    <div class="rounded-circle bg-success bg-opacity-10 d-inline-flex align-items-center justify-content-center mb-2" style="width: 48px; height: 48px;">
                        <i class="bi bi-send-check fs-4 text-success"></i>
                    </div>
                    <h3 class="fw-bold mb-0">{{ $stats['reminders_today'] }}</h3>
                    <small class="text-muted">Reminders Today</small>
                </div>
            </div>
        </div>
    </div>

    {{-- Urgency Summary --}}
    <div class="row g-3 mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-body py-3">
                    <div class="row text-center">
                        <div class="col">
                            <a href="{{ route('staff.unclaimed.index', ['urgency' => 'critical']) }}" class="text-decoration-none">
                                <span class="badge bg-danger fs-5 mb-1">{{ $stats['critical'] }}</span>
                                <div class="small text-muted">🚨 Critical (14+ days)</div>
                            </a>
                        </div>
                        <div class="col">
                            <a href="{{ route('staff.unclaimed.index', ['urgency' => 'urgent']) }}" class="text-decoration-none">
                                <span class="badge bg-warning text-dark fs-5 mb-1">{{ $stats['urgent'] }}</span>
                                <div class="small text-muted">⚠️ Urgent (7-13 days)</div>
                            </a>
                        </div>
                        <div class="col">
                            <a href="{{ route('staff.unclaimed.index', ['urgency' => 'warning']) }}" class="text-decoration-none">
                                <span class="badge bg-info fs-5 mb-1">{{ $stats['warning'] }}</span>
                                <div class="small text-muted">⏰ Warning (3-6 days)</div>
                            </a>
                        </div>
                        <div class="col">
                            <a href="{{ route('staff.unclaimed.index', ['urgency' => 'pending']) }}" class="text-decoration-none">
                                <span class="badge bg-secondary fs-5 mb-1">{{ $stats['pending'] }}</span>
                                <div class="small text-muted">📌 Pending (1-2 days)</div>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Filters --}}
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body py-3">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-md-4">
                    <label class="form-label small">Search</label>
                    <input type="text" name="search" class="form-control"
                           placeholder="Tracking #, customer name, phone..."
                           value="{{ request('search') }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label small">Urgency</label>
                    <select name="urgency" class="form-select">
                        <option value="">All</option>
                        <option value="critical" {{ request('urgency') == 'critical' ? 'selected' : '' }}>🚨 Critical (14+)</option>
                        <option value="urgent" {{ request('urgency') == 'urgent' ? 'selected' : '' }}>⚠️ Urgent (7-13)</option>
                        <option value="warning" {{ request('urgency') == 'warning' ? 'selected' : '' }}>⏰ Warning (3-6)</option>
                        <option value="pending" {{ request('urgency') == 'pending' ? 'selected' : '' }}>📌 Pending (1-2)</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small">Min Days</label>
                    <input type="number" name="min_days" class="form-control"
                           placeholder="0" min="0" value="{{ request('min_days') }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label small">Max Days</label>
                    <input type="number" name="max_days" class="form-control"
                           placeholder="30" min="0" value="{{ request('max_days') }}">
                </div>
                <div class="col-md-2 d-flex gap-2">
                    <button type="submit" class="btn btn-primary flex-grow-1">
                        <i class="bi bi-search"></i>
                    </button>
                    <a href="{{ route('staff.unclaimed.index') }}" class="btn btn-outline-secondary">
                        <i class="bi bi-x-lg"></i>
                    </a>
                </div>
            </form>
        </div>
    </div>

    {{-- Orders Table --}}
    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <form id="bulkForm" action="{{ route('staff.unclaimed.bulk-reminders') }}" method="POST">
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
                                <th>Service</th>
                                <th class="text-end">Amount</th>
                                <th class="text-center">Days</th>
                                <th class="text-center">Status</th>
                                <th class="text-center">Reminders</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($orders as $order)
                                @php
                                    $days = $order->days_unclaimed;
                                    $urgency = $order->unclaimed_status;
                                    $color = $order->unclaimed_color;
                                @endphp
                                <tr class="{{ $urgency === 'critical' ? 'table-danger' : ($urgency === 'urgent' ? 'table-warning' : '') }}">
                                    <td>
                                        <input type="checkbox" class="form-check-input order-checkbox"
                                               name="order_ids[]" value="{{ $order->id }}">
                                    </td>
                                    <td>
                                        <a href="{{ route('staff.unclaimed.show', $order) }}" class="fw-semibold text-decoration-none">
                                            {{ $order->tracking_number }}
                                        </a>
                                        <div class="small text-muted">
                                            Ready: {{ $order->ready_at->format('M d, Y') }}
                                        </div>
                                    </td>
                                    <td>
                                        <div class="fw-semibold">{{ $order->customer->name }}</div>
                                        <div class="small text-muted">
                                            <a href="tel:{{ $order->customer->phone }}" class="text-decoration-none">
                                                <i class="bi bi-telephone me-1"></i>{{ $order->customer->phone }}
                                            </a>
                                        </div>
                                    </td>
                                    <td>{{ $order->service->name ?? 'N/A' }}</td>
                                    <td class="text-end fw-bold">₱{{ number_format($order->total_amount, 2) }}</td>
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
                                    <td class="text-center">
                                        <span class="badge bg-secondary">{{ $order->reminder_count ?? 0 }}</span>
                                        @if($order->last_reminder_at)
                                            <div class="small text-muted">{{ $order->last_reminder_at->diffForHumans() }}</div>
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        <div class="btn-group btn-group-sm">
                                            {{-- Call Customer --}}
                                            <a href="tel:{{ $order->customer->phone }}"
                                               class="btn btn-outline-success" title="Call Customer">
                                                <i class="bi bi-telephone"></i>
                                            </a>

                                            {{-- Send Reminder --}}
                                            <form action="{{ route('staff.unclaimed.send-reminder', $order) }}"
                                                  method="POST" class="d-inline">
                                                @csrf
                                                <button type="submit" class="btn btn-outline-primary" title="Send Push Notification">
                                                    <i class="bi bi-bell"></i>
                                                </button>
                                            </form>

                                            {{-- View Details --}}
                                            <a href="{{ route('staff.unclaimed.show', $order) }}"
                                               class="btn btn-outline-secondary" title="View Details">
                                                <i class="bi bi-eye"></i>
                                            </a>

                                            {{-- Mark Claimed --}}
                                            <form action="{{ route('staff.unclaimed.mark-claimed', $order) }}"
                                                  method="POST" class="d-inline">
                                                @csrf
                                                <button type="submit" class="btn btn-outline-success"
                                                        title="Mark as Claimed"
                                                        onclick="return confirm('Mark this order as claimed/paid?')">
                                                    <i class="bi bi-check-lg"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="text-center py-5">
                                        <i class="bi bi-emoji-smile fs-1 text-success d-block mb-2"></i>
                                        <h5 class="text-success">Great news!</h5>
                                        <p class="text-muted mb-0">No unclaimed laundry at the moment.</p>
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

    {{-- Recovery Tips --}}
    @if($stats['total'] > 0)
    <div class="card border-0 shadow-sm mt-4 bg-light">
        <div class="card-body">
            <h6 class="fw-bold mb-3"><i class="bi bi-lightbulb text-warning me-2"></i>Recovery Tips</h6>
            <div class="row">
                <div class="col-md-4">
                    <div class="d-flex align-items-start">
                        <span class="badge bg-primary me-2">1</span>
                        <div class="small">
                            <strong>Call first</strong> - Personal calls have higher success rates than notifications.
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="d-flex align-items-start">
                        <span class="badge bg-primary me-2">2</span>
                        <div class="small">
                            <strong>Be friendly</strong> - Remind customers their laundry is waiting, don't pressure.
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="d-flex align-items-start">
                        <span class="badge bg-primary me-2">3</span>
                        <div class="small">
                            <strong>Offer delivery</strong> - For critical cases, offer delivery service.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>

{{-- Call Log Modal --}}
<div class="modal fade" id="callLogModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Log Call Attempt</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="callLogOrderId">
                <div class="mb-3">
                    <label class="form-label">Call Result</label>
                    <select class="form-select" id="callResult" required>
                        <option value="">Select result...</option>
                        <option value="answered">✅ Answered - Will pickup</option>
                        <option value="no_answer">📵 No Answer</option>
                        <option value="busy">📞 Busy</option>
                        <option value="wrong_number">❌ Wrong Number</option>
                        <option value="voicemail">📼 Voicemail</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Notes (optional)</label>
                    <textarea class="form-control" id="callNotes" rows="2" placeholder="Any additional notes..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="submitCallLog()">Save Log</button>
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

// Call log modal
function openCallLog(orderId) {
    document.getElementById('callLogOrderId').value = orderId;
    document.getElementById('callResult').value = '';
    document.getElementById('callNotes').value = '';
    new bootstrap.Modal(document.getElementById('callLogModal')).show();
}

function submitCallLog() {
    const orderId = document.getElementById('callLogOrderId').value;
    const result = document.getElementById('callResult').value;
    const notes = document.getElementById('callNotes').value;

    if (!result) {
        alert('Please select a call result');
        return;
    }

    fetch(`/staff/unclaimed/${orderId}/log-call`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
        },
        body: JSON.stringify({ result, notes })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            bootstrap.Modal.getInstance(document.getElementById('callLogModal')).hide();
            location.reload();
        }
    });
}
</script>
@endpush
