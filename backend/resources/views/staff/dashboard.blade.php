@extends('staff.layouts.staff')



@section('page-title', 'Dashboard Overview')





@section('content')

    <div class="dashboard-container">

        <!-- Page Header -->

        <div class="page-header modern-header">

            <div class="header-content">

                <h1 class="header-title">Dashboard Overview</h1>

                <p class="page-subtitle">Monitor your laundry business performance across all branches</p>

            </div>

            <div class="page-actions">

                <button onclick="window.print()" class="btn btn-secondary btn-icon-text">

                    <i class="fas fa-print"></i> <span>Print Report</span>

                </button>

                <a href="{{ route('staff.dashboard.export') }}" class="btn btn-primary btn-icon-text">

                    <i class="fas fa-download"></i> <span>Export Data</span>

                </a>

            </div>

        </div>



        <!-- Filters -->

        <div class="filters-card modern-filters">

            <form method="GET" action="{{ route('staff.dashboard') }}" class="filters-form">

                <div class="filter-group">

                    <label class="filter-label">
                        <i class="fas fa-calendar"></i>
                        <span>Date Range</span>
                    </label>

                    <select name="date_range" class="form-select modern-select" onchange="this.form.submit()">

                        <option value="today" {{ $current_filters['date_range'] == 'today' ? 'selected' : '' }}>Today</option>

                        <option value="yesterday" {{ $current_filters['date_range'] == 'yesterday' ? 'selected' : '' }}>
                            Yesterday</option>

                        <option value="last_7_days" {{ $current_filters['date_range'] == 'last_7_days' ? 'selected' : '' }}>
                            Last 7 Days</option>

                        <option value="last_30_days" {{ $current_filters['date_range'] == 'last_30_days' ? 'selected' : '' }}>
                            Last 30 Days</option>

                        <option value="this_month" {{ $current_filters['date_range'] == 'this_month' ? 'selected' : '' }}>This
                            Month</option>

                        <option value="last_month" {{ $current_filters['date_range'] == 'last_month' ? 'selected' : '' }}>Last
                            Month</option>

                    </select>

                </div>



                <div class="filter-group">

                    <label class="filter-label">
                        <i class="fas fa-store"></i>
                        <span>Branch</span>
                    </label>

                    <select name="branch_id" class="form-select modern-select" onchange="this.form.submit()">

                        <option value="">All Branches</option>

                        @foreach($branches as $branch)

                            <option value="{{ $branch->id }}" {{ $current_filters['branch_id'] == $branch->id ? 'selected' : '' }}>

                                {{ $branch->name }}

                            </option>

                        @endforeach

                    </select>

                </div>



                @if($current_filters['date_range'] != 'last_30_days' || $current_filters['branch_id'])

                    <a href="{{ route('staff.dashboard') }}" class="btn btn-secondary reset-btn">

                        <i class="fas fa-redo"></i> Reset Filters

                    </a>

                @endif

            </form>

        </div>



        <!-- Alerts Section -->

        @if(count($alerts) > 0)

            <div class="alerts-section">

                @foreach($alerts as $alert)

                    <div class="alert alert-{{ $alert['type'] }}">

                        <div class="alert-icon">

                            <i class="fas fa-{{ $alert['icon'] }}"></i>

                        </div>

                        <div class="alert-content">

                            <div class="alert-title">{{ $alert['title'] }}</div>

                            <div class="alert-text">{{ $alert['message'] }}</div>

                        </div>

                        @if(isset($alert['action']))

                            <a href="{{ $alert['action'] }}" class="alert-action">

                                {{ $alert['action_text'] ?? 'View Details' }}

                            </a>

                        @endif

                    </div>

                @endforeach

            </div>

        @endif



        <!-- KPI Cards Grid -->

        <div class="kpi-grid modern-kpi-grid">

            <!-- Today's Revenue -->

            <div class="kpi-card modern-kpi-card">

                <div class="kpi-icon modern-icon" style="background: linear-gradient(135deg, #06B6D4, #0891B2);">

                    <i class="fas fa-peso-sign"></i>

                </div>

                <div class="kpi-content">

                    <div class="kpi-label">Today's Revenue</div>

                    <div class="kpi-value">₱{{ number_format($kpis['today_revenue']['value'], 2) }}</div>

                    <div class="kpi-change {{ $kpis['today_revenue']['change'] >= 0 ? 'up' : 'down' }}">

                        <i class="fas fa-{{ $kpis['today_revenue']['change'] >= 0 ? 'arrow-up' : 'arrow-down' }}"></i>

                        {{ number_format(abs($kpis['today_revenue']['change']), 1) }}% vs {{ $kpis['today_revenue']['vs'] }}

                    </div>

                </div>

            </div>



            <!-- Monthly Revenue -->

            <div class="kpi-card modern-kpi-card">

                <div class="kpi-icon modern-icon" style="background: linear-gradient(135deg, #10b981, #059669);">

                    <i class="fas fa-chart-line"></i>

                </div>

                <div class="kpi-content">

                    <div class="kpi-label">This Month</div>

                    <div class="kpi-value">₱{{ number_format($kpis['monthly_revenue']['value'], 2) }}</div>

                    <div class="kpi-change {{ $kpis['monthly_revenue']['change'] >= 0 ? 'up' : 'down' }}">

                        <i class="fas fa-{{ $kpis['monthly_revenue']['change'] >= 0 ? 'arrow-up' : 'arrow-down' }}"></i>

                        {{ number_format(abs($kpis['monthly_revenue']['change']), 1) }}% vs
                        {{ $kpis['monthly_revenue']['vs'] }}

                    </div>

                </div>

            </div>



            <!-- Unclaimed Value -->

            @if($unclaimed['total_value'] > 0)

                <div class="kpi-card kpi-danger">

                    <div class="kpi-icon" style="background: #ef4444;">

                        <i class="fas fa-exclamation-triangle"></i>

                    </div>

                    <div class="kpi-content">

                        <div class="kpi-label">Unclaimed Value</div>

                        <div class="kpi-value">₱{{ number_format($unclaimed['total_value'], 2) }}</div>

                        <div class="kpi-meta">{{ $unclaimed['total_count'] }} orders at risk</div>

                    </div>

                </div>

            @endif



            <!-- Active Orders -->

            <div class="kpi-card modern-kpi-card">

                <div class="kpi-icon modern-icon" style="background: linear-gradient(135deg, #3b82f6, #1d4ed8);">

                    <i class="fas fa-box"></i>

                </div>

                <div class="kpi-content">

                    <div class="kpi-label">Active Orders</div>

                    <div class="kpi-value">{{ $kpis['active_orders']['value'] }}</div>

                    <div class="kpi-meta">Across all branches</div>

                </div>

            </div>



            <!-- Ready for Pickup -->

            <div class="kpi-card modern-kpi-card kpi-warning">

                <div class="kpi-icon modern-icon" style="background: linear-gradient(135deg, #f59e0b, #d97706);">

                    <i class="fas fa-shopping-bag"></i>

                </div>

                <div class="kpi-content">

                    <div class="kpi-label">Ready for Pickup</div>

                    <div class="kpi-value">{{ $kpis['ready_for_pickup']['value'] }}</div>

                    <div class="kpi-meta">Avg: {{ number_format($kpis['ready_for_pickup']['avg_wait_days'], 1) }} days old
                    </div>

                </div>

            </div>



            <!-- Completed Today -->

            <div class="kpi-card modern-kpi-card kpi-success">

                <div class="kpi-icon modern-icon" style="background: linear-gradient(135deg, #10b981, #047857);">

                    <i class="fas fa-check-circle"></i>

                </div>

                <div class="kpi-content">

                    <div class="kpi-label">Completed Today</div>

                    <div class="kpi-value">{{ $kpis['completed_today']['value'] }}</div>

                    <div class="kpi-meta">Successfully delivered</div>

                </div>

            </div>



            <!-- Total Customers -->

            <div class="kpi-card modern-kpi-card">

                <div class="kpi-icon modern-icon" style="background: linear-gradient(135deg, #6366f1, #4f46e5);">

                    <i class="fas fa-users"></i>

                </div>

                <div class="kpi-content">

                    <div class="kpi-label">Total Customers</div>

                    <div class="kpi-value">{{ number_format($kpis['total_customers']['value']) }}</div>

                    <div class="kpi-meta">{{ $kpis['total_customers']['new_this_month'] }} new this month</div>

                </div>

            </div>



            <!-- Pending Pickups -->

            <div class="kpi-card modern-kpi-card">

                <div class="kpi-icon modern-icon" style="background: linear-gradient(135deg, #ec4899, #be185d);">

                    <i class="fas fa-truck"></i>

                </div>

                <div class="kpi-content">

                    <div class="kpi-label">Pending Pickups</div>

                    <div class="kpi-value">{{ $kpis['pending_pickups']['value'] }}</div>

                    <div class="kpi-meta">

                        <a href="{{ route('staff.pickups.index') }}"
                            style="color: #8b5cf6; text-decoration: underline;">View Map →</a>

                    </div>

                </div>

            </div>

        </div>



        <!-- Charts Row -->

        <div class="charts-row modern-charts">

            <!-- Revenue Trend Chart -->

            <div class="chart-card modern-chart-card chart-full">

                <div class="chart-header">

                    <h3 class="chart-title">

                        <i class="fas fa-chart-area"></i> Revenue Trend

                    </h3>

                    <div class="chart-actions">

                        <button class="btn-icon" title="Refresh" onclick="location.reload()">

                            <i class="fas fa-sync-alt"></i>

                        </button>

                        <button class="btn-icon" title="Export">

                            <i class="fas fa-download"></i>

                        </button>

                    </div>

                </div>

                <div class="chart-body">

                    <canvas id="revenueTrendChart" height="80"></canvas>

                </div>

                <div class="chart-footer">

                    <div class="chart-stat">

                        <span class="stat-label">Average Daily</span>

                        <span class="stat-value">₱{{ number_format($revenue['per_day'], 2) }}</span>

                    </div>

                    <div class="chart-stat">

                        <span class="stat-label">Highest Day</span>

                        <span class="stat-value">₱{{ number_format($revenue['highest'], 2) }}</span>

                    </div>

                    <div class="chart-stat">

                        <span class="stat-label">Total Orders</span>

                        <span class="stat-value">{{ number_format($revenue['orders']) }}</span>

                    </div>

                </div>

            </div>

        </div>



        <!-- Branch Performance & Order Pipeline -->

        <div class="charts-row charts-half modern-charts">

            <!-- Branch Performance -->

            <div class="chart-card modern-chart-card">

                <div class="chart-header">

                    <h3 class="chart-title">

                        <i class="fas fa-store"></i> Branch Performance

                    </h3>

                </div>

                <div class="chart-body">

                    @foreach($branches as $branch)

                        <div class="progress-item">

                            <div class="progress-header">

                                <span class="progress-label">{{ $branch['name'] }}</span>

                                <span class="progress-value">₱{{ number_format($branch['revenue'], 2) }}
                                    ({{ $branch['percentage'] }}%)</span>

                            </div>

                            <div class="progress-bar-container">

                                <div class="progress-bar"
                                    style="width: {{ $branch['percentage'] }}%; background: linear-gradient(to right, #2D2B5F, #FF5C35);">
                                </div>

                            </div>

                        </div>

                    @endforeach

                </div>

            </div>



            <!-- Order Status Pipeline -->

            <div class="chart-card modern-chart-card">

                <div class="chart-header">

                    <h3 class="chart-title">

                        <i class="fas fa-stream"></i> Order Status Pipeline

                    </h3>

                </div>

                <div class="chart-body">

                    <div class="pipeline-item">

                        <div class="pipeline-header">

                            <span class="pipeline-label">

                                <span class="status-dot" style="background: #3b82f6;"></span>

                                Received

                            </span>

                            <span class="pipeline-value">{{ $pipeline['received'] ?? 0 }} orders</span>

                        </div>

                        <div class="progress-bar-container">

                            <div class="progress-bar"
                                style="width: {{ $pipeline['received'] ?? 0 > 0 ? '100%' : '0%' }}; background: #3b82f6;">
                            </div>

                        </div>

                    </div>



                    <div class="pipeline-item">

                        <div class="pipeline-header">

                            <span class="pipeline-label">

                                <span class="status-dot" style="background: #f59e0b;"></span>

                                Ready

                            </span>

                            <span class="pipeline-value">{{ $pipeline['ready'] ?? 0 }} orders</span>

                        </div>

                        <div class="progress-bar-container">

                            <div class="progress-bar"
                                style="width: {{ $pipeline['ready'] ?? 0 > 0 ? '100%' : '0%' }}; background: #f59e0b;">
                            </div>

                        </div>

                    </div>



                    <div class="pipeline-item">

                        <div class="pipeline-header">

                            <span class="pipeline-label">

                                <span class="status-dot" style="background: #10b981;"></span>

                                Paid

                            </span>

                            <span class="pipeline-value">{{ $pipeline['paid'] ?? 0 }} orders</span>

                        </div>

                        <div class="progress-bar-container">

                            <div class="progress-bar"
                                style="width: {{ $pipeline['paid'] ?? 0 > 0 ? '100%' : '0%' }}; background: #10b981;"></div>

                        </div>

                    </div>



                    <div class="pipeline-item">

                        <div class="pipeline-header">

                            <span class="pipeline-label">

                                <span class="status-dot" style="background: #6366f1;"></span>

                                Completed Today

                            </span>

                            <span class="pipeline-value">{{ $pipeline['completed'] ?? 0 }} orders</span>

                        </div>

                        <div class="progress-bar-container">

                            <div class="progress-bar"
                                style="width: {{ $pipeline['completed'] ?? 0 > 0 ? '100%' : '0%' }}; background: #6366f1;">
                            </div>

                        </div>

                    </div>

                </div>

            </div>

        </div>



        <!-- Branch Locations Map -->

        <div class="chart-card modern-chart-card" style="margin-bottom: 2rem;">

            <div class="chart-header">

                <h3 class="chart-title">

                    <i class="fas fa-map-location-dot"></i> Branch Locations

                </h3>

                <div class="chart-actions">

                    <button class="btn-icon" onclick="location.reload()" title="Refresh Map">

                        <i class="fas fa-sync-alt"></i>

                    </button>

                    <button class="btn-icon" data-bs-toggle="modal" data-bs-target="#mapModal" title="Open Map Modal">

                        <i class="fas fa-expand"></i>

                    </button>

                </div>

            </div>

            <div class="chart-body" style="padding: 0; overflow: hidden; position: relative;">

                <div id="branchMap" style="height: 500px; width: 100%; border-radius: 8px;"></div>

                <div id="map-controls-container" style="position: absolute; top: 10px; left: 10px; z-index: 1000;">
                    <div id="eta-display-container" style="display: none; margin-bottom: 10px; background: rgba(255,255,255,0.95); padding: 6px 10px; border-radius: 6px; box-shadow: 0 2px 6px rgba(0,0,0,0.12);">
                    </div>

                    <div id="route-loading-spinner" style="display: none; align-items: center; gap:8px; margin-bottom: 6px; background: rgba(255,255,255,0.95); padding: 6px 10px; border-radius: 6px; box-shadow: 0 2px 6px rgba(0,0,0,0.12);">
                        <div class="spinner-border spinner-border-sm text-primary" role="status" aria-hidden="true"></div>
                        <div class="spinner-text" style="font-size:13px; color:#1f2937;">Loading route&hellip;</div>
                    </div>

                    <div class="route-controls" style="display: none; margin-top: 6px; background: rgba(255,255,255,0.95); padding: 6px; border-radius: 6px; box-shadow: 0 2px 6px rgba(0,0,0,0.12);">
                        <button class="route-btn btn-clear-route" onclick="clearRouteFromMaps()">
                            <i class="bi bi-x-circle"></i> Clear Route
                        </button>
                    </div>
                </div>

            </div>

        </div>

        @include('components.map-modal')

        {{-- Route Details Panel (fixed right) --}}
        <div id="routeDetailsPanel" class="route-details-panel" style="display: none;"></div>



        <!-- Recent Orders Table -->

        <div class="table-card modern-table-card">

            <div class="table-header">

                <h3 class="table-title">

                    <i class="fas fa-list"></i> Recent Orders

                </h3>

                <a href="{{ route('staff.orders.index') }}" class="btn btn-secondary btn-sm">

                    View All Orders <i class="fas fa-arrow-right"></i>

                </a>

            </div>

            <div class="table-responsive">

                <table class="data-table">

                    <thead>

                        <tr>

                            <th>Tracking #</th>

                            <th>Customer</th>

                            <th>Branch</th>

                            <th>Service</th>

                            <th>Weight</th>

                            <th>Amount</th>

                            <th>Status</th>

                            <th>Date</th>

                            <th>Actions</th>

                        </tr>

                    </thead>

                    <tbody>

                        @forelse($recent_orders as $order)

                            <tr>

                                <td>

                                    <strong class="tracking-number">{{ $order->tracking_number }}</strong>

                                </td>

                                <td>

                                    <div class="customer-info">

                                        <div class="customer-name">{{ $order->customer->name }}</div>

                                    </div>

                                </td>

                                <td>

                                    <span class="branch-badge">{{ $order->branch->name }}</span>

                                </td>

                                <td>{{ $order->service->name }}</td>

                                <td>{{ number_format($order->weight, 2) }} kg</td>

                                <td>

                                    <strong class="amount">₱{{ number_format($order->total_amount, 2) }}</strong>

                                </td>

                                <td>

                                    <span class="status-badge status-{{ $order->status }}">

                                        {{ ucfirst($order->status) }}

                                    </span>

                                </td>

                                <td>

                                    <span class="date-text">{{ $order->created_at->format('M d, Y') }}</span>

                                    <span class="time-text">{{ $order->created_at->format('h:i A') }}</span>

                                </td>

                                <td>

                                    <div class="action-buttons">

                                        <a href="{{ route('staff.orders.show', $order->id) }}" class="btn-icon" title="View">

                                            <i class="fas fa-eye"></i>

                                        </a>

                                        <a href="{{ route('staff.orders.edit', $order->id) }}" class="btn-icon" title="Edit">

                                            <i class="fas fa-edit"></i>

                                        </a>

                                    </div>

                                </td>

                            </tr>

                        @empty

                            <tr>

                                <td colspan="9" class="text-center">

                                    <div class="empty-state">

                                        <i class="fas fa-inbox"></i>

                                        <p>No orders found</p>

                                    </div>

                                </td>

                            </tr>

                        @endforelse

                    </tbody>

                </table>

            </div>

        </div>

    </div>

