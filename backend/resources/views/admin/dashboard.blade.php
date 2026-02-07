@extends('admin.layouts.app')

@section('title', 'Dashboard Analytics')

@section('content')

<div class="container-fluid px-4 py-4 dashboard-container">

    {{-- Dashboard Header with Stats --}}
    <div class="row mb-6 align-items-center">
        <div class="col-lg-8">
            <div class="d-flex align-items-center gap-3">
                <div class="dashboard-icon-container">
                    <i class="bi bi-speedometer2"></i>
                </div>
                <div>
                    <h1 class="h2 mb-1 fw-bold text-primary-dark">Dashboard Analytics</h1>
                    <p class="text-muted mb-0">
                        <i class="bi bi-calendar-check me-2"></i>
                        <span id="current-date">{{ now()->format('l, F j, Y') }}</span>
                        •
                        <span class="text-success">
                            <i class="bi bi-circle-fill me-1" style="font-size: 8px;"></i>
                            <span id="last-sync">Live</span>
                        </span>
                    </p>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="d-flex gap-2 justify-content-lg-end">
                <button onclick="refreshDashboard()" class="btn btn-sm rounded-pill btn-outline-primary d-flex align-items-center" id="refresh-btn">
                    <i class="bi bi-arrow-clockwise me-2"></i>
                    <span>Refresh</span>
                </button>
                <div class="dropdown">
                    <button class="btn btn-sm rounded-pill btn-danger d-flex align-items-center" type="button" data-bs-toggle="dropdown">
                        <i class="bi bi-download me-2"></i>
                        Export
                    </button>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="{{ route('admin.reports.index') }}"><i class="bi bi-file-pdf me-2"></i>View Reports</a></li>
                        <li><a class="dropdown-item" href="#" onclick="exportData('excel')"><i class="bi bi-file-excel me-2"></i>Export to Excel</a></li>
                        <li><a class="dropdown-item" href="#" onclick="exportData('csv')"><i class="bi bi-file-text me-2"></i>Export to CSV</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    {{-- System Status Cards --}}
    <div class="row g-4 mb-6">
        <div class="col-md-3">
            <div class="system-status-card bg-primary-gradient">
                <div class="d-flex align-items-center">
                    <div class="status-icon">
                        <i class="bi bi-database"></i>
                    </div>
                    <div class="ms-3">
                        <small class="text-white opacity-75">Database</small>
                        <h5 class="mb-0 text-white">{{ $stats['system_pulse']['db_connected'] ? 'Connected' : 'Offline' }}</h5>
                    </div>
                </div>
                <div class="status-indicator {{ $stats['system_pulse']['db_connected'] ? 'status-active' : 'status-inactive' }}"></div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="system-status-card bg-warning-gradient">
                <div class="d-flex align-items-center">
                    <div class="status-icon">
                        <i class="bi bi-bell"></i>
                    </div>
                    <div class="ms-3">
                        <small class="text-white opacity-75">Notifications</small>
                        <h5 class="mb-0 text-white">{{ $stats['fcm_ready'] ? 'Ready' : 'Setup' }}</h5>
                    </div>
                </div>
                <div class="status-indicator status-warning"></div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="system-status-card bg-info-gradient">
                <div class="d-flex align-items-center">
                    <div class="status-icon">
                        <i class="bi bi-clock-history"></i>
                    </div>
                    <div class="ms-3">
                        <small class="text-white opacity-75">Avg. Processing</small>
                        <h5 class="mb-0 text-white">{{ $stats['avgProcessingTime'] ?? '0 days' }}</h5>
                    </div>
                </div>
                <div class="status-indicator status-active"></div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="system-status-card bg-danger-gradient">
                <div class="d-flex align-items-center">
                    <div class="status-icon">
                        <i class="bi bi-exclamation-triangle"></i>
                    </div>
                    <div class="ms-3">
                        <small class="text-white opacity-75">Data Errors</small>
                        <h5 class="mb-0 text-white">{{ $stats['dataQuality']['data_entry_errors'] ?? 0 }}</h5>
                    </div>
                </div>
                <div class="status-indicator {{ $stats['dataQuality']['data_entry_errors'] > 0 ? 'status-warning' : 'status-inactive' }}"></div>
            </div>
        </div>
    </div>

    {{-- Main KPI Cards --}}
    <div class="row g-4 mb-6">
        {{-- Today's Orders --}}
        <div class="col-md-6 col-lg-3" data-kpi-card="orders">
            <div class="kpi-card">
                <div class="kpi-card-header">
                    <div class="kpi-icon bg-primary-light">
                        <i class="bi bi-basket3 text-primary"></i>
                    </div>
                    <div class="dropdown">
                        <button class="btn btn-link p-0" type="button" data-bs-toggle="dropdown">
                            <i class="bi bi-three-dots-vertical text-muted"></i>
                        </button>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="{{ route('admin.orders.index') }}?date={{ now()->format('Y-m-d') }}">View Today's Orders</a></li>
                            <li><a class="dropdown-item" href="{{ route('admin.orders.create') }}">Create New Order</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="{{ route('admin.reports.orders') }}">Order Reports</a></li>
                        </ul>
                    </div>
                </div>
                <h6 class="kpi-label">Today's Orders</h6>
                <h2 class="kpi-value" data-kpi="todayOrders">{{ $stats['todayOrders'] }}</h2>
                <div class="kpi-trend">
                    <span class="trend-badge {{ $stats['ordersChange'] >= 0 ? 'trend-up' : 'trend-down' }}">
                        <i class="bi {{ $stats['ordersChange'] >= 0 ? 'bi-arrow-up' : 'bi-arrow-down' }} me-1"></i>
                        {{ abs($stats['ordersChange']) }}% vs yesterday
                    </span>
                    <small class="text-muted">Total: {{ $stats['totalOrders'] ?? 0 }} orders in system</small>
                </div>
            </div>
        </div>

        {{-- Today's Revenue --}}
        <div class="col-md-6 col-lg-3" data-kpi-card="revenue">
            <div class="kpi-card">
                <div class="kpi-card-header">
                    <div class="kpi-icon bg-success-light">
                        <i class="bi bi-cash-coin text-success"></i>
                    </div>
                    <div class="dropdown">
                        <button class="btn btn-link p-0" type="button" data-bs-toggle="dropdown">
                            <i class="bi bi-three-dots-vertical text-muted"></i>
                        </button>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="{{ route('admin.reports.index') }}?period=today">Today's Revenue Report</a></li>
                            <li><a class="dropdown-item" href="{{ route('admin.reports.index') }}?period=month">Monthly Report</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="{{ route('admin.orders.index') }}?status=paid">View Paid Orders</a></li>
                        </ul>
                    </div>
                </div>
                <h6 class="kpi-label">Today's Revenue</h6>
                <h2 class="kpi-value" data-kpi="todayRevenue">₱{{ number_format($stats['todayRevenue'], 0) }}</h2>
                <div class="kpi-trend">
                    <span class="trend-badge {{ $stats['revenueChange'] >= 0 ? 'trend-up' : 'trend-down' }}">
                        <i class="bi {{ $stats['revenueChange'] >= 0 ? 'bi-arrow-up' : 'bi-arrow-down' }} me-1"></i>
                        {{ abs($stats['revenueChange']) }}% vs yesterday
                    </span>
                    <small class="text-muted">Month: ₱{{ number_format($stats['thisMonthRevenue'] ?? 0, 0) }}</small>
                </div>
            </div>
        </div>

        {{-- Active Customers --}}
        <div class="col-md-6 col-lg-3" data-kpi-card="customers">
            <div class="kpi-card">
                <div class="kpi-card-header">
                    <div class="kpi-icon bg-info-light">
                        <i class="bi bi-people text-info"></i>
                    </div>
                    <div class="dropdown">
                        <button class="btn btn-link p-0" type="button" data-bs-toggle="dropdown">
                            <i class="bi bi-three-dots-vertical text-muted"></i>
                        </button>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="{{ route('admin.customers.index') }}">View All Customers</a></li>
                            <li><a class="dropdown-item" href="{{ route('admin.customers.create') }}">Add New Customer</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="{{ route('admin.customers.index') }}?new=this_month">New This Month</a></li>
                        </ul>
                    </div>
                </div>
                <h6 class="kpi-label">Active Customers</h6>
                <h2 class="kpi-value" data-kpi="activeCustomers">{{ number_format($stats['activeCustomers']) }}</h2>
                <div class="kpi-trend">
                    <span class="trend-badge trend-up">
                        <i class="bi bi-plus-circle me-1"></i>
                        +{{ $stats['newCustomersThisMonth'] ?? 0 }} this month
                    </span>
                    <small class="text-muted">
                        @if(isset($stats['customerRegistrationSource']['app']))
                            {{ $stats['customerRegistrationSource']['app'] }} app users
                        @endif
                    </small>
                </div>
            </div>
        </div>

        {{-- Unclaimed Items (Critical) --}}
        <div class="col-md-6 col-lg-3" data-kpi-card="unclaimed">
            <a href="{{ route('admin.unclaimed.index') }}" class="text-decoration-none">
                <div class="kpi-card critical-card">
                    <div class="kpi-card-header">
                        <div class="kpi-icon bg-danger-light">
                            <i class="bi bi-exclamation-triangle text-danger"></i>
                        </div>
                        <span class="badge bg-danger">Critical</span>
                    </div>
                    <h6 class="kpi-label">Unclaimed Items</h6>
                    <h2 class="kpi-value" data-kpi="unclaimedLaundry">{{ $stats['unclaimedLaundry'] }}</h2>
                    <div class="kpi-trend">
                        <span class="trend-badge trend-down">
                            <i class="bi bi-clock me-1"></i>
                            Est. Loss: ₱{{ number_format($stats['estimatedUnclaimedLoss'] ?? 0, 0) }}
                        </span>
                        <small class="text-muted">Click to manage unclaimed items</small>
                    </div>
                    <div class="critical-action">
                        <a href="{{ route('admin.unclaimed.remindAll') }}" class="btn btn-sm btn-danger">
                            <i class="bi bi-bell me-1"></i>Send Reminders
                        </a>
                    </div>
                </div>
            </a>
        </div>
    </div>

    {{-- Quick Actions Grid --}}
    <div class="card mb-6 border-0 shadow-sm">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h6 class="mb-0 fw-semibold text-primary">Quick Actions</h6>
                <span class="badge bg-primary bg-opacity-10 text-primary">
                    <i class="bi bi-lightning me-1"></i>Instant Access
                </span>
            </div>
            <div class="row g-3">
                @php
                    $quickActions = [
                        ['route' => 'admin.orders.create', 'icon' => 'bi-plus-lg', 'label' => 'Create Order', 'desc' => 'New order', 'color' => 'primary'],
                        ['route' => 'admin.customers.create', 'icon' => 'bi-person-plus', 'label' => 'New Customer', 'desc' => 'Register', 'color' => 'info'],
                        ['route' => 'admin.pickups.index', 'icon' => 'bi-truck', 'label' => 'Pickups', 'desc' => 'Delivery', 'color' => 'warning'],
                        ['route' => 'admin.unclaimed.index', 'icon' => 'bi-box-seam', 'label' => 'Unclaimed', 'desc' => 'Inventory', 'color' => 'danger'],
                        ['route' => 'admin.promotions.create', 'icon' => 'bi-percent', 'label' => 'Promotions', 'desc' => 'Marketing', 'color' => 'success'],
                        ['route' => 'admin.reports.index', 'icon' => 'bi-graph-up', 'label' => 'Reports', 'desc' => 'Analytics', 'color' => 'secondary'],
                    ];
                @endphp
                @foreach($quickActions as $action)
                    @if(Route::has($action['route']))
                    <div class="col-6 col-md-4 col-lg-3 col-xl-2">
                        <a href="{{ route($action['route']) }}" class="quick-action-card text-center">
                            <div class="action-icon bg-{{ $action['color'] }}-light mb-3">
                                <i class="bi {{ $action['icon'] }} text-{{ $action['color'] }}"></i>
                            </div>
                            <h6 class="action-label mb-1">{{ $action['label'] }}</h6>
                            <small class="text-muted">{{ $action['desc'] }}</small>
                        </a>
                    </div>
                    @endif
                @endforeach
            </div>
        </div>
    </div>

    {{-- Dashboard Tabs Navigation --}}
    <div class="dashboard-tabs mb-4">
        <ul class="nav nav-pills" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="overview-tab" data-bs-toggle="pill" data-bs-target="#overview" type="button">
                    <i class="bi bi-speedometer2 me-2"></i>Overview
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="orders-tab" data-bs-toggle="pill" data-bs-target="#orders" type="button">
                    <i class="bi bi-basket me-2"></i>Orders
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="customers-tab" data-bs-toggle="pill" data-bs-target="#customers" type="button">
                    <i class="bi bi-people me-2"></i>Customers
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="operations-tab" data-bs-toggle="pill" data-bs-target="#operations" type="button">
                    <i class="bi bi-gear me-2"></i>Operations
                </button>
            </li>
        </ul>
    </div>

    {{-- Tabs Content --}}
    <div class="tab-content">
        {{-- Overview Tab --}}
        <div class="tab-pane fade show active" id="overview" role="tabpanel">
            <div class="row g-4">
                {{-- Order Pipeline --}}
                <div class="col-lg-8">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-header bg-transparent border-0">
                            <h6 class="mb-0 fw-semibold">Order Pipeline</h6>
                            <small class="text-muted">Current status of all orders</small>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                @php
                                    $pipelineStatuses = [
                                        ['status' => 'received', 'label' => 'Received', 'icon' => 'bi-inbox', 'color' => 'primary', 'description' => 'Orders received and awaiting processing'],
                                        ['status' => 'processing', 'label' => 'Processing', 'icon' => 'bi-gear', 'color' => 'warning', 'description' => 'Currently being processed'],
                                        ['status' => 'ready', 'label' => 'Ready', 'icon' => 'bi-check-circle', 'color' => 'info', 'description' => 'Ready for pickup/delivery'],
                                        ['status' => 'completed', 'label' => 'Completed', 'icon' => 'bi-check2-all', 'color' => 'success', 'description' => 'Completed orders'],
                                        ['status' => 'cancelled', 'label' => 'Cancelled', 'icon' => 'bi-x-circle', 'color' => 'danger', 'description' => 'Cancelled orders']
                                    ];
                                @endphp
                                @foreach($pipelineStatuses as $status)
                                    <div class="col-md-4">
                                        <div class="pipeline-card status-{{ $status['color'] }}">
                                            <div class="d-flex align-items-center justify-content-between mb-3">
                                                <div class="pipeline-icon">
                                                    <i class="bi {{ $status['icon'] }}"></i>
                                                </div>
                                                <span class="pipeline-count">{{ $stats['orderPipeline'][$status['status']] ?? 0 }}</span>
                                            </div>
                                            <h6 class="pipeline-label">{{ $status['label'] }}</h6>
                                            <p class="pipeline-desc small text-muted mb-0">{{ $status['description'] }}</p>
                                            <div class="mt-3">
                                                <a href="{{ route('admin.orders.index') }}?status={{ $status['status'] }}" class="btn btn-sm btn-{{ $status['color'] }}-light w-100">
                                                    View Details
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Unclaimed Breakdown --}}
                <div class="col-lg-4">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-header bg-danger bg-opacity-10 border-danger border-start border-3">
                            <h6 class="mb-0 fw-semibold text-danger">Unclaimed Items</h6>
                            <small class="text-danger">Requires immediate attention</small>
                        </div>
                        <div class="card-body">
                            @php
                                $unclaimedCategories = [
                                    ['label' => '0-7 Days', 'key' => 'within_7_days', 'color' => 'success', 'icon' => 'bi-clock'],
                                    ['label' => '1-2 Weeks', 'key' => '1_to_2_weeks', 'color' => 'warning', 'icon' => 'bi-clock-history'],
                                    ['label' => '2-4 Weeks', 'key' => '2_to_4_weeks', 'color' => 'orange', 'icon' => 'bi-exclamation-circle'],
                                    ['label' => '>1 Month', 'key' => 'over_1_month', 'color' => 'danger', 'icon' => 'bi-exclamation-triangle'],
                                ];
                            @endphp
                            @foreach($unclaimedCategories as $category)
                                <div class="unclaimed-item mb-3">
                                    <div class="d-flex align-items-center justify-content-between">
                                        <div class="d-flex align-items-center">
                                            <div class="unclaimed-icon bg-{{ $category['color'] }}-light me-3">
                                                <i class="bi {{ $category['icon'] }} text-{{ $category['color'] }}"></i>
                                            </div>
                                            <div>
                                                <h6 class="mb-0">{{ $category['label'] }}</h6>
                                                <small class="text-muted">Time since completion</small>
                                            </div>
                                        </div>
                                        <div class="text-end">
                                            <h4 class="mb-0 text-{{ $category['color'] }}">{{ $stats['unclaimedBreakdown'][$category['key']] ?? 0 }}</h4>
                                            <small class="text-muted">items</small>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                            <div class="alert alert-danger mt-4">
                                <div class="d-flex align-items-center">
                                    <i class="bi bi-exclamation-triangle me-3 fs-4"></i>
                                    <div>
                                        <strong>Estimated Financial Impact:</strong>
                                        <h5 class="mb-0 mt-1">₱{{ number_format($stats['estimatedUnclaimedLoss'] ?? 0, 2) }}</h5>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Revenue Chart (Mini) --}}
                <div class="col-lg-12">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-transparent border-0 d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="mb-0 fw-semibold">Revenue Trend</h6>
                                <small class="text-muted">Last 7 days performance</small>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="chart-container">
                                <canvas id="revenueChart" height="100"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Orders Tab --}}
        <div class="tab-pane fade" id="orders" role="tabpanel">
            <div class="row g-4">
                {{-- Orders by Service Type --}}
                <div class="col-lg-6">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-header bg-transparent border-0">
                            <h6 class="mb-0 fw-semibold">Revenue by Service</h6>
                            <small class="text-muted">Breakdown by service type</small>
                        </div>
                        <div class="card-body">
                            @if(isset($stats['revenueByService']) && count($stats['revenueByService']) > 0)
                                <div class="table-responsive">
                                    <table class="table table-sm">
                                        <thead>
                                            <tr>
                                                <th>Service</th>
                                                <th class="text-end">Revenue</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($stats['revenueByService'] as $service)
                                                <tr>
                                                    <td>{{ $service['service'] }}</td>
                                                    <td class="text-end">₱{{ number_format($service['revenue'], 0) }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @else
                                <div class="text-center py-5">
                                    <i class="bi bi-basket text-muted fs-1 mb-3"></i>
                                    <p class="text-muted">No service data available</p>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- Recent Orders --}}
                <div class="col-lg-6">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-header bg-transparent border-0 d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="mb-0 fw-semibold">Recent Orders</h6>
                                <small class="text-muted">Latest activities</small>
                            </div>
                            <a href="{{ route('admin.orders.index') }}" class="btn btn-sm btn-outline-primary">View All</a>
                        </div>
                        <div class="card-body">
                            <div class="recent-orders">
                                @php
                                    $recentOrders = \App\Models\Order::with('customer')->latest()->limit(5)->get();
                                @endphp
                                @forelse($recentOrders as $order)
                                    <div class="recent-order-item mb-3">
                                        <div class="d-flex align-items-center justify-content-between">
                                            <div class="d-flex align-items-center">
                                                <div class="order-status-badge status-{{ $order->status }} me-3">
                                                    <i class="bi bi-circle-fill"></i>
                                                </div>
                                                <div>
                                                    <h6 class="mb-0">Order #{{ $order->order_number ?? $order->id }}</h6>
                                                    <small class="text-muted">
                                                        {{ $order->customer->name ?? 'Guest' }} •
                                                        {{ $order->created_at->diffForHumans() }}
                                                    </small>
                                                </div>
                                            </div>
                                            <div class="text-end">
                                                <h6 class="mb-0">₱{{ number_format($order->total_amount, 0) }}</h6>
                                                <small class="text-capitalize text-muted">{{ $order->status }}</small>
                                            </div>
                                        </div>
                                    </div>
                                @empty
                                    <div class="text-center py-5">
                                        <i class="bi bi-basket text-muted fs-1 mb-3"></i>
                                        <p class="text-muted">No recent orders</p>
                                    </div>
                                @endforelse
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Customers Tab --}}
        <div class="tab-pane fade" id="customers" role="tabpanel">
            <div class="row g-4">
                {{-- Customer Registration Sources --}}
                <div class="col-lg-6">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-header bg-transparent border-0">
                            <h6 class="mb-0 fw-semibold">Registration Sources</h6>
                            <small class="text-muted">Where customers come from</small>
                        </div>
                        <div class="card-body">
                            <div class="chart-container">
                                <canvas id="customerSourceChart" height="200"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Top Customers --}}
                <div class="col-lg-6">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-header bg-transparent border-0 d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="mb-0 fw-semibold">Top Customers</h6>
                                <small class="text-muted">By lifetime value</small>
                            </div>
                            <a href="{{ route('admin.customers.index') }}" class="btn btn-sm btn-outline-primary">View All</a>
                        </div>
                        <div class="card-body">
                            @php
                                $topCustomers = \App\Models\Customer::withSum('orders', 'total_amount')
                                    ->withCount('orders')
                                    ->orderBy('orders_sum_total_amount', 'desc')
                                    ->limit(5)
                                    ->get();
                            @endphp
                            @forelse($topCustomers as $customer)
                                <div class="top-customer-item mb-3">
                                    <div class="d-flex align-items-center justify-content-between">
                                        <div class="d-flex align-items-center">
                                            <div class="customer-avatar me-3">
                                                {{ substr($customer->name, 0, 1) }}
                                            </div>
                                            <div>
                                                <h6 class="mb-0">{{ $customer->name }}</h6>
                                                <small class="text-muted">{{ $customer->orders_count }} orders</small>
                                            </div>
                                        </div>
                                        <div class="text-end">
                                            <h6 class="mb-0 text-success">₱{{ number_format($customer->orders_sum_total_amount, 0) }}</h6>
                                            <small class="text-muted">Lifetime value</small>
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <div class="text-center py-5">
                                    <i class="bi bi-people text-muted fs-1 mb-3"></i>
                                    <p class="text-muted">No customer data available</p>
                                </div>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Operations Tab --}}
        <div class="tab-pane fade" id="operations" role="tabpanel">
            <div class="row g-4">
                {{-- Left: Pickup Management Panel --}}
                <div class="col-lg-5">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-header bg-transparent border-0 d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="mb-0 fw-semibold">Pickup Management</h6>
                                <small class="text-muted">Select multiple pickups for optimized route</small>
                            </div>
                            <div>
                                <span id="selectedPickupCount" class="badge bg-purple" style="display: none;">0</span>
                            </div>
                        </div>
                        <div class="card-body">
                            {{-- Multi-Route Action Buttons --}}
                            <div id="multiRouteBtn" class="d-grid mb-4" style="display: none;">
                                <button class="btn btn-purple" onclick="getOptimizedMultiRoute()">
                                    <i class="bi bi-route me-2"></i>Optimize Route (<span id="selectedCount">0</span> selected)
                                </button>
                            </div>

                            {{-- Auto-Optimize Button --}}
                            <div class="d-grid mb-4">
                                <button class="btn btn-primary" onclick="autoRouteAllVisible()">
                                    <i class="bi bi-magic me-2"></i> Auto-Optimize All Pending
                                </button>
                            </div>

                            {{-- Quick Actions --}}
                            <div class="d-flex gap-2 mb-4">
                                <button class="btn btn-sm btn-outline-purple flex-fill" onclick="selectAllPending()">
                                    <i class="bi bi-check-square me-1"></i> Select All Pending
                                </button>
                                <button class="btn btn-sm btn-outline-danger flex-fill" onclick="clearSelections()">
                                    <i class="bi bi-x-circle me-1"></i> Clear All
                                </button>
                            </div>

                            {{-- Pickup Status Summary --}}
                            <h6 class="mb-3 fw-semibold text-secondary">Pickup Status Summary</h6>
                            @foreach([
                                'pending'    => 'Pending',
                                'accepted'   => 'Accepted',
                                'en_route'   => 'En Route',
                                'picked_up'  => 'Picked Up',
                                'cancelled'  => 'Cancelled',
                            ] as $statusKey => $label)
                                <div class="d-flex justify-content-between align-items-center mb-3 pb-3 border-bottom">
                                    <div class="d-flex align-items-center">
                                        <div class="pickup-status-indicator status-{{ $statusKey }} me-3"></div>
                                        <div>
                                            <h6 class="mb-0">{{ $label }}</h6>
                                            <small class="text-muted">Pickup requests</small>
                                        </div>
                                    </div>
                                    <div class="text-end">
                                        <h4 class="mb-0">{{ $stats['pickupStats'][$statusKey] ?? 0 }}</h4>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                {{-- Right: Map View --}}
                <div class="col-lg-7">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-header bg-transparent border-0 d-flex justify-content-between align-items-center">
                            <h6 class="mb-0 fw-semibold">Logistics Map</h6>
                            <div class="d-flex gap-2">
                                <button class="btn btn-sm btn-purple" id="multiRouteTopBtn" style="display: none;"
                                        onclick="getOptimizedMultiRoute()">
                                    <i class="bi bi-route"></i> Optimize (<span id="selectedCountTop">0</span>)
                                </button>
                                <button class="btn btn-sm btn-outline-primary" onclick="refreshMapMarkers()">
                                    <i class="bi bi-geo-alt"></i> Refresh
                                </button>
                                <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#mapModal">
                                    <i class="bi bi-arrows-fullscreen"></i> Fullscreen
                                </button>
                            </div>
                        </div>
                        <div class="card-body p-0 position-relative">
                            <div id="logisticsMap" style="height: 500px; width: 100%; border-radius: 0 0 12px 12px;"></div>
                            <div id="map-controls-container" style="position: absolute; top: 10px; left: 10px; z-index: 1000;">
                                <div id="eta-display-container" style="display: none; margin-bottom: 10px;"></div>
                                <div class="route-controls" style="display: none;">
                                    <button class="route-btn btn-clear-route" onclick="clearRoute()">
                                        <i class="bi bi-x-circle"></i> Clear Route
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Route Details Panel (Initially Hidden) --}}
    <div id="routeDetailsPanel" class="route-details-panel" style="display: none;"></div>

    {{-- Fullscreen Map Modal --}}
    <div class="modal fade" id="mapModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-fullscreen">
            <div class="modal-content">
                <div class="modal-header border-bottom shadow-sm">
                    <h5 class="modal-title fw-bold">Logistics Command Center</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-0">
                    <div id="modalLogisticsMap" style="height: 100%; width: 100%;"></div>
                </div>
            </div>
        </div>
    </div>

    {{-- Notification Statistics --}}
    <div class="row g-4 mt-4">
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-transparent border-0">
                    <h6 class="mb-0 fw-semibold">System Metrics</h6>
                    <small class="text-muted">Performance indicators</small>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        @php
                            $systemStats = [
                                ['label' => 'Data Accuracy', 'value' => ($stats['dataQuality']['info_accuracy'] ?? 0) . '%', 'color' => 'success', 'icon' => 'bi-check-circle'],
                                ['label' => 'Avg Order Value', 'value' => '₱' . number_format(($stats['todayRevenue'] ?? 0) / max($stats['todayOrders'], 1), 0), 'color' => 'primary', 'icon' => 'bi-currency-dollar'],
                                ['label' => 'Processing Time', 'value' => $stats['avgProcessingTime'] ?? '0 days', 'color' => 'warning', 'icon' => 'bi-clock'],
                                ['label' => 'System Uptime', 'value' => '100%', 'color' => 'info', 'icon' => 'bi-server'],
                            ];
                        @endphp
                        @foreach($systemStats as $stat)
                            <div class="col-6">
                                <div class="system-stat-card bg-{{ $stat['color'] }}-light">
                                    <div class="d-flex align-items-center">
                                        <div class="stat-icon me-3">
                                            <i class="bi {{ $stat['icon'] }} text-{{ $stat['color'] }}"></i>
                                        </div>
                                        <div>
                                            <small class="text-muted">{{ $stat['label'] }}</small>
                                            <h4 class="mb-0 text-{{ $stat['color'] }}">{{ $stat['value'] }}</h4>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>

