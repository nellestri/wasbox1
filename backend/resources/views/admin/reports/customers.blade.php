@extends('admin.layouts.app')

@section('title', 'Customers Report')

@section('content')
<div class="container-fluid px-4 py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="mb-0 fw-bold">Customers Report</h2>
            <p class="text-muted mb-0">Customer analytics and insights</p>
        </div>
        <a href="{{ route('admin.reports.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-2"></i>Back to Reports
        </a>
    </div>

    {{-- Customers Table --}}
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Customers List</h5>
            <form method="POST" action="{{ route('admin.reports.export') }}">
                @csrf
                <input type="hidden" name="type" value="customers">
                <button type="submit" class="btn btn-sm btn-success">
                    <i class="bi bi-download me-2"></i>Export CSV
                </button>
            </form>
        </div>
        <div class="card-body">
            @if($customers->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Customer</th>
                                <th>Email</th>
                                <th>Phone</th>
                                <th class="text-center">Total Orders</th>
                                <th class="text-end">Total Spent</th>
                                <th>Registered</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($customers as $customer)
                                @php
                                    $totalSpent = $customer->orders()
                                        ->where('payment_status', 'paid')
                                        ->sum('total_amount');
                                @endphp
                                <tr>
                                    <td>
                                        <a href="{{ route('admin.customers.show', $customer->id) }}">
                                            {{ $customer->name }}
                                        </a>
                                    </td>
                                    <td>{{ $customer->email }}</td>
                                    <td>{{ $customer->phone }}</td>
                                    <td class="text-center">{{ $customer->orders_count }}</td>
                                    <td class="text-end">₱{{ number_format($totalSpent, 2) }}</td>
                                    <td>{{ $customer->created_at->format('M d, Y') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="mt-3">
                    {{ $customers->links() }}
                </div>
            @else
                <div class="text-center py-5">
                    <i class="bi bi-people text-muted" style="font-size: 3rem;"></i>
                    <p class="text-muted mt-3">No customers found</p>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