@endsection



@push('scripts')

    <script>

        document.addEventListener('DOMContentLoaded', function () {

            // Initialize OpenStreetMap with Leaflet

            const map = L.map('branchMap').setView([12.8797, 121.7740], 6); // Centered on Philippines



            // Add OpenStreetMap tile layer

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {

                attribution: '&copy; <a href="https://www.openstreetmap.org/">OpenStreetMap</a>',

                maxZoom: 19,

                className: 'map-tiles'

            }).addTo(map);



            // Branch data from controller

            const branches = @json($branches);



            // Custom icons for branches

            const branchIcon = L.divIcon({

                html: `<div style="background: linear-gradient(135deg, #2D2B5F, #FF5C35); color: white; border-radius: 50%; width: 40px; height: 40px; display: flex; align-items: center; justify-content: center; font-weight: bold; box-shadow: 0 2px 8px rgba(0,0,0,0.2); border: 2px solid white;">

    <i class="fas fa-store" style="color: white;"></i>

    </div>`,

                iconSize: [40, 40],

                className: 'branch-marker'

            });



            // Add markers for each branch

            branches.forEach((branch, index) => {

                if (branch.latitude && branch.longitude) {

                    const marker = L.marker(

                        [parseFloat(branch.latitude), parseFloat(branch.longitude)],

                        { icon: branchIcon }

                    ).addTo(map);



                    // Popup content with branch info

                    const popupContent = `

    <div style="font-family: 'Inter', sans-serif; min-width: 200px;">

    <div style="font-weight: 700; color: #2D2B5F; margin-bottom: 8px; font-size: 14px;">

    ${branch.name}

    </div>

    <div style="font-size: 13px; color: #4B5563; margin-bottom: 6px;">

    <i class="fas fa-map-marker-alt" style="color: #FF5C35; margin-right: 6px;"></i>

    ${branch.address || 'N/A'}

    </div>

    <div style="font-size: 13px; color: #4B5563; margin-bottom: 6px;">

    <i class="fas fa-phone" style="color: #FF5C35; margin-right: 6px;"></i>

    ${branch.phone || 'N/A'}

    </div>

    <div style="font-size: 13px; color: #4B5563; margin-bottom: 8px;">

    <i class="fas fa-peso-sign" style="color: #FF5C35; margin-right: 6px;"></i>

    Revenue: ₱${branch.revenue ? parseFloat(branch.revenue).toLocaleString('en-US', { minimumFractionDigits: 2 }) : '0.00'}

    </div>

    <div style="border-top: 1px solid #E5E7EB; padding-top: 8px; margin-top: 8px;">

    <a href="/staff/branches/${branch.id}" style="color: #2D2B5F; text-decoration: none; font-weight: 600; font-size: 13px;">

    View Details <i class="fas fa-arrow-right" style="margin-left: 4px;"></i>

    </a>

    </div>

    </div>

    `;



                    marker.bindPopup(popupContent, {

                        maxWidth: 280,

                        className: 'branch-popup'

                    });



                    // Open popup on marker click

                    marker.on('click', function () {

                        marker.openPopup();

                    });

                }

            });



            // Fit bounds to show all markers

            if (branches.length > 0) {

                const bounds = [];

                branches.forEach(branch => {

                    if (branch.latitude && branch.longitude) {

                        bounds.push([parseFloat(branch.latitude), parseFloat(branch.longitude)]);

                    }

                });

                if (bounds.length > 0) {

                    const group = new L.featureGroup(bounds.map(b => L.marker(b)));

                    map.fitBounds(group.getBounds().pad(0.1));

                }

            }

            // Expose map instance for later scripts
            window.branchMap = map;

        });

    </script>