@endsection

@push('styles')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.css" />
<link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.Default.css" />
<style>
:root {
    --primary-dark: #2D2B5F;
    --primary: #3D3B6B;
    --primary-light: #E8E8F5;
    --secondary: #6C757D;
    --success: #10B981;
    --success-light: #D1FAE5;
    --info: #3B82F6;
    --info-light: #DBEAFE;
    --warning: #F59E0B;
    --warning-light: #FEF3C7;
    --danger: #EF4444;
    --danger-light: #FEE2E2;
    --purple: #8B5CF6;
    --purple-light: #F3F0FF;
    --orange: #F97316;
}

/* Multi-route specific styles */
.selected-pickup {
    background-color: rgba(139, 92, 246, 0.1) !important;
    border: 2px solid #8B5CF6 !important;
}

.stop-marker {
    z-index: 1002 !important;
}

.btn-purple {
    background-color: #8B5CF6;
    border-color: #8B5CF6;
    color: white;
}

.btn-purple:hover {
    background-color: #7C3AED;
    border-color: #7C3AED;
}

.btn-outline-purple {
    border-color: #8B5CF6;
    color: #8B5CF6;
}

.btn-outline-purple:hover {
    background-color: #8B5CF6;
    color: white;
}

.bg-purple {
    background-color: #8B5CF6 !important;
}

