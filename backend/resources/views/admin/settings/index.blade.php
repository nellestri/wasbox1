@use('App\Models\SystemSetting')
@use('Illuminate\Support\Facades\File')
@use('Illuminate\Support\Facades\Storage')
@extends('admin.layouts.app')

@section('title', 'System Settings')

@section('content')
<div class="container-fluid px-4 py-4">
    {{-- Header Section --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold text-dark mb-1">Control Panel</h2>
            <p class="text-muted small">Configure global rules, FCM notifications, and system maintenance.</p>
        </div>
        <div id="save-indicator" class="text-muted small d-none">
            <i class="bi bi-clock-history me-1"></i> Last saved: Just now
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success border-0 shadow-sm rounded-4 mb-4 d-flex align-items-center">
            <i class="bi bi-check-circle-fill fs-4 me-3"></i>
            <div>{{ session('success') }}</div>
            <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="row g-4">
        {{-- Navigation Sidebar --}}
        <div class="col-xl-3 col-lg-4">
            <div class="card border-0 shadow-sm rounded-4 sticky-top" style="top: 20px;">
                <div class="card-body p-3">
                    <div class="nav flex-column nav-pills" id="settingsTabs" role="tablist">
                        <button class="nav-link active d-flex align-items-center py-3 mb-1 text-start" data-bs-toggle="pill" data-bs-target="#general" type="button">
                            <i class="bi bi-shop fs-5 me-3"></i>
                            <div>
                                <div class="fw-bold">General</div>
                                <div class="x-small opacity-75">Branding & Contact</div>
                            </div>
                        </button>
                        <button class="nav-link d-flex align-items-center py-3 mb-1 text-start" data-bs-toggle="pill" data-bs-target="#unclaimed" type="button">
                            <i class="bi bi-clock-history fs-5 me-3"></i>
                            <div>
                                <div class="fw-bold">Unclaimed Rules</div>
                                <div class="x-small opacity-75">Thresholds & Policy</div>
                            </div>
                        </button>
                        <button class="nav-link d-flex align-items-center py-3 mb-1 text-start" data-bs-toggle="pill" data-bs-target="#notifications" type="button">
                            <i class="bi bi-megaphone fs-5 me-3"></i>
                            <div>
                                <div class="fw-bold">Notifications</div>
                                <div class="x-small opacity-75">FCM & Push Alerts</div>
                            </div>
                        </button>
                        <button class="nav-link d-flex align-items-center py-3 mb-1 text-start" data-bs-toggle="pill" data-bs-target="#status" type="button">
                            <i class="bi bi-heart-pulse fs-5 me-3"></i>
                            <div>
                                <div class="fw-bold">System Health</div>
                                <div class="x-small opacity-75">Server & DB Status</div>
                            </div>
                        </button>
                        <button class="nav-link d-flex align-items-center py-3 text-start" data-bs-toggle="pill" data-bs-target="#backup" type="button">
                            <i class="bi bi-database-up fs-5 me-3"></i>
                            <div>
                                <div class="fw-bold">Backup & Data</div>
                                <div class="x-small opacity-75">Export & Security</div>
                            </div>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        {{-- Settings Content Area --}}
        <div class="col-xl-9 col-lg-8">
            {{-- FIXED: Removed @method('PUT') since route only accepts POST --}}
            <form action="{{ route('admin.settings.update') }}" method="POST" enctype="multipart/form-data">
                @csrf

                <div class="tab-content">
                    {{-- GENERAL --}}
                    <div class="tab-pane fade show active" id="general">
                        <div class="card border-0 shadow-sm rounded-4 mb-4">
                            <div class="card-header bg-white py-3 border-bottom">
                                <h5 class="fw-bold mb-0">Identity & Branding</h5>
                            </div>
                            <div class="card-body p-4">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold">Shop Display Name</label>
                                        <input type="text" name="shop_name" class="form-control rounded-3" value="{{ SystemSetting::get('shop_name', 'WashBox') }}">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold">Official Contact No.</label>
                                        <input type="text" name="contact_number" class="form-control rounded-3" value="{{ SystemSetting::get('contact_number') }}">
                                    </div>
                                    <div class="col-12 mt-4">
                                        <label class="form-label fw-bold">System Logo</label>
                                        <div class="d-flex align-items-center bg-light p-4 rounded-4 border border-dashed">
                                            @if(SystemSetting::get('app_logo'))
                                                <img src="{{ Storage::url(SystemSetting::get('app_logo')) }}" class="rounded me-4 shadow-sm" style="height: 60px; width: 60px; object-fit: contain; background: #fff;">
                                            @endif
                                            <div>
                                                <input type="file" name="app_logo" class="form-control form-control-sm mb-1" accept="image/*">
                                                <span class="x-small text-muted">Recommended: PNG 512x512px with transparent background.</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- UNCLAIMED RULES --}}
                    <div class="tab-pane fade" id="unclaimed">
                        <div class="card border-0 shadow-sm rounded-4 mb-4">
                            <div class="card-header bg-white py-3 border-bottom">
                                <h5 class="fw-bold mb-0 text-danger"><i class="bi bi-shield-exclamation me-2"></i>Inventory Retention</h5>
                            </div>
                            <div class="card-body p-4">
                                <div class="row align-items-center mb-4">
                                    <div class="col-md-8">
                                        <h6 class="fw-bold mb-1">Auto-Disposal Threshold</h6>
                                        <p class="text-muted small mb-0">How many days should an order remain unclaimed before marking for disposal?</p>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="input-group">
                                            <input type="number" name="disposal_threshold_days" class="form-control text-center fw-bold" value="{{ SystemSetting::get('disposal_threshold_days', 30) }}">
                                            <span class="input-group-text">Days</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="form-check form-switch p-3 bg-light rounded-3">
                                    <input class="form-check-input ms-0 me-3" type="checkbox" name="enable_unclaimed_notifications" style="width: 3em; height: 1.5em;" {{ SystemSetting::get('enable_unclaimed_notifications') ? 'checked' : '' }}>
                                    <label class="form-check-label fw-bold">Automated Retention Alerts</label>
                                    <div class="text-muted x-small ms-5">Send daily reminders to customers with overdue laundry.</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- NOTIFICATIONS --}}
                    <div class="tab-pane fade" id="notifications">
                        <div class="card border-0 shadow-sm rounded-4 mb-4">
                            <div class="card-header bg-white py-3 border-bottom">
                                <h5 class="fw-bold mb-0 text-primary">Cloud Messaging (FCM)</h5>
                            </div>
                            <div class="card-body p-4 text-center py-5">
                                <i class="bi bi-cloud-check text-primary display-4 mb-3"></i>
                                <div class="row g-3 text-start mt-2">
                                    <div class="col-12">
                                        <label class="form-label fw-bold small">Server Key</label>
                                        <input type="password" name="fcm_server_key" class="form-control font-monospace" value="{{ SystemSetting::get('fcm_server_key') }}">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- SYSTEM STATUS --}}
                    @php $health = app(\App\Http\Controllers\Admin\SettingsController::class)->systemStatus(); @endphp
                    <div class="tab-pane fade" id="status">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="card border-0 shadow-sm rounded-4 p-4 text-center">
                                    <div class="display-6 mb-2 {{ $health['database'] ? 'text-success' : 'text-danger' }}">
                                        <i class="bi bi-database-fill-check"></i>
                                    </div>
                                    <h6 class="fw-bold mb-1">Database</h6>
                                    <span class="badge {{ $health['database'] ? 'bg-success' : 'bg-danger' }} rounded-pill">
                                        {{ $health['database'] ? 'Healthy & Connected' : 'Connection Error' }}
                                    </span>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card border-0 shadow-sm rounded-4 p-4 text-center">
                                    <div class="display-6 mb-2 {{ $health['fcm'] ? 'text-success' : 'text-warning' }}">
                                        <i class="bi bi-broadcast-pin"></i>
                                    </div>
                                    <h6 class="fw-bold mb-1">FCM Status</h6>
                                    <span class="badge {{ $health['fcm'] ? 'bg-success' : 'bg-warning' }} rounded-pill px-3">
                                        {{ $health['fcm'] ? 'Push Engine Ready' : 'Key Missing' }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- BACKUP --}}
                    <div class="tab-pane fade" id="backup">
                        <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                            <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                                <h5 class="fw-bold mb-0">Available Snapshots</h5>
                                <button type="button" class="btn btn-primary btn-sm px-3" onclick="generateBackup()">
                                    <i class="bi bi-plus-lg me-1"></i> New Backup
                                </button>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <thead class="bg-light x-small text-uppercase">
                                        <tr><th class="ps-4">File ID</th><th>Size</th><th>Created</th><th class="text-end pe-4">Action</th></tr>
                                    </thead>
                                    <tbody>
                                        @php
                                            $backupPath = storage_path('app/backups');
                                            $backupFiles = File::exists($backupPath) ? collect(File::files($backupPath))->sortByDesc(fn($f) => $f->getMTime()) : collect();
                                        @endphp
                                        @forelse($backupFiles as $file)
                                        <tr>
                                            <td class="ps-4 fw-bold font-monospace x-small">{{ $file->getFilename() }}</td>
                                            <td><span class="badge bg-light text-dark">{{ number_format($file->getSize() / 1024, 1) }} KB</span></td>
                                            <td class="text-muted small">{{ date('M j, Y H:i', $file->getMTime()) }}</td>
                                            <td class="text-end pe-4">
                                                <a href="{{ route('admin.settings.download-backup', $file->getFilename()) }}" class="btn btn-light btn-sm"><i class="bi bi-download"></i></a>
                                            </td>
                                        </tr>
                                        @empty
                                        <tr><td colspan="4" class="text-center py-5 text-muted small">No backup history available.</td></tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="sticky-bottom bg-white py-3 mt-4 border-top">
                    <div class="d-flex justify-content-end gap-2">
                        <button type="reset" class="btn btn-light px-4 rounded-3">Discard Changes</button>
                        <button type="submit" class="btn btn-primary px-5 rounded-3 shadow" style="background: #3D3B6B;">
                            Save Settings
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
    .x-small { font-size: 0.75rem; }
    .nav-pills .nav-link { border-radius: 12px; color: #6c757d; transition: all 0.2s; }
    .nav-pills .nav-link.active { background-color: #f0f1ff; color: #3D3B6B; }
    .nav-pills .nav-link:hover:not(.active) { background-color: #f8f9fa; color: #333; }
    .tab-pane { animation: fadeIn 0.3s ease; }
    @keyframes fadeIn { from { opacity: 0; transform: translateY(5px); } to { opacity: 1; transform: translateY(0); } }
</style>

<script>
function generateBackup() {
    fetch('{{ route("admin.settings.backup") }}', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Content-Type': 'application/json',
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(data.message);
            location.reload();
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        alert('Error creating backup');
        console.error(error);
    });
}
</script>
@endsection