@endpush



@push('scripts')
<script>
// Modal + routing globals
let modalMapInstance = null;
let modalPickupCluster = null;
let routeLayer = null;
let startMarker = null;
let endMarker = null;

function showToast(message, type = 'info') {
    // Use Bootstrap toasts for nicer UX (same implementation as admin)
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
    const toast = new bootstrap.Toast(toastEl, { delay: 4000 });
    toast.show();
    toastEl.addEventListener('hidden.bs.toast', () => toastEl.remove());
}

function decodePolyline(encoded) {
    if (!encoded || typeof encoded !== 'string') return [];
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

        points.push([lat / 1e5, lng / 1e5]);
    }
    return points;
}

function clearRouteFromMaps() {
    const maps = [modalMapInstance, window.branchMap];

    maps.forEach(map => {
        if (map && typeof map.removeLayer === 'function') {
            try {
                if (routeLayer && typeof routeLayer.removeFrom === 'function') {
                    routeLayer.removeFrom(map);
                } else if (routeLayer) {
                    map.removeLayer(routeLayer);
                }
            } catch (e) {
                console.warn('Could not remove routeLayer:', e);
            }

            try {
                if (startMarker && typeof startMarker.removeFrom === 'function') {
                    startMarker.removeFrom(map);
                } else if (startMarker) {
                    map.removeLayer(startMarker);
                }
            } catch (e) {
                console.warn('Could not remove startMarker:', e);
            }

            try {
                if (endMarker && typeof endMarker.removeFrom === 'function') {
                    endMarker.removeFrom(map);
                } else if (endMarker) {
                    map.removeLayer(endMarker);
                }
            } catch (e) {
                console.warn('Could not remove endMarker:', e);
            }
        }
    });

    routeLayer = null; startMarker = null; endMarker = null;

    // Cancel any pending route fetch retries
    Object.keys(activeRouteRequests || {}).forEach(pid => {
        try { if (activeRouteRequests[pid].controller) activeRouteRequests[pid].controller.abort(); } catch(e) {}
        try { if (activeRouteRequests[pid].timer) clearTimeout(activeRouteRequests[pid].timer); } catch(e) {}
        delete activeRouteRequests[pid];
    });

    // Hide both main and modal ETA containers (if present)
    const etaContainers = [document.getElementById('eta-display-container'), document.getElementById('eta-display-container-modal')];
    etaContainers.forEach(c => { if (c) c.style.display = 'none'; });

    // Hide route loading spinners if visible
    const spinners = [document.getElementById('route-loading-spinner'), document.getElementById('route-loading-spinner-modal')];
    spinners.forEach(s => { if (s) s.style.display = 'none'; });

    closeRouteDetails();
    toggleRouteControls(false);
}