.bg-purple-light {
    background-color: rgba(139, 92, 246, 0.1) !important;
}

.text-purple {
    color: #8B5CF6 !important;
}

/* Dashboard Container */
.dashboard-container {
    background: #F8FAFC;
}

/* Dashboard Header */
.dashboard-icon-container {
    width: 60px;
    height: 60px;
    background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 24px;
}

/* System Status Cards */
.system-status-card {
    padding: 20px;
    border-radius: 12px;
    position: relative;
    color: white;
    transition: transform 0.3s ease;
}

.system-status-card:hover {
    transform: translateY(-2px);
}

.bg-primary-gradient { background: linear-gradient(135deg, var(--primary) 0%, #4F4D8C 100%); }
.bg-success-gradient { background: linear-gradient(135deg, var(--success) 0%, #059669 100%); }
.bg-warning-gradient { background: linear-gradient(135deg, var(--warning) 0%, #D97706 100%); }
.bg-danger-gradient { background: linear-gradient(135deg, var(--danger) 0%, #DC2626 100%); }
.bg-info-gradient { background: linear-gradient(135deg, var(--info) 0%, #2563EB 100%); }

.status-icon {
    width: 50px;
    height: 50px;
    background: rgba(255, 255, 255, 0.2);
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
}

.status-indicator {
    position: absolute;
    top: 20px;
    right: 20px;
    width: 10px;
    height: 10px;
    border-radius: 50%;
}

.status-active { background: var(--success); animation: pulse 2s infinite; }
.status-warning { background: var(--warning); animation: pulse 2s infinite; }
.status-inactive { background: var(--secondary); }

/* KPI Cards */
.kpi-card {
    background: white;
    border-radius: 12px;
    padding: 24px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    transition: all 0.3s ease;
    height: 100%;
    position: relative;
    overflow: hidden;
}

.kpi-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
}

.kpi-card.critical-card {
    border-left: 4px solid var(--danger);
    background: linear-gradient(to right, #FEF2F2, white);
}

.kpi-card-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 16px;
}

.kpi-icon {
    width: 50px;
    height: 50px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
}

.bg-primary-light { background: var(--primary-light); }
.bg-success-light { background: var(--success-light); }
.bg-info-light { background: var(--info-light); }
.bg-warning-light { background: var(--warning-light); }
.bg-danger-light { background: var(--danger-light); }

.kpi-label {
    font-size: 14px;
    color: var(--secondary);
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 8px;
}

.kpi-value {
    font-size: 2.5rem;
    font-weight: 800;
    color: var(--primary-dark);
    margin-bottom: 12px;
    line-height: 1;
}

.kpi-trend {
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.trend-badge {
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
    display: inline-flex;
    align-items: center;
    width: fit-content;
}

.trend-up {
    background: var(--success-light);
    color: var(--success);
}

.trend-down {
    background: var(--danger-light);
    color: var(--danger);
}

.critical-action {
    margin-top: 16px;
}

/* Quick Actions */
.quick-action-card {
    background: white;
    border-radius: 12px;
    padding: 20px 15px;
    display: block;
    text-decoration: none;
    border: 1px solid #E5E7EB;
    transition: all 0.3s ease;
}

.quick-action-card:hover {
    transform: translateY(-4px);
    border-color: var(--primary);
    box-shadow: 0 10px 15px rgba(0, 0, 0, 0.1);
}

.action-icon {
    width: 60px;
    height: 60px;
    border-radius: 12px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
}

.action-label {
    color: var(--primary-dark);
    font-weight: 600;
}

/* Dashboard Tabs */
.dashboard-tabs .nav-pills {
    background: white;
    padding: 8px;
    border-radius: 12px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.dashboard-tabs .nav-link {
    padding: 12px 20px;
    border-radius: 8px;
    color: var(--secondary);
    font-weight: 500;
    transition: all 0.3s ease;
}

.dashboard-tabs .nav-link.active {
    background: var(--primary);
    color: white;
}

.dashboard-tabs .nav-link:not(.active):hover {
    background: var(--primary-light);
    color: var(--primary);
}

/* Pipeline Cards */
.pipeline-card {
    background: white;
    border-radius: 12px;
    padding: 20px;
    border: 1px solid #E5E7EB;
    transition: all 0.3s ease;
    height: 100%;
}

.pipeline-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.pipeline-card.status-primary { border-top: 4px solid var(--primary); }
.pipeline-card.status-warning { border-top: 4px solid var(--warning); }
.pipeline-card.status-info { border-top: 4px solid var(--info); }
.pipeline-card.status-success { border-top: 4px solid var(--success); }
.pipeline-card.status-danger { border-top: 4px solid var(--danger); }

.pipeline-icon {
    width: 50px;
    height: 50px;
    background: var(--primary-light);
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
    color: var(--primary);
}

.pipeline-count {
    font-size: 2rem;
    font-weight: 800;
    color: var(--primary-dark);
}

.pipeline-label {
    font-size: 16px;
    font-weight: 600;
    color: var(--primary-dark);
    margin: 12px 0 8px;
}

/* Recent Orders */
.recent-order-item {
    padding: 12px;
    background: #F9FAFB;
    border-radius: 8px;
    border-left: 3px solid var(--primary);
}

.order-status-badge {
    width: 12px;
    height: 12px;
}

.order-status-badge.status-received { color: var(--primary); }
.order-status-badge.status-processing { color: var(--warning); }
.order-status-badge.status-ready { color: var(--info); }
.order-status-badge.status-completed { color: var(--success); }
.order-status-badge.status-cancelled { color: var(--danger); }

/* Top Customers */
.top-customer-item {
    padding: 12px;
    background: #F9FAFB;
    border-radius: 8px;
}

.customer-avatar {
    width: 40px;
    height: 40px;
    background: var(--primary);
    color: white;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
}

/* System Stats */
.system-stat-card {
    padding: 16px;
    border-radius: 10px;
    transition: transform 0.3s ease;
}

.system-stat-card:hover {
    transform: translateY(-2px);
}

.stat-icon {
    width: 40px;
    height: 40px;
    background: white;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
}

/* Unclaimed Items */
.unclaimed-item {
    padding: 12px;
    background: white;
    border-radius: 8px;
    border: 1px solid #E5E7EB;
}

.unclaimed-icon {
    width: 40px;
    height: 40px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 18px;
}

/* Pickup Status */
.pickup-status-indicator {
    width: 12px;
    height: 12px;
    border-radius: 50%;
}

.pickup-status-indicator.status-pending { background: var(--primary); }
.pickup-status-indicator.status-accepted { background: var(--info); }
.pickup-status-indicator.status-en_route { background: var(--warning); }
.pickup-status-indicator.status-picked_up { background: var(--success); }
.pickup-status-indicator.status-cancelled { background: var(--danger); }

/* Animations */
@keyframes pulse {
    0% { box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7); }
    70% { box-shadow: 0 0 0 10px rgba(16, 185, 129, 0); }
    100% { box-shadow: 0 0 0 0 rgba(16, 185, 129, 0); }
}

@keyframes bounce {
    0%, 100% { transform: translateY(0); }
    50% { transform: translateY(-5px); }
}

@keyframes pulsePurple {
    0% { box-shadow: 0 0 0 0 rgba(139, 92, 246, 0.7); }
    70% { box-shadow: 0 0 0 10px rgba(139, 92, 246, 0); }
    100% { box-shadow: 0 0 0 0 rgba(139, 92, 246, 0); }
}

/* Responsive */
@media (max-width: 768px) {
    .kpi-value {
        font-size: 2rem;
    }

    .dashboard-tabs .nav-link {
        padding: 8px 12px;
        font-size: 14px;
    }

    .quick-action-card {
        padding: 15px 10px;
    }

    .system-status-card {
        padding: 15px;
    }
}

/* Chart Container */
.chart-container {
    position: relative;
    height: 300px;
    width: 100%;
}

/* Map Styles */
#logisticsMap {
    height: 500px;
    width: 100%;
    border-radius: 8px;
    z-index: 1;
}

/* Map Marker Custom Styles */
.leaflet-marker-icon {
    z-index: 1000 !important;
}

.branch-marker,
.start-marker,
.end-marker,
.pickup-marker,
.stop-marker {
    z-index: 1001 !important;
}

/* Popup Styling */
.leaflet-popup-content {
    margin: 13px 19px;
    line-height: 1.4;
    min-width: 250px;
}

.leaflet-popup-content h6 {
    margin-top: 0;
    margin-bottom: 8px;
    font-size: 14px;
}

.leaflet-popup-content .badge {
    font-size: 11px;
    padding: 4px 8px;
}

.leaflet-popup-content .d-grid {
    gap: 6px;
}

.leaflet-popup-content .btn-sm {
    padding: 4px 8px;
    font-size: 12px;
}

/* ETA Display */
.eta-display {
    background: white;
    padding: 15px 20px;
    border-radius: 12px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.15);
    border-left: 4px solid #28a745;
    animation: slideInUp 0.3s ease;
}

.eta-label {
    font-size: 11px;
    color: #6c757d;
    margin-bottom: 5px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.eta-time {
    font-size: 24px;
    font-weight: 700;
    color: #28a745;
    margin-bottom: 5px;
}

.eta-display small {
    font-size: 12px;
    color: #6c757d;
}

@keyframes slideInUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Route Controls */
.route-controls {
    background: white;
    padding: 10px;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.15);
    animation: slideInLeft 0.3s ease;
}

.route-btn {
    background: white;
    border: 1px solid #dc3545;
    border-radius: 6px;
    padding: 8px 12px;
    font-weight: 500;
    display: flex;
    align-items: center;
    gap: 6px;
    cursor: pointer;
    transition: all 0.3s;
    font-size: 13px;
    color: #dc3545;
    width: 100%;
    justify-content: center;
}

.route-btn:hover {
    background: #f8d7da;
    transform: translateY(-1px);
}

@keyframes slideInLeft {
    from {
        opacity: 0;
        transform: translateX(-20px);
    }
    to {
        opacity: 1;
        transform: translateX(0);
    }
}

/* Route Details Panel */
.route-details-panel {
    position: fixed;
    top: 20px;
    right: 20px;
    width: 350px;
    max-width: calc(100% - 40px);
    z-index: 1050;
    box-shadow: 0 4px 20px rgba(0,0,0,0.15);
    border-radius: 12px;
    background: white;
    animation: slideInRight 0.3s ease;
}

@keyframes slideInRight {
    from {
        opacity: 0;
        transform: translateX(20px);
    }
    to {
        opacity: 1;
        transform: translateX(0);
    }
}

/* Map Controls Container */
#map-controls-container {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

/* Selected pickup counter animation */
#selectedPickupCount {
    animation: pulsePurple 1.5s infinite;
}
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="https://unpkg.com/leaflet.markercluster@1.5.3/dist/leaflet.markercluster.js"></script>
<script>
// Dashboard Configuration
const DASHBOARD_CONFIG = {
    autoRefresh: true,
    refreshInterval: 30000,
    charts: {
        revenue: null,
        customerSource: null
    },
    cacheKey: 'dashboard_active_tab'
};

// Global Variables
let logisticsMapInstance = null;
let modalMapInstance = null;
let routeLayer = null;
let startMarker = null;
let endMarker = null;
let pickupMarkers = [];
let pickupCluster = null;
let modalPickupCluster = null;
let selectedPickups = new Set();

// Initialize everything when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    initializeCharts();
    initializeTabs();
    initializeAutoRefresh();
    initializeDateUpdater();

    // Initialize map if on operations tab
    if (document.getElementById('operations-tab').classList.contains('active')) {
        setTimeout(() => {
            initLogisticsMap();
        }, 500);
    }

    // Setup modal map
    setupModalMap();
});

/**
 * MULTI-STOP ROUTE OPTIMIZATION FUNCTIONS
 */

function togglePickupSelection(pickupId) {
    if (selectedPickups.has(pickupId)) {
        selectedPickups.delete(pickupId);
    } else {
        selectedPickups.add(pickupId);
    }

    updateSelectedPickupCount();

    // Highlight marker on map
    const marker = findMarkerByPickupId(pickupId);
    if (marker) {
        if (selectedPickups.has(pickupId)) {
            marker.getElement().classList.add('selected-pickup');
        } else {
            marker.getElement().classList.remove('selected-pickup');
        }
    }
}

function findMarkerByPickupId(pickupId) {
    return pickupMarkers.find(marker => {
        const popupContent = marker.getPopup()?.getContent();
        return popupContent && popupContent.includes(`pickup-${pickupId}`);
    });
}

function updateSelectedPickupCount() {
    const count = selectedPickups.size;

    // Update all count displays
    document.querySelectorAll('#selectedCount, #selectedCountTop').forEach(el => {
        el.textContent = count;
    });

    const countBadge = document.getElementById('selectedPickupCount');
    if (countBadge) {
        countBadge.textContent = count;
        countBadge.style.display = count > 0 ? 'inline-block' : 'none';
    }

    // Show/hide multi-route buttons
    const multiRouteBtn = document.getElementById('multiRouteBtn');
    const multiRouteTopBtn = document.getElementById('multiRouteTopBtn');

    if (multiRouteBtn) multiRouteBtn.style.display = count > 1 ? 'block' : 'none';
    if (multiRouteTopBtn) multiRouteTopBtn.style.display = count > 1 ? 'block' : 'none';
}

async function getOptimizedMultiRoute() {
    if (selectedPickups.size < 2) {
        showToast('Please select at least 2 pickups for route optimization', 'warning');
        return;
    }

    const pickupIds = Array.from(selectedPickups);

    try {
        showToast('Optimizing route for ' + pickupIds.length + ' stops...', 'info');

        const response = await fetch('/admin/logistics/optimize-route', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({ pickup_ids: pickupIds })
        });

        if (!response.ok) {
            console.error('[v0] Route optimization failed with status:', response.status);
            showToast('Server error: ' + response.statusText, 'danger');
            return;
        }

        const data = await response.json();
        console.log('[v0] Route optimization response:', data);

        if (data.success) {
            drawMultiStopRoute(data);
            showMultiRouteSummary(data);
            showToast('Route optimized successfully!', 'success');
        } else {
            console.error('[v0] Route optimization failed:', data.error || data.message);
            showToast(data.error || data.message || 'Failed to optimize route', 'danger');
        }
    } catch (error) {
        console.error('[v0] Route optimization network error:', error);
        showToast('Network error: ' + (error.message || 'Could not reach server'), 'danger');
    }
}

function drawMultiStopRoute(data) {
    clearRoute(); // Clear existing route

    const isModalOpen = document.getElementById('mapModal').classList.contains('show');
    const targetMap = isModalOpen ? modalMapInstance : logisticsMapInstance;

    if (!targetMap) {
        console.error('No map instance found');
        return;
    }

    // Decode the polyline geometry
    const coordinates = decodePolyline(data.geometry);

    // Draw the optimized route line
    routeLayer = L.polyline(coordinates, {
        color: '#8B5CF6',
        weight: 6,
        opacity: 0.8,
        lineJoin: 'round'
    }).addTo(targetMap);

    // Add numbered markers for each stop
    if (data.stops && Array.isArray(data.stops)) {
        data.stops.forEach((stop, index) => {
            const isFirst = index === 0;
            const isLast = index === data.stops.length - 1;

            let markerColor, iconHtml;

            if (isFirst) {
                // Start (Branch)
                markerColor = '#007BFF';
                iconHtml = '<i class="bi bi-shop"></i>';
            } else if (isLast) {
                // Last pickup
                markerColor = '#10B981';
                iconHtml = '<i class="bi bi-flag-fill"></i>';
            } else {
                // Intermediate pickups
                markerColor = '#F59E0B';
                iconHtml = `<span style="font-weight:bold;font-size:14px">${index}</span>`;
            }

            const marker = L.marker([stop.location[1], stop.location[0]], {
                icon: L.divIcon({
                    className: 'stop-marker',
                    html: `<div style="background:${markerColor};width:36px;height:36px;border-radius:50%;border:3px solid white;color:white;font-size:12px;display:flex;align-items:center;justify-content:center;box-shadow:0 2px 6px rgba(0,0,0,0.3);">${iconHtml}</div>`,
                    iconSize: [36, 36],
                    iconAnchor: [18, 36]
                })
            }).addTo(targetMap);

            // Store reference to the marker
            pickupMarkers.push(marker);
        });
    }

    // Fit map to show entire route
    targetMap.fitBounds(routeLayer.getBounds(), { padding: [50, 50] });

    // Update ETA display
    updateETADisplay(data.duration + ' total trip time');
}

function showMultiRouteSummary(data) {
    const summary = `
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-purple text-white d-flex justify-content-between align-items-center">
                <h6 class="mb-0"><i class="bi bi-route me-2"></i>Optimized Route Summary</h6>
                <button class="btn btn-sm btn-light" onclick="closeRouteDetails()">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-6">
                        <small class="text-muted">Total Distance</small>
                        <h5>${data.distance}</h5>
                    </div>
                    <div class="col-6">
                        <small class="text-muted">Total Time</small>
                        <h5>${data.duration}</h5>
                    </div>
                </div>
                <hr>
                <div class="mb-3">
                    <small class="text-muted">Pickup Order:</small>
                    <ol class="mt-2 ps-3">
                        <li><strong>Start:</strong> Main Branch (Sibulan)</li>
                        ${data.stops ? data.stops.slice(1).map((stop, idx) =>
                            `<li><strong>Stop ${idx + 1}:</strong> ${stop.name || 'Pickup Location ' + (idx + 1)}</li>`
                        ).join('') : ''}
                    </ol>
                </div>
                <hr>
                <div class="d-grid gap-2">
                    <button class="btn btn-success" onclick="startMultiPickupNavigation()">
                        <i class="bi bi-play-circle me-2"></i>Start Multi-Pickup Run
                    </button>
                    <button class="btn btn-outline-primary" onclick="printRouteSchedule()">
                        <i class="bi bi-printer me-2"></i>Print Schedule
                    </button>
                    <button class="btn btn-outline-danger" onclick="clearRoute()">
                        <i class="bi bi-x-circle me-2"></i>Clear Route
                    </button>
                </div>
            </div>
        </div>
    `;

    const routeDetails = document.getElementById('routeDetailsPanel');
    if (routeDetails) {
        routeDetails.innerHTML = summary;
        routeDetails.style.display = 'block';
    }
}

async function startMultiPickupNavigation() {
    const pickupIds = Array.from(selectedPickups);

    if (pickupIds.length === 0) {
        showToast('No pickups selected', 'warning');
        return;
    }

    try {
        showToast('Starting multi-pickup navigation...', 'info');

        const response = await fetch('/admin/logistics/start-multi-pickup', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({ pickup_ids: pickupIds })
        });

        const data = await response.json();

        if (data.success) {
            showToast('Multi-pickup navigation started! All selected pickups are now marked as "En Route".', 'success');
            // Refresh the pickups list
            refreshMapMarkers();
            // Clear selection
            clearSelections();
        } else {
            showToast(data.error || 'Failed to start navigation', 'danger');
        }
    } catch (error) {
        console.error('Navigation error:', error);
        showToast('Failed to start navigation', 'danger');
    }
}

async function autoRouteAllVisible() {
    try {
        showToast('Finding optimal route for all pending pickups...', 'info');

        // Get all pending pickup IDs from the current view
        const pendingPickups = {!! json_encode($stats['pendingPickups'] ?? []) !!};

        if (!pendingPickups || pendingPickups.length < 2) {
            showToast('Need at least 2 pending pickups to optimize route', 'warning');
            return;
        }

        const pickupIds = pendingPickups.map(p => p.id);

        // Select all pending pickups
        selectedPickups.clear();
        pickupIds.forEach(id => selectedPickups.add(id));
        updateSelectedPickupCount();

        const response = await fetch('/admin/logistics/optimized-trip', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({ ids: pickupIds })
        });

        const data = await response.json();

        if (data.success) {
            drawMultiStopRoute(data);
            showMultiRouteSummary(data);
            showToast('Route optimized for ' + pickupIds.length + ' pickups!', 'success');
        } else {
            showToast(data.error || 'Failed to optimize route', 'danger');
        }
    } catch (error) {
        console.error('Auto-route error:', error);
        showToast('Failed to optimize route', 'danger');
    }
}

async function selectAllPending() {
    try {
        const response = await fetch('/admin/logistics/pending-pickups');
        const data = await response.json();

        if (data.success && data.pickups) {
            selectedPickups.clear();
            data.pickups.forEach(pickup => {
                selectedPickups.add(pickup.id);
            });
            updateSelectedPickupCount();
            showToast(`Selected ${data.pickups.length} pending pickups`, 'success');
        }
    } catch (error) {
        console.error('Error selecting pending pickups:', error);
        showToast('Failed to load pending pickups', 'danger');
    }
}

function clearSelections() {
    selectedPickups.clear();
    updateSelectedPickupCount();
    // Remove selected class from all markers
    pickupMarkers.forEach(marker => {
        marker.getElement()?.classList.remove('selected-pickup');
    });
}

function printRouteSchedule() {
    // Create a printable version of the route
    const routeDetails = document.getElementById('routeDetailsPanel');
    if (routeDetails) {
        const printWindow = window.open('', '_blank');
        printWindow.document.write(`
            <html>
                <head>
                    <title>Route Schedule - ${new Date().toLocaleDateString()}</title>
                    <style>
                        body { font-family: Arial, sans-serif; padding: 20px; }
                        h1 { color: #333; }
                        .route-info { margin: 20px 0; }
                        .stop-list { margin: 15px 0; }
                        .stop-item { padding: 5px 0; border-bottom: 1px solid #eee; }
                        .footer { margin-top: 30px; font-size: 12px; color: #666; }
                    </style>
                </head>
                <body>
                    ${routeDetails.innerHTML}
                    <div class="footer">
                        Printed on ${new Date().toLocaleString()}
                    </div>
                </body>
            </html>
        `);
        printWindow.document.close();
        printWindow.print();
    }
}

/**
 * LOGISTICS MAP FUNCTIONS
 */
function initLogisticsMap() {
    const container = document.getElementById('logisticsMap');
    if (!container) {
        console.error('Map container not found');
        return;
    }

    // Clear existing map
    if (logisticsMapInstance) {
        logisticsMapInstance.remove();
        logisticsMapInstance = null;
    }

    // Initialize map with Sibulan, Negros Oriental coordinates
    logisticsMapInstance = L.map('logisticsMap').setView([9.3068, 123.3033], 13);

    // Add OpenStreetMap tiles
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap contributors',
        maxZoom: 19
    }).addTo(logisticsMapInstance);

    // Initialize marker cluster for pickups
    pickupCluster = L.markerClusterGroup({ chunkedLoading: true });
    logisticsMapInstance.addLayer(pickupCluster);

    // Add cluster toggle control
    createClusterToggleControl(logisticsMapInstance, 'clusterToggleAdmin', true);

    // Load pickup data
    loadPickupsAndRender();
}

