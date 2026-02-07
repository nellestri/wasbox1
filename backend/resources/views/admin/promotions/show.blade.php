@extends('admin.layouts.app')

@section('page-title', 'Promotion Details - ' . $promotion->name)
@section('title', 'Promotion Analytics')

@section('content')
<div class="container-fluid px-4 py-4">
    {{-- Top Header Section --}}
    <div class="d-flex justify-content-between align-items-start mb-4">
        <div>
            <h2 class="fw-bold mb-1" style="color: #1F2937;">{{ $promotion->name }}</h2>
            <div class="d-flex align-items-center gap-2">
                <span class="badge {{ $promotion->getStatusBadgeClass() }} rounded-pill px-3 py-2">
                    <i class="bi bi-circle-fill me-1" style="font-size: 0.5rem;"></i>
                    {{ strtoupper($promotion->getStatus()) }}
                </span>
                <span class="text-muted small border-start ps-2">
                    <i class="bi bi-geo-alt me-1"></i> {{ $promotion->branch->name ?? 'All Branches' }}
                </span>
                <span class="text-muted small border-start ps-2">
                    <i class="bi bi-calendar3 me-1"></i>
                    {{ $promotion->start_date->format('M d') }} - {{ $promotion->end_date->format('M d, Y') }}
                </span>
            </div>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.promotions.edit', $promotion) }}" class="btn btn-primary shadow-sm" style="background: #3D3B6B; border: none;">
                <i class="bi bi-pencil me-2"></i>Edit Campaign
            </a>
            <a href="{{ route('admin.promotions.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-2"></i>Back to List
            </a>
        </div>
    </div>

    {{-- Analytics Row --}}
    <div class="row g-4 mb-4">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm rounded-4 p-3 bg-white border-start border-4 border-success">
                <small class="text-muted fw-bold text-uppercase" style="font-size: 0.7rem;">Gross Revenue</small>
                <h3 class="fw-bold mb-0 text-dark">₱{{ number_format($promotion->usages->sum('final_amount'), 2) }}</h3>
                <small class="text-success small"><i class="bi bi-graph-up me-1"></i>Total sales value</small>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm rounded-4 p-3 bg-white border-start border-4 border-danger">
                <small class="text-muted fw-bold text-uppercase" style="font-size: 0.7rem;">Discount Value</small>
                <h3 class="fw-bold mb-0 text-dark">₱{{ number_format($promotion->usages->sum('discount_amount'), 2) }}</h3>
                <small class="text-danger small"><i class="bi bi-tags me-1"></i>Total savings given</small>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm rounded-4 p-3 bg-white border-start border-4 border-primary">
                <small class="text-muted fw-bold text-uppercase" style="font-size: 0.7rem;">Redemptions</small>
                <h3 class="fw-bold mb-0 text-dark">{{ $promotion->usage_count }}</h3>
                <small class="text-muted small">Of {{ $promotion->max_usage ?? '∞' }} limit</small>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm rounded-4 p-3 bg-white border-start border-4 border-warning">
                <small class="text-muted fw-bold text-uppercase" style="font-size: 0.7rem;">Avg. Transaction</small>
                @php $avg = $promotion->usage_count > 0 ? $promotion->usages->avg('final_amount') : 0; @endphp
                <h3 class="fw-bold mb-0 text-dark">₱{{ number_format($avg, 2) }}</h3>
                <small class="text-muted small">Per customer use</small>
            </div>
        </div>
    </div>

    <div class="row g-4">
        {{-- Left Side: Poster & Info --}}
        <div class="col-lg-4">
            @if($promotion->isPosterPromotion())
                <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4">
                    <div class="card-header bg-white py-3 border-bottom">
                        <h6 class="fw-bold mb-0">Active Poster Design</h6>
                    </div>
                    {{-- Visual Poster --}}
                    <div id="poster-download-area" class="position-relative"
                        style="width: 100%; aspect-ratio: 1/1; background: {{ $promotion->getColorGradient() }};">

                        <div class="d-flex flex-column justify-content-center align-items-center h-100 p-4 text-center text-white">
                            <h4 class="fw-bold mb-0" style="letter-spacing: 2px;">WASHBOX</h4>
                            <div class="fw-bold mb-2" style="font-size: 1.3rem;">{{ strtoupper($promotion->poster_title) }}</div>

                            @if($promotion->poster_subtitle)
                                <div class="mb-3" style="font-size: 0.9rem; background: rgba(255,255,255,0.2); padding: 4px 16px; border-radius: 20px;">
                                    {{ strtoupper($promotion->poster_subtitle) }}
                                </div>
                            @endif

                            <div class="d-flex align-items-center gap-2 mb-2">
                                <div style="background: #1F2937; color: white; padding: 4px 12px; border-radius: 50%; font-weight: bold;">₱</div>
                                <div style="font-size: 4rem; font-weight: bold; line-height: 1;">{{ number_format($promotion->display_price, 0) }}</div>
                            </div>

                            <div class="fw-bold mb-3 small">{{ strtoupper($promotion->price_unit) }}</div>

                            <div class="d-flex flex-wrap justify-content-center gap-2 mb-3">
                                @foreach($promotion->poster_features ?? [] as $feature)
                                    <span class="badge bg-white text-dark shadow-sm" style="font-size: 0.7rem; padding: 6px 12px; border-radius: 50px;">
                                        {{ $feature }}
                                    </span>
                                @endforeach
                            </div>

                            <div class="small opacity-75 fw-light" style="font-size: 0.7rem;">{{ $promotion->poster_notes }}</div>
                        </div>
                    </div>
                    <div class="card-body bg-light border-top">
                        <button class="btn btn-success w-100 shadow-sm" id="downloadPosterBtn">
                            <i class="bi bi-download me-2"></i>Download for Social Media
                        </button>
                    </div>
                </div>
            @endif

            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-header bg-white py-3 border-bottom">
                    <h6 class="fw-bold mb-0 text-dark">Internal Details</h6>
                </div>
                <div class="card-body">
                    <p class="text-muted small mb-3">{{ $promotion->description ?: 'No internal description provided.' }}</p>
                    <hr>
                    <div class="mb-2">
                        <small class="text-muted d-block">Promo Code</small>
                        <span class="fw-bold text-primary">{{ $promotion->promo_code ?: 'None (Auto-applied)' }}</span>
                    </div>
                    <div class="mb-0">
                        <small class="text-muted d-block">Type</small>
                        <span class="fw-bold">{{ $promotion->getTypeLabel() }}</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Right Side: Usage Table --}}
        <div class="{{ $promotion->isPosterPromotion() ? 'col-lg-8' : 'col-12' }}">
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                    <h6 class="fw-bold mb-0">
                        <i class="bi bi-clock-history me-2 text-primary"></i>Recent Redemption History
                    </h6>
                    <span class="badge bg-light text-dark border">{{ $promotion->usages->count() }} Entries</span>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-light small text-uppercase text-muted">
                                <tr>
                                    <th class="ps-4">Date & Time</th>
                                    <th>Customer</th>
                                    <th>Order Details</th>
                                    <th>Savings</th>
                                    <th class="pe-4 text-end">Amount Paid</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($promotion->usages as $usage)
                                <tr>
                                    <td class="ps-4">
                                        <div class="fw-bold text-dark">{{ $usage->created_at->format('M d, Y') }}</div>
                                        <small class="text-muted">{{ $usage->created_at->format('h:i A') }}</small>
                                    </td>
                                    <td>
                                        <div class="fw-bold">{{ $usage->user->name ?? 'Guest Customer' }}</div>
                                        <small class="text-muted">{{ $usage->user->phone ?? 'No contact' }}</small>
                                    </td>
                                    <td>
                                        <a href="{{ route('admin.orders.show', $usage->order_id) }}" class="text-decoration-none fw-semibold">
                                            #{{ $usage->order->order_number ?? $usage->order_id }}
                                        </a>
                                        <div class="x-small text-muted">Original: ₱{{ number_format($usage->original_amount, 2) }}</div>
                                    </td>
                                    <td>
                                        <span class="text-danger fw-bold">-₱{{ number_format($usage->discount_amount, 2) }}</span>
                                    </td>
                                    <td class="pe-4 text-end">
                                        <span class="fw-bold text-dark">₱{{ number_format($usage->final_amount, 2) }}</span>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="5" class="text-center py-5">
                                        <i class="bi bi-inbox display-4 text-muted opacity-25"></i>
                                        <p class="text-muted mt-2">No transactions recorded for this promotion yet.</p>
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                @if($promotion->usages->isNotEmpty())
                    <div class="card-footer bg-light py-3 border-top-0">
                        <div class="row text-center">
                            <div class="col-6 border-end">
                                <small class="text-muted d-block text-uppercase" style="font-size: 0.65rem;">Total Discounts</small>
                                <span class="fw-bold text-danger">₱{{ number_format($promotion->usages->sum('discount_amount'), 2) }}</span>
                            </div>
                            <div class="col-6">
                                <small class="text-muted d-block text-uppercase" style="font-size: 0.65rem;">Net Collected</small>
                                <span class="fw-bold text-success">₱{{ number_format($promotion->usages->sum('final_amount'), 2) }}</span>
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
<script>
document.getElementById('downloadPosterBtn')?.addEventListener('click', function() {
    const btn = this;
    const posterArea = document.getElementById('poster-download-area');

    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Generating...';

    html2canvas(posterArea, {
        scale: 3, // High quality
        useCORS: true,
        backgroundColor: null
    }).then(canvas => {
        const link = document.createElement('a');
        const filename = "{{ Str::slug($promotion->name) }}-poster.png";
        link.download = filename;
        link.href = canvas.toDataURL('image/png');
        link.click();

        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-download me-2"></i>Download for Social Media';
    });
});
</script>
@endpush

<style>
    .x-small { font-size: 0.7rem; }
    .table-hover tbody tr:hover { background-color: rgba(61, 59, 107, 0.02); }
    .card { transition: transform 0.2s; }
</style>
@endsection