function showSingleRouteDetails(routeData, pickupId) {
    let detailsPanel = document.getElementById('routeDetailsPanel');
    if (!detailsPanel) return;

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
                        <i class="bi bi-signpost"></i> ${routeData.route?.distance?.text || ''}
                    </h5>
                    <p class="text-muted">
                        <i class="bi bi-clock"></i> ${routeData.route?.duration?.text || ''}
                    </p>
                </div>
                <hr>
                <div class="mb-3">
                    <small class="text-muted">From (Branch):</small>
                    <p class="mb-0"><b>${routeData.branch?.name || 'Branch'}</b></p>
                    <small class="text-muted">${routeData.branch?.address || ''}</small>
                </div>
                <div class="mb-3">
                    <small class="text-muted">To (Pickup):</small>
                    <p class="mb-0"><b>Customer Location</b></p>
                    <small class="text-muted">${routeData.estimated_arrival || ''} ETA</small>
                </div>
                <hr>
                <div class="d-grid gap-2">
                    <button class="btn btn-success" onclick="startNavigationForPickup(${pickupId})">
                        <i class="bi bi-play-circle me-2"></i> Start Navigation
                    </button>
                    <button class="btn btn-outline-primary" onclick="printRoute()">
                        <i class="bi bi-printer me-2"></i> Print Directions
                    </button>
                    <button class="btn btn-outline-danger" onclick="clearRouteFromMaps()">
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
    if (panel) panel.style.display = 'none';
}

function updateETADisplay(etaTime) {
    const containers = [
        document.getElementById('eta-display-container'),
        document.getElementById('eta-display-container-modal')
    ].filter(Boolean);

    containers.forEach(etaContainer => {
        etaContainer.innerHTML = `
            <div class="eta-display">
                <div class="eta-label">Estimated Arrival</div>
                <div class="eta-time">${etaTime || ''}</div>
                <small class="text-muted">Based on current traffic</small>
            </div>
        `;
        etaContainer.style.display = 'block';
    });
}

function toggleRouteControls(show = true) {
    // Toggle both main map and modal route controls if present
    const routeControls = document.querySelectorAll('.route-controls, .route-controls-modal');
    routeControls.forEach(el => el.style.display = show ? 'block' : 'none');
}

const ROUTE_RETRY_MAX = 2;
const ROUTE_TIMEOUT_MS = 10000; // 10s before retry
let activeRouteRequests = {}; // { [pickupId]: { controller, retries, timer } }

function showRouteSpinner(show = true) {
    const spinnerEls = [
        document.getElementById('route-loading-spinner'),
        document.getElementById('route-loading-spinner-modal')
    ].filter(Boolean);

    spinnerEls.forEach(el => {
        if (show) {
            el.style.display = 'flex';
            // fade in
            el.style.opacity = 0;
            requestAnimationFrame(() => { el.style.transition = 'opacity 180ms ease'; el.style.opacity = 1; });
        } else {
            // fade out then hide
            el.style.transition = 'opacity 180ms ease';
            el.style.opacity = 0;
            setTimeout(() => { el.style.display = 'none'; }, 200);
        }
    });
}

async function fetchRouteWithRetries(pickupId, attempt = 0) {
    const url = `/staff/pickups/${pickupId}/route`;

    // Abort controller for this attempt
    const controller = new AbortController();

    // Clear previous timer if any and store new state
    if (activeRouteRequests[pickupId] && activeRouteRequests[pickupId].timer) {
        clearTimeout(activeRouteRequests[pickupId].timer);
    }

    const timer = setTimeout(() => handleRouteTimeout(pickupId), ROUTE_TIMEOUT_MS);
    activeRouteRequests[pickupId] = { controller, retries: attempt, timer };

    try {
        const response = await fetch(url, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }, signal: controller.signal });
        clearTimeout(activeRouteRequests[pickupId].timer);
        delete activeRouteRequests[pickupId];

        if (!response.ok) throw new Error(`HTTP ${response.status}`);
        const data = await response.json();
        return data;
    } catch (err) {
        // If aborted due to retry, do not treat as final error here
        if (err.name === 'AbortError') {
            // The timeout handler will re-invoke fetchRouteWithRetries
            return null;
        }
        clearTimeout(activeRouteRequests[pickupId]?.timer);
        delete activeRouteRequests[pickupId];
        throw err;
    }
}