function loadPickupsAndRender() {
    console.log('Loading pickup data...');

    // Get pickup data from your Blade template
    const pickupData = {!! json_encode($stats['pendingPickups'] ?? []) !!};

    console.log('Pickup data:', pickupData);

    if (!pickupData || pickupData.length === 0) {
        console.log('No pickup data available');
        addSamplePickups();
        return;
    }

    // Clear existing markers
    clearPickupMarkers();

    // Add branch marker
    addBranchMarker();

    // Add pickup markers
    pickupData.forEach(pickup => {
        if (pickup.latitude && pickup.longitude) {
            addPickupMarker(pickup);
        } else {
            console.warn('Pickup missing coordinates:', pickup);
        }
    });

    // Fit bounds to show all markers
    fitMapToMarkers();
}

function addSamplePickups() {
    console.log('Adding sample pickup data for testing...');

    // Sample pickup data for testing
    const samplePickups = [
        {
            id: 1,
            customer: { name: 'John Doe', address: '123 Main St' },
            pickup_address: '123 Main St, Dumaguete',
            latitude: 9.3100,
            longitude: 123.3080,
            status: 'pending'
        },
        {
            id: 2,
            customer: { name: 'Jane Smith', address: '456 Oak Ave' },
            pickup_address: '456 Oak Ave, Dumaguete',
            latitude: 9.3150,
            longitude: 123.3120,
            status: 'pending'
        },
        {
            id: 3,
            customer: { name: 'Bob Johnson', address: '789 Pine Rd' },
            pickup_address: '789 Pine Rd, Dumaguete',
            latitude: 9.3200,
            longitude: 123.3050,
            status: 'pending'
        }
    ];

    samplePickups.forEach(pickup => {
        addPickupMarker(pickup);
    });

    fitMapToMarkers();
}

