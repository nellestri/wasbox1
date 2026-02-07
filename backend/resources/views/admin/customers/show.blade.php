@extends('admin.layouts.app')

@section('title', 'Customer Profile - ' . $customer->name)

@section('content')
<div class="container-fluid px-4 py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1">
                    <li class="breadcrumb-item"><a href="{{ route('admin.customers.index') }}" class="text-decoration-none">Customers</a></li>
                    <li class="breadcrumb-item active">View Profile</li>
                </ol>
            </nav>
            <h2 class="fw-bold text-dark">Customer Overview</h2>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.customers.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i> Back
            </a>
            <a href="{{ route('admin.customers.edit', $customer->id) }}" class="btn btn-primary shadow-sm" style="background: #3D3B6B; border-color: #3D3B6B;">
                <i class="bi bi-pencil-square me-1"></i> Edit Profile
            </a>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-xl-4 col-lg-5">
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                <div class="card-body p-0">
                    <div class="text-center py-5 bg-light border-bottom">
                        <div class="position-relative d-inline-block mb-3">
                            <i class="bi bi-person-circle text-secondary" style="font-size: 5rem;"></i>
                            <span class="position-absolute bottom-0 end-0 p-2 border border-light rounded-circle {{ $customer->is_active ? 'bg-success' : 'bg-danger' }}"></span>
                        </div>
                        <h4 class="fw-bold mb-1">{{ $customer->name }}</h4>
                        <p class="text-muted small mb-0">{{ $customer->phone }}</p>
                    </div>

                    <div class="p-4">
                        <h6 class="text-uppercase text-muted small fw-bold mb-3">Account Information</h6>

                        <div class="d-flex justify-content-between mb-3">
                            <span class="text-secondary"><i class="bi bi-geo-alt me-2"></i>Preferred Branch</span>
                            <span class="fw-bold text-dark">{{ $customer->preferredBranch->name ?? 'N/A' }}</span>
                        </div>

                        <div class="d-flex justify-content-between mb-3">
                            <span class="text-secondary"><i class="bi bi-tag me-2"></i>Registration Type</span>
                            <span class="badge bg-light text-primary border">{{ $customer->registration_type_label }}</span>
                        </div>

                        <div class="d-flex justify-content-between mb-3">
                            <span class="text-secondary"><i class="bi bi-calendar-check me-2"></i>Date Joined</span>
                            <span class="text-dark">{{ $customer->created_at->format('M d, Y') }}</span>
                        </div>

                        <div class="mt-4 pt-3 border-top">
                            <h6 class="text-uppercase text-muted small fw-bold mb-2">Address</h6>
                            <p class="text-dark small mb-0">{{ $customer->address ?? 'No address provided.' }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-8 col-lg-7">
            <div class="row g-3 mb-4">
                <div class="col-sm-6">
                    <div class="card border-0 shadow-sm rounded-4 p-4 bg-white border-start border-primary border-4">
                        <div class="d-flex align-items-center">
                            <div class="flex-shrink-0 bg-primary bg-opacity-10 p-3 rounded-3 me-3">
                                <i class="bi bi-cart-check text-primary fs-3"></i>
                            </div>
                            <div>
                                <h6 class="text-muted small fw-bold text-uppercase mb-1">Total Orders</h6>
                                <h3 class="fw-bold mb-0 text-dark">{{ $customer->getTotalOrdersCount() }}</h3>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-sm-6">
                    <div class="card border-0 shadow-sm rounded-4 p-4 bg-white border-start border-success border-4">
                        <div class="d-flex align-items-center">
                            <div class="flex-shrink-0 bg-success bg-opacity-10 p-3 rounded-3 me-3">
                                <i class="bi bi-cash-stack text-success fs-3"></i>
                            </div>
                            <div>
                                <h6 class="text-muted small fw-bold text-uppercase mb-1">Total Lifetime Spent</h6>
                                <h3 class="fw-bold mb-0 text-dark">₱{{ number_format($customer->getTotalSpent(), 2) }}</h3>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                    <h5 class="fw-bold mb-0 text-dark">Recent Order History</h5>
                    <button class="btn btn-sm btn-light border text-muted">View All</button>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-light text-muted small text-uppercase">
                                <tr>
                                    <th class="ps-4">Order #</th>
                                    <th>Date</th>
                                    <th>Amount</th>
                                    <th class="pe-4">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($customer->orders->sortByDesc('created_at')->take(5) as $order)
                                <tr>
                                    <td class="ps-4">
                                        <span class="fw-bold text-primary">#{{ $order->id }}</span>
                                    </td>
                                    <td class="text-muted">
                                        {{ $order->created_at->format('M d, Y') }}
                                    </td>
                                    <td class="fw-bold text-dark">
                                        ₱{{ number_format($order->total_amount, 2) }}
                                    </td>
                                    <td class="pe-4">
                                        @php
                                            $statusClass = [
                                                'completed' => 'bg-success bg-opacity-10 text-success border-success',
                                                'pending' => 'bg-warning bg-opacity-10 text-warning border-warning',
                                                'processing' => 'bg-info bg-opacity-10 text-info border-info',
                                                'cancelled' => 'bg-danger bg-opacity-10 text-danger border-danger'
                                            ][$order->status] ?? 'bg-secondary bg-opacity-10 text-secondary border-secondary';
                                        @endphp
                                        <span class="badge border rounded-pill px-3 py-2 {{ $statusClass }}">
                                            {{ ucfirst($order->status) }}
                                        </span>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="4" class="text-center py-5">
                                        <i class="bi bi-inbox display-6 text-light"></i>
                                        <p class="text-muted mt-2 mb-0">No orders found for this customer.</p>
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
</div>
@endsection