function handleRouteTimeout(pickupId) {
    const entry = activeRouteRequests[pickupId];
    if (!entry) return;

    if ((entry.retries || 0) < ROUTE_RETRY_MAX) {
        // retry
        entry.controller.abort();
        showToast('Route request taking too long — retrying...', 'warning');
        fetchRouteWithRetries(pickupId, (entry.retries || 0) + 1).then(data => {
            if (data && data.success && data.route) {
                // draw route and UI
                // keep spinner until parent handler hides it
                // note: call the same logic used after fetch success
                try {
                    if (document.getElementById('mapModal')?.classList.contains('show') && modalMapInstance) {
                        modalMapInstance.invalidateSize();
                        drawSingleRouteOnMap(data);
                    } else if (window.branchMap) {
                        try { window.branchMap.invalidateSize(); } catch (e) { }
                        drawSingleRouteOnMap(data);
                    } else if (modalMapInstance) {
                        modalMapInstance.invalidateSize();
                        drawSingleRouteOnMap(data);
                    }
                    showSingleRouteDetails(data, pickupId);
                    if (data.estimated_arrival) updateETADisplay(data.estimated_arrival);
                    toggleRouteControls(true);
                    showRouteSpinner(false);
                    showToast('Route loaded successfully!', 'success');
                } catch (e) {
                    console.error('Error handling retried route:', e);
                }
            } else {
                // if retry returned null, the fetch was aborted and a further retry or finalization will be handled elsewhere
            }
        }).catch(err => {
            console.error('Route retry failed:', err);
            showRouteSpinner(false);
            showToast('Failed to load route after retry: ' + (err.message || err), 'danger');
        });
    } else {
        // give up
        entry.controller.abort();
        showRouteSpinner(false);
        showToast('Route request is taking unusually long — please try again later', 'danger');
        delete activeRouteRequests[pickupId];
    }
}

function printRoute() {
    window.print();
}

function drawSingleRouteOnMap(resp) {
    const routeData = resp.route || resp;
    if (!routeData) return;

    clearRouteFromMaps();

    if (routeData.geometry) {
        const coordinates = decodePolyline(routeData.geometry);

        const isModalOpen = document.getElementById('mapModal') && document.getElementById('mapModal').classList.contains('show');
        const targetMap = isModalOpen ? modalMapInstance : (window.branchMap || modalMapInstance);

        if (!targetMap) {
            console.error('No active map instance available to draw route.');
            return;
        }

        try { targetMap.invalidateSize(); } catch (e) { console.warn('Could not invalidate map size:', e); }

        routeLayer = L.polyline(coordinates, {
            color: '#3D3B6B',
            weight: 6,
            opacity: 0.8,
            lineJoin: 'round'
        }).addTo(targetMap);

        let branchCoords;
        if (resp.branch && resp.branch.latitude && resp.branch.longitude) {
            branchCoords = [parseFloat(resp.branch.latitude), parseFloat(resp.branch.longitude)];
        } else {
            branchCoords = @json([$branches->first()->latitude ?? 9.3068, $branches->first()->longitude ?? 123.3033]);
        }

        startMarker = L.circleMarker(branchCoords, {
            radius: 8,
            fillColor: '#007BFF',
            color: '#fff',
            weight: 2,
            fillOpacity: 1
        }).addTo(targetMap).bindPopup('<b>Branch</b>');

        const endCoord = coordinates[coordinates.length - 1];
        endMarker = L.marker(endCoord, {
            icon: L.divIcon({
                className: 'end-marker',
                html: '<div style="background:#28A745;width:30px;height:30px;border-radius:50%;border:3px solid white;display:flex;align-items:center;justify-content:center;box-shadow:0 2px 5px rgba(0,0,0,0.2);"><i class="bi bi-geo-alt-fill" style="color:white;"></i></div>',
                iconSize: [30, 30],
                iconAnchor: [15, 30]
            })
        }).addTo(targetMap).bindPopup('<b>Customer Location</b>');

        targetMap.fitBounds(routeLayer.getBounds(), { padding: [50, 50] });
    }
}

async function getRouteToPickup(pickupId) {
    try {
        // show spinner + toast and use retryable fetch helper
        showRouteSpinner(true);
        showToast('Loading route...', 'info');

        let data;
        try {
            data = await fetchRouteWithRetries(pickupId, 0);
            if (!data) {
                // fetch was aborted for a retry; the retry handler will manage spinner and UI
                return null;
            }
        } catch (err) {
            showRouteSpinner(false);
            showToast('Failed to load route: ' + (err.message || err), 'danger');
            return null;
        }

        if (data.success && data.route) {
            // Draw on main branch map when available; if modal is already open draw there instead
            const modalEl = document.getElementById('mapModal');
            const isModalOpen = modalEl && modalEl.classList.contains('show');
            if (isModalOpen && modalMapInstance) {
                modalMapInstance.invalidateSize();
                drawSingleRouteOnMap(data);
            } else if (window.branchMap) {
                try { window.branchMap.invalidateSize(); } catch (e) { }
                drawSingleRouteOnMap(data);
            } else if (modalMapInstance) {
                // modal exists but isn't open; draw there as a fallback
                modalMapInstance.invalidateSize();
                drawSingleRouteOnMap(data);
            } else {
                console.warn('No map instance available to draw route for pickup', pickupId);
            }

            // Show route details panel like admin
            showSingleRouteDetails(data, pickupId);

            // Update ETA display and show route controls
            if (data.estimated_arrival) {
                updateETADisplay(data.estimated_arrival);
            }
            toggleRouteControls(true);

            // hide spinner and show success
            showRouteSpinner(false);
            showToast('Route loaded successfully!', 'success');

            return data;
        } else {
            throw new Error(data.error || 'Invalid route data');
        }
    } catch (error) {
        console.error('Error fetching route:', error);
        // hide spinner
        showRouteSpinner(false);
        showToast('Failed to load route: ' + (error.message || error), 'danger');
        return null;
    }
}

async function startNavigationForPickup(pickupId) {
    try {
        const response = await fetch(`/staff/pickups/${pickupId}/start-navigation`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Content-Type': 'application/json'
            }
        });

        const data = await response.json();
        if (data.success) {
            showToast('Navigation started!', 'success');
            // refresh page to reflect status change
            location.reload();
        } else {
            showToast('Failed to start navigation: ' + (data.error || data.message), 'danger');
        }
    } catch (error) {
        console.error('Error starting navigation:', error);
        showToast('Failed to start navigation', 'danger');
    }
}

