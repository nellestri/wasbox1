@extends('admin.layouts.app')

@section('title', 'Services & Add-Ons Management')
@php
    // Fetch add-ons if not passed from controller
    $addons = $addons ?? \App\Models\AddOn::latest()->get();
@endphp
@section('content')
<div class="container-fluid px-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold text-dark mb-1">Services & Add-Ons</h2>
            <p class="text-muted small mb-0">Manage laundry services and additional options</p>
        </div>
        <div class="btn-group shadow-sm">
            <a href="{{ route('admin.services.create') }}" class="btn btn-primary px-4">
                <i class="bi bi-plus-circle me-2"></i>New Service
            </a>
            <button type="button" class="btn btn-outline-primary px-4" data-bs-toggle="modal" data-bs-target="#createAddonModal">
                <i class="bi bi-plus-lg me-2"></i>New Add-On
            </button>
        </div>
    </div>

    {{-- Stats Cards --}}
    <div class="row mb-4">
        <div class="col-md-6 col-lg-3 mb-3">
            <div class="card border-0 shadow-sm h-100" style="border-left: 4px solid #3D3B6B;">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center">
                        <div class="bg-primary bg-opacity-10 p-3 rounded-3 me-3">
                            <i class="bi bi-droplet text-primary fs-4"></i>
                        </div>
                        <div>
                            <h5 class="fw-bold mb-1">{{ $services->count() }}</h5>
                            <p class="text-muted small mb-0">Total Services</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-lg-3 mb-3">
            <div class="card border-0 shadow-sm h-100" style="border-left: 4px solid #28a745;">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center">
                        <div class="bg-success bg-opacity-10 p-3 rounded-3 me-3">
                            <i class="bi bi-plus-circle text-success fs-4"></i>
                        </div>
                        <div>
                            <h5 class="fw-bold mb-1">{{ $addons->count() }}</h5>
                            <p class="text-muted small mb-0">Total Add-Ons</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-lg-3 mb-3">
            <div class="card border-0 shadow-sm h-100" style="border-left: 4px solid #fd7e14;">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center">
                        <div class="bg-warning bg-opacity-10 p-3 rounded-3 me-3">
                            <i class="bi bi-check-circle text-warning fs-4"></i>
                        </div>
                        <div>
                            <h5 class="fw-bold mb-1">{{ $services->where('is_active', true)->count() }}</h5>
                            <p class="text-muted small mb-0">Active Services</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-lg-3 mb-3">
            <div class="card border-0 shadow-sm h-100" style="border-left: 4px solid #17a2b8;">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center">
                        <div class="bg-info bg-opacity-10 p-3 rounded-3 me-3">
                            <i class="bi bi-tag text-info fs-4"></i>
                        </div>
                        <div>
                            <h5 class="fw-bold mb-1">{{ $addons->where('is_active', true)->count() }}</h5>
                            <p class="text-muted small mb-0">Active Add-Ons</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Services Section --}}
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-header bg-white border-0 pb-0 pt-4 px-4">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="fw-bold mb-1 text-dark">
                        <i class="bi bi-droplet me-2" style="color: #3D3B6B;"></i>
                        Laundry Services
                    </h5>
                    <p class="text-muted small mb-0">Manage all laundry service packages</p>
                </div>
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" id="showInactiveServices">
                    <label class="form-check-label small" for="showInactiveServices">Show Inactive</label>
                </div>
            </div>
        </div>
        <div class="card-body p-4">
            @if($services->count() > 0)
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead class="table-light">
                        <tr>
                            <th width="50">ID</th>
                            <th>Service Name</th>
                            <th>Type</th>
                            <th>Pricing</th>
                            <th>Turnaround</th>
                            <th>Price</th>
                            <th>Weight Limit</th>
                            <th>Status</th>
                            <th>Orders</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($services as $service)
                        <tr class="service-row" data-active="{{ $service->is_active }}">
                            <td class="fw-bold">#{{ $service->id }}</td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div>
                                        <strong>{{ $service->name }}</strong>
                                        @if($service->description)
                                        <div class="small text-muted">{{ Str::limit($service->description, 50) }}</div>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span class="badge bg-{{ $service->service_type === 'full_service' ? 'primary' : ($service->service_type === 'self_service' ? 'info' : 'warning') }}">
                                    {{ ucfirst(str_replace('_', ' ', $service->service_type)) }}
                                </span>
                            </td>
                            <td>
                                <span class="badge bg-light text-dark">
                                    {{ $service->pricing_type === 'per_kg' ? 'Per kg' : 'Per load' }}
                                </span>
                            </td>
                            <td>{{ $service->turnaround_time }}h</td>
                            <td class="fw-bold text-primary">
                                @if($service->pricing_type === 'per_kg')
                                    ₱{{ number_format($service->price_per_kg, 2) }}/kg
                                @else
                                    ₱{{ number_format($service->price_per_load, 2) }}/load
                                @endif
                            </td>
                            <td>
                                @if($service->min_weight && $service->max_weight)
                                    {{ $service->min_weight }}-{{ $service->max_weight }}kg
                                @elseif($service->max_weight)
                                    up to {{ $service->max_weight }}kg
                                @else
                                    -
                                @endif
                            </td>
                            <td>
                                <span class="badge bg-{{ $service->is_active ? 'success' : 'secondary' }}">
                                    {{ $service->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td>
                                <span class="badge bg-light text-dark">
                                    {{ $service->orders_count ?? 0 }}
                                </span>
                            </td>
                            <td class="text-end">
                                <div class="btn-group btn-group-sm">
                                    <a href="{{ route('admin.services.edit', $service) }}" class="btn btn-outline-primary">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <button type="button" class="btn btn-outline-danger delete-service"
                                            data-id="{{ $service->id }}"
                                            data-name="{{ $service->name }}">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @else
            <div class="text-center py-5">
                <div class="mb-3">
                    <i class="bi bi-droplet display-1 text-muted opacity-25"></i>
                </div>
                <h5 class="text-muted mb-2">No services yet</h5>
                <p class="text-muted mb-4">Start by creating your first laundry service</p>
                <a href="{{ route('admin.services.create') }}" class="btn btn-primary">
                    <i class="bi bi-plus-circle me-2"></i>Create First Service
                </a>
            </div>
            @endif
        </div>
    </div>

    {{-- Add-Ons Section --}}
    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-header bg-white border-0 pb-0 pt-4 px-4">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="fw-bold mb-1 text-dark">
                        <i class="bi bi-plus-circle me-2" style="color: #28a745;"></i>
                        Add-On Services
                    </h5>
                    <p class="text-muted small mb-0">Manage additional laundry options</p>
                </div>
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" id="showInactiveAddons">
                    <label class="form-check-label small" for="showInactiveAddons">Show Inactive</label>
                </div>
            </div>
        </div>
        <div class="card-body p-4">
            @if($addons->count() > 0)
            <div class="row g-3" id="addonsContainer">
                @foreach($addons as $addon)
                <div class="col-md-6 col-lg-4 col-xl-3 addon-item" data-active="{{ $addon->is_active }}">
                    <div class="card border h-100 hover-shadow-sm {{ $addon->is_active ? 'border-success border-opacity-25' : 'border-secondary border-opacity-25' }}">
                        <div class="card-body p-3">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <div class="flex-grow-1">
                                    <div class="d-flex align-items-center mb-1">
                                        <h6 class="fw-bold mb-0 text-truncate">{{ $addon->name }}</h6>
                                        @if(!$addon->is_active)
                                            <span class="badge bg-secondary ms-2">Inactive</span>
                                        @endif
                                    </div>
                                    @if($addon->description)
                                    <p class="small text-muted mb-2">{{ Str::limit($addon->description, 60) }}</p>
                                    @endif
                                </div>
                                <div class="dropdown">
                                    <button class="btn btn-link text-muted p-0 border-0" type="button" data-bs-toggle="dropdown">
                                        <i class="bi bi-three-dots-vertical"></i>
                                    </button>
                                    <ul class="dropdown-menu shadow-sm">
                                        <li>
                                            <button class="dropdown-item edit-addon"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#editAddonModal"
                                                    data-addon-id="{{ $addon->id }}"
                                                    data-addon-name="{{ $addon->name }}"
                                                    data-addon-slug="{{ $addon->slug }}"
                                                    data-addon-description="{{ $addon->description }}"
                                                    data-addon-price="{{ $addon->price }}"
                                                    data-addon-is-active="{{ $addon->is_active }}">
                                                <i class="bi bi-pencil me-2"></i>Edit
                                            </button>
                                        </li>
                                        <li>
                                            <button class="dropdown-item text-danger delete-addon"
                                                    data-id="{{ $addon->id }}"
                                                    data-name="{{ $addon->name }}">
                                                <i class="bi bi-trash me-2"></i>Delete
                                            </button>
                                        </li>
                                    </ul>
                                </div>
                            </div>

                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <span class="badge bg-light text-dark small">
                                        {{ $addon->slug }}
                                    </span>
                                </div>
                                <div class="text-end">
                                    <h5 class="fw-bold mb-0 text-success">₱{{ number_format($addon->price, 2) }}</h5>
                                </div>
                            </div>
                        </div>
                        <div class="card-footer bg-transparent border-top py-2 px-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <small class="text-muted">
                                    <i class="bi bi-cart me-1"></i>
                                    {{ $addon->orders_count ?? 0 }} orders
                                </small>
                                <div class="form-check form-switch">
                                    <input class="form-check-input addon-status-toggle"
                                           type="checkbox"
                                           data-id="{{ $addon->id }}"
                                           {{ $addon->is_active ? 'checked' : '' }}>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
            @else
            <div class="text-center py-5">
                <div class="mb-3">
                    <i class="bi bi-plus-circle display-1 text-muted opacity-25"></i>
                </div>
                <h5 class="text-muted mb-2">No add-ons yet</h5>
                <p class="text-muted mb-4">Start by creating your first add-on service</p>
                <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#createAddonModal">
                    <i class="bi bi-plus-lg me-2"></i>Create First Add-On
                </button>
            </div>
            @endif
        </div>
    </div>
</div>

{{-- Create Add-On Modal --}}
<div class="modal fade" id="createAddonModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <form id="createAddonForm" method="POST">
                @csrf
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title"><i class="bi bi-plus-circle me-2"></i>Create New Add-On</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label fw-semibold">Add-On Name *</label>
                            <input type="text" name="name" class="form-control" required placeholder="e.g., Fabric Conditioner">
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">Slug *</label>
                            <input type="text" name="slug" class="form-control" required placeholder="e.g., fabric-conditioner">
                            <small class="text-muted">URL-friendly version (lowercase, hyphens)</small>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">Description</label>
                            <textarea name="description" class="form-control" rows="2" placeholder="Add-on description..."></textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">Price (₱) *</label>
                            <div class="input-group">
                                <span class="input-group-text">₱</span>
                                <input type="number" name="price" class="form-control" step="0.01" min="0" required placeholder="0.00">
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="is_active" id="addonActive" checked>
                                <label class="form-check-label fw-semibold" for="addonActive">Active Add-On</label>
                            </div>
                            <small class="text-muted">Inactive add-ons won't appear in order creation</small>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-top">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success px-4">Create Add-On</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Edit Add-On Modal --}}
<div class="modal fade" id="editAddonModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <form id="editAddonForm" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title"><i class="bi bi-pencil me-2"></i>Edit Add-On</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label fw-semibold">Add-On Name *</label>
                            <input type="text" name="name" id="editAddonName" class="form-control" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">Slug *</label>
                            <input type="text" name="slug" id="editAddonSlug" class="form-control" required>
                            <small class="text-muted">URL-friendly version</small>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">Description</label>
                            <textarea name="description" id="editAddonDescription" class="form-control" rows="2"></textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">Price (₱) *</label>
                            <div class="input-group">
                                <span class="input-group-text">₱</span>
                                <input type="number" name="price" id="editAddonPrice" class="form-control" step="0.01" min="0" required>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="is_active" id="editAddonActive">
                                <label class="form-check-label fw-semibold" for="editAddonActive">Active Add-On</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-top">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success px-4">Update Add-On</button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Set form action URLs - FIXED: Changed from services.store to addons.store
    const createAddonForm = document.getElementById('createAddonForm');
    if (createAddonForm) {
        // Check if route exists, otherwise use URL
        @if(route('admin.addons.store', [], false))
            createAddonForm.action = "{{ route('admin.addons.store') }}";
        @else
            createAddonForm.action = "/admin/addons";
        @endif
    }

    // Show/Hide inactive services
    const showInactiveServices = document.getElementById('showInactiveServices');
    if (showInactiveServices) {
        showInactiveServices.addEventListener('change', function() {
            const serviceRows = document.querySelectorAll('.service-row');
            serviceRows.forEach(row => {
                if (!this.checked && row.dataset.active === '0') {
                    row.style.display = 'none';
                } else {
                    row.style.display = '';
                }
            });
        });
        showInactiveServices.dispatchEvent(new Event('change'));
    }

    // Show/Hide inactive addons
    const showInactiveAddons = document.getElementById('showInactiveAddons');
    if (showInactiveAddons) {
        showInactiveAddons.addEventListener('change', function() {
            const addons = document.querySelectorAll('.addon-item');
            addons.forEach(addon => {
                if (!this.checked && addon.dataset.active === '0') {
                    addon.style.display = 'none';
                } else {
                    addon.style.display = 'block';
                }
            });
        });
        showInactiveAddons.dispatchEvent(new Event('change'));
    }

    // Edit addon modal handler
    const editAddonModal = document.getElementById('editAddonModal');
    if (editAddonModal) {
        editAddonModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            const addonId = button.getAttribute('data-addon-id');
            const form = document.getElementById('editAddonForm');

            // Set the correct update URL for addons
            @if(route('admin.addons.update', ['addon' => ':id'], false))
                form.action = "{{ route('admin.addons.update', ['addon' => ':id']) }}".replace(':id', addonId);
            @else
                form.action = `/admin/addons/${addonId}`;
            @endif

            document.getElementById('editAddonName').value = button.getAttribute('data-addon-name');
            document.getElementById('editAddonSlug').value = button.getAttribute('data-addon-slug');
            document.getElementById('editAddonDescription').value = button.getAttribute('data-addon-description') || '';
            document.getElementById('editAddonPrice').value = button.getAttribute('data-addon-price');
            document.getElementById('editAddonActive').checked = button.getAttribute('data-addon-is-active') === '1';
        });
    }

    // Addon status toggle - FIXED VERSION
    document.querySelectorAll('.addon-status-toggle').forEach(toggle => {
        toggle.addEventListener('change', function() {
            const addonId = this.getAttribute('data-id');
            const isActive = this.checked; // Returns boolean true/false

            fetch(`/admin/addons/${addonId}/toggle-status`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ is_active: isActive }) // Send as boolean
            })
            .then(response => {
                if (!response.ok) {
                    return response.json().then(err => {
                        throw new Error(JSON.stringify(err));
                    });
                }
                return response.json();
            })
            .then(data => {
                if (!data.success) {
                    this.checked = !this.checked;
                    showAlert('danger', data.message || 'Error updating status');
                } else {
                    showAlert('success', 'Status updated successfully!');

                    // Update the card border color and badge
                    const card = this.closest('.card');
                    const badge = card.querySelector('.badge');
                    const addonItem = card.closest('.addon-item');

                    if (isActive) {
                        card.classList.remove('border-secondary', 'border-opacity-25');
                        card.classList.add('border-success', 'border-opacity-25');
                        if (badge) {
                            badge.remove();
                        }
                        addonItem.dataset.active = '1';
                    } else {
                        card.classList.remove('border-success', 'border-opacity-25');
                        card.classList.add('border-secondary', 'border-opacity-25');
                        if (!badge) {
                            const titleContainer = card.querySelector('.d-flex.align-items-center.mb-1');
                            const inactiveBadge = document.createElement('span');
                            inactiveBadge.className = 'badge bg-secondary ms-2';
                            inactiveBadge.textContent = 'Inactive';
                            titleContainer.appendChild(inactiveBadge);
                        }
                        addonItem.dataset.active = '0';
                    }

                    // Trigger change event for show/hide filter
                    if (showInactiveAddons) {
                        showInactiveAddons.dispatchEvent(new Event('change'));
                    }
                }
            })
            .catch(error => {
                console.error('Error:', error);
                this.checked = !this.checked;

                try {
                    const errData = JSON.parse(error.message);
                    if (errData.errors?.is_active) {
                        showAlert('danger', errData.errors.is_active[0]);
                    } else {
                        showAlert('danger', errData.message || 'Error updating status');
                    }
                } catch (e) {
                    showAlert('danger', 'Error updating status');
                }
            });
        });
    });

    // Delete service
    document.querySelectorAll('.delete-service').forEach(button => {
        button.addEventListener('click', function() {
            const serviceId = this.getAttribute('data-id');
            const serviceName = this.getAttribute('data-name');

            if (confirm(`Are you sure you want to delete "${serviceName}"? This action cannot be undone.`)) {
                fetch(`/admin/services/${serviceId}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'Accept': 'application/json'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showAlert('success', data.message || 'Service deleted successfully!');
                        setTimeout(() => location.reload(), 1500);
                    } else {
                        alert(data.message || 'Error deleting service');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error deleting service');
                });
            }
        });
    });

    // Delete addon
    document.querySelectorAll('.delete-addon').forEach(button => {
        button.addEventListener('click', function() {
            const addonId = this.getAttribute('data-id');
            const addonName = this.getAttribute('data-name');

            if (confirm(`Are you sure you want to delete "${addonName}"? This action cannot be undone.`)) {
                fetch(`/admin/addons/${addonId}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'Accept': 'application/json'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showAlert('success', data.message || 'Add-on deleted successfully!');
                        setTimeout(() => location.reload(), 1500);
                    } else {
                        alert(data.message || 'Error deleting add-on');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error deleting add-on');
                });
            }
        });
    });

    // Auto-generate slug for add-ons
    function slugify(text) {
        return text.toLowerCase()
            .replace(/[^a-z0-9\s-]/g, '')
            .replace(/\s+/g, '-')
            .replace(/-+/g, '-')
            .trim();
    }

    // Create addon form slug generation
    const createAddonModalEl = document.getElementById('createAddonModal');
    if (createAddonModalEl) {
        const createName = createAddonModalEl.querySelector('input[name="name"]');
        const createSlug = createAddonModalEl.querySelector('input[name="slug"]');

        if (createName && createSlug) {
            createName.addEventListener('input', function() {
                if (!createSlug.value || createSlug.dataset.manual !== 'true') {
                    createSlug.value = slugify(this.value);
                }
            });
            createSlug.addEventListener('input', function() {
                this.dataset.manual = 'true';
            });
        }
    }

    // Edit addon form slug generation
    const editNameInput = document.getElementById('editAddonName');
    const editSlugInput = document.getElementById('editAddonSlug');
    if (editNameInput && editSlugInput) {
        editNameInput.addEventListener('input', function() {
            if (!editSlugInput.value || editSlugInput.dataset.manual !== 'true') {
                editSlugInput.value = slugify(this.value);
            }
        });
        editSlugInput.addEventListener('input', function() {
            this.dataset.manual = 'true';
        });
    }

    // Handle create addon form submission via AJAX
    if (createAddonForm) {
        createAddonForm.addEventListener('submit', function(e) {
            e.preventDefault();

            const submitBtn = this.querySelector('button[type="submit"]');

            // Convert form data to JSON for proper boolean handling
            const jsonData = {
                name: this.querySelector('input[name="name"]').value,
                slug: this.querySelector('input[name="slug"]').value,
                description: this.querySelector('textarea[name="description"]').value || '',
                price: parseFloat(this.querySelector('input[name="price"]').value),
                is_active: this.querySelector('input[name="is_active"]').checked // Boolean
            };

            // Validate price
            if (isNaN(jsonData.price) || jsonData.price < 0) {
                showAlert('danger', 'Please enter a valid price');
                return false;
            }

            // Disable button and show loading
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Creating...';

            fetch(this.action, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify(jsonData)
            })
            .then(response => {
                if (!response.ok) {
                    return response.json().then(err => {
                        throw new Error(JSON.stringify(err));
                    });
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    // Show success message
                    showAlert('success', data.message || 'Add-on created successfully!');

                    // Close modal
                    const modal = bootstrap.Modal.getInstance(createAddonModalEl);
                    modal.hide();

                    // Reset form
                    this.reset();

                    // Reload after delay
                    setTimeout(() => location.reload(), 1500);
                } else {
                    showAlert('danger', data.message || 'Error creating add-on');
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = 'Create Add-On';
                }
            })
            .catch(error => {
                console.error('Error:', error);

                try {
                    const errData = JSON.parse(error.message);
                    if (errData.errors) {
                        // Show validation errors
                        let errorMessages = '';
                        Object.values(errData.errors).forEach(err => {
                            errorMessages += err.join('<br>') + '<br>';
                        });
                        showAlert('danger', errorMessages);
                    } else {
                        showAlert('danger', errData.message || 'Error creating add-on');
                    }
                } catch (e) {
                    showAlert('danger', 'Error creating add-on: ' + error.message);
                }

                submitBtn.disabled = false;
                submitBtn.innerHTML = 'Create Add-On';
            });
        });
    }

    // Handle edit addon form submission via AJAX
    const editAddonForm = document.getElementById('editAddonForm');
    if (editAddonForm) {
        editAddonForm.addEventListener('submit', function(e) {
            e.preventDefault();

            const submitBtn = this.querySelector('button[type="submit"]');

            // Convert form data to JSON for proper boolean handling
            const jsonData = {
                name: document.getElementById('editAddonName').value,
                slug: document.getElementById('editAddonSlug').value,
                description: document.getElementById('editAddonDescription').value || '',
                price: parseFloat(document.getElementById('editAddonPrice').value),
                is_active: document.getElementById('editAddonActive').checked,
                _method: 'PUT'
            };

            // Validate price
            if (isNaN(jsonData.price) || jsonData.price < 0) {
                showAlert('danger', 'Please enter a valid price');
                return false;
            }

            // Disable button and show loading
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Updating...';

            fetch(this.action, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify(jsonData)
            })
            .then(response => {
                if (!response.ok) {
                    return response.json().then(err => {
                        throw new Error(JSON.stringify(err));
                    });
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    // Show success message
                    showAlert('success', data.message || 'Add-on updated successfully!');

                    // Close modal
                    const modal = bootstrap.Modal.getInstance(editAddonModal);
                    modal.hide();

                    // Reload after delay
                    setTimeout(() => location.reload(), 1500);
                } else {
                    showAlert('danger', data.message || 'Error updating add-on');
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = 'Update Add-On';
                }
            })
            .catch(error => {
                console.error('Error:', error);

                try {
                    const errData = JSON.parse(error.message);
                    if (errData.errors) {
                        // Show validation errors
                        let errorMessages = '';
                        Object.values(errData.errors).forEach(err => {
                            errorMessages += err.join('<br>') + '<br>';
                        });
                        showAlert('danger', errorMessages);
                    } else {
                        showAlert('danger', errData.message || 'Error updating add-on');
                    }
                } catch (e) {
                    showAlert('danger', 'Error updating add-on');
                }

                submitBtn.disabled = false;
                submitBtn.innerHTML = 'Update Add-On';
            });
        });
    }
});

