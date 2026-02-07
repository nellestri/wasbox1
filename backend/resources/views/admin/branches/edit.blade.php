@extends('admin.layouts.app')

@section('title', 'Edit Branch')

@section('page-title', 'Edit Branch')

@section('content')
<div class="container-fluid px-4 py-4">
    <div class="d-flex align-items-center mb-4">
        <a href="{{ route('admin.branches.index') }}" class="me-2 text-dark"><i class="bi bi-arrow-left"></i></a>
        <h5 class="fw-bold mb-0">Branch Configuration</h5>
        <span class="ms-auto badge bg-light text-dark px-3 py-2 rounded-pill">BRANCH CODE: {{ $branch->code }}</span>
    </div>

    @if ($errors->any())
    <div class="alert alert-danger">
        <ul class="mb-0">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    <form method="POST" action="{{ route('admin.branches.update', $branch->id) }}">
        @csrf
        @method('PUT')
        <div class="row g-4">
            <div class="col-lg-8">
                <div class="card shadow-sm p-4 mb-4">
                    <h5 class="fw-bold mb-2"><i class="bi bi-building me-2"></i>General Information</h5>
                    <p class="text-muted mb-4">Primary identification and location details.</p>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Branch Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('name') is-invalid @enderror" name="name" value="{{ old('name', $branch->name) }}" required>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Branch Code <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('code') is-invalid @enderror" name="code" value="{{ old('code', $branch->code) }}" required maxlength="10">
                            <small class="text-muted">Unique code (e.g., SBL, DGT, BAI)</small>
                            @error('code')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Address <span class="text-danger">*</span></label>
                        <textarea class="form-control @error('address') is-invalid @enderror" name="address" rows="2" required>{{ old('address', $branch->address) }}</textarea>
                        @error('address')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">City <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('city') is-invalid @enderror" name="city" value="{{ old('city', $branch->city) }}" required>
                            @error('city')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Province <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('province') is-invalid @enderror" name="province" value="{{ old('province', $branch->province) }}" required>
                            @error('province')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Phone Number <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('phone') is-invalid @enderror" name="phone" value="{{ old('phone', $branch->phone) }}" required>
                            @error('phone')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Email Address</label>
                            <input type="email" class="form-control @error('email') is-invalid @enderror" name="email" value="{{ old('email', $branch->email) }}">
                            @error('email')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Branch Manager</label>
                            <input type="text" class="form-control @error('manager') is-invalid @enderror" name="manager" value="{{ old('manager', $branch->manager) }}">
                            @error('manager')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Operating Status</label>
                            <select class="form-select @error('is_active') is-invalid @enderror" name="is_active">
                                <option value="1" {{ old('is_active', $branch->is_active) ? 'selected' : '' }}>Active</option>
                                <option value="0" {{ !old('is_active', $branch->is_active) ? 'selected' : '' }}>Inactive</option>
                            </select>
                            @error('is_active')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="card mt-4">
                        <div class="card-header bg-light">
                            <h6 class="fw-bold mb-0"><i class="bi bi-geo-alt me-2"></i>Location Coordinates</h6>
                            <small class="text-muted">For Google Maps integration (optional)</small>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Latitude</label>
                                    <input type="number" step="any" class="form-control @error('latitude') is-invalid @enderror" name="latitude" value="{{ old('latitude', $branch->latitude) }}" placeholder="e.g., 9.5937">
                                    @error('latitude')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Longitude</label>
                                    <input type="number" step="any" class="form-control @error('longitude') is-invalid @enderror" name="longitude" value="{{ old('longitude', $branch->longitude) }}" placeholder="e.g., 123.1030">
                                    @error('longitude')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card mt-4">
                        <div class="card-header bg-light">
                            <h6 class="fw-bold mb-0"><i class="bi bi-clock me-2"></i>Operating Hours</h6>
                            <small class="text-muted">JSON format for flexible hours (optional)</small>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Operating Hours JSON</label>
                                <textarea class="form-control @error('operating_hours') is-invalid @enderror" name="operating_hours" rows="6" placeholder='{"monday": {"open": "09:00", "close": "18:00"}, "tuesday": {"open": "09:00", "close": "18:00"}, ...}'>{{ old('operating_hours', $branch->operating_hours_json) }}</textarea>
                                <small class="text-muted">
                                    Format: {"day": {"open": "HH:MM", "close": "HH:MM"}}<br>
                                    Example for Sunday closed: {"sunday": {"closed": true}}
                                </small>
                                @error('operating_hours')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card shadow-sm p-4 mb-4">
                    <h6 class="fw-bold mb-3"><i class="bi bi-graph-up-arrow me-2"></i>Branch Insights</h6>
                    <div class="mb-2">
                        <div class="d-flex justify-content-between">
                            <span class="text-muted">Orders (MTD)</span>
                            <span class="fw-bold">{{ $branch->orders_mtd }}</span>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span class="text-muted">Revenue (MTD)</span>
                            <span class="fw-bold text-success">₱{{ number_format($branch->revenue_mtd, 2) }}</span>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span class="text-muted">Active Staff</span>
                            <span class="fw-bold">{{ $branch->active_staff }} Members</span>
                        </div>
                    </div>
                </div>

                <div class="card shadow-sm p-4 mb-4">
                    <h6 class="fw-bold mb-3"><i class="bi bi-clock-history me-2"></i>Historical Data</h6>
                    <div class="mb-2">
                        <span class="text-muted">Created At</span>
                        <div class="fw-bold">{{ \Carbon\Carbon::parse($branch->created_at)->format('M d, Y h:i A') }}</div>
                    </div>
                    <div class="mb-2">
                        <span class="text-muted">Last Updated</span>
                        <div class="fw-bold">{{ \Carbon\Carbon::parse($branch->updated_at)->format('M d, Y h:i A') }}</div>
                    </div>
                    <div class="alert alert-warning mt-3 py-2 px-3" style="font-size: 0.95rem;">
                        <i class="bi bi-exclamation-triangle me-2"></i>
                        Changing branch code may affect tracking and reporting. Use caution.
                    </div>
                </div>

                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-primary btn-lg">
                        <i class="bi bi-check-circle me-2"></i>Save Changes
                    </button>
                    <a href="{{ route('admin.branches.index') }}" class="btn btn-light border">
                        <i class="bi bi-x-circle me-2"></i>Cancel
                    </a>

                    @if($branch->is_active)
                    <button type="button" class="btn btn-outline-danger mt-3" data-bs-toggle="modal" data-bs-target="#deactivateModal">
                        <i class="bi bi-power me-2"></i>Deactivate Branch
                    </button>
                    @else
                    <button type="button" class="btn btn-outline-success mt-3" data-bs-toggle="modal" data-bs-target="#activateModal">
                        <i class="bi bi-power me-2"></i>Activate Branch
                    </button>
                    @endif
                </div>
            </div>
        </div>
    </form>