function setupModalMap() {
    const modalEl = document.getElementById('mapModal');
    if (!modalEl) return;

    modalEl.addEventListener('shown.bs.modal', () => {
        if (!modalMapInstance) {
            modalMapInstance = L.map('modalLogisticsMap').setView([@json($branches->first()->latitude ?? 9.3068), @json($branches->first()->longitude ?? 123.3033)], 13);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { attribution: '© OpenStreetMap' }).addTo(modalMapInstance);

            // modal cluster
            modalPickupCluster = L.markerClusterGroup({ chunkedLoading: true });
            modalMapInstance.addLayer(modalPickupCluster);
        }

        setTimeout(() => {
            modalMapInstance.invalidateSize();

            // clear layers except tile layer
            modalMapInstance.eachLayer((layer) => {
                if (layer instanceof L.Marker || layer instanceof L.Polyline || layer instanceof L.CircleMarker) {
                    try { modalMapInstance.removeLayer(layer); } catch (e) { }
                }
            });

            // re-add tile layer (redundant-safe)
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(modalMapInstance);

            // Add branch marker
            const branchCoords = [@json($branches->first()->latitude ?? 9.3068), @json($branches->first()->longitude ?? 123.3033)];
            L.marker(branchCoords, {
                icon: L.divIcon({ className: 'branch-marker', html: '<div style="background:#007BFF;width:40px;height:40px;border-radius:50%;border:3px solid white;display:flex;align-items:center;justify-content:center;"><i class="bi bi-shop" style="color:white;"></i></div>', iconSize: [40,40] })
            }).addTo(modalMapInstance).bindPopup('<b>Branch</b>');

            // Add pickups to modal cluster
            const pickupLocations = @json($pickupLocations ?? []);
            pickupLocations.forEach(pickup => {
                if (pickup.latitude && pickup.longitude) {
                    const color = pickup.status === 'en_route' ? '#007BFF' : (pickup.status === 'pending' ? '#FFC107' : '#28A745');
                    const modalMarker = L.marker([pickup.latitude, pickup.longitude], {
                        icon: L.divIcon({ html: `<div style="background:${color};width:32px;height:32px;border-radius:50%;border:3px solid white;display:flex;align-items:center;justify-content:center;"><i class="bi bi-geo-alt-fill" style="color:white;font-size:14px;"></i></div>`, iconSize: [32,32] })
                    }).bindPopup(`<div style="min-width:200px"><h6><b>${pickup.customer ? pickup.customer.name : 'Customer'}</b></h6><p class="mb-1 small">${pickup.pickup_address || 'No address'}</p><div class="d-grid gap-1"><button class="btn btn-sm btn-primary" onclick="getRouteToPickup(${pickup.id}); this.blur();">Direct Route</button> <button class="btn btn-sm btn-success" onclick="startNavigationForPickup(${pickup.id}); this.blur();">Start Navigation</button></div></div>`);

                    if (modalPickupCluster) modalPickupCluster.addLayer(modalMarker); else modalMarker.addTo(modalMapInstance);
                }
            });

        }, 200);
    });
}

// Wire everything up on DOM load
document.addEventListener('DOMContentLoaded', function () {
    const map = window.branchMap;
    if (!map) return;

    const pickupStatusClass = (s) => s === 'pending' ? 'warning' : s === 'en_route' ? 'primary' : s === 'picked_up' ? 'success' : 'secondary';
    const pickupLocations = @json($pickupLocations ?? []);
    const pickupMarkers = [];

    // Initialize marker cluster
    const pickupCluster = L.markerClusterGroup({ chunkedLoading: true });
    map.addLayer(pickupCluster);

    // Add a small cluster toggle control
    const ClusterControl = L.Control.extend({
        onAdd: function(map) {
            const container = L.DomUtil.create('div', 'leaflet-bar cluster-toggle-control p-2 bg-white rounded shadow-sm');
            container.innerHTML = `
                <div class="form-check m-1">
                    <input class="form-check-input" type="checkbox" id="clusterToggleStaff" checked>
                    <label class="form-check-label small ms-1" for="clusterToggleStaff">Cluster pickups</label>
                </div>
            `;
            L.DomEvent.disableClickPropagation(container);
            return container;
        }
    });
    map.addControl(new ClusterControl({ position: 'topright' }));
    setTimeout(() => {
        const el = document.getElementById('clusterToggleStaff');
        if (el) el.addEventListener('change', (e) => {
            if (e.target.checked) {
                map.addLayer(pickupCluster);
            } else {
                map.removeLayer(pickupCluster);
            }
        });
    }, 200);

    pickupLocations.forEach(pickup => {
        if (pickup.latitude && pickup.longitude) {
            const color = pickup.status === 'en_route' ? '#007BFF' : (pickup.status === 'pending' ? '#FFC107' : '#28A745');
            const icon = L.divIcon({
                html: `<div style="background:${color};width:28px;height:28px;border-radius:50%;border:3px solid white;display:flex;align-items:center;justify-content:center;"><i class="bi bi-pin-fill" style="color:white;font-size:12px;"></i></div>`,
                iconSize: [28, 28],
                className: 'pickup-pin'
            });

            const m = L.marker([parseFloat(pickup.latitude), parseFloat(pickup.longitude)], { icon });

            const popup = `
                <div style="min-width:200px">
                    <h6><b>${pickup.customer ? pickup.customer.name : 'Customer'}</b></h6>
                    <p class="mb-1 small">${pickup.pickup_address || 'No address'}</p>
                    <span class="badge bg-${pickupStatusClass(pickup.status)}" style="text-transform:capitalize;">${pickup.status}</span>
                    <hr class="my-2">
                    <div class="d-grid gap-1">
                        <a href="/staff/pickups/${pickup.id}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-eye me-1"></i> View</a>
                        <button class="btn btn-sm btn-primary" onclick="getRouteToPickup(${pickup.id}); this.blur();"><i class="bi bi-signpost me-1"></i> Direct Route</button>
                        <button class="btn btn-sm btn-success" onclick="startNavigationForPickup(${pickup.id}); this.blur();"><i class="bi bi-play-circle me-1"></i> Start Navigation</button>
                        <a target="_blank" href="https://www.google.com/maps?q=${pickup.latitude},${pickup.longitude}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-geo-alt me-1"></i> Open in Maps</a>
                    </div>
                </div>`;

            m.bindPopup(popup);
            pickupCluster.addLayer(m);
            pickupMarkers.push(m);
        }
    });

    // Fit to include pickups (prefer cluster bounds)
    if (pickupCluster.getLayers().length > 0) {
        const bounds = pickupCluster.getBounds();
        if (bounds && bounds.isValid && bounds.isValid()) {
            map.fitBounds(bounds.pad(0.1));
            return;
        }
    }

    if (pickupMarkers.length > 0) {
        const bounds = [];
        pickupMarkers.forEach(m => bounds.push([m.getLatLng().lat, m.getLatLng().lng]));
        const group = new L.featureGroup(bounds.map(b => L.marker(b)));
        map.fitBounds(group.getBounds().pad(0.1));
    }

    // Setup modal map for routes
    setupModalMap();
});
</script>

@endpush

