@extends('admin.layouts.app')

@section('title', 'Notifications')
@section('page-title', 'Notifications')

@section('content')
<div class="container-fluid">
    {{-- Header --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold mb-1">Notifications</h4>
            <p class="text-muted mb-0">Stay updated with the latest activities</p>
        </div>
        <div class="d-flex gap-2">
            <form action="{{ route('admin.notifications.mark-all-read') }}" method="POST" class="d-inline">
                @csrf
                <button type="submit" class="btn btn-outline-primary">
                    <i class="bi bi-check-all me-1"></i> Mark All as Read
                </button>
            </form>
            <form action="{{ route('admin.notifications.delete-all-read') }}" method="POST" class="d-inline">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-outline-danger" onclick="return confirm('Delete all read notifications?')">
                    <i class="bi bi-trash me-1"></i> Clear Read
                </button>
            </form>
        </div>
    </div>

    {{-- Stats --}}
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm rounded-4 border-start border-4 border-primary">
                <div class="card-body py-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <p class="text-muted mb-0">Total</p>
                            <h3 class="fw-bold mb-0">{{ $stats['total'] }}</h3>
                        </div>
                        <div class="rounded-circle bg-primary bg-opacity-10 p-3">
                            <i class="bi bi-bell fs-4 text-primary"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm rounded-4 border-start border-4 border-warning">
                <div class="card-body py-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <p class="text-muted mb-0">Unread</p>
                            <h3 class="fw-bold mb-0">{{ $stats['unread'] }}</h3>
                        </div>
                        <div class="rounded-circle bg-warning bg-opacity-10 p-3">
                            <i class="bi bi-envelope fs-4 text-warning"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm rounded-4 border-start border-4 border-success">
                <div class="card-body py-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <p class="text-muted mb-0">Today</p>
                            <h3 class="fw-bold mb-0">{{ $stats['today'] }}</h3>
                        </div>
                        <div class="rounded-circle bg-success bg-opacity-10 p-3">
                            <i class="bi bi-calendar-check fs-4 text-success"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Filters --}}
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-body p-3">
            <form method="GET" class="row g-2 align-items-center">
                <div class="col-md-3">
                    <select name="type" class="form-select" onchange="this.form.submit()">
                        <option value="">All Types</option>
                        @foreach($types as $key => $label)
                            <option value="{{ $key }}" {{ request('type') == $key ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <select name="status" class="form-select" onchange="this.form.submit()">
                        <option value="">All Status</option>
                        <option value="unread" {{ request('status') == 'unread' ? 'selected' : '' }}>Unread</option>
                        <option value="read" {{ request('status') == 'read' ? 'selected' : '' }}>Read</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <a href="{{ route('admin.notifications.index') }}" class="btn btn-outline-secondary w-100">
                        <i class="bi bi-x-lg"></i> Clear
                    </a>
                </div>
            </form>
        </div>
    </div>

    {{-- Notifications List --}}
    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-body p-0">
            @forelse($notifications as $notification)
                <div class="notification-item d-flex align-items-start p-3 border-bottom {{ !$notification->is_read ? 'bg-light' : '' }}">
                    {{-- Icon --}}
                    <div class="rounded-circle d-flex align-items-center justify-content-center me-3 flex-shrink-0"
                         style="width: 48px; height: 48px; background: var(--bs-{{ $notification->color }}-bg-subtle, #e9ecef);">
                        <i class="bi {{ $notification->icon_class }} fs-5 text-{{ $notification->color }}"></i>
                    </div>

                    {{-- Content --}}
                    <div class="flex-grow-1">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <h6 class="mb-1 fw-semibold">
                                    {{ $notification->title }}
                                    @if(!$notification->is_read)
                                        <span class="badge bg-primary ms-2">New</span>
                                    @endif
                                </h6>
                                <p class="mb-1 text-muted">{{ $notification->message }}</p>
                                <small class="text-muted">
                                    <i class="bi bi-clock me-1"></i>{{ $notification->time_ago }}
                                    @if($notification->branch)
                                        • <i class="bi bi-building me-1"></i>{{ $notification->branch->name }}
                                    @endif
                                </small>
                            </div>
                            <div class="d-flex gap-2">
                                @if($notification->link)
                                    <a href="{{ $notification->link }}" class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                @endif
                                @if(!$notification->is_read)
                                    <form action="{{ route('admin.notifications.mark-read', $notification) }}" method="POST" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-outline-success" title="Mark as read">
                                            <i class="bi bi-check"></i>
                                        </button>
                                    </form>
                                @endif
                                <form action="{{ route('admin.notifications.destroy', $notification) }}" method="POST" class="d-inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            @empty
                <div class="text-center py-5 text-muted">
                    <i class="bi bi-bell-slash fs-1 d-block mb-2"></i>
                    <p>No notifications found</p>
                </div>
            @endforelse
        </div>

        @if($notifications->hasPages())
            <div class="card-footer bg-white">
                {{ $notifications->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