function fitMapToMarkers() {
    // Prefer cluster bounds if cluster has markers
    if (pickupCluster && pickupCluster.getLayers().length > 0) {
        const bounds = pickupCluster.getBounds();
        if (bounds.isValid && bounds.isValid()) {
            logisticsMapInstance.fitBounds(bounds.pad(0.1));
            return;
        }
    }

    if (pickupMarkers.length === 0) return;

    const group = new L.featureGroup(pickupMarkers);
    logisticsMapInstance.fitBounds(group.getBounds().pad(0.1));
}

function clearPickupMarkers() {
    // Clear cluster layers first
    if (pickupCluster && logisticsMapInstance) {
        pickupCluster.clearLayers();
    }

    // Fallback for any individual markers
    pickupMarkers.forEach(marker => {
        if (marker && logisticsMapInstance) {
            try {
                logisticsMapInstance.removeLayer(marker);
            } catch (e) {
                // ignore
            }
        }
    });
    pickupMarkers = [];
}

function addBranchMarker() {
    const branchCoords = [9.3068, 123.3033]; // Sibulan coordinates

    const marker = L.marker(branchCoords, {
        icon: L.divIcon({
            className: 'branch-marker',
            html: '<div style="background:#007BFF;width:40px;height:40px;border-radius:50%;border:3px solid white;box-shadow:0 2px 6px rgba(0,0,0,0.3);display:flex;align-items:center;justify-content:center;"><i class="bi bi-shop" style="color:white;font-size:18px;"></i></div>',
            iconSize: [40, 40],
            iconAnchor: [20, 40]
        })
    })
    .addTo(logisticsMapInstance)
    .bindPopup(`
        <div style="min-width:200px">
            <h6><b>Main Branch - Sibulan</b></h6>
            <p class="mb-1 small">Negros Oriental, Philippines</p>
            <hr class="my-2">
            <div class="d-grid gap-1">
                <button class="btn btn-sm btn-primary" onclick="showBranchInfo()">
                    <i class="bi bi-info-circle"></i> Branch Info
                </button>
            </div>
        </div>
    `);

    // Add to pickupMarkers array for bounds calculation
    pickupMarkers.push(marker);
}