</div>

<!-- Deactivation Modal -->
<div class="modal fade" id="deactivateModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirm Deactivation</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-warning">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>
                    <strong>Warning:</strong> Deactivating this branch will prevent new orders and restrict access for branch users.
                </div>
                <p>Are you sure you want to deactivate <strong>{{ $branch->name }}</strong>?</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <form action="{{ route('admin.branches.deactivate', $branch->id) }}" method="POST" style="display: inline;">
                    @csrf
                    @method('PATCH')
                    <button type="submit" class="btn btn-danger">Deactivate Branch</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Activation Modal -->
<div class="modal fade" id="activateModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirm Activation</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to activate <strong>{{ $branch->name }}</strong>? This will allow new orders and restore access for branch users.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <form action="{{ route('admin.branches.activate', $branch->id) }}" method="POST" style="display: inline;">
                    @csrf
                    @method('PATCH')
                    <button type="submit" class="btn btn-success">Activate Branch</button>
                </form>
            </div>
        </div>
    </div>
</div>

@endsection

@push('styles')
<style>
    .form-label {
        font-size: 0.9rem;
        font-weight: 600;
    }
    .text-muted {
        font-size: 0.85rem;
    }
    .card-header {
        padding: 0.75rem 1.25rem;
    }
</style>
@endpush
