@extends('admin.layouts.app')

@section('title', 'Add Service')

@section('content')
<div class="container-fluid px-4 py-4">
    {{-- Header --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold text-dark mb-1">Add New Service</h2>
            <p class="text-muted small mb-0">Create a new laundry service offering</p>
        </div>
        <a href="{{ route('admin.services.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-2"></i>Back to Services
        </a>
    </div>

    <form action="{{ route('admin.services.store') }}" method="POST" enctype="multipart/form-data">
        @csrf

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
                                    value="{{ old('name') }}" placeholder="e.g., Bestseller Package" required>
                                @error('name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Service Type <span class="text-danger">*</span></label>
                                <select name="service_type" class="form-select @error('service_type') is-invalid @enderror" required>
                                    <option value="">Select Service Type</option>
                                    <option value="full_service" {{ old('service_type') == 'full_service' ? 'selected' : '' }}>Full Service</option>
                                    <option value="self_service" {{ old('service_type') == 'self_service' ? 'selected' : '' }}>Self Service</option>
                                    <option value="special_item" {{ old('service_type') == 'special_item' ? 'selected' : '' }}>Special Item (Comforters/Blankets)</option>
                                    <option value="addon" {{ old('service_type') == 'addon' ? 'selected' : '' }}>Add-on</option>
                                </select>
                                @error('service_type')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="text-muted">
                                    • Full Service: Complete laundry packages<br>
                                    • Self Service: Customer-operated services<br>
                                    • Special Item: Comforters, blankets per piece<br>
                                    • Add-on: Extra services or products
                                </small>
                            </div>

                            <div class="col-12">
                                <label class="form-label fw-semibold">Description</label>
                                <textarea name="description" class="form-control @error('description') is-invalid @enderror"
                                    rows="3" placeholder="Describe the service details, inclusions, etc...">{{ old('description') }}</textarea>
                                @error('description')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Pricing Configuration --}}
                <div class="card border-0 shadow-sm rounded-4 mb-4">
                    <div class="card-header bg-white border-bottom py-3">
                        <h6 class="mb-0 fw-bold text-dark">
                            <i class="bi bi-cash-coin me-2" style="color: #3D3B6B;"></i>
                            Pricing Configuration
                        </h6>
                    </div>
                    <div class="card-body p-4">
                        <div class="row mb-4">
                            <div class="col-12">
                                <label class="form-label fw-semibold">Pricing Type <span class="text-danger">*</span></label>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <div class="form-check card border rounded-3 p-3">
                                            <input class="form-check-input" type="radio" name="pricing_type"
                                                id="pricingPerKg" value="per_kg"
                                                {{ old('pricing_type', 'per_load') == 'per_kg' ? 'checked' : '' }}>
                                            <label class="form-check-label fw-semibold" for="pricingPerKg">
                                                <i class="bi bi-scale text-primary"></i> Per Kilogram Pricing
                                            </label>
                                            <p class="text-muted small mb-0 mt-1">
                                                Price varies by weight (e.g., ₱80/kg). Good for regular laundry.
                                            </p>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-check card border rounded-3 p-3">
                                            <input class="form-check-input" type="radio" name="pricing_type"
                                                id="pricingPerLoad" value="per_load"
                                                {{ old('pricing_type', 'per_load') == 'per_load' ? 'checked' : '' }}>
                                            <label class="form-check-label fw-semibold" for="pricingPerLoad">
                                                <i class="bi bi-basket text-success"></i> Per Load/Piece Pricing
                                            </label>
                                            <p class="text-muted small mb-0 mt-1">
                                                Fixed price per load or piece (e.g., ₱200/load, ₱150/piece).
                                            </p>
                                        </div>
                                    </div>
                                </div>
                                @error('pricing_type')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="row g-3">
                            {{-- Price per kg (shown when per_kg selected) --}}
                            <div class="col-md-6" id="pricePerKgContainer">
                                <label class="form-label fw-semibold">Price per Kilogram <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text">₱</span>
                                    <input type="number" name="price_per_kg" step="0.01" min="0"
                                        class="form-control @error('price_per_kg') is-invalid @enderror"
                                        value="{{ old('price_per_kg', 0) }}" placeholder="80.00">
                                    @error('price_per_kg')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <small class="text-muted">Base price per kg</small>
                            </div>

                            {{-- Price per load (shown when per_load selected) --}}
                            <div class="col-md-6" id="pricePerLoadContainer">
                                <label class="form-label fw-semibold">Price per Load/Piece <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text">₱</span>
                                    <input type="number" name="price_per_load" step="0.01" min="0"
                                        class="form-control @error('price_per_load') is-invalid @enderror"
                                        value="{{ old('price_per_load', 0) }}" placeholder="200.00">
                                    @error('price_per_load')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <small class="text-muted">Fixed price per load or piece</small>
                            </div>

                            {{-- Weight Limits (for per_kg pricing) --}}
                            <div class="col-md-6" id="minWeightContainer">
                                <label class="form-label fw-semibold">Minimum Weight (Optional)</label>
                                <div class="input-group">
                                    <input type="number" name="min_weight" step="0.1" min="0"
                                        class="form-control @error('min_weight') is-invalid @enderror"
                                        value="{{ old('min_weight') }}" placeholder="1.0">
                                    <span class="input-group-text">kg</span>
                                    @error('min_weight')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <small class="text-muted">Minimum order weight</small>
                            </div>

                            <div class="col-md-6" id="maxWeightContainer">
                                <label class="form-label fw-semibold">Maximum Weight (Optional)</label>
                                <div class="input-group">
                                    <input type="number" name="max_weight" step="0.1" min="0"
                                        class="form-control @error('max_weight') is-invalid @enderror"
                                        value="{{ old('max_weight') }}" placeholder="8.0">
                                    <span class="input-group-text">kg</span>
                                    @error('max_weight')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <small class="text-muted">Maximum order weight per load</small>
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
                                <label class="form-label fw-semibold">Turnaround Time (Hours) <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="number" name="turnaround_time" min="1" max="168" step="1"
                                        class="form-control @error('turnaround_time') is-invalid @enderror"
                                        value="{{ old('turnaround_time', 24) }}" placeholder="24" required>
                                    <span class="input-group-text">hours</span>
                                    @error('turnaround_time')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <small class="text-muted">
                                    • Full Service: 24-48 hours<br>
                                    • Self Service: 2-4 hours<br>
                                    • Add-ons: 0 hours
                                </small>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Status</label>
                                <select name="is_active" class="form-select @error('is_active') is-invalid @enderror">
                                    <option value="1" {{ old('is_active', '1') == '1' ? 'selected' : '' }}>Active (Available)</option>
                                    <option value="0" {{ old('is_active') == '0' ? 'selected' : '' }}>Inactive (Hidden)</option>
                                </select>
                                <small class="text-muted">Service availability in the system</small>
                                @error('is_active')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            {{-- Slug (auto-generated) --}}
                            <div class="col-12">
                                <label class="form-label fw-semibold">URL Slug</label>
                                <input type="text" name="slug" class="form-control @error('slug') is-invalid @enderror"
                                    value="{{ old('slug') }}" placeholder="bestseller-package (auto-generated)">
                                @error('slug')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="text-muted">Leave blank to auto-generate from service name</small>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Action Buttons --}}
                <div class="d-flex gap-2 mb-4">
                    <button type="submit" class="btn btn-primary px-5 shadow-sm" style="background: #3D3B6B; border: none;">
                        <i class="bi bi-check-circle me-2"></i>Add Service
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
                                Service Icon (Optional)
                            </h6>
                        </div>
                        <div class="card-body p-4 text-center">
                            <div class="mb-3">
                                <div class="rounded-3 mx-auto d-flex align-items-center justify-content-center"
                                    id="iconPreview"
                                    style="width: 150px; height: 150px; background: linear-gradient(135deg, #3D3B6B 0%, #6366F1 100%);">
                                    <i class="bi bi-droplet text-white" style="font-size: 4rem;"></i>
                                </div>
                            </div>
                            <input type="file" name="icon" id="iconInput" class="form-control @error('icon') is-invalid @enderror" accept="image/*">
                            <small class="text-muted d-block mt-2">Max 2MB (JPG, PNG, SVG, GIF)</small>
                            @error('icon')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    {{-- Service Preview --}}
                    <div class="card border-0 shadow-sm rounded-4 mb-3">
                        <div class="card-header bg-white border-bottom py-3">
                            <h6 class="mb-0 fw-bold text-dark">
                                <i class="bi bi-eye me-2" style="color: #3D3B6B;"></i>
                                Service Preview
                            </h6>
                        </div>
                        <div class="card-body p-4">
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Service Name:</label>
                                <div id="previewName" class="text-dark fw-semibold">-</div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Pricing:</label>
                                <div id="previewPricing" class="text-success fw-bold">-</div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Type:</label>
                                <div id="previewType">
                                    <span class="badge bg-secondary">-</span>
                                </div>
                            </div>
                            <div class="mb-0">
                                <label class="form-label fw-semibold">Turnaround:</label>
                                <div id="previewTime" class="text-muted">- hours</div>
                            </div>
                        </div>
                    </div>

                    {{-- Quick Tips --}}
                    <div class="card border-0 shadow-sm rounded-4">
                        <div class="card-body p-4">
                            <h6 class="fw-bold mb-3">
                                <i class="bi bi-lightbulb text-warning me-2"></i>Quick Tips
                            </h6>
                            <ul class="small text-muted mb-0 ps-3">
                                <li class="mb-2"><strong>Full Service:</strong> Packages like Bestseller, Regular, Premium</li>
                                <li class="mb-2"><strong>Self Service:</strong> Customer-operated (Wash, Dry, Fold)</li>
                                <li class="mb-2"><strong>Special Item:</strong> Comforters/blankets per piece pricing</li>
                                <li class="mb-2"><strong>Add-ons:</strong> Detergent, fabcon, extra wash, etc.</li>
                                <li class="mb-2">Set per-load pricing for packages</li>
                                <li class="mb-2">Add weight limits for full service packages</li>
                                <li class="mb-0">Special items: No weight limits, per piece pricing</li>
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