@push('styles')

    <style>
        .dashboard-container {

            padding: 0;

            max-width: 100%;

        }



        /* Page Header */

        .page-header {

            display: flex;

            justify-content: space-between;

            align-items: flex-start;

            margin-bottom: 2rem;

        }



        .page-title {

            font-size: 2rem;

            font-weight: 700;

            color: #1a202c;

            margin-bottom: 0.5rem;

        }



        .page-subtitle {

            color: #718096;

            font-size: 1rem;

        }



        .page-actions {

            display: flex;

            gap: 0.75rem;

        }



        /* Buttons */

        .btn {

            padding: 0.625rem 1.25rem;

            border: none;

            border-radius: 0.5rem;

            font-weight: 500;

            cursor: pointer;

            transition: all 0.2s;

            display: inline-flex;

            align-items: center;

            gap: 0.5rem;

            text-decoration: none;

            font-size: 0.9375rem;

        }



        .btn-primary {

            background: linear-gradient(135deg, #2D2B5F 0%, #3D3B7F 100%);

            color: white;

        }



        .btn-primary:hover {

            transform: translateY(-2px);

            box-shadow: 0 4px 12px rgba(45, 43, 95, 0.4);

        }



        .btn-secondary {

            background: #f7fafc;

            color: #4a5568;

            border: 1px solid #e2e8f0;

        }



        .btn-secondary:hover {

            background: #edf2f7;

        }



        .btn-sm {

            padding: 0.5rem 1rem;

            font-size: 0.875rem;

        }



        .btn-icon {

            width: 36px;

            height: 36px;

            display: flex;

            align-items: center;

            justify-content: center;

            border-radius: 0.5rem;

            background: #f7fafc;

            border: 1px solid #e2e8f0;

            cursor: pointer;

            transition: all 0.2s;

            color: #4a5568;

            text-decoration: none;

        }



        .btn-icon:hover {

            background: #edf2f7;

            color: #8b5cf6;

        }



        /* OpenStreetMap Styles */

        #branchMap {

            border-radius: 8px;

            border: 1px solid #e2e8f0;

            position: relative;

            z-index: 1;

        }



        #branchMap .leaflet-container {

            background: #f9fafb;

            border-radius: 8px;

        }



        .branch-marker {

            filter: drop-shadow(0 2px 4px rgba(0, 0, 0, 0.1));

        }



        .branch-popup .leaflet-popup-content-wrapper {

            background: white;

            border-radius: 8px;

            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);

            border: none;

        }



        .branch-popup .leaflet-popup-tip {

            background: white;

            border: none;

        }



        .leaflet-control-attribution {

            background: rgba(255, 255, 255, 0.8);

            font-size: 11px;

        }



        .leaflet-popup-close-button {

            color: #2D2B5F;

        }



        .leaflet-popup-close-button:hover {

            color: #FF5C35;

        }



        /* Filters */

        .filters-card {

            background: white;

            padding: 1.5rem;

            border-radius: 1rem;

            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);

            margin-bottom: 2rem;

        }



        .filters-form {

            display: flex;

            gap: 1rem;

            align-items: flex-end;

            flex-wrap: wrap;

        }



        .filter-group {

            display: flex;

            flex-direction: column;

            gap: 0.5rem;

            min-width: 200px;

        }



        .filter-label {

            font-size: 0.875rem;

            font-weight: 500;

            color: #4a5568;

        }



        .form-select {

            padding: 0.625rem 1rem;

            border: 1px solid #e2e8f0;

            border-radius: 0.5rem;

            font-size: 0.875rem;

            background: white;

            cursor: pointer;

        }



        .form-select:focus {

            outline: none;

            border-color: #8b5cf6;

            box-shadow: 0 0 0 3px rgba(139, 92, 246, 0.1);

        }



        .reset-btn {

            margin-left: auto;

        }



        /* Alerts */

        .alerts-section {

            margin-bottom: 2rem;

        }



        .alert {

            background: white;

            padding: 1rem 1.5rem;

            border-radius: 0.75rem;

            margin-bottom: 1rem;

            display: flex;

            align-items: center;

            gap: 1rem;

            border-left: 4px solid;

            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);

        }



        .alert-warning {

            border-left-color: #f59e0b;

            background: #fffbeb;

        }



        .alert-danger {

            border-left-color: #ef4444;

            background: #fef2f2;

        }



        .alert-success {

            border-left-color: #10b981;

            background: #f0fdf4;

        }



        .alert-icon {

            width: 40px;

            height: 40px;

            display: flex;

            align-items: center;

            justify-content: center;

            border-radius: 0.5rem;

            font-size: 1.25rem;

        }



        .alert-warning .alert-icon {

            background: #fef3c7;

            color: #f59e0b;

        }



        .alert-danger .alert-icon {

            background: #fee2e2;

            color: #ef4444;

        }



        .alert-success .alert-icon {

            background: #d1fae5;

            color: #10b981;

        }



        .alert-content {

            flex: 1;

        }



        .alert-title {

            font-weight: 600;

            margin-bottom: 0.25rem;

            color: #1a202c;

        }



        .alert-text {

            font-size: 0.875rem;

            color: #4a5568;

        }



        .alert-action {

            padding: 0.5rem 1rem;

            background: white;

            border: 1px solid #e2e8f0;

            border-radius: 0.5rem;

            font-size: 0.875rem;

            font-weight: 500;

            text-decoration: none;

            color: #4a5568;

            transition: all 0.2s;

        }



        .alert-action:hover {

            background: #f7fafc;

            color: #8b5cf6;

        }



        /* KPI Grid */

        .kpi-grid {

            display: grid;

            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));

            gap: 1.5rem;

            margin-bottom: 2rem;

        }



        .kpi-card {

            background: white;

            padding: 1.5rem;

            border-radius: 1rem;

            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);

            display: flex;

            gap: 1rem;

            transition: transform 0.2s, box-shadow 0.2s;

        }



        .kpi-card:hover {

            transform: translateY(-4px);

            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);

        }



        .kpi-icon {

            width: 60px;

            height: 60px;

            border-radius: 1rem;

            display: flex;

            align-items: center;

            justify-content: center;

            color: white;

            font-size: 1.5rem;

            flex-shrink: 0;

        }



        .kpi-content {

            flex: 1;

            display: flex;

            flex-direction: column;

            gap: 0.5rem;

        }



        .kpi-label {

            font-size: 0.875rem;

            color: #718096;

            font-weight: 500;

        }



        .kpi-value {

            font-size: 1.875rem;

            font-weight: 700;

            color: #1a202c;

        }



        .kpi-change,
        .kpi-meta {

            font-size: 0.875rem;

            display: flex;

            align-items: center;

            gap: 0.25rem;

        }



        .kpi-change.up {

            color: #10b981;

        }



        .kpi-change.down {

            color: #ef4444;

        }



        .kpi-meta {

            color: #718096;

        }



        /* Charts */

        .charts-row {

            display: grid;

            gap: 1.5rem;

            margin-bottom: 2rem;

        }



        .charts-half {

            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));

        }



        .chart-card {

            background: white;

            border-radius: 1rem;

            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);

            overflow: hidden;

        }



        .chart-full {

            grid-column: 1 / -1;

        }



        .chart-header {

            padding: 1.5rem;

            border-bottom: 1px solid #f7fafc;

            display: flex;

            justify-content: space-between;

            align-items: center;

        }



        .chart-title {

            font-size: 1.125rem;

            font-weight: 600;

            color: #1a202c;

            display: flex;

            align-items: center;

            gap: 0.5rem;

        }



        .chart-actions {

            display: flex;

            gap: 0.5rem;

        }



        .chart-body {

            padding: 1.5rem;

        }



        .chart-footer {

            padding: 1.5rem;

            border-top: 1px solid #f7fafc;

            display: flex;

            justify-content: space-around;

            background: #fafbfc;

        }



        .chart-stat {

            display: flex;

            flex-direction: column;

            gap: 0.25rem;

            text-align: center;

        }



        .stat-label {

            font-size: 0.875rem;

            color: #718096;

        }



        .stat-value {

            font-size: 1.25rem;

            font-weight: 600;

            color: #1a202c;

        }



        /* Progress Bars */

        .progress-item,
        .pipeline-item {

            margin-bottom: 1.5rem;

        }



        .progress-item:last-child,
        .pipeline-item:last-child {

            margin-bottom: 0;

        }



        .progress-header,
        .pipeline-header {

            display: flex;

            justify-content: space-between;

            margin-bottom: 0.5rem;

            font-size: 0.875rem;

        }



        .progress-label,
        .pipeline-label {

            font-weight: 500;

            color: #1a202c;

            display: flex;

            align-items: center;

            gap: 0.5rem;

        }



        .status-dot {

            width: 8px;

            height: 8px;

            border-radius: 50%;

        }



        .progress-value,
        .pipeline-value {

            font-weight: 600;

            color: #8b5cf6;

        }



        .progress-bar-container {

            height: 12px;

            background: #f7fafc;

            border-radius: 6px;

            overflow: hidden;

        }



        .progress-bar {

            height: 100%;

            border-radius: 6px;

            transition: width 0.3s ease;

        }



        /* Table */

        .table-card {

            background: white;

            border-radius: 1rem;

            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);

            overflow: hidden;

        }



        .table-header {

            padding: 1.5rem;

            border-bottom: 1px solid #f7fafc;

            display: flex;

            justify-content: space-between;

            align-items: center;

        }



        .table-title {

            font-size: 1.125rem;

            font-weight: 600;

            color: #1a202c;

            display: flex;

            align-items: center;

            gap: 0.5rem;

        }



        .table-responsive {

            overflow-x: auto;

        }



        .data-table {

            width: 100%;

            border-collapse: collapse;

        }



        .data-table thead {

            background: #fafbfc;

        }



        .data-table th {

            padding: 1rem 1.5rem;

            text-align: left;

            font-weight: 600;

            font-size: 0.875rem;

            color: #4a5568;

            border-bottom: 1px solid #e2e8f0;

        }



        .data-table td {

            padding: 1rem 1.5rem;

            border-bottom: 1px solid #f7fafc;

        }



        .data-table tbody tr:hover {

            background: #fafbfc;

        }



        .tracking-number {

            color: #8b5cf6;

            font-weight: 600;

        }



        .customer-info {

            display: flex;

            flex-direction: column;

            gap: 0.25rem;

        }



        .customer-name {

            font-weight: 500;

            color: #1a202c;

        }



        .branch-badge {

            display: inline-block;

            padding: 0.25rem 0.75rem;

            background: #eef2ff;

            color: #6366f1;

            border-radius: 0.375rem;

            font-size: 0.875rem;

            font-weight: 500;

        }



        .amount {

            color: #10b981;

        }



        .status-badge {

            display: inline-block;

            padding: 0.25rem 0.75rem;

            border-radius: 0.375rem;

            font-size: 0.75rem;

            font-weight: 600;

            text-transform: uppercase;

        }



        .status-received {

            background: #dbeafe;

            color: #1e40af;

        }



        .status-ready {

            background: #fef3c7;

            color: #92400e;

        }



        .status-paid {

            background: #d1fae5;

            color: #065f46;

        }



        .status-completed {

            background: #e0e7ff;

            color: #3730a3;

        }



        .date-text,
        .time-text {

            display: block;

            font-size: 0.875rem;

        }



        .date-text {

            color: #1a202c;

            font-weight: 500;

        }



        .time-text {

            color: #718096;

            font-size: 0.75rem;

        }



        .action-buttons {

            display: flex;

            gap: 0.5rem;

        }



        .empty-state {

            padding: 3rem;

            text-align: center;

            color: #9ca3af;

        }



        .empty-state i {

            font-size: 3rem;

            margin-bottom: 1rem;

        }



        .empty-state p {

            font-size: 1rem;

        }



        /* Responsive */

        @media (max-width: 768px) {

            .page-header {

                flex-direction: column;

                gap: 1rem;

            }



            .page-actions {

                width: 100%;

            }



            .filters-form {

                flex-direction: column;

            }



            .filter-group {

                width: 100%;

            }



            .kpi-grid {

                grid-template-columns: 1fr;

            }



            .charts-half {

                grid-template-columns: 1fr;

            }

            /* Route Controls (copied from admin dashboard for consistent look) */
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
            }

            .eta-display {
                display:flex;
                align-items:center;
                gap:12px;
                background:#fff;
                padding:10px;
                border-radius:8px;
                box-shadow:0 1px 4px rgba(0,0,0,0.08);
            }

            .eta-label { font-size: 12px; color: #6c757d; }
            .eta-time { font-size: 18px; font-weight:700; color: #28a745; margin-bottom: 0; }

            /* Spinner fade polish */
            #route-loading-spinner, #route-loading-spinner-modal {
                display: none; /* toggled via JS */
                opacity: 0;
                align-items: center;
                transition: opacity 180ms ease;
            }

            @keyframes slideInLeft {
                from { opacity: 0; transform: translateX(-10px); }
                to { opacity: 1; transform: none; }
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

        }
    </style>

@endpush



@push('scripts')

    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

    <script>

        // Revenue Trend Chart

        const revenueTrendCtx = document.getElementById('revenueTrendChart');

        if (revenueTrendCtx) {

            const revenueTrendData = @json($charts['revenue_trend']);

            const labels = Object.keys(revenueTrendData);

            const data = Object.values(revenueTrendData);



            new Chart(revenueTrendCtx, {

                type: 'line',

                data: {

                    labels: labels.map(date => {

                        const d = new Date(date);

                        return d.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });

                    }),

                    datasets: [{

                        label: 'Revenue',

                        data: data,

                        borderColor: '#8b5cf6',

                        backgroundColor: 'rgba(139, 92, 246, 0.1)',

                        tension: 0.4,

                        fill: true,

                        pointRadius: 4,

                        pointHoverRadius: 6,

                    }]

                },

                options: {

                    responsive: true,

                    maintainAspectRatio: true,

                    plugins: {

                        legend: {

                            display: false

                        },

                        tooltip: {

                            callbacks: {

                                label: function (context) {

                                    return '₱' + context.parsed.y.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

                                }

                            }

                        }

                    },

                    scales: {

                        y: {

                            beginAtZero: true,

                            ticks: {

                                callback: function (value) {

                                    return '₱' + value.toLocaleString('en-US');

                                }

                            }

                        }

                    }

                }

            });

        }



        // Auto-refresh dashboard every 5 minutes

        setTimeout(() => {

            location.reload();

        }, 300000);

    </script>

@endpush
