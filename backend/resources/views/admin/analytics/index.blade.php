@extends('admin.layouts.app')

@section('title', 'Analytics Dashboard')
@section('page-title', 'Analytics Dashboard')

@section('content')
<div class="container-fluid px-4 py-4">
    {{-- Header with Date Range --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <p class="text-muted small">Comprehensive business insights and performance metrics</p>
        </div>
        <div>
            <button class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#dateRangeModal">
                <i class="bi bi-calendar-range me-2"></i>{{ \Carbon\Carbon::parse($startDate)->format('M d') }} - {{ \Carbon\Carbon::parse($endDate)->format('M d, Y') }}
            </button>
        </div>
    </div>

    {{-- Key Metrics Row --}}
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm rounded-4 h-100" style="background: linear-gradient(135deg, #3D3B6B 0%, #2D2850 100%);">
                <div class="card-body p-4 text-white">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div class="bg-white bg-opacity-20 p-3 rounded-3">
                            <i class="bi bi-cash-stack fs-3"></i>
                        </div>
                        @if($revenueAnalytics['growth_percentage'] != 0)
                        <span class="badge {{ $revenueAnalytics['growth_percentage'] > 0 ? 'bg-success' : 'bg-danger' }}">
                            <i class="bi bi-arrow-{{ $revenueAnalytics['growth_percentage'] > 0 ? 'up' : 'down' }}"></i>
                            {{ abs($revenueAnalytics['growth_percentage']) }}%
                        </span>
                        @endif
                    </div>
                    <h6 class="mb-2 opacity-75">Total Revenue</h6>
                    <h2 class="fw-bold mb-0">₱{{ number_format($revenueAnalytics['total'], 2) }}</h2>
                    <small class="opacity-75">vs previous period</small>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card border-0 shadow-sm rounded-4 h-100 border-start border-primary border-4">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div class="bg-primary bg-opacity-10 p-3 rounded-3">
                            <i class="bi bi-box-seam fs-3 text-primary"></i>
                        </div>
                    </div>
                    <h6 class="text-muted mb-2">Total Orders</h6>
                    <h2 class="fw-bold mb-2">{{ number_format($orderAnalytics['total']) }}</h2>
                    <div class="progress" style="height: 4px;">
                        <div class="progress-bar bg-primary" style="width: {{ $orderAnalytics['completion_rate'] }}%"></div>
                    </div>
                    <small class="text-muted">{{ $orderAnalytics['completion_rate'] }}% completion rate</small>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card border-0 shadow-sm rounded-4 h-100 border-start border-success border-4">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div class="bg-success bg-opacity-10 p-3 rounded-3">
                            <i class="bi bi-people fs-3 text-success"></i>
                        </div>
                    </div>
                    <h6 class="text-muted mb-2">Total Customers</h6>
                    <h2 class="fw-bold mb-2">{{ number_format($customerAnalytics['total']) }}</h2>
                    <small class="text-success fw-semibold">+{{ number_format($customerAnalytics['new']) }} new</small>
                    <small class="text-muted"> this period</small>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card border-0 shadow-sm rounded-4 h-100 border-start border-warning border-4">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div class="bg-warning bg-opacity-10 p-3 rounded-3">
                            <i class="bi bi-graph-up-arrow fs-3 text-warning"></i>
                        </div>
                    </div>
                    <h6 class="text-muted mb-2">Avg Order Value</h6>
                    <h2 class="fw-bold mb-2">₱{{ number_format($revenueAnalytics['average_order_value'], 2) }}</h2>
                    <small class="text-muted">Per order revenue</small>
                </div>
            </div>
        </div>
    </div>

    {{-- Revenue Trend & Orders Comparison --}}
    <div class="row g-3 mb-4">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-header bg-white border-bottom py-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <h6 class="mb-0 fw-bold text-dark">
                            <i class="bi bi-graph-up me-2" style="color: #3D3B6B;"></i>Revenue Trend
                        </h6>
                        <div class="btn-group btn-group-sm">
                            <button class="btn btn-outline-secondary active">Daily</button>
                            <button class="btn btn-outline-secondary">Weekly</button>
                            <button class="btn btn-outline-secondary">Monthly</button>
                        </div>
                    </div>
                </div>
                <div class="card-body p-4">
                    <canvas id="revenueTrendChart" height="80"></canvas>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-header bg-white border-bottom py-3">
                    <h6 class="mb-0 fw-bold text-dark">
                        <i class="bi bi-pie-chart me-2" style="color: #3D3B6B;"></i>Order Status
                    </h6>
                </div>
                <div class="card-body p-4">
                    <canvas id="orderStatusChart" height="200"></canvas>
                    <div class="mt-3 pt-3 border-top">
                        <div class="row g-2 text-center">
                            <div class="col-6">
                                <div class="p-2 bg-light rounded">
                                    <small class="text-muted d-block">Completed</small>
                                    <strong class="text-success">{{ $orderAnalytics['completed'] }}</strong>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="p-2 bg-light rounded">
                                    <small class="text-muted d-block">Pending</small>
                                    <strong class="text-warning">{{ $orderAnalytics['total'] - $orderAnalytics['completed'] }}</strong>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Branch Performance & Service Popularity --}}
    <div class="row g-3 mb-4">
        <div class="col-lg-7">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-header bg-white border-bottom py-3">
                    <h6 class="mb-0 fw-bold text-dark">
                        <i class="bi bi-building me-2" style="color: #3D3B6B;"></i>Branch Performance
                    </h6>
                </div>
                <div class="card-body p-4">
                    <canvas id="branchRevenueChart" height="80"></canvas>
                </div>
                <div class="card-footer bg-light border-0">
                    <div class="row g-2 text-center">
                        @foreach($branchPerformance['branches'] as $index => $branch)
                        <div class="col-4">
                            <small class="text-muted d-block">{{ $branch['name'] }}</small>
                            <strong>₱{{ number_format($branch['revenue'], 0) }}</strong>
                            <br><small class="text-muted">{{ $branch['orders'] }} orders</small>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-header bg-white border-bottom py-3">
                    <h6 class="mb-0 fw-bold text-dark">
                        <i class="bi bi-star me-2" style="color: #3D3B6B;"></i>Service Popularity
                    </h6>
                </div>
                <div class="card-body p-4">
                    @foreach($servicePopularity['services'] as $index => $service)
                    <div class="mb-3 {{ $loop->last ? '' : 'pb-3 border-bottom' }}">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <div>
                                <strong class="text-dark">{{ $service['name'] }}</strong>
                            </div>
                            <span class="badge bg-light text-dark">{{ $service['orders'] }} orders</span>
                        </div>
                        <div class="progress" style="height: 8px;">
                            @php
                                $percentage = $servicePopularity['services'][0]['orders'] > 0
                                    ? ($service['orders'] / $servicePopularity['services'][0]['orders']) * 100
                                    : 0;
                            @endphp
                            <div class="progress-bar" style="width: {{ $percentage }}%; background: linear-gradient(135deg, #3D3B6B 0%, #6366F1 100%);"></div>
                        </div>
                        <small class="text-muted">₱{{ number_format($service['revenue'], 2) }} revenue</small>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    {{-- Customer Analytics & Top Customers --}}
    <div class="row g-3 mb-4">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-header bg-white border-bottom py-3">
                    <h6 class="mb-0 fw-bold text-dark">
                        <i class="bi bi-people me-2" style="color: #3D3B6B;"></i>Customer Growth
                    </h6>
                </div>
                <div class="card-body p-4">
                    <canvas id="customerGrowthChart" height="60"></canvas>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-header bg-white border-bottom py-3">
                    <h6 class="mb-0 fw-bold text-dark">
                        <i class="bi bi-trophy me-2" style="color: #FFD700;"></i>Top Customers
                    </h6>
                </div>
                <div class="card-body p-0">
                    <div class="list-group list-group-flush">
                        @forelse($customerAnalytics['top_customers'] as $index => $customer)
                        <div class="list-group-item border-0 py-3">
                            <div class="d-flex align-items-center">
                                <div class="me-3">
                                    <div class="bg-primary bg-opacity-10 rounded-circle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                        <strong style="color: #3D3B6B;">{{ $index + 1 }}</strong>
                                    </div>
                                </div>
                                <div class="flex-grow-1">
                                    <strong class="d-block text-dark">{{ $customer->name }}</strong>
                                    <small class="text-muted">{{ Str::limit($customer->email, 20) }}</small>
                                </div>
                                <div class="text-end">
                                    <strong class="d-block" style="color: #3D3B6B;">₱{{ number_format($customer->orders_sum_total_amount ?? 0, 0) }}</strong>
                                    <small class="text-muted">{{ $customer->orders_count ?? 0 }} orders</small>
                                </div>
                            </div>
                        </div>
                        @empty
                        <div class="text-center py-5">
                            <i class="bi bi-people text-muted" style="font-size: 3rem; opacity: 0.3;"></i>
                            <p class="text-muted mb-0 mt-2">No customer data</p>
                        </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Promotion Effectiveness --}}
    <div class="row g-3 mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-header bg-white border-bottom py-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <h6 class="mb-0 fw-bold text-dark">
                            <i class="bi bi-megaphone me-2" style="color: #3D3B6B;"></i>Promotion Effectiveness
                        </h6>
                        <a href="{{ route('admin.promotions.index') }}" class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-plus-circle me-1"></i>Create Promotion
                        </a>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-light text-muted small text-uppercase">
                                <tr>
                                    <th class="ps-4" style="width: 30%;">Promotion Name</th>
                                    <th class="text-center">Type</th>
                                    <th class="text-center">Status</th>
                                    <th class="text-end">Usage</th>
                                    <th class="text-end">Revenue</th>
                                    <th class="text-end">Discount</th>
                                    <th class="text-end pe-4">ROI</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($promotionEffectiveness['promotions'] as $promo)
                                <tr>
                                    <td class="ps-4">
                                        <div>
                                            <strong class="text-dark">{{ $promo['name'] }}</strong>
                                            @if($promo['type'] === 'poster_promo')
                                                <br><small class="text-muted">Poster Promotion</small>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-light text-dark border">
                                            {{ ucfirst(str_replace('_', ' ', $promo['type'])) }}
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        @if($promo['is_active'])
                                            <span class="badge bg-success rounded-pill">Active</span>
                                        @else
                                            <span class="badge bg-secondary rounded-pill">Inactive</span>
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        <strong>{{ number_format($promo['usage_count']) }}</strong>
                                    </td>
                                    <td class="text-end">
                                        <strong style="color: #3D3B6B;">₱{{ number_format($promo['revenue'], 2) }}</strong>
                                    </td>
                                    <td class="text-end text-danger">
                                        -₱{{ number_format($promo['total_discount'], 2) }}
                                    </td>
                                    <td class="text-end pe-4">
                                        @php
                                            $roi = $promo['total_discount'] > 0
                                                ? ($promo['revenue'] / $promo['total_discount'])
                                                : 0;
                                        @endphp
                                        <span class="badge {{ $roi > 3 ? 'bg-success' : ($roi > 1.5 ? 'bg-warning' : 'bg-danger') }} rounded-pill px-3">
                                            {{ number_format($roi, 2) }}x
                                        </span>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="7" class="text-center py-5">
                                        <i class="bi bi-megaphone text-muted" style="font-size: 3rem; opacity: 0.3;"></i>
                                        <p class="text-muted mb-0 mt-2">No promotion data available</p>
                                        <a href="{{ route('admin.promotions.create') }}" class="btn btn-sm btn-primary mt-2" style="background: #3D3B6B; border: none;">
                                            Create Your First Promotion
                                        </a>
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Processing Time & Performance Metrics --}}
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm rounded-4 text-center">
                <div class="card-body p-4">
                    <div class="bg-info bg-opacity-10 p-3 rounded-3 d-inline-block mb-3">
                        <i class="bi bi-clock-history fs-1 text-info"></i>
                    </div>
                    <h6 class="text-muted mb-2">Avg Processing Time</h6>
                    <h2 class="fw-bold mb-0">{{ $orderAnalytics['avg_processing_time_hours'] }}</h2>
                    <small class="text-muted">hours</small>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card border-0 shadow-sm rounded-4 text-center">
                <div class="card-body p-4">
                    <div class="bg-success bg-opacity-10 p-3 rounded-3 d-inline-block mb-3">
                        <i class="bi bi-check-circle fs-1 text-success"></i>
                    </div>
                    <h6 class="text-muted mb-2">Completion Rate</h6>
                    <h2 class="fw-bold mb-0">{{ $orderAnalytics['completion_rate'] }}%</h2>
                    <div class="progress mt-2" style="height: 6px;">
                        <div class="progress-bar bg-success" style="width: {{ $orderAnalytics['completion_rate'] }}%"></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card border-0 shadow-sm rounded-4 text-center">
                <div class="card-body p-4">
                    <div class="bg-primary bg-opacity-10 p-3 rounded-3 d-inline-block mb-3">
                        <i class="bi bi-receipt fs-1 text-primary"></i>
                    </div>
                    <h6 class="text-muted mb-2">Avg Orders/Customer</h6>
                    <h2 class="fw-bold mb-0">{{ $customerAnalytics['avg_orders_per_customer'] }}</h2>
                    <small class="text-muted">orders</small>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card border-0 shadow-sm rounded-4 text-center">
                <div class="card-body p-4">
                    <div class="bg-warning bg-opacity-10 p-3 rounded-3 d-inline-block mb-3">
                        <i class="bi bi-arrow-up-right-circle fs-1 text-warning"></i>
                    </div>
                    <h6 class="text-muted mb-2">Revenue Growth</h6>
                    <h2 class="fw-bold mb-0 {{ $revenueAnalytics['growth_percentage'] > 0 ? 'text-success' : 'text-danger' }}">
                        {{ $revenueAnalytics['growth_percentage'] > 0 ? '+' : '' }}{{ $revenueAnalytics['growth_percentage'] }}%
                    </h2>
                    <small class="text-muted">vs previous</small>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Date Range Modal --}}