// Auto-generate slug from name
document.querySelector('input[name="name"]').addEventListener('input', function(e) {
    const name = e.target.value;
    const slugInput = document.querySelector('input[name="slug"]');

    if (!slugInput.value || slugInput.value === slugInput.dataset.original) {
        const slug = name.toLowerCase()
            .replace(/[^\w\s-]/g, '')
            .replace(/\s+/g, '-')
            .replace(/--+/g, '-')
            .trim();
        slugInput.value = slug;
        slugInput.dataset.original = slug;
    }
});

// Toggle pricing fields based on pricing type
function togglePricingFields() {
    const pricingType = document.querySelector('input[name="pricing_type"]:checked').value;
    const pricePerKgContainer = document.getElementById('pricePerKgContainer');
    const pricePerLoadContainer = document.getElementById('pricePerLoadContainer');
    const minWeightContainer = document.getElementById('minWeightContainer');
    const maxWeightContainer = document.getElementById('maxWeightContainer');

    if (pricingType === 'per_kg') {
        pricePerKgContainer.style.display = 'block';
        pricePerLoadContainer.style.display = 'none';
        minWeightContainer.style.display = 'block';
        maxWeightContainer.style.display = 'block';

        // Update validation
        document.querySelector('input[name="price_per_kg"]').required = true;
        document.querySelector('input[name="price_per_load"]').required = false;
    } else {
        pricePerKgContainer.style.display = 'none';
        pricePerLoadContainer.style.display = 'block';
        minWeightContainer.style.display = 'none';
        maxWeightContainer.style.display = 'none';

        // Update validation
        document.querySelector('input[name="price_per_kg"]').required = false;
        document.querySelector('input[name="price_per_load"]').required = true;
    }
}