function addPickupMarker(pickup) {
    const statusColors = {
        'pending': '#FFC107',
        'accepted': '#17A2B8',
        'en_route': '#007BFF',
        'picked_up': '#28A745',
        'cancelled': '#DC3545'
    };

    const color = statusColors[pickup.status] || '#6C757D';

    const marker = L.marker([pickup.latitude, pickup.longitude], {
        icon: L.divIcon({
            className: 'pickup-marker',
            html: `<div style="background:${color};width:32px;height:32px;border-radius:50%;border:3px solid white;box-shadow:0 2px 6px rgba(0,0,0,0.3);display:flex;align-items:center;justify-content:center;" id="marker-${pickup.id}"><i class="bi bi-geo-alt-fill" style="color:white;font-size:14px;"></i></div>`,
            iconSize: [32, 32],
            iconAnchor: [16, 32]
        })
    })
    .bindPopup(createPickupPopup(pickup));

    // Add to cluster if available, otherwise add to the map
    if (pickupCluster) {
        pickupCluster.addLayer(marker);
    } else {
        marker.addTo(logisticsMapInstance);
    }

    pickupMarkers.push(marker);
}

function createPickupPopup(pickup) {
    const isSelected = selectedPickups.has(pickup.id);
    const selectBtnClass = isSelected ? 'btn-purple' : 'btn-outline-purple';
    const selectBtnIcon = isSelected ? 'bi-check-square-fill' : 'bi-check-square';
    const selectBtnText = isSelected ? 'Selected' : 'Select for Multi-Route';

    return `
        <div style="min-width:250px" class="pickup-${pickup.id}">
            <h6><b>${pickup.customer?.name || 'Customer'}</b></h6>
            <p class="mb-1 small">${pickup.pickup_address || 'No address'}</p>
            <span class="badge bg-${getStatusColor(pickup.status)}">${pickup.status}</span>
            <hr class="my-2">
            <div class="d-grid gap-1">
                <button class="btn btn-sm ${selectBtnClass}" onclick="togglePickupSelection(${pickup.id}); this.blur();">
                    <i class="bi ${selectBtnIcon} me-1"></i> ${selectBtnText}
                </button>
                <button class="btn btn-sm btn-primary" onclick="getRouteToPickup(${pickup.id})">
                    <i class="bi bi-signpost me-1"></i> Direct Route
                </button>
                <button class="btn btn-sm btn-success" onclick="startNavigationForPickup(${pickup.id})">
                    <i class="bi bi-play-circle me-1"></i> Start Navigation
                </button>
                <button class="btn btn-sm btn-outline-secondary" onclick="viewPickupDetails(${pickup.id})">
                    <i class="bi bi-eye me-1"></i> View Details
                </button>
            </div>
        </div>
    `;
}

