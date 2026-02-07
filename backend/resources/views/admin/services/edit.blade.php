@extends('admin.layouts.app')

@section('title', 'Edit Service')

@section('content')
<div class="container-fluid px-4 py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold text-dark mb-1">Edit Service</h2>
            <p class="text-muted small mb-0">Update laundry service details</p>
        </div>
        <a href="{{ route('admin.services.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-2"></i>Back to Services
        </a>
    </div>

    <form action="{{ route('admin.services.update', $service->id) }}" method="POST" enctype="multipart/form-data">
        @csrf
        @method('PUT')

        <div class="row g-4">
            {{-- Left Column - Main Form --}}
            <div class="col-lg-8">
                {{-- Basic Information --}}
                <div class="card border-0 shadow-sm rounded-4 mb-4">
                    <div class="card-header bg-white border-bottom py-3">
                        <h6 class="mb-0 fw-bold text-dark">
                            <i class="bi bi-info-circle me-2" style="color: #3D3B6B;"></i>
                            Basic Information
                        </h6>
                    </div>
                    <div class="card-body p-4">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Service Name <span class="text-danger">*</span></label>
                                <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                                    value="{{ old('name', $service->name) }}" required>
                                @error('name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Service Type</label>
                                <select name="service_type" class="form-select @error('service_type') is-invalid @enderror">
                                    <option value="">Select Type</option>
                                    <option value="wash" {{ old('service_type', $service->service_type) == 'wash' ? 'selected' : '' }}>Wash Only</option>
                                    <option value="dry" {{ old('service_type', $service->service_type) == 'dry' ? 'selected' : '' }}>Dry Only</option>
                                    <option value="wash_fold" {{ old('service_type', $service->service_type) == 'wash_fold' ? 'selected' : '' }}>Wash & Fold</option>
                                    <option value="wash_iron" {{ old('service_type', $service->service_type) == 'wash_iron' ? 'selected' : '' }}>Wash & Iron</option>
                                    <option value="iron" {{ old('service_type', $service->service_type) == 'iron' ? 'selected' : '' }}>Iron Only</option>
                                    <option value="dry_clean" {{ old('service_type', $service->service_type) == 'dry_clean' ? 'selected' : '' }}>Dry Cleaning</option>
                                </select>
                                @error('service_type')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-12">
                                <label class="form-label fw-semibold">Description</label>
                                <textarea name="description" class="form-control @error('description') is-invalid @enderror"
                                    rows="3">{{ old('description', $service->description) }}</textarea>
                                @error('description')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Pricing & Weight --}}
                <div class="card border-0 shadow-sm rounded-4 mb-4">
                    <div class="card-header bg-white border-bottom py-3">
                        <h6 class="mb-0 fw-bold text-dark">
                            <i class="bi bi-cash-coin me-2" style="color: #3D3B6B;"></i>
                            Pricing & Weight Limits
                        </h6>
                    </div>
                    <div class="card-body p-4">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Price per Kilogram <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text">₱</span>
                                    <input type="number" name="price_per_kg" step="0.01"
                                        class="form-control @error('price_per_kg') is-invalid @enderror"
                                        value="{{ old('price_per_kg', $service->price_per_kg) }}" required>
                                    @error('price_per_kg')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <small class="text-muted">Base price per kg</small>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Minimum Weight</label>
                                <div class="input-group">
                                    <input type="number" name="min_weight" step="0.1"
                                        class="form-control @error('min_weight') is-invalid @enderror"
                                        value="{{ old('min_weight', $service->min_weight) }}">
                                    <span class="input-group-text">kg</span>
                                    @error('min_weight')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <small class="text-muted">Minimum order weight</small>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Maximum Weight</label>
                                <div class="input-group">
                                    <input type="number" name="max_weight" step="0.1"
                                        class="form-control @error('max_weight') is-invalid @enderror"
                                        value="{{ old('max_weight', $service->max_weight) }}">
                                    <span class="input-group-text">kg</span>
                                    @error('max_weight')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <small class="text-muted">Maximum order weight</small>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Service Details --}}
                <div class="card border-0 shadow-sm rounded-4 mb-4">
                    <div class="card-header bg-white border-bottom py-3">
                        <h6 class="mb-0 fw-bold text-dark">
                            <i class="bi bi-clock-history me-2" style="color: #3D3B6B;"></i>
                            Service Details
                        </h6>
                    </div>
                    <div class="card-body p-4">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Turnaround Time</label>
                                <div class="input-group">
                                    <input type="number" name="turnaround_time"
                                        class="form-control @error('turnaround_time') is-invalid @enderror"
                                        value="{{ old('turnaround_time', $service->turnaround_time) }}">
                                    <span class="input-group-text">hours</span>
                                    @error('turnaround_time')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <small class="text-muted">Estimated completion time</small>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Status</label>
                                <select name="is_active" class="form-select">
                                    <option value="1" {{ old('is_active', $service->is_active) == '1' ? 'selected' : '' }}>Active (Available)</option>
                                    <option value="0" {{ old('is_active', $service->is_active) == '0' ? 'selected' : '' }}>Inactive (Hidden)</option>
                                </select>
                                <small class="text-muted">Service availability</small>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Action Buttons --}}
                <div class="d-flex gap-2 mb-4">
                    <button type="submit" class="btn btn-primary px-5 shadow-sm" style="background: #3D3B6B; border: none;">
                        <i class="bi bi-check-circle me-2"></i>Update Service
                    </button>
                    <a href="{{ route('admin.services.index') }}" class="btn btn-outline-secondary px-4">
                        <i class="bi bi-x-circle me-2"></i>Cancel
                    </a>
                </div>
            </div>

            {{-- Right Column - Preview & Icon --}}
            <div class="col-lg-4">
                <div class="sticky-top" style="top: 20px;">
                    {{-- Icon Upload --}}
                    <div class="card border-0 shadow-sm rounded-4 mb-3">
                        <div class="card-header bg-white border-bottom py-3">
                            <h6 class="mb-0 fw-bold text-dark">
                                <i class="bi bi-image me-2" style="color: #3D3B6B;"></i>
                                Service Icon
                            </h6>
                        </div>
                        <div class="card-body p-4 text-center">
                            <div class="mb-3">
                                <div class="rounded-3 mx-auto d-flex align-items-center justify-content-center"
                                    id="iconPreview"
                                    style="width: 150px; height: 150px; background: linear-gradient(135deg, #3D3B6B 0%, #6366F1 100%);">
                                    @if($service->icon_path)
                                        <img src="{{ asset('storage/' . $service->icon_path) }}" class="w-100 h-100 rounded-3" style="object-fit: contain; background: white; padding: 10px;">
                                    @else
                                        <i class="bi bi-droplet text-white" style="font-size: 4rem;"></i>
                                    @endif
                                </div>
                            </div>
                            <input type="file" name="icon" id="iconInput" class="form-control @error('icon') is-invalid @enderror" accept="image/*">
                            <small class="text-muted d-block mt-2">Max 2MB (JPG, PNG, SVG)</small>
                            @error('icon')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    {{-- Quick Tips --}}
                    <div class="card border-0 shadow-sm rounded-4">
                        <div class="card-body p-4">
                            <h6 class="fw-bold mb-3">
                                <i class="bi bi-lightbulb text-warning me-2"></i>Quick Tips
                            </h6>
                            <ul class="small text-muted mb-0 ps-3">
                                <li class="mb-2">Use clear, descriptive service names</li>
                                <li class="mb-2">Set competitive pricing per kilogram</li>
                                <li class="mb-2">Define min/max weight to manage orders</li>
                                <li class="mb-2">Upload a simple, recognizable icon</li>
                                <li class="mb-2">Set realistic turnaround times</li>
                                <li class="mb-0">Start as Active to make available</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

@push('scripts')
<script>
// Icon preview
document.getElementById('iconInput').addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('iconPreview').innerHTML =
                `<img src="${e.target.result}" class="w-100 h-100 rounded-3" style="object-fit: contain; background: white; padding: 10px;">`;
        }
        reader.readAsDataURL(file);
    }
});
</script>
@endpush
@endsection