// Add event listeners to pricing type radios
document.querySelectorAll('input[name="pricing_type"]').forEach(radio => {
    radio.addEventListener('change', togglePricingFields);
});

// Live preview update
function updatePreview() {
    const name = document.querySelector('input[name="name"]').value || '-';
    const serviceType = document.querySelector('select[name="service_type"]').value;
    const pricingType = document.querySelector('input[name="pricing_type"]:checked')?.value;
    const pricePerKg = document.querySelector('input[name="price_per_kg"]').value;
    const pricePerLoad = document.querySelector('input[name="price_per_load"]').value;
    const turnaround = document.querySelector('input[name="turnaround_time"]').value;

    // Update name
    document.getElementById('previewName').textContent = name || '-';

    // Update pricing preview
    if (pricingType === 'per_kg') {
        document.getElementById('previewPricing').textContent =
            pricePerKg ? `₱${parseFloat(pricePerKg).toFixed(2)}/kg` : '-';
    } else {
        document.getElementById('previewPricing').textContent =
            pricePerLoad ? `₱${parseFloat(pricePerLoad).toFixed(2)}/load` : '-';
    }

    // Update type badge
    const typeBadge = document.getElementById('previewType');
    const typeLabels = {
        'full_service': { text: 'Full Service', class: 'bg-primary' },
        'self_service': { text: 'Self Service', class: 'bg-success' },
        'special_item': { text: 'Special Item', class: 'bg-warning text-dark' },
        'addon': { text: 'Add-on', class: 'bg-info' }
    };

    if (serviceType && typeLabels[serviceType]) {
        typeBadge.innerHTML = `<span class="badge ${typeLabels[serviceType].class}">${typeLabels[serviceType].text}</span>`;
    } else {
        typeBadge.innerHTML = '<span class="badge bg-secondary">-</span>';
    }

    // Update turnaround time
    document.getElementById('previewTime').textContent =
        turnaround ? `${turnaround} hours` : '- hours';
}

