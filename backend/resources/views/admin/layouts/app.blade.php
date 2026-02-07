<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="WashBox Admin Dashboard">
    <title>@yield('title', 'Dashboard') - WashBox Admin</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">

    <!-- Custom CSS -->
    <style>
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
        /* SIDEBAR STYLES - Enhanced */
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

        .sidebar-menu .badge {
            position: absolute;
            right: 1.5rem;
            top: 50%;
            transform: translateY(-50%);
            font-size: 0.65rem;
            padding: 0.25rem 0.5rem;
            background: var(--accent-color);
        }

        .sidebar.collapsed .sidebar-menu .badge {
            right: 0.25rem;
            top: 0.25rem;
            transform: none;
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
        /* TOPBAR - Enhanced */
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
            background: rgba(var(--card-bg-rgb), 0.8);
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
            display: flex;
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
        /* NOTIFICATION SYSTEM - Enhanced */
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
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0% {
                box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.4);
            }
            70% {
                box-shadow: 0 0 0 6px rgba(239, 68, 68, 0);
            }
            100% {
                box-shadow: 0 0 0 0 rgba(239, 68, 68, 0);
            }
        }

        .notification-dropdown {
            width: 400px;
            max-height: 500px;
            border: 1px solid var(--border-color);
            box-shadow: var(--shadow-xl);
            background: var(--card-bg);
            transform-origin: top right;
            animation: dropdownSlide 0.2s ease;
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

        .notification-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1.25rem;
            border-bottom: 1px solid var(--border-color);
        }

        .notification-header h6 {
            font-weight: 600;
            margin: 0;
            color: var(--text-primary);
        }

        .notification-actions {
            display: flex;
            gap: 0.5rem;
        }

        .notification-actions .btn {
            padding: 0.375rem 0.75rem;
            font-size: 0.75rem;
        }

        .notification-dropdown-list {
            max-height: 350px;
            overflow-y: auto;
        }

        .notification-dropdown-list::-webkit-scrollbar {
            width: 6px;
        }

        .notification-dropdown-list::-webkit-scrollbar-track {
            background: transparent;
        }

        .notification-dropdown-list::-webkit-scrollbar-thumb {
            background: var(--border-color);
            border-radius: 3px;
        }

        .notification-item {
            display: flex;
            align-items: flex-start;
            padding: 1rem 1.25rem;
            border-bottom: 1px solid var(--border-color);
            text-decoration: none;
            color: inherit;
            transition: all 0.15s ease;
            position: relative;
        }

        .notification-item:hover {
            background: var(--bg-color);
        }

        .notification-item.unread {
            background: rgba(var(--primary-color-rgb), 0.05);
        }

        .notification-item.unread::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            bottom: 0;
            width: 3px;
            background: var(--accent-color);
        }

        .notification-icon {
            width: 40px;
            height: 40px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 1rem;
            flex-shrink: 0;
            background: var(--bg-color);
            color: var(--primary-color);
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
            margin-bottom: 0.25rem;
            color: var(--text-primary);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .notification-message {
            font-size: 0.8125rem;
            color: var(--text-secondary);
            margin-bottom: 0.25rem;
            line-height: 1.4;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .notification-time {
            font-size: 0.75rem;
            color: var(--text-secondary);
            display: flex;
            align-items: center;
            gap: 0.25rem;
        }

        .notification-actions-inline {
            opacity: 0;
            transition: opacity 0.2s ease;
        }

        .notification-item:hover .notification-actions-inline {
            opacity: 1;
        }

        .notification-empty {
            padding: 3rem 1.5rem;
            text-align: center;
            color: var(--text-secondary);
        }

        .notification-empty i {
            font-size: 2rem;
            margin-bottom: 1rem;
            opacity: 0.5;
        }

        .notification-skeleton {
            padding: 1.25rem;
        }

        .skeleton-circle {
            width: 40px;
            height: 40px;
            border-radius: 12px;
            background: var(--border-color);
            animation: pulse-bg 1.5s ease-in-out infinite;
        }

        .skeleton-line {
            height: 12px;
            background: var(--border-color);
            border-radius: 4px;
            margin-bottom: 0.5rem;
            animation: pulse-bg 1.5s ease-in-out infinite;
        }

        .skeleton-line.short {
            width: 60%;
        }

        @keyframes pulse-bg {
            0%, 100% {
                opacity: 1;
            }
            50% {
                opacity: 0.5;
            }
        }

        /* ============================================ */
        /* USER MENU - Enhanced */
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
        /* TOAST NOTIFICATIONS */
        /* ============================================ */
        .toast-container {
            position: fixed;
            top: 1rem;
            right: 1rem;
            z-index: 9999;
        }

        .custom-toast {
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-left: 4px solid;
            box-shadow: var(--shadow-lg);
            border-radius: 0.75rem;
            padding: 1rem 1.25rem;
            margin-bottom: 1rem;
            animation: slideInRight 0.3s ease;
            display: flex;
            align-items: flex-start;
            gap: 1rem;
            max-width: 350px;
        }

        .custom-toast i {
            font-size: 1.25rem;
            flex-shrink: 0;
        }

        .custom-toast.success {
            border-left-color: var(--success-color);
        }

        .custom-toast.error {
            border-left-color: var(--danger-color);
        }

        .custom-toast.warning {
            border-left-color: var(--warning-color);
        }

        .custom-toast.info {
            border-left-color: var(--info-color);
        }

        @keyframes slideInRight {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }

        /* ============================================ */
        /* PREMIUM DASHBOARD STYLES */
        /* ============================================ */
        .kpi-card-hover {
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .kpi-card-hover:hover {
            transform: translateY(-4px);
            box-shadow: 0 20px 25px -5px rgba(45, 43, 95, 0.15), 0 10px 10px -5px rgba(45, 43, 95, 0.04) !important;
        }

        .action-btn {
            transition: all 0.3s ease;
        }

        .action-btn:hover {
            transform: scale(1.05);
            box-shadow: 0 20px 25px -5px rgba(45, 43, 95, 0.3) !important;
        }

        .nav-link {
            transition: all 0.3s ease !important;
            color: #6B7280 !important;
        }

        .nav-link:hover:not(.active) {
            color: #2D2B5F !important;
            opacity: 0.7;
        }

        .nav-link.active {
            color: #2D2B5F !important;
            border-bottom-color: #2D2B5F !important;
        }

        .dashboard-icon-container {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            background: linear-gradient(135deg, #2D2B5F 0%, #3D3B7F 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: white;
            box-shadow: 0 4px 12px rgba(45, 43, 95, 0.2);
        }

        .text-primary-dark {
            color: #2D2B5F !important;
        }

        .bg-primary-gradient {
            background: linear-gradient(135deg, #2D2B5F 0%, #3D3B7F 100%) !important;
        }

        .bg-warning-gradient {
            background: linear-gradient(135deg, #F59E0B 0%, #F79A2F 100%) !important;
        }

        .bg-info-gradient {
            background: linear-gradient(135deg, #3B82F6 0%, #60A5FA 100%) !important;
        }

        .bg-danger-gradient {
            background: linear-gradient(135deg, #EF4444 0%, #F87171 100%) !important;
        }

        .system-status-card {
            padding: 1.5rem;
            border-radius: 12px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: relative;
            overflow: hidden;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        .status-icon {
            width: 40px;
            height: 40px;
            border-radius: 8px;
            background: rgba(255, 255, 255, 0.2);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
            color: white;
        }

        .status-indicator {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            animation: dashboard-pulse 2s infinite;
        }

        .status-active {
            background: #10B981;
            box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.4);
        }

        .status-inactive {
            background: #EF4444;
            box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.4);
        }

        .status-warning {
            background: #F59E0B;
            box-shadow: 0 0 0 0 rgba(245, 158, 11, 0.4);
        }

        @keyframes dashboard-pulse {
            0% {
                box-shadow: 0 0 0 0 currentColor;
            }
            70% {
                box-shadow: 0 0 0 6px rgba(255, 255, 255, 0);
            }
            100% {
                box-shadow: 0 0 0 0 rgba(255, 255, 255, 0);
            }
        }

        .mb-6 {
            margin-bottom: 1.5rem !important;
        }

        .pb-4 {
            padding-bottom: 1.5rem !important;
        }

        /* ============================================ */
        /* RESPONSIVE DESIGN */
        /* ============================================ */
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
                width: 300px;
                z-index: 1050;
            }

            .sidebar.show {
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
                width: calc(100vw - 2rem);
                max-width: 400px;
                position: fixed !important;
                top: 80px !important;
                left: 50% !important;
                transform: translateX(-50%) !important;
                right: auto !important;
            }

            .dropdown-menu {
                position: fixed !important;
                top: 80px !important;
                left: 50% !important;
                transform: translateX(-50%) !important;
                right: auto !important;
                min-width: 250px;
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

            .topbar {
                padding: 1rem 1.5rem;
            }

            .content-area {
                padding: 1.5rem;
            }

            .notification-dropdown {
                width: 350px;
            }

            .user-name {
                max-width: 120px;
            }
        }

        @media (min-width: 1025px) and (max-width: 1200px) {
            .sidebar {
                width: 240px;
            }

            .sidebar.collapsed {
                width: var(--sidebar-collapsed-width);
            }

            .main-content {
                margin-left: 240px;
            }

            .main-content.expanded {
                margin-left: var(--sidebar-collapsed-width);
            }
        }

        /* Print styles */
        @media print {
            .sidebar,
            .topbar,
            .notification-bell,
            .theme-toggle,
            .user-menu {
                display: none !important;
            }

            .main-content {
                margin-left: 0 !important;
            }

            .content-area {
                padding: 0;
            }
        }
    </style>

    @stack('styles')
</head>

<body data-theme="light">
    <!-- Toast Container -->
    <div class="toast-container" id="toastContainer"></div>

    <!-- Sidebar Overlay -->
    <div class="sidebar-overlay" id="sidebarOverlay" aria-hidden="true"></div>

    <!-- Sidebar -->
    <x-sidebar role="{{ auth()->user()->role ?? 'admin' }}" />

    <!-- Main Content -->
    <div class="main-content" id="mainContent">
        <!-- Topbar -->
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
                    <a href="{{ route('admin.dashboard') }}" class="breadcrumb-item">Dashboard</a>
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

                <!-- Notification Bell -->
                <div class="notification-wrapper">
                    <button class="notification-bell" type="button" id="notificationDropdown"
                        data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false"
                        aria-label="Notifications">
                        <i class="bi bi-bell"></i>
                        <span class="badge rounded-pill bg-danger notification-badge" id="notificationBadge"
                            style="display: none;">
                            0
                        </span>
                    </button>

                    <div class="dropdown-menu dropdown-menu-end notification-dropdown rounded-3 p-0"
                        aria-labelledby="notificationDropdown">
                        <!-- Header -->
                        <div class="notification-header">
                            <h6 class="mb-0">
                                <i class="bi bi-bell me-2 text-primary"></i>Notifications
                            </h6>
                            <div class="notification-actions">
                                <button class="btn btn-sm btn-outline-secondary" onclick="markAllNotificationsRead()">
                                    <i class="bi bi-check-all me-1"></i>Mark all read
                                </button>
                                <button class="btn btn-sm btn-outline-secondary" onclick="clearAllNotifications()">
                                    <i class="bi bi-trash me-1"></i>Clear all
                                </button>
                            </div>
                        </div>

                        <!-- Notifications List -->
                        <div class="notification-dropdown-list" id="notificationDropdownList">
                            <!-- Loading skeleton will be inserted here -->
                        </div>

                        <!-- Footer -->
                        <div class="border-top px-3 py-2 text-center">
                            <a href="{{ route('admin.notifications.index') }}"
                                class="text-decoration-none small fw-semibold text-primary">
                                View All Notifications <i class="bi bi-arrow-right ms-1"></i>
                            </a>
                        </div>
                    </div>
                </div>

                <!-- User Menu -->
                <div class="user-menu-wrapper">
                    <div class="user-menu dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                        <div class="user-avatar" id="userAvatar">
                            {{ strtoupper(substr(auth()->user()->name ?? 'A', 0, 1)) }}
                        </div>
                        <div class="user-info">
                            <span class="user-name">{{ auth()->user()->name ?? 'Admin' }}</span>
                            <span class="user-role">{{ ucfirst(auth()->user()->role ?? 'Administrator') }}</span>
                        </div>
                        <i class="bi bi-chevron-down"></i>
                    </div>
                    <ul class="dropdown-menu dropdown-menu-end shadow border-0 rounded-3">
                        <li>
                            <a class="dropdown-item py-2" href="{{ route('admin.profile') }}">
                                <i class="bi bi-person"></i>Profile
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item py-2" href="{{ route('admin.settings') }}">
                                <i class="bi bi-gear"></i>Settings
                            </a>
                        </li>
                        <li>
                            <hr class="dropdown-divider">
                        </li>
                        <li>
                            <form action="{{ route('admin.logout') }}" method="POST" class="mb-0">
                                @csrf
                                <button type="submit" class="dropdown-item py-2 text-danger">
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
            <x-alert />
            @yield('content')
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>

    <!-- Enhanced Admin Scripts -->
    <script>
        // ============================================
        // ADMIN DASHBOARD CONTROLLER
        // ============================================

        class AdminDashboard {
            constructor() {
                this.init();
            }

            init() {
                this.initSidebar();
                this.initTheme();
                this.initNotifications();
                this.initAccessibility();
                this.initEventListeners();
            }

            // ============================================
            // SIDEBAR MANAGEMENT
            // ============================================
            initSidebar() {
                this.sidebar = document.getElementById('sidebar');
                this.mainContent = document.getElementById('mainContent');
                this.mobileMenuToggle = document.getElementById('mobileMenuToggle');
                this.sidebarOverlay = document.getElementById('sidebarOverlay');
                this.sidebarToggleBtn = this.sidebar?.querySelector('.sidebar-toggle-btn');

                // Get saved state
                this.isCollapsed = localStorage.getItem('sidebarCollapsed') === 'true';
                this.applySidebarState();

                // Initialize tooltips
                this.initSidebarTooltips();

                // Add event listeners
                this.setupSidebarEvents();
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

            setupSidebarEvents() {
                // Mobile menu toggle
                this.mobileMenuToggle?.addEventListener('click', () => this.toggleMobileSidebar());

                // Desktop toggle button
                this.sidebarToggleBtn?.addEventListener('click', () => this.toggleDesktopSidebar());

                // Close sidebar on overlay click
                this.sidebarOverlay?.addEventListener('click', () => this.closeMobileSidebar());

                // Close sidebar on menu item click (mobile)
                document.querySelectorAll('.sidebar-menu a').forEach(link => {
                    link.addEventListener('click', () => {
                        if (window.innerWidth <= 768) {
                            this.closeMobileSidebar();
                        }
                    });
                });

                // Handle window resize
                window.addEventListener('resize', () => this.handleResize());

                // Close with Escape key
                document.addEventListener('keydown', (e) => this.handleKeydown(e));
            }

            toggleMobileSidebar() {
                const isOpening = !this.sidebar.classList.contains('show');

                this.sidebar.classList.toggle('show');
                this.sidebarOverlay.classList.toggle('show');
                document.body.style.overflow = this.sidebar.classList.contains('show') ? 'hidden' : '';

                // Update aria-expanded for accessibility
                this.mobileMenuToggle?.setAttribute('aria-expanded',
                    this.sidebar.classList.contains('show').toString()
                );

                // Focus management for accessibility
                if (isOpening) {
                    setTimeout(() => {
                        const firstFocusable = this.sidebar.querySelector('a, button');
                        firstFocusable?.focus();
                    }, 100);
                }
            }

            closeMobileSidebar() {
                this.sidebar.classList.remove('show');
                this.sidebarOverlay.classList.remove('show');
                document.body.style.overflow = '';
                this.mobileMenuToggle?.setAttribute('aria-expanded', 'false');
            }

            toggleDesktopSidebar() {
                if (window.innerWidth > 768) {
                    this.sidebar.classList.toggle('collapsed');
                    this.mainContent.classList.toggle('expanded');

                    this.isCollapsed = this.sidebar.classList.contains('collapsed');
                    localStorage.setItem('sidebarCollapsed', this.isCollapsed.toString());

                    this.initSidebarTooltips();

                    // Dispatch custom event
                    window.dispatchEvent(new CustomEvent('sidebarToggle', {
                        detail: { isCollapsed: this.isCollapsed }
                    }));
                }
            }

            initSidebarTooltips() {
                // Remove existing tooltips
                const tooltips = bootstrap.Tooltip.getInstance(this.sidebar);
                if (tooltips) tooltips.dispose();

                if (this.sidebar?.classList.contains('collapsed')) {
                    document.querySelectorAll('.sidebar-menu a').forEach(item => {
                        const label = item.querySelector('.menu-text')?.textContent?.trim();
                        if (label) {
                            item.setAttribute('data-bs-toggle', 'tooltip');
                            item.setAttribute('data-bs-placement', 'right');
                            item.setAttribute('data-bs-title', label);
                            item.setAttribute('data-bs-custom-class', 'sidebar-tooltip');
                        }
                    });
                } else {
                    document.querySelectorAll('.sidebar-menu a').forEach(item => {
                        item.removeAttribute('data-bs-toggle');
                        item.removeAttribute('data-bs-placement');
                        item.removeAttribute('data-bs-title');
                    });
                }

                // Initialize Bootstrap tooltips
                const tooltipTriggerList = [].slice.call(
                    document.querySelectorAll('[data-bs-toggle="tooltip"]')
                );
                tooltipTriggerList.forEach(tooltipTriggerEl => {
                    new bootstrap.Tooltip(tooltipTriggerEl, {
                        delay: { show: 300, hide: 0 }
                    });
                });
            }

            // ============================================
            // THEME MANAGEMENT
            // ============================================
            initTheme() {
                this.themeToggle = document.getElementById('themeToggle');
                this.currentTheme = localStorage.getItem('theme') || 'light';
                this.applyTheme();

                this.themeToggle?.addEventListener('click', () => this.toggleTheme());
            }

            applyTheme() {
                document.documentElement.setAttribute('data-theme', this.currentTheme);
                localStorage.setItem('theme', this.currentTheme);

                // Update theme toggle icon
                const themeIcon = this.themeToggle?.querySelector('.bi-sun, .bi-moon');
                if (themeIcon) {
                    themeIcon.style.transition = 'opacity 0.3s, transform 0.3s';
                }
            }

            toggleTheme() {
                this.currentTheme = this.currentTheme === 'light' ? 'dark' : 'light';
                this.applyTheme();

                // Add transition class for smooth theme switch
                document.body.classList.add('theme-transitioning');
                setTimeout(() => {
                    document.body.classList.remove('theme-transitioning');
                }, 300);
            }

            // ============================================
            // NOTIFICATION SYSTEM
            // ============================================
            initNotifications() {
                this.notificationBadge = document.getElementById('notificationBadge');
                this.notificationList = document.getElementById('notificationDropdownList');

                // Load notifications on dropdown open
                const notificationDropdown = document.getElementById('notificationDropdown');
                if (notificationDropdown) {
                    notificationDropdown.addEventListener('show.bs.dropdown', () => {
                        this.loadNotifications();
                    });
                }

                // Initial load
                this.loadNotifications();

                // Poll for new notifications every 30 seconds
                this.notificationInterval = setInterval(() => {
                    this.loadNotifications(false); // Silent update
                }, 30000);
            }

            async loadNotifications(showLoading = true) {
                if (showLoading) {
                    this.showNotificationSkeleton();
                }

                try {
                    const response = await this.fetchWithCSRF('{{ route("admin.notifications.recent") }}');
                    if (!response.ok) throw new Error('Failed to load notifications');

                    const data = await response.json();
                    this.updateNotificationBadge(data.unread_count);
                    this.renderNotifications(data.notifications);
                } catch (error) {
                    console.error('Notification error:', error);
                    this.showNotificationError();
                }
            }

            showNotificationSkeleton() {
                if (!this.notificationList) return;

                this.notificationList.innerHTML = `
                    <div class="notification-skeleton">
                        ${Array(3).fill(`
                            <div class="d-flex align-items-center p-3">
                                <div class="skeleton-circle"></div>
                                <div class="ms-3 w-100">
                                    <div class="skeleton-line"></div>
                                    <div class="skeleton-line short"></div>
                                </div>
                            </div>
                        `).join('')}
                    </div>
                `;
            }

            showNotificationError() {
                if (!this.notificationList) return;

                this.notificationList.innerHTML = `
                    <div class="notification-empty">
                        <i class="bi bi-wifi-off fs-3 d-block mb-2"></i>
                        <div class="fw-semibold">Connection Error</div>
                        <small>Unable to load notifications</small>
                        <button class="btn btn-sm btn-outline-primary mt-2" onclick="adminDashboard.loadNotifications()">
                            <i class="bi bi-arrow-clockwise"></i> Retry
                        </button>
                    </div>
                `;
            }

            updateNotificationBadge(count) {
                if (!this.notificationBadge) return;

                if (count > 0) {
                    this.notificationBadge.textContent = count > 99 ? '99+' : count;
                    this.notificationBadge.style.display = 'flex';

                    // Add animation for new notifications
                    if (count > parseInt(this.notificationBadge.textContent || 0)) {
                        this.notificationBadge.classList.add('pulse');
                        setTimeout(() => {
                            this.notificationBadge.classList.remove('pulse');
                        }, 1000);
                    }
                } else {
                    this.notificationBadge.style.display = 'none';
                }
            }

            renderNotifications(notifications) {
                if (!this.notificationList) return;

                if (!notifications || notifications.length === 0) {
                    this.notificationList.innerHTML = `
                        <div class="notification-empty">
                            <i class="bi bi-check-circle fs-1 text-success d-block mb-2"></i>
                            <div class="fw-semibold">All caught up!</div>
                            <small>No new notifications</small>
                        </div>
                    `;
                    return;
                }

                const html = notifications.map(n => {
                    const unreadClass = !n.is_read ? 'unread' : '';
                    const iconClass = this.getNotificationIcon(n.type);
                    const timeAgo = this.formatTimeAgo(n.created_at);

                    return `
                        <a href="${n.link || '#'}" class="notification-item ${unreadClass}"
                           onclick="adminDashboard.markNotificationRead(${n.id}, event)">
                            <div class="notification-icon ${iconClass.color}">
                                <i class="bi ${iconClass.icon}"></i>
                            </div>
                            <div class="notification-content">
                                <div class="notification-title">
                                    <span>${this.escapeHtml(n.title)}</span>
                                    ${!n.is_read ? '<span class="badge bg-primary" style="font-size: 0.6rem;">NEW</span>' : ''}
                                </div>
                                <div class="notification-message">${this.escapeHtml(n.message)}</div>
                                <div class="notification-time">
                                    <i class="bi bi-clock me-1"></i>${timeAgo}
                                </div>
                            </div>
                            <div class="notification-actions-inline">
                                <button class="btn btn-sm btn-link text-danger p-0"
                                        onclick="adminDashboard.deleteNotification(${n.id}, event)">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </div>
                        </a>
                    `;
                }).join('');

                this.notificationList.innerHTML = html;
            }

            getNotificationIcon(type) {
                const icons = {
                    'info': { icon: 'bi-info-circle', color: 'text-info' },
                    'success': { icon: 'bi-check-circle', color: 'text-success' },
                    'warning': { icon: 'bi-exclamation-triangle', color: 'text-warning' },
                    'danger': { icon: 'bi-x-circle', color: 'text-danger' },
                    'default': { icon: 'bi-bell', color: 'text-primary' }
                };
                return icons[type] || icons.default;
            }

            formatTimeAgo(dateString) {
                const date = new Date(dateString);
                const now = new Date();
                const diffMs = now - date;
                const diffMins = Math.floor(diffMs / 60000);
                const diffHours = Math.floor(diffMs / 3600000);
                const diffDays = Math.floor(diffMs / 86400000);

                if (diffMins < 1) return 'Just now';
                if (diffMins < 60) return `${diffMins}m ago`;
                if (diffHours < 24) return `${diffHours}h ago`;
                if (diffDays < 7) return `${diffDays}d ago`;
                return date.toLocaleDateString();
            }

            async markNotificationRead(id, event) {
                if (event) {
                    event.preventDefault();
                    event.stopPropagation();
                }

                try {
                    await this.fetchWithCSRF(`/admin/notifications/${id}/read`, {
                        method: 'POST'
                    });

                    // Update UI immediately
                    const notificationItem = document.querySelector(`.notification-item[onclick*="${id}"]`);
                    if (notificationItem) {
                        notificationItem.classList.remove('unread');
                        const badge = notificationItem.querySelector('.badge');
                        if (badge) badge.remove();
                    }

                    // Reload notifications after a short delay
                    setTimeout(() => this.loadNotifications(false), 300);

                    // Navigate to link if provided
                    if (event?.currentTarget?.href && event.currentTarget.href !== '#') {
                        window.location.href = event.currentTarget.href;
                    }
                } catch (error) {
                    console.error('Error marking notification as read:', error);
                    this.showToast('Failed to mark notification as read', 'error');
                }
            }

            async markAllNotificationsRead() {
                try {
                    await this.fetchWithCSRF('{{ route("admin.notifications.mark-all-read") }}', {
                        method: 'POST'
                    });

                    this.loadNotifications();
                    this.showToast('All notifications marked as read', 'success');
                } catch (error) {
                    console.error('Error marking all notifications as read:', error);
                    this.showToast('Failed to mark all notifications as read', 'error');
                }
            }

            async deleteNotification(id, event) {
                if (event) {
                    event.preventDefault();
                    event.stopPropagation();
                }

                if (!confirm('Are you sure you want to delete this notification?')) {
                    return;
                }

                try {
                    await this.fetchWithCSRF(`/admin/notifications/${id}`, {
                        method: 'DELETE'
                    });

                    // Remove from UI
                    const notificationItem = document.querySelector(`.notification-item[onclick*="${id}"]`);
                    if (notificationItem) {
                        notificationItem.style.opacity = '0';
                        setTimeout(() => {
                            notificationItem.remove();
                            // Check if list is empty
                            if (!this.notificationList.querySelector('.notification-item')) {
                                this.renderNotifications([]);
                            }
                        }, 300);
                    }

                    this.showToast('Notification deleted', 'success');
                } catch (error) {
                    console.error('Error deleting notification:', error);
                    this.showToast('Failed to delete notification', 'error');
                }
            }

            async clearAllNotifications() {
                if (!confirm('Are you sure you want to clear all notifications?')) {
                    return;
                }

            }

            // ============================================
            // UTILITY METHODS
            // ============================================
            async fetchWithCSRF(url, options = {}) {
                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

                return fetch(url, {
                    ...options,
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        ...options.headers
                    }
                });
            }

            escapeHtml(text) {
                const div = document.createElement('div');
                div.textContent = text;
                return div.innerHTML;
            }

            showToast(message, type = 'info') {
                const toast = document.createElement('div');
                toast.className = `custom-toast ${type}`;
                toast.innerHTML = `
                    <i class="bi ${this.getToastIcon(type)}"></i>
                    <div class="toast-content">
                        <div class="toast-message">${this.escapeHtml(message)}</div>
                    </div>
                    <button class="btn btn-sm btn-link p-0 ms-auto" onclick="this.parentElement.remove()">
                        <i class="bi bi-x"></i>
                    </button>
                `;

                const container = document.getElementById('toastContainer') || document.body;
                container.appendChild(toast);

                // Auto-remove after 5 seconds
                setTimeout(() => {
                    if (toast.parentElement) {
                        toast.style.opacity = '0';
                        setTimeout(() => toast.remove(), 300);
                    }
                }, 5000);
            }

            getToastIcon(type) {
                const icons = {
                    'success': 'bi-check-circle',
                    'error': 'bi-x-circle',
                    'warning': 'bi-exclamation-triangle',
                    'info': 'bi-info-circle'
                };
                return icons[type] || icons.info;
            }

            // ============================================
            // ACCESSIBILITY
            // ============================================
            initAccessibility() {
                // Add skip to content link
                const skipLink = document.createElement('a');
                skipLink.href = '#mainContent';
                skipLink.className = 'skip-to-content';
                skipLink.innerHTML = 'Skip to main content';
                document.body.insertBefore(skipLink, document.body.firstChild);

                // Initialize focus traps for modals
                this.initFocusTraps();
            }

            initFocusTraps() {
                // This would be expanded for modal focus trapping
                // For now, just ensure focus stays within dropdowns when open
                document.addEventListener('keydown', (e) => {
                    if (e.key === 'Tab' && document.querySelector('.dropdown-menu.show')) {
                        this.trapFocus(e);
                    }
                });
            }

            trapFocus(e) {
                // Focus trap implementation for dropdowns
                const dropdown = document.querySelector('.dropdown-menu.show');
                if (!dropdown) return;

                const focusable = dropdown.querySelectorAll('button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])');
                if (focusable.length === 0) return;

                const firstFocusable = focusable[0];
                const lastFocusable = focusable[focusable.length - 1];

                if (e.shiftKey && document.activeElement === firstFocusable) {
                    e.preventDefault();
                    lastFocusable.focus();
                } else if (!e.shiftKey && document.activeElement === lastFocusable) {
                    e.preventDefault();
                    firstFocusable.focus();
                }
            }

            // ============================================
            // EVENT HANDLERS
            // ============================================
            initEventListeners() {
                // Debounced resize handler
                let resizeTimer;
                window.addEventListener('resize', () => {
                    clearTimeout(resizeTimer);
                    resizeTimer = setTimeout(() => this.handleResize(), 250);
                });

                // Service worker for PWA (optional)
                if ('serviceWorker' in navigator) {
                    window.addEventListener('load', () => {
                        navigator.serviceWorker.register('/sw.js').catch(err => {
                            console.log('ServiceWorker registration failed: ', err);
                        });
                    });
                }
            }

            handleResize() {
                if (window.innerWidth > 768) {
                    this.closeMobileSidebar();
                }

                // Reinitialize tooltips if needed
                if (this.sidebar?.classList.contains('collapsed')) {
                    this.initSidebarTooltips();
                }
            }

            handleKeydown(e) {
                if (e.key === 'Escape') {
                    if (window.innerWidth <= 768) {
                        this.closeMobileSidebar();
                    } else {
                        this.sidebar?.classList.remove('collapsed');
                        this.mainContent?.classList.remove('expanded');
                        localStorage.setItem('sidebarCollapsed', 'false');
                        this.isCollapsed = false;
                    }
                }

                // Close dropdowns with Escape
                if (e.key === 'Escape') {
                    const dropdowns = document.querySelectorAll('.dropdown-menu.show');
                    dropdowns.forEach(dropdown => {
                        bootstrap.Dropdown.getInstance(dropdown.previousElementSibling)?.hide();
                    });
                }
            }
        }

        // Initialize the dashboard when DOM is loaded
        document.addEventListener('DOMContentLoaded', () => {
            window.adminDashboard = new AdminDashboard();

            // Add CSS for skip link
            const style = document.createElement('style');
            style.textContent = `
                .skip-to-content {
                    position: absolute;
                    top: -40px;
                    left: 0;
                    background: var(--primary-color);
                    color: white;
                    padding: 0.5rem 1rem;
                    text-decoration: none;
                    z-index: 9999;
                    transition: top 0.3s ease;
                }
                .skip-to-content:focus {
                    top: 0;
                }
                .sidebar-tooltip .tooltip-inner {
                    background: var(--sidebar-bg);
                    color: white;
                    font-size: 0.75rem;
                    padding: 0.5rem 0.75rem;
                }
                .sidebar-tooltip .tooltip-arrow {
                    border-right-color: var(--sidebar-bg) !important;
                }
                .theme-transitioning * {
                    transition: background-color 0.3s ease, color 0.3s ease, border-color 0.3s ease !important;
                }
            `;
            document.head.appendChild(style);
        });
    </script>

    @stack('scripts')
</body>
</html>
