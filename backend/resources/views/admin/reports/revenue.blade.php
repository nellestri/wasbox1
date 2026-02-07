@extends('admin.layouts.app')

@section('title', 'Revenue Report')

@section('content')
<div class="container-fluid px-4 py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="mb-0 fw-bold">Revenue Report</h2>
            <p class="text-muted mb-0">Daily revenue breakdown</p>
        </div>
        <a href="{{ route('admin.reports.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-2"></i>Back to Reports
        </a>
    </div>

    {{-- Date Filter --}}
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-5">
                    <label class="form-label">Start Date</label>
                    <input type="date" name="start_date" class="form-control" value="{{ $startDate->format('Y-m-d') }}">
                </div>
                <div class="col-md-5">
                    <label class="form-label">End Date</label>
                    <input type="date" name="end_date" class="form-control" value="{{ $endDate->format('Y-m-d') }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label">&nbsp;</label>
                    <button type="submit" class="btn btn-primary w-100">Filter</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Revenue Data --}}
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Daily Revenue</h5>
            <form method="POST" action="{{ route('admin.reports.export') }}">
                @csrf
                <input type="hidden" name="type" value="revenue">
                <input type="hidden" name="start_date" value="{{ $startDate->format('Y-m-d') }}">
                <input type="hidden" name="end_date" value="{{ $endDate->format('Y-m-d') }}">
                <button type="submit" class="btn btn-sm btn-success">
                    <i class="bi bi-download me-2"></i>Export CSV
                </button>
            </form>
        </div>
        <div class="card-body">
            @if($data->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Orders</th>
                                <th class="text-end">Revenue</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php $totalRevenue = 0; $totalOrders = 0; @endphp
                            @foreach($data as $row)
                                @php
                                    $totalRevenue += $row->revenue;
                                    $totalOrders += $row->orders;
                                @endphp
                                <tr>
                                    <td>{{ \Carbon\Carbon::parse($row->date)->format('M d, Y') }}</td>
                                    <td>{{ $row->orders }}</td>
                                    <td class="text-end">₱{{ number_format($row->revenue, 2) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr class="fw-bold">
                                <td>Total</td>
                                <td>{{ $totalOrders }}</td>
                                <td class="text-end">₱{{ number_format($totalRevenue, 2) }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            @else
                <div class="text-center py-5">
                    <i class="bi bi-graph-down text-muted" style="font-size: 3rem;"></i>
                    <p class="text-muted mt-3">No revenue data for this period</p>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
