@extends('admin.layouts.app')

@section('title', 'Create New Branch')
@section('page-title', 'Create New Branch')

@section('content')
<div class="container-fluid px-4 py-4">
    <div class="d-flex align-items-center mb-4">
        <a href="{{ route('admin.branches.index') }}" class="me-2 text-dark"><i class="bi bi-arrow-left"></i></a>
        <h5 class="fw-bold mb-0">Add New Branch</h5>
    </div>

    @if ($errors->any())
    <div class="alert alert-danger mb-4">
        <ul class="mb-0">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    <form method="POST" action="{{ route('admin.branches.store') }}" class="card shadow-sm p-4">
        @csrf

        <div class="row">
            <div class="col-lg-8">
                <h5 class="fw-bold mb-3"><i class="bi bi-building me-2"></i>Branch Information</h5>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Branch Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('name') is-invalid @enderror"
                               name="name" value="{{ old('name') }}" required placeholder="e.g., WashBox Sibulan">
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Branch Code <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('code') is-invalid @enderror"
                               name="code" value="{{ old('code') }}" required maxlength="10" placeholder="e.g., SBL, DGT, BAI">
                        <small class="text-muted">Unique 3-10 letter code</small>
                        @error('code')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">Address <span class="text-danger">*</span></label>
                    <textarea class="form-control @error('address') is-invalid @enderror"
                              name="address" rows="2" required placeholder="Full street address">{{ old('address') }}</textarea>
                    @error('address')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">City <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('city') is-invalid @enderror"
                               name="city" value="{{ old('city') }}" required placeholder="e.g., Sibulan">
                        @error('city')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Province <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('province') is-invalid @enderror"
                               name="province" value="{{ old('province', $defaultProvince ?? 'Negros Oriental') }}" required placeholder="e.g., Negros Oriental">
                        @error('province')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Phone Number <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('phone') is-invalid @enderror"
                               name="phone" value="{{ old('phone') }}" required placeholder="e.g., 09171234567">
                        @error('phone')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Email Address</label>
                        <input type="email" class="form-control @error('email') is-invalid @enderror"
                               name="email" value="{{ old('email') }}" placeholder="e.g., branch@washbox.com">
                        @error('email')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="row mb-4">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Branch Manager</label>
                        <input type="text" class="form-control @error('manager') is-invalid @enderror"
                               name="manager" value="{{ old('manager') }}" placeholder="Name of branch manager">
                        @error('manager')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Status</label>
                        <select class="form-select @error('is_active') is-invalid @enderror" name="is_active">
                            <option value="1" {{ old('is_active', 1) ? 'selected' : '' }}>Active</option>
                            <option value="0" {{ !old('is_active', 1) ? 'selected' : '' }}>Inactive</option>
                        </select>
                        <small class="text-muted">Inactive branches won't appear in mobile app</small>
                        @error('is_active')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="card mb-4">
                    <div class="card-header bg-light">
                        <h6 class="fw-bold mb-0"><i class="bi bi-geo-alt me-2"></i>Location Coordinates (Optional)</h6>
                        <small class="text-muted">For Google Maps integration</small>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Latitude</label>
                                <input type="number" step="any" class="form-control @error('latitude') is-invalid @enderror"
                                       name="latitude" value="{{ old('latitude') }}" placeholder="e.g., 9.5937">
                                @error('latitude')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Longitude</label>
                                <input type="number" step="any" class="form-control @error('longitude') is-invalid @enderror"
                                       name="longitude" value="{{ old('longitude') }}" placeholder="e.g., 123.1030">
                                @error('longitude')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header bg-light">
                        <h6 class="fw-bold mb-0"><i class="bi bi-clock me-2"></i>Operating Hours (Optional)</h6>
                        <small class="text-muted">JSON format for custom hours</small>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Operating Hours JSON</label>
                            <textarea class="form-control @error('operating_hours') is-invalid @enderror"
                                      name="operating_hours" rows="6"
                                      placeholder='{
    "monday": {"open": "08:00", "close": "18:00"},
    "tuesday": {"open": "08:00", "close": "18:00"},
    "wednesday": {"open": "08:00", "close": "18:00"},
    "thursday": {"open": "08:00", "close": "18:00"},
    "friday": {"open": "08:00", "close": "18:00"},
    "saturday": {"open": "09:00", "close": "17:00"},
    "sunday": {"open": "09:00", "close": "17:00"}
}'>{{ old('operating_hours') }}</textarea>
                            <small class="text-muted">
                                Format: {"day": {"open": "HH:MM", "close": "HH:MM"}}<br>
                                For closed days: {"sunday": {"closed": true}}<br>
                                Leave empty for default hours
                            </small>
                            @error('operating_hours')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card mb-4">
                    <div class="card-header bg-light">
                        <h6 class="fw-bold mb-0"><i class="bi bi-info-circle me-2"></i>Quick Tips</h6>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-info">
                            <h6><i class="bi bi-lightbulb me-2"></i>Best Practices:</h6>
                            <ul class="mb-0">
                                <li>Use unique branch codes (SBL, DGT, BAI)</li>
                                <li>Ensure phone numbers are correct for customer contact</li>
                                <li>Set accurate coordinates for Google Maps</li>
                                <li>Update operating hours for accurate "Open Now" status</li>
                            </ul>
                        </div>

                        <div class="alert alert-warning">
                            <h6><i class="bi bi-exclamation-triangle me-2"></i>Important:</h6>
                            <ul class="mb-0">
                                <li>Inactive branches won't appear in mobile app</li>
                                <li>Branch code cannot be changed after creation</li>
                                <li>Operating hours affect real-time "Open/Closed" status</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-primary btn-lg">
                        <i class="bi bi-plus-circle me-2"></i>Create Branch
                    </button>
                    <a href="{{ route('admin.branches.index') }}" class="btn btn-light border">
                        <i class="bi bi-x-circle me-2"></i>Cancel
                    </a>
                </div>

                <div class="card mt-4">
                    <div class="card-header bg-light">
                        <h6 class="fw-bold mb-0"><i class="bi bi-eye me-2"></i>Preview</h6>
                    </div>
                    <div class="card-body">
                        <small class="text-muted">This branch will appear in mobile app as:</small>
                        <div class="mt-2 p-3 bg-light rounded">
                            <div class="d-flex align-items-center mb-2">
                                <div class="bg-primary rounded-circle p-2 me-2">
                                    <i class="bi bi-building text-white"></i>
                                </div>
                                <div>
                                    <strong id="preview-name">{{ old('name', 'WashBox Branch') }}</strong>
                                    <div class="text-muted small" id="preview-code">{{ old('code', 'CODE') }}</div>
                                </div>
                            </div>
                            <div class="small">
                                <div><i class="bi bi-geo-alt me-1"></i> <span id="preview-address">{{ old('address', 'Address not set') }}</span></div>
                                <div><i class="bi bi-telephone me-1"></i> <span id="preview-phone">{{ old('phone', 'Phone not set') }}</span></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