// Helper function to show alerts
function showAlert(type, message) {
    // Remove existing alerts
    document.querySelectorAll('.alert-position-fixed').forEach(alert => alert.remove());

    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} alert-dismissible fade show alert-position-fixed`;
    alertDiv.style.cssText = 'position: fixed; top: 20px; right: 20px; z-index: 9999; max-width: 400px;';
    alertDiv.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    document.body.appendChild(alertDiv);

    // Auto remove after 5 seconds
    setTimeout(() => {
        if (alertDiv.parentNode) {
            alertDiv.parentNode.removeChild(alertDiv);
        }
    }, 5000);
}
</script>

<style>
.hover-shadow-sm {
    transition: all 0.2s ease;
}
.hover-shadow-sm:hover {
    transform: translateY(-2px);
    box-shadow: 0 .5rem 1rem rgba(0,0,0,.15)!important;
}
.border-left-primary {
    border-left: 4px solid #3D3B6B!important;
}
.border-left-success {
    border-left: 4px solid #28a745!important;
}
.border-left-warning {
    border-left: 4px solid #fd7e14!important;
}
.border-left-info {
    border-left: 4px solid #17a2b8!important;
}
.card-header.bg-white {
    background-color: #fff !important;
}
.form-switch .form-check-input:checked {
    background-color: #28a745;
    border-color: #28a745;
}
</style>
@endpush
@endsection
