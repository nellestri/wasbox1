@props(['role' => 'staff'])

<aside class="sidebar shadow" id="sidebar">
    {{-- Desktop Toggle Button --}}
    <div class="sidebar-toggle-btn" id="desktopToggleBtn">
        <i class="bi bi-chevron-left"></i>
    </div>

    <div class="sidebar-brand">
        <div class="d-flex flex-column align-items-center text-center py-2 position-relative">
            {{-- Logo Container --}}
            <div class="logo-container mb-2 shadow-sm"
                 style="width: 85px; height: 85px; border-radius: 50%; overflow: hidden; border: 2px solid rgba(255,255,255,0.3); background: white;">
                <img src="{{ asset('images/logo.png') }}"
                     alt="WashBox Logo"
                     style="width: 100%; height: 100%; object-fit: contain; padding: 8px;">
            </div>

            {{-- Brand Text --}}
            <div class="brand-text">
                <h5 class="text-white mb-1 fw-bold" style="font-size: 1.1rem;">WASHBOX</h5>

                {{-- Branch Info --}}
                @auth
                    @if(auth()->user()->branch)
                        <div class="branch-info mt-1">
                            <small class="text-white-50 d-block fw-bold" style="font-size: 0.6rem; letter-spacing: 1px;">
                                {{ strtoupper(auth()->user()->branch->name) }} BRANCH
                            </small>
                        </div>
                    @endif
                @endauth
            </div>

            {{-- Mobile Close Button --}}
            <button class="btn btn-link text-white d-md-none p-0 position-absolute"
                    id="sidebarClose"
                    style="top: 10px; right: 15px;">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>
    </div>

    <ul class="sidebar-menu">
        {{-- Dashboard --}}
        <li class="nav-item">
            <a href="{{ route('staff.dashboard') }}"
               class="nav-link {{ request()->routeIs('staff.dashboard') ? 'active' : '' }}">
                <i class="bi bi-speedometer2"></i>
                <span class="menu-text">Dashboard</span>
            </a>
        </li>

        {{-- Operations Section --}}
        <li><span class="nav-label">Operations</span></li>

        {{-- New Order --}}
        <li class="nav-item">
            <a href="{{ route('staff.orders.create') }}"
               class="nav-link {{ request()->routeIs('staff.orders.create') ? 'active' : '' }}">
                <i class="bi bi-plus-circle text-info"></i>
                <span class="menu-text">New Order</span>
            </a>
        </li>

        {{-- Laundries --}}
        <li class="nav-item">
            <a href="{{ route('staff.orders.index') }}"
               class="nav-link {{ request()->routeIs('staff.orders.*') && !request()->routeIs('staff.orders.create') ? 'active' : '' }}">
                <i class="bi bi-basket"></i>
                <span class="menu-text">Laundries</span>
            </a>
        </li>

        {{-- Pickups --}}
        <li class="nav-item">
            <a href="{{ route('staff.pickups.index') }}"
               class="nav-link {{ request()->routeIs('staff.pickups.*') ? 'active' : '' }}">
                <i class="bi bi-truck"></i>
                <span class="menu-text">Pickups</span>
            </a>
        </li>

        {{-- Customers --}}
        <li class="nav-item">
            <a href="{{ route('staff.customers.index') }}"
               class="nav-link {{ request()->routeIs('staff.customers.*') ? 'active' : '' }}">
                <i class="bi bi-people"></i>
                <span class="menu-text">Customers</span>
            </a>
        </li>

        {{-- Unclaimed --}}
        <li class="nav-item">
            <a href="{{ route('staff.unclaimed.index') }}"
               class="nav-link {{ request()->routeIs('staff.unclaimed.*') ? 'active' : '' }}">
                <i class="bi bi-exclamation-octagon text-warning"></i>
                <span class="menu-text">Unclaimed</span>
            </a>
        </li>

        <li><hr class="border-white opacity-10 mx-3"></li>

        {{-- Settings --}}
        <li class="nav-item">
            <a href="#"
               class="nav-link">
                <i class="bi bi-gear"></i>
                <span class="menu-text">Settings</span>
            </a>
        </li>
    </ul>
</aside>