// Add event listeners for live preview
const previewInputs = [
    'input[name="name"]',
    'select[name="service_type"]',
    'input[name="price_per_kg"]',
    'input[name="price_per_load"]',
    'input[name="turnaround_time"]'
];

previewInputs.forEach(selector => {
    document.querySelector(selector)?.addEventListener('input', updatePreview);
    document.querySelector(selector)?.addEventListener('change', updatePreview);
});

// Add event listener for pricing type change
document.querySelectorAll('input[name="pricing_type"]').forEach(radio => {
    radio.addEventListener('change', updatePreview);
});

// Auto-show/hide weight fields based on service type
document.querySelector('select[name="service_type"]').addEventListener('change', function(e) {
    const serviceType = e.target.value;

    if (serviceType === 'special_item' || serviceType === 'addon') {
        // For special items and addons, default to per-load pricing
        document.getElementById('pricingPerLoad').checked = true;
        togglePricingFields();
        updatePreview();
    }
});

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    togglePricingFields();
    updatePreview();

    // Store original slug value
    const slugInput = document.querySelector('input[name="slug"]');
    if (slugInput) {
        slugInput.dataset.original = slugInput.value;
    }
});
</script>

<style>
.form-check .card {
    transition: all 0.2s ease;
    cursor: pointer;
}

.form-check .card:hover {
    border-color: #3D3B6B;
    transform: translateY(-2px);
}

.form-check-input:checked + .form-check-label .card {
    border-color: #3D3B6B;
    background-color: rgba(61, 59, 107, 0.05);
}

/* Smooth transitions for toggling fields */
#pricePerKgContainer,
#pricePerLoadContainer,
#minWeightContainer,
#maxWeightContainer {
    transition: all 0.3s ease;
}
</style>
@endpush
@endsection