// Live preview update
document.addEventListener('DOMContentLoaded', function() {
    const nameInput = document.querySelector('input[name="name"]');
    const codeInput = document.querySelector('input[name="code"]');
    const addressInput = document.querySelector('textarea[name="address"]');
    const phoneInput = document.querySelector('input[name="phone"]');

    const previewName = document.getElementById('preview-name');
    const previewCode = document.getElementById('preview-code');
    const previewAddress = document.getElementById('preview-address');
    const previewPhone = document.getElementById('preview-phone');

    function updatePreview() {
        previewName.textContent = nameInput.value || 'WashBox Branch';
        previewCode.textContent = codeInput.value || 'CODE';
        previewAddress.textContent = addressInput.value || 'Address not set';
        previewPhone.textContent = phoneInput.value || 'Phone not set';
    }

    // Update preview on input
    [nameInput, codeInput, addressInput, phoneInput].forEach(input => {
        input.addEventListener('input', updatePreview);
    });

    // Initial update
    updatePreview();

    // Format phone number
    const phoneField = document.querySelector('input[name="phone"]');
    phoneField.addEventListener('input', function(e) {
        let value = e.target.value.replace(/\D/g, '');
        if (value.length > 0) {
            if (value.length <= 4) {
                value = value;
            } else if (value.length <= 7) {
                value = value.slice(0, 4) + ' ' + value.slice(4);
            } else if (value.length <= 11) {
                value = value.slice(0, 4) + ' ' + value.slice(4, 7) + ' ' + value.slice(7);
            } else {
                value = value.slice(0, 11);
            }
        }
        e.target.value = value;
    });
});
</script>
@endpush

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
    #preview-name {
        font-size: 1rem;
        line-height: 1.2;
    }
    #preview-code {
        font-size: 0.75rem;
        opacity: 0.8;
    }
</style>
@endpush