<div class="modal fade" id="dateRangeModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-bottom">
                <h5 class="modal-title fw-bold">Select Date Range</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="GET" action="{{ route('admin.analytics') }}">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Start Date</label>
                        <input type="date" class="form-control" name="start_date" value="{{ $startDate }}">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">End Date</label>
                        <input type="date" class="form-control" name="end_date" value="{{ $endDate }}">
                    </div>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-outline-secondary btn-sm" onclick="setQuickRange('today')">Today</button>
                        <button type="button" class="btn btn-outline-secondary btn-sm" onclick="setQuickRange('week')">This Week</button>
                        <button type="button" class="btn btn-outline-secondary btn-sm" onclick="setQuickRange('month')">This Month</button>
                        <button type="button" class="btn btn-outline-secondary btn-sm" onclick="setQuickRange('year')">This Year</button>
                    </div>
                </div>
                <div class="modal-footer border-top-0">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" style="background: #3D3B6B; border: none;">Apply</button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
// Chart.js default configuration
Chart.defaults.font.family = "'Inter', sans-serif";
Chart.defaults.color = '#6B7280';

// Color palette
const colors = {
    primary: '#3D3B6B',
    secondary: '#6366F1',
    success: '#10B981',
    danger: '#EF4444',
    warning: '#F59E0B',
    info: '#3B82F6'
};

