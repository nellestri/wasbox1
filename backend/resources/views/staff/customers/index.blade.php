@extends('staff.layouts.staff')

@section('title', 'Customers')
@section('page-title', 'Customers Management')

@section('content')
<div class="container-fluid px-0">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <p class="text-muted small mb-0">Manage walk-in and mobile app registered customers for {{ $currentBranch->name }}.</p>
        </div>
        <a href="{{ route('staff.customers.create') }}" class="btn btn-primary shadow-sm px-4" style="background: #3D3B6B; border: none;">
            <i class="bi bi-person-plus-fill me-2"></i>Create New Customer
        </a>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="stat-card border-0 shadow-sm p-3 bg-white rounded-3">
                <div class="d-flex align-items-center">
                    <div class="stat-icon me-3 d-flex align-items-center justify-content-center rounded-circle" style="background: #DBEAFE; color: #3B82F6; width: 48px; height: 48px;">
                        <i class="bi bi-people fs-4"></i>
                    </div>
                    <div>
                        <div class="stat-value h4 fw-bold mb-0">{{ number_format($stats['total']) }}</div>
                        <div class="stat-label text-muted small">Total Customers</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card border-0 shadow-sm p-3 bg-white rounded-3">
                <div class="d-flex align-items-center">
                    <div class="stat-icon me-3 d-flex align-items-center justify-content-center rounded-circle" style="background: #FEE2E2; color: #EF4444; width: 48px; height: 48px;">
                        <i class="bi bi-person fs-4"></i>
                    </div>
                    <div>
                        <div class="stat-value h4 fw-bold mb-0">{{ number_format($stats['walk_in']) }}</div>
                        <div class="stat-label text-muted small">Walk-in</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card border-0 shadow-sm p-3 bg-white rounded-3">
                <div class="d-flex align-items-center">
                    <div class="stat-icon me-3 d-flex align-items-center justify-content-center rounded-circle" style="background: #DCFCE7; color: #16A34A; width: 48px; height: 48px;">
                        <i class="bi bi-person-check fs-4"></i>
                    </div>
                    <div>
                        <div class="stat-value h4 fw-bold mb-0">{{ number_format($stats['self_registered']) }}</div>
                        <div class="stat-label text-muted small">Self-Registered</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card border-0 shadow-sm p-3 bg-white rounded-3">
                <div class="d-flex align-items-center">
                    <div class="stat-icon me-3 d-flex align-items-center justify-content-center rounded-circle" style="background: #FEF3C7; color: #F59E0B; width: 48px; height: 48px;">
                        <i class="bi bi-person-plus fs-4"></i>
                    </div>
                    <div>
                        <div class="stat-value h4 fw-bold mb-0">{{ number_format($stats['new_today']) }}</div>
                        <div class="stat-label text-muted small">New Today</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="table-container mb-4 bg-white p-3 rounded-3 shadow-sm">
        <form method="GET" class="row g-3 align-items-center">
            <div class="col-md-3">
                <select name="registration_type" class="form-select border-0 bg-light" onchange="this.form.submit()">
                    <option value="">All Types</option>
                    <option value="walk_in" {{ request('registration_type') === 'walk_in' ? 'selected' : '' }}>Walk-in</option>
                    <option value="self_registered" {{ request('registration_type') === 'self_registered' ? 'selected' : '' }}>Self-Registered</option>
                </select>
            </div>
            <div class="col-md-3">
                <select name="status" class="form-select border-0 bg-light" onchange="this.form.submit()">
                    <option value="">All Status</option>
                    <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                    <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                </select>
            </div>
            <div class="col-md-6">
                <div class="input-group">
                    <input type="text" name="search" class="form-control border-0 bg-light" placeholder="Search name, phone, email..." value="{{ request('search') }}">
                    <button class="btn btn-dark px-3" type="submit"><i class="bi bi-search"></i></button>
                    @if(request()->anyFilled(['search', 'status', 'registration_type']))
                        <a href="{{ route('staff.customers.index') }}" class="btn btn-outline-danger border-0 bg-light"><i class="bi bi-x-circle"></i></a>
                    @endif
                </div>
            </div>
        </form>
    </div>

    <div class="table-container bg-white rounded-3 shadow-sm overflow-hidden">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light">
                    <tr>
                        <th class="ps-4 py-3">Customer</th>
                        <th>Contact Information</th>
                        <th>Reg. Type</th>
                        <th class="text-center">Orders</th>
                        <th>Total Revenue</th>
                        <th>Status</th>
                        <th class="text-end pe-4">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($customers as $customer)
                    <tr>
                        <td class="ps-4">
                            <div class="d-flex align-items-center">
                                @if($customer->profile_photo_url)
                                    <img src="{{ $customer->profile_photo_url }}" alt="{{ $customer->name }}" class="rounded-circle me-3" style="width: 40px; height: 40px; object-fit: cover;">
                                @else
                                    <div class="rounded-circle me-3 d-flex align-items-center justify-content-center text-white fw-bold" style="width: 40px; height: 40px; background: #3D3B6B;">
                                        {{ strtoupper(substr($customer->name, 0, 1)) }}
                                    </div>
                                @endif
                                <div>
                                    <div class="fw-bold text-dark">{{ $customer->name }}</div>
                                    <small class="text-muted">ID: #CUST-{{ str_pad($customer->id, 4, '0', STR_PAD_LEFT) }}</small>
                                </div>
                            </div>
                        </td>
                        <td>
                            <div class="small"><i class="bi bi-telephone me-1"></i> {{ $customer->phone }}</div>
                            @if($customer->email)
                                <div class="small text-muted"><i class="bi bi-envelope me-1"></i> {{ $customer->email }}</div>
                            @endif
                        </td>
                        <td>
                            <span class="badge rounded-pill {{ $customer->registration_type == 'walk_in' ? 'bg-secondary' : 'bg-primary' }} bg-opacity-10 {{ $customer->registration_type == 'walk_in' ? 'text-secondary' : 'text-primary' }} px-3">
                                {{ $customer->registration_type == 'walk_in' ? 'Walk-in' : 'Mobile App' }}
                            </span>
                        </td>
                        <td class="text-center">
                            <span class="fw-bold">{{ $customer->orders_count ?? $customer->orders()->count() }}</span>
                        </td>
                        <td class="fw-bold text-dark">
                            ₱{{ number_format($customer->getTotalSpent(), 2) }}
                        </td>
                        <td>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" disabled {{ $customer->is_active ? 'checked' : '' }}>
                                <small class="ms-1 {{ $customer->is_active ? 'text-success' : 'text-danger' }}">{{ $customer->is_active ? 'Active' : 'Inactive' }}</small>
                            </div>
                        </td>
                        <td class="text-end pe-4">
                            <div class="btn-group">
                                <a href="{{ route('staff.customers.show', $customer) }}" class="btn btn-sm btn-outline-secondary border-0" title="View Details">
                                    <i class="bi bi-eye-fill"></i>
                                </a>
                                <a href="{{ route('staff.customers.edit', $customer) }}" class="btn btn-sm btn-outline-secondary border-0" title="Edit Customer">
                                    <i class="bi bi-pencil-square"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="text-center py-5">
                            <img src="https://cdn-icons-png.flaticon.com/512/7486/7486744.png" style="width: 80px; opacity: 0.3;" alt="No data" class="mb-3">
                            <p class="text-muted">No customers found matching your criteria.</p>
                            <a href="{{ route('staff.customers.index') }}" class="btn btn-sm btn-link text-decoration-none">Clear all filters</a>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($customers->hasPages())
            <div class="p-4 border-top bg-light bg-opacity-50">
                {{ $customers->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
