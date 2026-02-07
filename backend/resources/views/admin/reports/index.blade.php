@extends('admin.layouts.app')

@section('title', 'Reports')

@section('content')
<div class="container-fluid px-4 py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="mb-0 fw-bold">Reports & Analytics</h2>
            <p class="text-muted mb-0">View and export business reports</p>
        </div>
    </div>

    {{-- Stats Cards --}}
    <div class="row g-4 mb-4">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="stat-icon bg-primary bg-opacity-10 text-primary me-3">
                            <i class="bi bi-currency-dollar"></i>
                        </div>
                        <div>
                            <div class="text-muted small">Total Revenue</div>
                            <h4 class="mb-0 fw-bold">₱{{ number_format($stats['total_revenue'], 2) }}</h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="stat-icon bg-success bg-opacity-10 text-success me-3">
                            <i class="bi bi-basket"></i>
                        </div>
                        <div>
                            <div class="text-muted small">Total Orders</div>
                            <h4 class="mb-0 fw-bold">{{ number_format($stats['total_orders']) }}</h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="stat-icon bg-info bg-opacity-10 text-info me-3">
                            <i class="bi bi-people"></i>
                        </div>
                        <div>
                            <div class="text-muted small">Total Customers</div>
                            <h4 class="mb-0 fw-bold">{{ number_format($stats['total_customers']) }}</h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="stat-icon bg-warning bg-opacity-10 text-warning me-3">
                            <i class="bi bi-shop"></i>
                        </div>
                        <div>
                            <div class="text-muted small">Active Branches</div>
                            <h4 class="mb-0 fw-bold">{{ number_format($stats['active_branches']) }}</h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Report Types --}}
    <div class="row g-4">
        <div class="col-md-6 col-lg-3">
            <div class="card border-0 shadow-sm h-100 hover-lift">
                <div class="card-body text-center p-4">
                    <div class="report-icon mb-3">
                        <i class="bi bi-graph-up text-primary" style="font-size: 3rem;"></i>
                    </div>
                    <h5 class="mb-2">Revenue Report</h5>
                    <p class="text-muted small mb-3">View revenue trends and analytics</p>
                    <a href="{{ route('admin.reports.revenue') }}" class="btn btn-outline-primary btn-sm">
                        View Report
                    </a>
                </div>
            </div>
        </div>

        <div class="col-md-6 col-lg-3">
            <div class="card border-0 shadow-sm h-100 hover-lift">
                <div class="card-body text-center p-4">
                    <div class="report-icon mb-3">
                        <i class="bi bi-basket text-success" style="font-size: 3rem;"></i>
                    </div>
                    <h5 class="mb-2">Orders Report</h5>
                    <p class="text-muted small mb-3">Detailed order history and status</p>
                    <a href="{{ route('admin.reports.orders') }}" class="btn btn-outline-success btn-sm">
                        View Report
                    </a>
                </div>
            </div>
        </div>

        <div class="col-md-6 col-lg-3">
            <div class="card border-0 shadow-sm h-100 hover-lift">
                <div class="card-body text-center p-4">
                    <div class="report-icon mb-3">
                        <i class="bi bi-people text-info" style="font-size: 3rem;"></i>
                    </div>
                    <h5 class="mb-2">Customers Report</h5>
                    <p class="text-muted small mb-3">Customer analytics and insights</p>
                    <a href="{{ route('admin.reports.customers') }}" class="btn btn-outline-info btn-sm">
                        View Report
                    </a>
                </div>
            </div>
        </div>

        <div class="col-md-6 col-lg-3">
            <div class="card border-0 shadow-sm h-100 hover-lift">
                <div class="card-body text-center p-4">
                    <div class="report-icon mb-3">
                        <i class="bi bi-shop text-warning" style="font-size: 3rem;"></i>
                    </div>
                    <h5 class="mb-2">Branches Report</h5>
                    <p class="text-muted small mb-3">Branch performance comparison</p>
                    <a href="{{ route('admin.reports.branches') }}" class="btn btn-outline-warning btn-sm">
                        View Report
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.stat-icon {
    width: 56px;
    height: 56px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
}

.hover-lift {
    transition: transform 0.2s, box-shadow 0.2s;
}

.hover-lift:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 16px rgba(0,0,0,0.1) !important;
}
</style>
@endsection
