<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="WashBox Staff Dashboard">
    <title>@yield('title', 'Dashboard') - WashBox Staff</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">

    <!-- Leaflet.js for OpenStreetMap -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.js"></script>
    <link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.css" />
    <link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.Default.css" />
    <script src="https://unpkg.com/leaflet.markercluster@1.5.3/dist/leaflet.markercluster.js"></script>

    <!-- Custom CSS -->
    <style>
        /* CSS Variables - Premium Design */
        :root {
            --sidebar-width: 260px;
            --sidebar-collapsed-width: 80px;
            --primary-color: #2D2B5F;
            --primary-light: #3D3B7F;
            --secondary-color: #FF5C35;
            --accent-color: #FF5C35;
            --success-color: #10B981;
            --warning-color: #F59E0B;
            --danger-color: #EF4444;
            --info-color: #3B82F6;
            --sidebar-bg: #2d2b5f;
            --sidebar-text: rgba(255, 255, 255, 0.9);
            --sidebar-hover: rgba(255, 255, 255, 0.12);
            --transition-speed: 0.3s;
            --ease-out: cubic-bezier(0.22, 1, 0.36, 1);

            /* Light theme (default) */
            --bg-color: #F9FAFB;
            --card-bg: #FFFFFF;
            --text-primary: #111827;
            --text-secondary: #6B7280;
            --border-color: #E5E7EB;
            --shadow-sm: 0 1px 3px rgba(0, 0, 0, 0.1);
            --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            --shadow-lg: 0 10px 25px -5px rgba(0, 0, 0, 0.1);
            --shadow-xl: 0 20px 40px -10px rgba(0, 0, 0, 0.1);
        }

        [data-theme="dark"] {
            --bg-color: #111827;
            --card-bg: #1F2937;
            --text-primary: #F9FAFB;
            --text-secondary: #D1D5DB;
            --border-color: #374151;
            --shadow-sm: 0 1px 3px rgba(0, 0, 0, 0.3);
            --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.3);
            --shadow-lg: 0 10px 25px -5px rgba(0, 0, 0, 0.4);
            --shadow-xl: 0 20px 40px -10px rgba(0, 0, 0, 0.5);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background-color: var(--bg-color);
            color: var(--text-primary);
            overflow-x: hidden;
            transition: background-color 0.3s ease, color 0.3s ease;
            line-height: 1.6;
        }

        /* ============================================ */
        /* SIDEBAR STYLES */
        /* ============================================ */
        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            height: 100vh;
            width: var(--sidebar-width);
            background: linear-gradient(180deg, #2D2B5F 0%, #1c1a4e 100%);
            color: var(--sidebar-text);
            overflow-y: auto;
            overflow-x: hidden;
            z-index: 1050;
            transition: all var(--transition-speed) var(--ease-out);
            box-shadow: 0 10px 25px -5px rgba(45, 43, 95, 0.2);
            display: flex;
            flex-direction: column;
            backdrop-filter: blur(10px);
            border-right: 1px solid rgba(255, 255, 255, 0.1);
            transform: translateX(0);
        }

        .sidebar::-webkit-scrollbar {
            width: 6px;
        }

        .sidebar::-webkit-scrollbar-track {
            background: rgba(255, 255, 255, 0.05);
        }

        .sidebar::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.2);
            border-radius: 3px;
        }

        .sidebar.collapsed {
            width: var(--sidebar-collapsed-width);
        }

        .sidebar.hide-mobile {
            transform: translateX(-100%);
        }

        .sidebar.show-mobile {
            transform: translateX(0);
            box-shadow: var(--shadow-xl);
        }

        .sidebar-brand {
            padding: 1.5rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            transition: padding var(--transition-speed) ease;
            position: relative;
        }

        .brand-container {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .logo-container {
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, #FF5C35, #FF7A52);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            transition: all var(--transition-speed) ease;
            box-shadow: 0 4px 12px rgba(255, 92, 53, 0.3);
        }

        .logo-container i {
            font-size: 1.5rem;
            color: white;
        }

        .brand-text {
            flex: 1;
            overflow: hidden;
        }

        .brand-text h3 {
            font-weight: 700;
            font-size: 1.25rem;
            margin: 0;
            color: white;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .brand-text small {
            font-size: 0.75rem;
            opacity: 0.8;
            color: rgba(255, 255, 255, 0.7);
            display: block;
            margin-top: 0.25rem;
        }

        .sidebar.collapsed .sidebar-brand {
            padding: 1.5rem 0.75rem;
        }

        .sidebar.collapsed .brand-text {
            display: none;
        }

        .sidebar.collapsed .logo-container {
            width: 44px;
            height: 44px;
            margin: 0 auto;
        }

        .sidebar-toggle-btn {
            position: absolute;
            top: 50%;
            right: -12px;
            transform: translateY(-50%);
            width: 24px;
            height: 24px;
            background: white;
            border: 2px solid var(--sidebar-bg);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            z-index: 1051;
            box-shadow: var(--shadow-md);
            transition: all var(--transition-speed) ease;
            color: var(--primary-color);
        }

        .sidebar-toggle-btn:hover {
            transform: translateY(-50%) scale(1.1);
            box-shadow: var(--shadow-lg);
        }

        .sidebar-toggle-btn i {
            font-size: 0.75rem;
            transition: transform var(--transition-speed) ease;
        }

        .sidebar.collapsed .sidebar-toggle-btn i {
            transform: rotate(180deg);
        }

        .sidebar-menu {
            list-style: none;
            padding: 1.5rem 0;
            margin: 0;
            flex: 1;
        }

        .sidebar-menu .nav-label {
            padding: 0.75rem 1.5rem 0.5rem;
            color: rgba(255, 255, 255, 0.5);
            font-size: 0.7rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            font-weight: 600;
            margin-top: 0.5rem;
            display: block;
            transition: padding var(--transition-speed) ease;
        }

        .sidebar.collapsed .nav-label {
            text-align: center;
            padding: 0.75rem 0.5rem 0.5rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            margin: 1rem 0.5rem 0.5rem;
        }

        .sidebar-menu li {
            position: relative;
        }

        .sidebar-menu li a {
            display: flex;
            align-items: center;
            padding: 0.875rem 1.5rem;
            color: rgba(255, 255, 255, 0.8);
            text-decoration: none;
            transition: all 0.25s ease;
            white-space: nowrap;
            position: relative;
            border-left: 3px solid transparent;
        }

        .sidebar-menu li a i {
            margin-right: 1rem;
            font-size: 1.2rem;
            min-width: 24px;
            text-align: center;
            transition: margin-right var(--transition-speed) ease;
        }

        .sidebar-menu li a:hover {
            background: var(--sidebar-hover);
            color: white;
            border-left-color: rgba(255, 255, 255, 0.3);
        }

        .sidebar-menu li a.active {
            background: rgba(255, 92, 53, 0.15);
            color: white;
            border-left-color: #FF5C35;
            font-weight: 500;
        }

        .sidebar-menu li a.active::before {
            content: '';
            position: absolute;
            right: 0;
            top: 50%;
            transform: translateY(-50%);
            width: 3px;
            height: 20px;
            background: #FF5C35;
            border-radius: 2px 0 0 2px;
        }

        .sidebar.collapsed .sidebar-menu li a {
            justify-content: center;
            padding: 0.875rem 0.75rem;
            border-left: none;
        }

        .sidebar.collapsed .sidebar-menu li a i {
            margin-right: 0;
            font-size: 1.3rem;
        }

        .sidebar.collapsed .sidebar-menu li a.active::before {
            right: -3px;
        }

        .sidebar-menu .menu-text {
            transition: opacity var(--transition-speed) ease;
        }

        .sidebar.collapsed .menu-text {
            opacity: 0;
            width: 0;
            overflow: hidden;
        }

        /* Mobile overlay */
        .sidebar-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(4px);
            z-index: 1049;
            opacity: 0;
            visibility: hidden;
            transition: opacity 0.3s ease, visibility 0.3s ease;
        }

        .sidebar-overlay.show {
            opacity: 1;
            visibility: visible;
        }

        /* ============================================ */
        /* MAIN CONTENT */
        /* ============================================ */
        .main-content {
            margin-left: var(--sidebar-width);
            min-height: 100vh;
            transition: all var(--transition-speed) var(--ease-out);
            background: var(--bg-color);
        }

        .main-content.expanded {
            margin-left: var(--sidebar-collapsed-width);
        }

        .main-content.full-width {
            margin-left: 0;
        }

        /* ============================================ */
        /* TOPBAR */
        /* ============================================ */
        .topbar {
            background: var(--card-bg);
            padding: 1rem 2rem;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 1040;
            backdrop-filter: blur(10px);
            transition: all 0.3s ease;
        }

        .topbar-left {
            display: flex;
            align-items: center;
            gap: 1.25rem;
        }

        .breadcrumb-nav {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.875rem;
        }

        .breadcrumb-item {
            color: var(--text-secondary);
            text-decoration: none;
            transition: color 0.2s ease;
        }

        .breadcrumb-item:hover {
            color: var(--primary-color);
        }

        .breadcrumb-divider {
            color: var(--text-secondary);
            opacity: 0.5;
        }

        .topbar-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--text-primary);
            margin: 0;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .topbar-title i {
            color: var(--primary-color);
            font-size: 1.25rem;
        }

        .mobile-menu-btn {
            display: none;
            background: none;
            border: none;
            font-size: 1.5rem;
            color: var(--text-primary);
            cursor: pointer;
            padding: 0.5rem;
            border-radius: 0.5rem;
            transition: all 0.2s ease;
            width: 44px;
            height: 44px;
            align-items: center;
            justify-content: center;
        }

        .mobile-menu-btn:hover {
            background: var(--border-color);
            transform: translateY(-1px);
        }

        .topbar-right {
            display: flex;
            align-items: center;
            gap: 1.25rem;
        }

        /* ============================================ */
        /* THEME TOGGLE */
        /* ============================================ */
        .theme-toggle {
            position: relative;
            width: 44px;
            height: 44px;
            border-radius: 0.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            background: transparent;
            border: 1px solid var(--border-color);
            color: var(--text-primary);
            cursor: pointer;
            transition: all 0.3s ease;
            overflow: hidden;
        }

        .theme-toggle:hover {
            background: var(--border-color);
            transform: translateY(-1px);
            box-shadow: var(--shadow-sm);
        }

        .theme-toggle i {
            font-size: 1.25rem;
            transition: transform 0.3s ease;
        }

        .theme-toggle .bi-sun {
            position: absolute;
            opacity: 0;
            transform: rotate(90deg);
        }

        [data-theme="dark"] .theme-toggle .bi-moon {
            opacity: 0;
            transform: rotate(-90deg);
        }

        [data-theme="dark"] .theme-toggle .bi-sun {
            opacity: 1;
            transform: rotate(0);
        }

        /* ============================================ */
        /* NOTIFICATION BELL - ENHANCED */
        /* ============================================ */
        .notification-wrapper {
            position: relative;
        }

        .notification-bell {
            position: relative;
            width: 44px;
            height: 44px;
            border-radius: 0.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            background: transparent;
            border: 1px solid var(--border-color);
            color: var(--text-primary);
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .notification-bell:hover {
            background: var(--border-color);
            transform: translateY(-1px);
            box-shadow: var(--shadow-sm);
        }

        .notification-bell i {
            font-size: 1.25rem;
        }

        .notification-badge {
            position: absolute;
            top: -4px;
            right: -4px;
            min-width: 20px;
            height: 20px;
            font-size: 0.7rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 0 5px;
            border: 2px solid var(--card-bg);
            background-color: var(--danger-color);
            color: white;
            border-radius: 50%;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.1); }
        }

        /* Notification Dropdown */
        .notification-dropdown {
            width: 380px;
            max-height: 500px;
            border: none !important;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15) !important;
            background: var(--card-bg);
            transform-origin: top right;
            animation: dropdownSlide 0.2s ease;
            border-radius: 1rem !important;
            overflow: hidden;
        }

        @keyframes dropdownSlide {
            from {
                opacity: 0;
                transform: translateY(-10px) scale(0.95);
            }
            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        .notification-dropdown-header {
            padding: 1rem 1.25rem;
            background: linear-gradient(135deg, #2D2B5F 0%, #FF5C35 100%);
            color: white;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .notification-dropdown-header h6 {
            margin: 0;
            font-weight: 700;
            font-size: 1rem;
        }

        .notification-list {
            max-height: 350px;
            overflow-y: auto;
        }

        .notification-list::-webkit-scrollbar {
            width: 6px;
        }

        .notification-list::-webkit-scrollbar-track {
            background: transparent;
        }

        .notification-list::-webkit-scrollbar-thumb {
            background: var(--border-color);
            border-radius: 3px;
        }

        .notification-item {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            padding: 1rem 1.25rem;
            border-bottom: 1px solid var(--border-color);
            transition: all 0.2s ease;
            cursor: pointer;
            text-decoration: none;
            color: inherit;
        }

        .notification-item:hover {
            background: var(--bg-color);
        }

        .notification-item.unread {
            background: rgba(108, 99, 255, 0.05);
            border-left: 3px solid var(--accent-color);
        }

        .notification-icon {
            width: 42px;
            height: 42px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .notification-icon i {
            font-size: 1.1rem;
        }

        .notification-content {
            flex: 1;
            min-width: 0;
        }

        .notification-title {
            font-weight: 600;
            font-size: 0.875rem;
            color: var(--text-primary);
            margin-bottom: 2px;
            display: -webkit-box;
            -webkit-line-clamp: 1;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .notification-message {
            font-size: 0.8rem;
            color: var(--text-secondary);
            margin-bottom: 4px;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .notification-time {
            font-size: 0.7rem;
            color: var(--text-secondary);
            opacity: 0.8;
        }

        .notification-dropdown-footer {
            padding: 0.875rem 1.25rem;
            text-align: center;
            border-top: 1px solid var(--border-color);
            background: var(--bg-color);
        }

        .notification-dropdown-footer a {
            color: var(--primary-color);
            font-weight: 600;
            font-size: 0.875rem;
            text-decoration: none;
            transition: color 0.2s ease;
        }

        .notification-dropdown-footer a:hover {
            color: var(--accent-color);
        }

        .notification-empty {
            padding: 3rem 1.5rem;
            text-align: center;
        }

        .notification-empty i {
            font-size: 3rem;
            color: var(--text-secondary);
            opacity: 0.5;
            margin-bottom: 1rem;
        }

        .notification-empty p {
            color: var(--text-secondary);
            margin: 0;
            font-size: 0.875rem;
        }

        /* ============================================ */
        /* USER MENU */
        /* ============================================ */
        .user-menu-wrapper {
            position: relative;
        }

        .user-menu {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            cursor: pointer;
            padding: 0.375rem;
            border-radius: 0.75rem;
            transition: all 0.3s ease;
            border: 1px solid var(--border-color);
            background: var(--card-bg);
        }

        .user-menu:hover {
            background: var(--border-color);
            transform: translateY(-1px);
            box-shadow: var(--shadow-sm);
        }

        .user-avatar {
            width: 44px;
            height: 44px;
            border-radius: 12px;
            background: linear-gradient(135deg, var(--primary-color), var(--accent-color));
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 1.1rem;
            flex-shrink: 0;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .user-info {
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            min-width: 0;
        }

        .user-name {
            font-weight: 600;
            font-size: 0.875rem;
            color: var(--text-primary);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 150px;
        }

        .user-role {
            font-size: 0.75rem;
            color: var(--text-secondary);
        }

        .dropdown-menu {
            border: 1px solid var(--border-color);
            box-shadow: var(--shadow-xl);
            background: var(--card-bg);
            transform-origin: top right;
            animation: dropdownSlide 0.2s ease;
            padding: 0.5rem;
            min-width: 220px;
        }

        .dropdown-item {
            padding: 0.75rem 1rem;
            border-radius: 0.5rem;
            color: var(--text-primary);
            display: flex;
            align-items: center;
            gap: 0.75rem;
            transition: all 0.2s ease;
            font-size: 0.875rem;
        }

        .dropdown-item:hover {
            background: var(--bg-color);
            transform: translateX(4px);
        }

        .dropdown-item i {
            width: 20px;
            text-align: center;
            color: var(--text-secondary);
        }

        .dropdown-divider {
            margin: 0.5rem;
            border-color: var(--border-color);
        }

        /* ============================================ */
        /* CONTENT AREA */
        /* ============================================ */
        .content-area {
            padding: 2rem;
            animation: fadeIn 0.5s ease;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* ============================================ */
        /* RESPONSIVE DESIGN */
        /* ============================================ */
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
                width: 300px;
                z-index: 1050;
                transition: transform 0.3s var(--ease-out);
            }

            .sidebar.show-mobile {
                transform: translateX(0);
                box-shadow: var(--shadow-xl);
            }

            .main-content {
                margin-left: 0 !important;
                width: 100%;
            }

            .mobile-menu-btn {
                display: flex;
            }

            .sidebar-toggle-btn {
                display: none;
            }

            .topbar {
                padding: 1rem;
            }

            .content-area {
                padding: 1.25rem;
            }

            .notification-dropdown {
                position: fixed !important;
                top: 80px !important;
                left: 50% !important;
                transform: translateX(-50%) !important;
                right: auto !important;
                width: calc(100vw - 2rem);
                max-width: 400px;
            }

            .user-info {
                display: none;
            }

            .breadcrumb-nav {
                display: none;
            }

            .topbar-title {
                font-size: 1.25rem;
            }
        }

        @media (min-width: 769px) and (max-width: 1024px) {
            .sidebar {
                width: 220px;
            }

            .sidebar.collapsed {
                width: var(--sidebar-collapsed-width);
            }

            .main-content {
                margin-left: 220px;
            }

            .main-content.expanded {
                margin-left: var(--sidebar-collapsed-width);
            }
        }
    </style>

    @stack('styles')
</head>

<body data-theme="light">
    <!-- Sidebar -->
    @include('staff.components.staff-sidebar')

    <!-- Sidebar Overlay -->
    <div class="sidebar-overlay" id="sidebarOverlay" aria-hidden="true"></div>

    <!-- Main Content -->
    <div class="main-content" id="mainContent">
        <!-- Topbar with Notification Bell and User Menu -->
        <div class="topbar">
            <div class="topbar-left">
                <!-- Mobile Menu Toggle -->
                <button class="mobile-menu-btn" id="mobileMenuToggle" aria-label="Toggle sidebar">
                    <i class="bi bi-list"></i>
                </button>

                <!-- Page Title with Icon -->
                <h1 class="topbar-title">
                    <i class="bi @yield('page-icon', 'bi-speedometer2')"></i>
                    @yield('page-title', 'Dashboard')
                </h1>

                <!-- Breadcrumb Navigation -->
                <nav class="breadcrumb-nav" aria-label="breadcrumb">
                    <a href="{{ route('staff.dashboard') }}" class="breadcrumb-item">Dashboard</a>
                    @hasSection('breadcrumbs')
                        <span class="breadcrumb-divider">/</span>
                        @yield('breadcrumbs')
                    @endif
                </nav>
            </div>

            <div class="topbar-right">
                <!-- Theme Toggle -->
                <button class="theme-toggle" id="themeToggle" aria-label="Toggle theme">
                    <i class="bi bi-moon"></i>
                    <i class="bi bi-sun"></i>
                </button>

                <!-- ============================================ -->
                <!-- NOTIFICATION BELL WITH DROPDOWN -->
                <!-- ============================================ -->
                <div class="notification-wrapper">
                    <button class="notification-bell" type="button" id="notificationDropdown"
                        data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false"
                        aria-label="Notifications">
                        <i class="bi bi-bell"></i>
                        <span class="notification-badge" id="notificationBadge" style="display: none;">0</span>
                    </button>

                    <!-- Notification Dropdown -->
                    <div class="dropdown-menu dropdown-menu-end notification-dropdown p-0"
                        aria-labelledby="notificationDropdown">

                        <!-- Header -->
                        <div class="notification-dropdown-header">
                            <h6><i class="bi bi-bell-fill me-2"></i>Notifications</h6>
                            <button class="btn btn-sm btn-light rounded-pill px-3" id="markAllReadBtn"
                                    style="display: none; font-size: 0.75rem;">
                                Mark all read
                            </button>
                        </div>

                        <!-- Notification List -->
                        <div class="notification-list" id="notificationList">
                            <!-- Loading State -->
                            <div class="notification-empty" id="notificationLoading">
                                <div class="spinner-border spinner-border-sm text-primary" role="status">
                                    <span class="visually-hidden">Loading...</span>
                                </div>
                                <p class="mt-2">Loading notifications...</p>
                            </div>

                            <!-- Empty State (hidden by default) -->
                            <div class="notification-empty" id="notificationEmpty" style="display: none;">
                                <i class="bi bi-bell-slash"></i>
                                <p>No notifications yet</p>
                            </div>

                            <!-- Notifications will be rendered here -->
                            <div id="notificationContent"></div>
                        </div>

                        <!-- Footer -->
                        <div class="notification-dropdown-footer">
                            <a href="{{ route('staff.notifications.index') }}">
                                View All Notifications <i class="bi bi-arrow-right ms-1"></i>
                            </a>
                        </div>
                    </div>
                </div>

                <!-- User Menu with Logout -->
                <div class="user-menu-wrapper">
                    <div class="user-menu dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                        <div class="user-avatar" id="userAvatar">
                            {{ strtoupper(substr(auth()->user()->name ?? 'S', 0, 1)) }}
                        </div>
                        <div class="user-info">
                            <span class="user-name">{{ auth()->user()->name ?? 'Staff' }}</span>
                            <span class="user-role">Staff</span>
                        </div>
                        <i class="bi bi-chevron-down"></i>
                    </div>
                    <ul class="dropdown-menu dropdown-menu-end shadow border-0 rounded-3">
                        <li>
                            <a class="dropdown-item py-2" href="{{ route('staff.profile') }}">
                                <i class="bi bi-person"></i>Profile
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item py-2" href="{{ route('staff.notifications.index') }}">
                                <i class="bi bi-bell"></i>Notifications
                                <span class="badge bg-danger rounded-pill ms-auto notification-menu-badge" style="display: none;">0</span>
                            </a>
                        </li>
                        <li>
                            <hr class="dropdown-divider">
                        </li>
                        <li>
                            <form id="logoutForm" method="POST" action="{{ route('staff.logout') }}" class="mb-0">
                                @csrf
                                <button type="submit" class="dropdown-item py-2 text-danger w-100 text-start">
                                    <i class="bi bi-box-arrow-right"></i>Logout
                                </button>
                            </form>
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Content Area -->
        <div class="content-area">
            @yield('content')
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <!-- ============================================ -->
    <!-- NOTIFICATION SYSTEM SCRIPT -->
    <!-- ============================================ -->
    <script>
        class StaffNotificationSystem {
            constructor() {
                this.badge = document.getElementById('notificationBadge');
                this.menuBadge = document.querySelector('.notification-menu-badge');
                this.list = document.getElementById('notificationList');
                this.content = document.getElementById('notificationContent');
                this.loading = document.getElementById('notificationLoading');
                this.empty = document.getElementById('notificationEmpty');
                this.markAllBtn = document.getElementById('markAllReadBtn');
                this.dropdown = document.getElementById('notificationDropdown');
                this.pollInterval = 30000; // 30 seconds
                this.csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;

                this.init();
            }

            init() {
                // Initial load
                this.fetchUnreadCount();

                // Fetch notifications when dropdown opens
                this.dropdown?.addEventListener('show.bs.dropdown', () => {
                    this.fetchNotifications();
                });

                // Mark all read button
                this.markAllBtn?.addEventListener('click', (e) => {
                    e.preventDefault();
                    e.stopPropagation();
                    this.markAllAsRead();
                });

                // Poll for new notifications
                setInterval(() => this.fetchUnreadCount(), this.pollInterval);
            }

            async fetchUnreadCount() {
                try {
                    const response = await fetch('{{ route("staff.notifications.unread-count") }}', {
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        }
                    });

                    if (response.ok) {
                        const data = await response.json();
                        this.updateBadge(data.count);
                    }
                } catch (error) {
                    console.error('Error fetching notification count:', error);
                }
            }

            async fetchNotifications() {
                this.showLoading();

                try {
                    const response = await fetch('{{ route("staff.notifications.recent") }}?limit=10', {
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        }
                    });

                    if (response.ok) {
                        const data = await response.json();
                        this.renderNotifications(data.notifications);
                    }
                } catch (error) {
                    console.error('Error fetching notifications:', error);
                    this.showEmpty();
                }
            }

            updateBadge(count) {
                if (this.badge) {
                    if (count > 0) {
                        this.badge.textContent = count > 99 ? '99+' : count;
                        this.badge.style.display = 'flex';
                        if (this.markAllBtn) this.markAllBtn.style.display = 'block';
                        if (this.menuBadge) {
                            this.menuBadge.textContent = count > 99 ? '99+' : count;
                            this.menuBadge.style.display = 'inline-block';
                        }
                    } else {
                        this.badge.style.display = 'none';
                        if (this.markAllBtn) this.markAllBtn.style.display = 'none';
                        if (this.menuBadge) this.menuBadge.style.display = 'none';
                    }
                }
            }

            showLoading() {
                if (this.loading) this.loading.style.display = 'block';
                if (this.empty) this.empty.style.display = 'none';
                if (this.content) this.content.innerHTML = '';
            }

            showEmpty() {
                if (this.loading) this.loading.style.display = 'none';
                if (this.empty) this.empty.style.display = 'block';
                if (this.content) this.content.innerHTML = '';
            }

            getNotificationIcon(type) {
                const icons = {
                    'order_received': 'bi-bag-check',
                    'order_ready': 'bi-check-circle',
                    'order_completed': 'bi-trophy',
                    'order_cancelled': 'bi-x-circle',
                    'pickup_request': 'bi-truck',
                    'pickup_accepted': 'bi-check2-circle',
                    'pickup_completed': 'bi-box-seam',
                    'payment_received': 'bi-credit-card',
                    'unclaimed_reminder': 'bi-clock-history',
                    'unclaimed_warning': 'bi-exclamation-triangle',
                    'new_customer': 'bi-person-plus',
                    'system': 'bi-gear',
                    'announcement': 'bi-megaphone',
                };
                return icons[type] || 'bi-bell';
            }

            getNotificationColor(type) {
                const colors = {
                    'order_received': 'primary',
                    'order_ready': 'success',
                    'order_completed': 'success',
                    'order_cancelled': 'danger',
                    'pickup_request': 'info',
                    'pickup_accepted': 'primary',
                    'pickup_completed': 'success',
                    'payment_received': 'success',
                    'unclaimed_reminder': 'warning',
                    'unclaimed_warning': 'danger',
                    'new_customer': 'info',
                    'system': 'secondary',
                    'announcement': 'primary',
                };
                return colors[type] || 'secondary';
            }

            renderNotifications(notifications) {
                if (this.loading) this.loading.style.display = 'none';

                if (!notifications || notifications.length === 0) {
                    this.showEmpty();
                    return;
                }

                if (this.empty) this.empty.style.display = 'none';

                const html = notifications.map(notification => {
                    const icon = notification.icon || this.getNotificationIcon(notification.type);
                    const color = notification.color || this.getNotificationColor(notification.type);
                    const url = notification.url || '#';

                    return `
                        <a href="${url}"
                           class="notification-item ${!notification.is_read ? 'unread' : ''}"
                           data-id="${notification.id}"
                           onclick="staffNotifications.markAsRead(${notification.id})">
                            <div class="notification-icon" style="background: var(--bs-${color}-bg-subtle, rgba(var(--bs-${color}-rgb), 0.1));">
                                <i class="bi ${icon} text-${color}"></i>
                            </div>
                            <div class="notification-content">
                                <div class="notification-title">
                                    ${notification.title}
                                    ${!notification.is_read ? '<span class="badge bg-primary ms-1" style="font-size: 0.6rem;">NEW</span>' : ''}
                                </div>
                                <div class="notification-message">${notification.message}</div>
                                <div class="notification-time">
                                    <i class="bi bi-clock me-1"></i>${notification.created_at}
                                </div>
                            </div>
                        </a>
                    `;
                }).join('');

                if (this.content) this.content.innerHTML = html;
            }

            async markAsRead(id) {
                try {
                    const response = await fetch(`{{ url('staff/notifications') }}/${id}/read`, {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': this.csrfToken,
                            'X-Requested-With': 'XMLHttpRequest',
                        }
                    });

                    if (response.ok) {
                        // Update UI
                        const item = document.querySelector(`.notification-item[data-id="${id}"]`);
                        if (item) {
                            item.classList.remove('unread');
                            const badge = item.querySelector('.badge');
                            if (badge) badge.remove();
                        }
                        this.fetchUnreadCount();
                    }
                } catch (error) {
                    console.error('Error marking notification as read:', error);
                }
            }

            async markAllAsRead() {
                try {
                    const response = await fetch('{{ route("staff.notifications.mark-all-read") }}', {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': this.csrfToken,
                            'X-Requested-With': 'XMLHttpRequest',
                        }
                    });

                    if (response.ok) {
                        // Update UI
                        document.querySelectorAll('.notification-item.unread').forEach(item => {
                            item.classList.remove('unread');
                            const badge = item.querySelector('.badge');
                            if (badge) badge.remove();
                        });
                        this.updateBadge(0);
                    }
                } catch (error) {
                    console.error('Error marking all as read:', error);
                }
            }
        }

        // Initialize notification system
        let staffNotifications;
        document.addEventListener('DOMContentLoaded', () => {
            staffNotifications = new StaffNotificationSystem();
        });
    </script>

    <!-- Staff Dashboard Controller -->
    <script>
        class StaffDashboard {
            constructor() {
                this.init();
            }

            init() {
                this.initSidebar();
                this.initTheme();
                this.initEventListeners();
            }

            initSidebar() {
                this.sidebar = document.getElementById('sidebar');
                this.mainContent = document.getElementById('mainContent');
                this.mobileMenuToggle = document.getElementById('mobileMenuToggle');
                this.sidebarOverlay = document.getElementById('sidebarOverlay');
                this.sidebarToggleBtn = this.sidebar?.querySelector('.sidebar-toggle-btn');

                this.isCollapsed = localStorage.getItem('staffSidebarCollapsed') === 'true';

                if (window.innerWidth <= 768) {
                    this.sidebar?.classList.add('hide-mobile');
                } else {
                    this.applySidebarState();
                }

                // Mobile menu toggle
                this.mobileMenuToggle?.addEventListener('click', () => this.toggleMobileSidebar());

                // Desktop toggle
                this.sidebarToggleBtn?.addEventListener('click', () => this.toggleDesktopSidebar());

                // Overlay click
                this.sidebarOverlay?.addEventListener('click', () => this.closeMobileSidebar());

                // Handle resize
                window.addEventListener('resize', () => this.handleResize());
            }

            applySidebarState() {
                if (this.isCollapsed) {
                    this.sidebar?.classList.add('collapsed');
                    this.mainContent?.classList.add('expanded');
                } else {
                    this.sidebar?.classList.remove('collapsed');
                    this.mainContent?.classList.remove('expanded');
                }
            }

            toggleMobileSidebar() {
                this.sidebar.classList.toggle('show-mobile');
                this.sidebar.classList.toggle('hide-mobile');
                this.sidebarOverlay.classList.toggle('show');
                document.body.style.overflow = this.sidebar.classList.contains('show-mobile') ? 'hidden' : '';
            }

            closeMobileSidebar() {
                this.sidebar.classList.remove('show-mobile');
                this.sidebar.classList.add('hide-mobile');
                this.sidebarOverlay.classList.remove('show');
                document.body.style.overflow = '';
            }

            toggleDesktopSidebar() {
                if (window.innerWidth > 768) {
                    this.sidebar.classList.toggle('collapsed');
                    this.mainContent.classList.toggle('expanded');
                    this.isCollapsed = this.sidebar.classList.contains('collapsed');
                    localStorage.setItem('staffSidebarCollapsed', this.isCollapsed.toString());
                }
            }

            handleResize() {
                if (window.innerWidth <= 768) {
                    this.closeMobileSidebar();
                    this.mainContent?.classList.add('full-width');
                } else {
                    this.mainContent?.classList.remove('full-width');
                    this.applySidebarState();
                }
            }

            initTheme() {
                this.themeToggle = document.getElementById('themeToggle');
                this.currentTheme = localStorage.getItem('theme') || 'light';
                document.documentElement.setAttribute('data-theme', this.currentTheme);

                this.themeToggle?.addEventListener('click', () => {
                    this.currentTheme = this.currentTheme === 'light' ? 'dark' : 'light';
                    document.documentElement.setAttribute('data-theme', this.currentTheme);
                    localStorage.setItem('theme', this.currentTheme);
                });
            }

            initEventListeners() {
                // Logout confirmation
                const logoutForm = document.getElementById('logoutForm');
                if (logoutForm) {
                    logoutForm.addEventListener('submit', (e) => {
                        if (!confirm('Are you sure you want to logout?')) {
                            e.preventDefault();
                        }
                    });
                }

                // Escape key handler
                document.addEventListener('keydown', (e) => {
                    if (e.key === 'Escape' && window.innerWidth <= 768) {
                        this.closeMobileSidebar();
                    }
                });
            }
        }

        // Initialize
        document.addEventListener('DOMContentLoaded', () => {
            window.staffDashboard = new StaffDashboard();
        });
    </script>

    @stack('scripts')
</body>
</html>