function getStatusColor(status) {
    const colors = {
        'pending': 'warning',
        'accepted': 'info',
        'en_route': 'primary',
        'picked_up': 'success',
        'cancelled': 'danger'
    };
    return colors[status] || 'secondary';
}

function createClusterToggleControl(map, id, defaultOn = true) {
    const ClusterControl = L.Control.extend({
        onAdd: function(map) {
            const container = L.DomUtil.create('div', 'leaflet-bar cluster-toggle-control p-2 bg-white rounded shadow-sm');
            container.innerHTML = `
                <div class="form-check m-1">
                    <input class="form-check-input" type="checkbox" id="${id}" ${defaultOn ? 'checked' : ''}>
                    <label class="form-check-label small ms-1" for="${id}">Cluster pickups</label>
                </div>
            `;
            L.DomEvent.disableClickPropagation(container);
            return container;
        }
    });

    map.addControl(new ClusterControl({ position: 'topright' }));

    // Wire up change handler after DOM is available
    setTimeout(() => {
        const el = document.getElementById(id);
        if (!el) return;
        el.addEventListener('change', (e) => {
            if (e.target.checked) {
                if (pickupCluster) map.addLayer(pickupCluster);
            } else {
                if (pickupCluster) map.removeLayer(pickupCluster);
            }
        });
    }, 200);
}

/**
 * SINGLE ROUTE FUNCTIONS
 */
async function getRouteToPickup(pickupId) {
    try {
        const url = `/admin/pickups/${pickupId}/route`;
        console.log('Fetching route from:', url);

        // Show loading state
        showToast('Loading route...', 'info');

        const response = await fetch(url, {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
        });

        if (!response.ok) {
            throw new Error(`HTTP error ${response.status}`);
        }

        const data = await response.json();
        console.log('Route data:', data);

        if (data.success && data.route) {
            // Draw the route on the map
            drawSingleRouteOnMap(data.route);

            // Show route details panel
            showSingleRouteDetails(data, pickupId);

            // Update ETA display
            updateETADisplay(data.estimated_arrival);

            // Show route controls
            toggleRouteControls(true);

            showToast('Route loaded successfully!', 'success');
            return data;
        } else {
            throw new Error('Invalid route data');
        }

    } catch (error) {
        console.error('Error fetching route:', error);
        showToast(`Failed to load route: ${error.message}`, 'error');
        return null;
    }
}

function drawSingleRouteOnMap(routeData) {
    const isModalOpen = document.getElementById('mapModal').classList.contains('show');
    const targetMap = isModalOpen ? modalMapInstance : logisticsMapInstance;

    if (!targetMap) {
        console.error("No active map instance found to draw the route.");
        return;
    }

    // Always clear existing routes from BOTH maps to keep them in sync
    clearRoute();

    if (routeData.geometry) {
        const coordinates = decodePolyline(routeData.geometry);

        // Create the polyline
        routeLayer = L.polyline(coordinates, {
            color: '#3D3B6B',
            weight: 6,
            opacity: 0.8,
            lineJoin: 'round'
        }).addTo(targetMap);

        // Add Start Marker
        const branchCoords = [9.3068, 123.3033];
        startMarker = L.circleMarker(branchCoords, {
            radius: 8,
            fillColor: "#007BFF",
            color: "#fff",
            weight: 2,
            fillOpacity: 1
        }).addTo(targetMap).bindPopup("<b>Main Branch</b>");

        // Add End Marker
        const endCoord = coordinates[coordinates.length - 1];
        endMarker = L.marker(endCoord, {
            icon: L.divIcon({
                className: 'end-marker',
                html: '<div style="background:#28A745;width:30px;height:30px;border-radius:50%;border:3px solid white;display:flex;align-items:center;justify-content:center;box-shadow:0 2px 5px rgba(0,0,0,0.2);"><i class="bi bi-geo-alt-fill" style="color:white;"></i></div>',
                iconSize: [30, 30],
                iconAnchor: [15, 30]
            })
        }).addTo(targetMap).bindPopup("<b>Customer Location</b>");

        // Zoom the correct map
        targetMap.fitBounds(routeLayer.getBounds(), { padding: [50, 50] });
    }
}

function decodePolyline(encoded) {
    if (!encoded || typeof encoded !== 'string') return [];

    // If backend already sent an array of [lat,lng], just return it
    if (encoded.startsWith('[')) return JSON.parse(encoded);

    var points = [];
    var index = 0, len = encoded.length;
    var lat = 0, lng = 0;

    while (index < len) {
        var b, shift = 0, result = 0;
        do {
            b = encoded.charCodeAt(index++) - 63;
            result |= (b & 0x1f) << shift;
            shift += 5;
        } while (b >= 0x20);
        var dlat = ((result & 1) ? ~(result >> 1) : (result >> 1));
        lat += dlat;

        shift = 0;
        result = 0;
        do {
            b = encoded.charCodeAt(index++) - 63;
            result |= (b & 0x1f) << shift;
            shift += 5;
        } while (b >= 0x20);
        var dlng = ((result & 1) ? ~(result >> 1) : (result >> 1));
        lng += dlng;

        points.push([lat / 1e5, lng / 1e5]); // Note: 1e5 for OSRM/Google
    }
    return points;
}

function clearRoute() {
    const maps = [logisticsMapInstance, modalMapInstance];

    maps.forEach(map => {
        if (map && typeof map.removeLayer === 'function') {
            try {
                if (routeLayer && typeof routeLayer.removeFrom === 'function') {
                    routeLayer.removeFrom(map);
                } else if (routeLayer) {
                    map.removeLayer(routeLayer);
                }
            } catch (e) {
                console.warn('[v0] Could not remove routeLayer:', e);
            }

            try {
                if (startMarker && typeof startMarker.removeFrom === 'function') {
                    startMarker.removeFrom(map);
                } else if (startMarker) {
                    map.removeLayer(startMarker);
                }
            } catch (e) {
                console.warn('[v0] Could not remove startMarker:', e);
            }

            try {
                if (endMarker && typeof endMarker.removeFrom === 'function') {
                    endMarker.removeFrom(map);
                } else if (endMarker) {
                    map.removeLayer(endMarker);
                }
            } catch (e) {
                console.warn('[v0] Could not remove endMarker:', e);
            }
        }
    });

    routeLayer = null;
    startMarker = null;
    endMarker = null;

    const etaContainer = document.getElementById('eta-display-container');
    if (etaContainer) etaContainer.style.display = 'none';

    toggleRouteControls(false);
    closeRouteDetails();
}

function toggleRouteControls(show = true) {
    const routeControls = document.querySelector('.route-controls');
    if (routeControls) {
        routeControls.style.display = show ? 'block' : 'none';
    }
}

function showSingleRouteDetails(routeData, pickupId) {
    let detailsPanel = document.getElementById('routeDetailsPanel');

    detailsPanel.innerHTML = `
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                <h6 class="mb-0">Route Details</h6>
                <button class="btn btn-sm btn-light" onclick="closeRouteDetails()">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <h5 class="text-success">
                        <i class="bi bi-signpost"></i> ${routeData.route?.distance?.text || '36.89 km'}
                    </h5>
                    <p class="text-muted">
                        <i class="bi bi-clock"></i> ${routeData.route?.duration?.text || '74 min'}
                    </p>
                </div>
                <hr>
                <div class="mb-3">
                    <small class="text-muted">From (Branch):</small>
                    <p class="mb-0"><b>Sibulan Branch</b></p>
                    <small class="text-muted">Negros Oriental</small>
                </div>
                <div class="mb-3">
                    <small class="text-muted">To (Pickup):</small>
                    <p class="mb-0"><b>Customer Location</b></p>
                    <small class="text-muted">${routeData.estimated_arrival || '06:23 PM'} ETA</small>
                </div>
                <hr>
                <div class="d-grid gap-2">
                    <button class="btn btn-success" onclick="startNavigation(${pickupId})">
                        <i class="bi bi-play-circle me-2"></i> Start Navigation
                    </button>
                    <button class="btn btn-outline-primary" onclick="printRoute()">
                        <i class="bi bi-printer me-2"></i> Print Directions
                    </button>
                    <button class="btn btn-outline-danger" onclick="clearRoute()">
                        <i class="bi bi-x-circle me-2"></i> Clear Route
                    </button>
                </div>
            </div>
        </div>
    `;

    detailsPanel.style.display = 'block';
}

function closeRouteDetails() {
    const panel = document.getElementById('routeDetailsPanel');
    if (panel) {
        panel.style.display = 'none';
    }
}

function updateETADisplay(etaTime) {
    const etaContainer = document.getElementById('eta-display-container');
    if (etaContainer) {
        etaContainer.innerHTML = `
            <div class="eta-display">
                <div class="eta-label">Estimated Arrival</div>
                <div class="eta-time">${etaTime || '06:23 PM'}</div>
                <small class="text-muted">Based on current traffic</small>
            </div>
        `;
        etaContainer.style.display = 'block';
    }
}

// Helper functions
function showBranchInfo() {
    alert('Main Branch Information\n\nLocation: Sibulan, Negros Oriental\nContact: (035) 123-4567\nHours: 8:00 AM - 6:00 PM');
}

function viewPickupDetails(pickupId) {
    window.open(`/admin/pickups/${pickupId}`, '_blank');
}

async function startNavigation(pickupId) {
    try {
        const response = await fetch(`/admin/pickups/${pickupId}/start-navigation`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Content-Type': 'application/json'
            }
        });

        const data = await response.json();

        if (data.success) {
            showToast('Navigation started!', 'success');
            // Update pickup status on the map
            refreshMapMarkers();
        } else {
            showToast('Failed to start navigation: ' + data.message, 'error');
        }
    } catch (error) {
        console.error('Error starting navigation:', error);
        showToast('Failed to start navigation', 'error');
    }
}

function startNavigationForPickup(pickupId) {
    startNavigation(pickupId);
}

function refreshMapMarkers() {
    if (logisticsMapInstance) {
        loadPickupsAndRender();
        logisticsMapInstance.setView([9.3068, 123.3033], 13);
        showToast('Map refreshed', 'info');
    }
}