// Revenue Trend Chart
const revenueTrendCtx = document.getElementById('revenueTrendChart').getContext('2d');
new Chart(revenueTrendCtx, {
    type: 'line',
    data: {
        labels: @json($revenueAnalytics['labels']),
        datasets: [{
            label: 'Revenue',
            data: @json($revenueAnalytics['data']),
            borderColor: colors.primary,
            backgroundColor: 'rgba(61, 59, 107, 0.1)',
            tension: 0.4,
            fill: true,
            borderWidth: 3,
            pointRadius: 4,
            pointHoverRadius: 6,
            pointBackgroundColor: '#fff',
            pointBorderColor: colors.primary,
            pointBorderWidth: 2
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: true,
        plugins: {
            legend: { display: false },
            tooltip: {
                backgroundColor: '#fff',
                titleColor: '#1F2937',
                bodyColor: '#6B7280',
                borderColor: '#E5E7EB',
                borderWidth: 1,
                padding: 12,
                boxPadding: 6,
                usePointStyle: true,
                callbacks: {
                    label: function(context) {
                        return ' Revenue: ₱' + context.parsed.y.toLocaleString('en-PH', {minimumFractionDigits: 2});
                    }
                }
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                grid: {
                    color: '#F3F4F6',
                    drawBorder: false
                },
                ticks: {
                    callback: function(value) {
                        return '₱' + (value / 1000) + 'k';
                    },
                    padding: 12
                }
            },
            x: {
                grid: {
                    display: false,
                    drawBorder: false
                },
                ticks: {
                    padding: 8
                }
            }
        }
    }
});

// Order Status Doughnut Chart
const orderStatusCtx = document.getElementById('orderStatusChart').getContext('2d');
new Chart(orderStatusCtx, {
    type: 'doughnut',
    data: {
        labels: @json($orderAnalytics['status_labels']),
        datasets: [{
            data: @json($orderAnalytics['status_data']),
            backgroundColor: [
                colors.primary,
                colors.secondary,
                colors.success,
                colors.warning,
                colors.info,
                colors.danger
            ],
            borderWidth: 0,
            cutout: '70%'
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: true,
        plugins: {
            legend: {
                position: 'bottom',
                labels: {
                    padding: 15,
                    usePointStyle: true,
                    pointStyle: 'circle',
                    font: {
                        size: 11
                    }
                }
            },
            tooltip: {
                backgroundColor: '#fff',
                titleColor: '#1F2937',
                bodyColor: '#6B7280',
                borderColor: '#E5E7EB',
                borderWidth: 1,
                padding: 12,
                usePointStyle: true
            }
        }
    }
});

// Branch Revenue Bar Chart
const branchRevenueCtx = document.getElementById('branchRevenueChart').getContext('2d');
new Chart(branchRevenueCtx, {
    type: 'bar',
    data: {
        labels: @json($branchPerformance['labels']),
        datasets: [{
            label: 'Revenue',
            data: @json($branchPerformance['revenue_data']),
            backgroundColor: colors.primary,
            borderRadius: 8,
            borderSkipped: false
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: true,
        plugins: {
            legend: { display: false },
            tooltip: {
                backgroundColor: '#fff',
                titleColor: '#1F2937',
                bodyColor: '#6B7280',
                borderColor: '#E5E7EB',
                borderWidth: 1,
                padding: 12,
                callbacks: {
                    label: function(context) {
                        return 'Revenue: ₱' + context.parsed.y.toLocaleString('en-PH', {minimumFractionDigits: 2});
                    }
                }
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                grid: {
                    color: '#F3F4F6',
                    drawBorder: false
                },
                ticks: {
                    callback: function(value) {
                        return '₱' + (value / 1000) + 'k';
                    },
                    padding: 12
                }
            },
            x: {
                grid: {
                    display: false,
                    drawBorder: false
                },
                ticks: {
                    padding: 8
                }
            }
        }
    }
});

// Customer Growth Area Chart
const customerGrowthCtx = document.getElementById('customerGrowthChart').getContext('2d');
new Chart(customerGrowthCtx, {
    type: 'line',
    data: {
        labels: @json($customerAnalytics['growth_labels']),
        datasets: [{
            label: 'New Customers',
            data: @json($customerAnalytics['growth_data']),
            borderColor: colors.success,
            backgroundColor: 'rgba(16, 185, 129, 0.1)',
            tension: 0.4,
            fill: true,
            borderWidth: 3,
            pointRadius: 4,
            pointHoverRadius: 6,
            pointBackgroundColor: '#fff',
            pointBorderColor: colors.success,
            pointBorderWidth: 2
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: true,
        plugins: {
            legend: { display: false },
            tooltip: {
                backgroundColor: '#fff',
                titleColor: '#1F2937',
                bodyColor: '#6B7280',
                borderColor: '#E5E7EB',
                borderWidth: 1,
                padding: 12,
                usePointStyle: true
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                grid: {
                    color: '#F3F4F6',
                    drawBorder: false
                },
                ticks: {
                    stepSize: 1,
                    padding: 12
                }
            },
            x: {
                grid: {
                    display: false,
                    drawBorder: false
                },
                ticks: {
                    padding: 8
                }
            }
        }
    }
});

// Quick date range selector
function setQuickRange(range) {
    const now = new Date();
    const startInput = document.querySelector('input[name="start_date"]');
    const endInput = document.querySelector('input[name="end_date"]');

    let startDate, endDate;

    switch(range) {
        case 'today':
            startDate = endDate = now;
            break;
        case 'week':
            startDate = new Date(now.setDate(now.getDate() - now.getDay()));
            endDate = new Date();
            break;
        case 'month':
            startDate = new Date(now.getFullYear(), now.getMonth(), 1);
            endDate = new Date();
            break;
        case 'year':
            startDate = new Date(now.getFullYear(), 0, 1);
            endDate = new Date();
            break;
    }

    startInput.value = startDate.toISOString().split('T')[0];
    endInput.value = endDate.toISOString().split('T')[0];
}
</script>
@endpush

@push('styles')
<style>
    .stat-card {
        transition: transform 0.2s;
    }

    .stat-card:hover {
        transform: translateY(-4px);
    }

    .table-hover tbody tr:hover {
        background-color: rgba(61, 59, 107, 0.02);
    }

    .list-group-item {
        transition: background-color 0.2s;
    }

    .list-group-item:hover {
        background-color: rgba(61, 59, 107, 0.02);
    }
</style>
@endpush
@endsection