/**
 * MODAL MAP FUNCTIONS
 */
function setupModalMap() {
    const modalEl = document.getElementById('mapModal');
    if (modalEl) {
        modalEl.addEventListener('shown.bs.modal', () => {
            if (!modalMapInstance) {
                // Initialize modal map
                modalMapInstance = L.map('modalLogisticsMap').setView([9.3068, 123.3033], 13);
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '© OpenStreetMap'
                }).addTo(modalMapInstance);
            }

            // Sync: Clear and Re-add markers to the modal map
            setTimeout(() => {
                modalMapInstance.invalidateSize();

                // Clear any old modal markers
                modalMapInstance.eachLayer((layer) => {
                    if (layer instanceof L.Marker || layer instanceof L.Polyline) {
                        modalMapInstance.removeLayer(layer);
                    }
                });

                // Re-add tile layer
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(modalMapInstance);

                // Reload all pickups into the modal map
                const pickupData = {!! json_encode($stats['pendingPickups'] ?? []) !!};

                // Add Branch
                addBranchToModalMap();

                // Add Pickups
                pickupData.forEach(pickup => {
                    if (pickup.latitude && pickup.longitude) {
                        addPickupMarkerToModalMap(pickup);
                    }
                });
            }, 300);
        });
    }
}

function addBranchToModalMap() {
    const branchCoords = [9.3068, 123.3033];
    L.marker(branchCoords, {
        icon: L.divIcon({
            className: 'branch-marker',
            html: '<div style="background:#007BFF;width:40px;height:40px;border-radius:50%;border:3px solid white;display:flex;align-items:center;justify-content:center;"><i class="bi bi-shop" style="color:white;"></i></div>',
            iconSize: [40, 40]
        })
    }).addTo(modalMapInstance).bindPopup("<b>Main Branch - Sibulan</b>");
}

function addPickupMarkerToModalMap(pickup) {
    const color = getStatusColor(pickup.status) === 'warning' ? '#FFC107' : '#007BFF';

    const modalMarker = L.marker([pickup.latitude, pickup.longitude], {
        icon: L.divIcon({
            className: 'pickup-marker',
            html: `<div style="background:${color};width:32px;height:32px;border-radius:50%;border:3px solid white;display:flex;align-items:center;justify-content:center;"><i class="bi bi-geo-alt-fill" style="color:white;font-size:14px;"></i></div>`,
            iconSize: [32, 32]
        })
    }).bindPopup(createPickupPopup(pickup));

    if (modalPickupCluster) {
        modalPickupCluster.addLayer(modalMarker);
    } else {
        modalMarker.addTo(modalMapInstance);
    }
}

/**
 * DASHBOARD CHART FUNCTIONS
 */
function initializeCharts() {
    // Revenue Chart
    const revenueCtx = document.getElementById('revenueChart');
    if (revenueCtx) {
        DASHBOARD_CONFIG.charts.revenue = new Chart(revenueCtx, {
            type: 'line',
            data: {
                labels: {!! json_encode($stats['revenueLabels'] ?? []) !!},
                datasets: [{
                    label: 'Daily Revenue',
                    data: {!! json_encode($stats['last7DaysRevenue'] ?? []) !!},
                    borderColor: '#3D3B6B',
                    backgroundColor: 'rgba(61, 59, 107, 0.1)',
                    borderWidth: 2,
                    fill: true,
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        mode: 'index',
                        intersect: false,
                        callbacks: {
                            label: function(context) {
                                return '₱' + context.parsed.y.toLocaleString();
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: { color: 'rgba(0, 0, 0, 0.05)' },
                        ticks: {
                            callback: function(value) { return '₱' + value.toLocaleString(); }
                        }
                    },
                    x: { grid: { display: false } }
                }
            }
        });
    }

    // Customer Source Chart
    const customerSourceCtx = document.getElementById('customerSourceChart');
    if (customerSourceCtx) {
        const sources = {!! json_encode($stats['customerRegistrationSource'] ?? []) !!};
        DASHBOARD_CONFIG.charts.customerSource = new Chart(customerSourceCtx, {
            type: 'doughnut',
            data: {
                labels: ['Walk-in', 'Mobile App', 'Referral', 'Other'],
                datasets: [{
                    data: [
                        sources.walk_in || 0,
                        sources.app || 0,
                        sources.referral || 0,
                        sources.other || 0
                    ],
                    backgroundColor: ['#3B82F6', '#10B981', '#F59E0B', '#8B5CF6'],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom', labels: { padding: 20, usePointStyle: true } }
                },
                cutout: '70%'
            }
        });
    }
}

/**
 * TAB MANAGEMENT
 */
function initializeTabs() {
    const activeTab = localStorage.getItem(DASHBOARD_CONFIG.cacheKey) || 'overview';
    const tabButton = document.getElementById(`${activeTab}-tab`);

    if (tabButton) {
        const tab = new bootstrap.Tab(tabButton);
        tab.show();
    }

    document.querySelectorAll('[data-bs-toggle="pill"]').forEach(tab => {
        tab.addEventListener('shown.bs.tab', function(event) {
            const activeTabName = event.target.getAttribute('id').replace('-tab', '');
            localStorage.setItem(DASHBOARD_CONFIG.cacheKey, activeTabName);

            // Initialize map if operations tab is shown
            if (activeTabName === 'operations') {
                setTimeout(() => {
                    if (!logisticsMapInstance) {
                        initLogisticsMap();
                    } else {
                        logisticsMapInstance.invalidateSize();
                    }
                }, 200);
            }
        });
    });
}

/**
 * AUTO REFRESH
 */
function initializeAutoRefresh() {
    if (DASHBOARD_CONFIG.autoRefresh) {
        setInterval(refreshDashboardStats, DASHBOARD_CONFIG.refreshInterval);
    }
}

function refreshDashboardStats() {
    const refreshBtn = document.getElementById('refresh-btn');
    const originalHtml = refreshBtn ? refreshBtn.innerHTML : '';

    if(refreshBtn) {
        refreshBtn.innerHTML = '<i class="bi bi-arrow-clockwise me-2"></i><span>Refreshing...</span>';
        refreshBtn.disabled = true;
    }

    fetch('/admin/dashboard/stats')
        .then(response => {
            if (!response.ok) throw new Error('Network response was not ok');
            return response.json();
        })
        .then(data => {
            updateDashboardData(data);
            showToast('Dashboard updated successfully', 'success');
        })
        .catch(error => {
            console.error('Error refreshing dashboard:', error);
            showToast('Failed to refresh dashboard', 'danger');
        })
        .finally(() => {
            if(refreshBtn) {
                refreshBtn.innerHTML = originalHtml;
                refreshBtn.disabled = false;
            }
        });
}

function refreshDashboard() {
    refreshDashboardStats();
}

function updateDashboardData(data) {
    if (data.todayOrders !== undefined) updateElementText('[data-kpi="todayOrders"]', data.todayOrders);
    if (data.todayRevenue !== undefined) updateElementText('[data-kpi="todayRevenue"]', '₱' + data.todayRevenue.toLocaleString());
    if (data.activeCustomers !== undefined) updateElementText('[data-kpi="activeCustomers"]', data.activeCustomers.toLocaleString());
    if (data.unclaimedLaundry !== undefined) updateElementText('[data-kpi="unclaimedLaundry"]', data.unclaimedLaundry);

    document.getElementById('last-sync').textContent = 'Updated just now';

    if (DASHBOARD_CONFIG.charts.revenue && data.revenueData) {
        DASHBOARD_CONFIG.charts.revenue.data.labels = data.revenueData.labels || [];
        DASHBOARD_CONFIG.charts.revenue.data.datasets[0].data = data.revenueData.values || [];
        DASHBOARD_CONFIG.charts.revenue.update();
    }
}

function updateElementText(selector, text) {
    const element = document.querySelector(selector);
    if (element) element.textContent = text;
}

function initializeDateUpdater() {
    const updateDate = () => {
        const now = new Date();
        const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
        const dateEl = document.getElementById('current-date');
        if(dateEl) dateEl.textContent = now.toLocaleDateString('en-US', options);
    };
    updateDate();
    setInterval(updateDate, 60000);
}

function showToast(message, type = 'info') {
    let toastContainer = document.querySelector('.toast-container');
    if (!toastContainer) {
        toastContainer = document.createElement('div');
        toastContainer.className = 'toast-container position-fixed bottom-0 end-0 p-3';
        document.body.appendChild(toastContainer);
    }

    const toastEl = document.createElement('div');
    toastEl.className = `toast align-items-center text-bg-${type} border-0`;
    toastEl.setAttribute('role', 'alert');
    toastEl.innerHTML = `
        <div class="d-flex">
            <div class="toast-body">${message}</div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>
    `;
    toastContainer.appendChild(toastEl);
    const toast = new bootstrap.Toast(toastEl, { delay: 3000 });
    toast.show();
    toastEl.addEventListener('hidden.bs.toast', () => toastEl.remove());
}

// Make functions available globally
window.refreshDashboard = refreshDashboard;
window.refreshMapMarkers = refreshMapMarkers;
window.getRouteToPickup = getRouteToPickup;
window.startNavigation = startNavigation;
window.startNavigationForPickup = startNavigationForPickup;
window.closeRouteDetails = closeRouteDetails;
window.clearRoute = clearRoute;
window.printRoute = printRoute;
window.showBranchInfo = showBranchInfo;
window.viewPickupDetails = viewPickupDetails;
window.togglePickupSelection = togglePickupSelection;
window.getOptimizedMultiRoute = getOptimizedMultiRoute;
window.autoRouteAllVisible = autoRouteAllVisible;
window.selectAllPending = selectAllPending;
window.clearSelections = clearSelections;
window.startMultiPickupNavigation = startMultiPickupNavigation;
window.printRouteSchedule = printRouteSchedule;

// Export function (placeholder)
window.exportData = function(format) {
    alert('Export to ' + format + ' functionality to be implemented');
};
</script>
@endpush
