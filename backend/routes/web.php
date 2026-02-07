<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\AddOnController;
use App\Http\Controllers\Admin\OrderController;
use App\Http\Controllers\Admin\StaffController;
use App\Http\Controllers\Api\ServiceController;
use App\Http\Controllers\Admin\BranchController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\PricingController;
use App\Http\Controllers\Admin\ReceiptController;
use App\Http\Controllers\Admin\CustomerController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\AnalyticsController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\LogisticsController;
use App\Http\Controllers\Admin\PromotionController;
use App\Http\Controllers\Admin\Auth\LoginController;
use App\Http\Controllers\Admin\PickupRequestController;
use App\Http\Controllers\Admin\Auth\StaffLoginController;
use App\Http\Controllers\Staff\OrderController as StaffOrderController;

// ============================================================================
// STAFF CONTROLLERS (FIXED!)
// ============================================================================
use App\Http\Controllers\Admin\Auth\LoginController as AdminLoginController;
use App\Http\Controllers\Staff\CustomerController as StaffCustomerController;
use App\Http\Controllers\Staff\DashboardController as StaffDashboardController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application.
| These routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group.
|
*/

// ============================================================================
// PUBLIC ROUTES
// ============================================================================

Route::get('/', function () {
    return redirect()->route('admin.login');
});

// ============================================================================
// ADMIN AUTHENTICATION ROUTES (Guest Only)
// ============================================================================

Route::middleware('guest')->prefix('admin')->name('admin.')->group(function () {
    Route::get('/login', [AdminLoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [AdminLoginController::class, 'login']);
    Route::get('/forgot-password', [AdminLoginController::class, 'showForgotPasswordForm'])->name('forgot-password');
    Route::post('/forgot-password', [AdminLoginController::class, 'sendResetLinkEmail'])->name('forgot-password.email');
});

// ============================================================================
// ADMIN AUTHENTICATED ROUTES
// ============================================================================

Route::middleware(['auth:sanctum', 'admin'])->prefix('admin')->name('admin.')->group(function () {

    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard/stats', [DashboardController::class, 'getStats'])->name('dashboard.stats');

    // Logout
    Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

    // ========================================================================
    // ANALYTICS
    // ========================================================================
    Route::get('/analytics', [AnalyticsController::class, 'index'])->name('analytics');

    // ========================================================================
    // PROMOTIONS (FIXED ROUTE NAME)
    // ========================================================================
    Route::resource('promotions', PromotionController::class);
    Route::patch('promotions/{promotion}/toggle', [PromotionController::class, 'toggleStatus'])
        ->name('promotions.toggleStatus');
    Route::get('promotions-analytics', [PromotionController::class, 'analytics'])
        ->name('promotions.analytics');

    // ========================================================================
    // LOGISTICS
    // ========================================================================
    Route::prefix('pickups')->name('pickups.')->group(function () {
        Route::get('/{pickup}/route', [LogisticsController::class, 'getPickupRoute'])
            ->name('route');
        Route::get('/{pickup}/directions', [LogisticsController::class, 'getDirections'])
            ->name('directions');
        Route::post('/{pickup}/start-navigation', [LogisticsController::class, 'startNavigation'])
            ->name('start-navigation');
        Route::post('/{pickup}/complete', [LogisticsController::class, 'completePickup'])
            ->name('complete');
    });


    Route::prefix('logistics')->name('logistics.')->group(function () {
        Route::post('/optimize-route', [LogisticsController::class, 'getOptimizedRoute'])
            ->name('optimize-route');
        Route::post('/create-route', [LogisticsController::class, 'createDeliveryRoute'])
            ->name('create-route');
        Route::get('/active-routes', [LogisticsController::class, 'getActiveRoutes'])
            ->name('active-routes');
        Route::get('/stats', [LogisticsController::class, 'getRouteStats'])
            ->name('stats');
            Route::get('/admin/pickups/{pickup}/route', [LogisticsController::class, 'getPickupRoute']);
    });
Route::get('/admin/pickups/{pickup}/route', [LogisticsController::class, 'getPickupRoute']);
    // ========================================================================
    // ORDERS
    // ========================================================================
    Route::resource('orders', OrderController::class);
    Route::get('/receipts/{order}', [ReceiptController::class, 'show'])->name('admin.receipts.show');
    Route::post('/orders/{order}/assign-staff', [OrderController::class, 'assignStaff'])->name('orders.assign-staff');
    Route::post('/orders/{order}/mark-paid', [OrderController::class, 'markPaid'])->name('orders.mark-paid');
    Route::post('/orders/{order}/mark-unclaimed', [OrderController::class, 'markUnclaimed'])->name('orders.mark-unclaimed');
    Route::put('/orders/{order}/status', [OrderController::class, 'updateStatus'])->name('orders.update-status');
    Route::post('/orders/{order}/record-payment', [OrderController::class, 'recordPayment'])->name('orders.record-payment');
    Route::post('/orders/{order}/payment', [OrderController::class, 'storePayment'])->name('orders.store-payment');

    // ========================================================================
    // CUSTOMERS
    // ========================================================================
    Route::resource('customers', CustomerController::class);
    Route::get('/customers/{customer}/orders', [CustomerController::class, 'orders'])->name('customers.orders');
    Route::post('/customers/{customer}/toggle-status', [CustomerController::class, 'toggleStatus'])->name('customers.toggle-status');

    // ========================================================================
    // BRANCHES
    // ========================================================================
    Route::resource('branches', BranchController::class);
    Route::post('/branches/{branch}/toggle-status', [BranchController::class, 'toggleStatus'])->name('branches.toggle-status');
    Route::patch('/branches/{branch}/deactivate', [BranchController::class, 'deactivate'])->name('branches.deactivate');
    Route::patch('/branches/{branch}/activate', [BranchController::class, 'activate'])->name('branches.activate');

    // ========================================================================
    // SERVICES
    // ========================================================================
    Route::resource('services', ServiceController::class);
    Route::post('/services/{service}/toggle-status', [ServiceController::class, 'toggleStatus'])->name('services.toggle-status');

    // Bulk actions
    Route::post('/services/bulk-activate', [ServiceController::class, 'bulkActivate'])
        ->name('services.bulk-activate');

    Route::post('/services/bulk-deactivate', [ServiceController::class, 'bulkDeactivate'])
        ->name('services.bulk-deactivate');

    Route::delete('/services/bulk-delete', [ServiceController::class, 'bulkDestroy'])
        ->name('services.bulk-delete');

     // Add-ons routes

   Route::resource('addons', AddOnController::class);

    // OR if you want explicit routes:
    Route::prefix('addons')->name('addons.')->group(function() {
        Route::post('/', [AddOnController::class, 'store'])->name('store');
        Route::put('/{addon}', [AddOnController::class, 'update'])->name('update');
        Route::delete('/{addon}', [AddOnController::class, 'destroy'])->name('destroy');
        Route::post('/{addon}/toggle-status', [AddOnController::class, 'toggleStatus'])->name('toggle-status');
    });
    // ========================================================================
    // STAFF MANAGEMENT
    // ========================================================================
    Route::resource('staff', StaffController::class);
    Route::post('/staff/{user}/toggle-status', [StaffController::class, 'toggleStatus'])->name('staff.toggle-status');
    Route::post('/staff/{user}/reset-password', [StaffController::class, 'resetPassword'])->name('staff.reset-password');

    // ========================================================================
    // UNCLAIMED LAUNDRY MANAGEMENT
    // ========================================================================
    Route::prefix('unclaimed')->name('unclaimed.')->group(function () {
        // Static routes FIRST
        Route::get('/history', [App\Http\Controllers\Admin\UnclaimedController::class, 'disposalHistory'])->name('history');
        Route::get('/remind-all', [App\Http\Controllers\Admin\UnclaimedController::class, 'remindAll'])->name('remindAll');
        Route::get('/export', [App\Http\Controllers\Admin\UnclaimedController::class, 'export'])->name('export');
        Route::get('/stats', [App\Http\Controllers\Admin\UnclaimedController::class, 'stats'])->name('stats');
        Route::post('/bulk-reminders', [App\Http\Controllers\Admin\UnclaimedController::class, 'sendBulkReminders'])->name('bulk-reminders');

        // Main list
        Route::get('/', [App\Http\Controllers\Admin\UnclaimedController::class, 'index'])->name('index');

        // Dynamic routes LAST
        Route::get('/{order}', [App\Http\Controllers\Admin\UnclaimedController::class, 'show'])->name('show');
        Route::post('/{id}/send-reminder', [App\Http\Controllers\Admin\UnclaimedController::class, 'sendReminder'])->name('send-reminder');
        Route::post('/{id}/mark-claimed', [App\Http\Controllers\Admin\UnclaimedController::class, 'markClaimed'])->name('mark-claimed');
        Route::post('/{id}/mark-disposed', [App\Http\Controllers\Admin\UnclaimedController::class, 'markDisposed'])->name('mark-disposed');
    });

    // ========================================================================
    // NOTIFICATIONS
    // ========================================================================
    Route::prefix('notifications')->name('notifications.')->group(function () {
        Route::get('/', [App\Http\Controllers\Admin\NotificationController::class, 'index'])->name('index');
        Route::get('/recent', [App\Http\Controllers\Admin\NotificationController::class, 'getRecent'])->name('recent');
        Route::get('/unread-count', [App\Http\Controllers\Admin\NotificationController::class, 'getUnreadCount'])->name('unread-count');
        Route::post('/{notification}/read', [App\Http\Controllers\Admin\NotificationController::class, 'markAsRead'])->name('mark-read');
        Route::post('/mark-all-read', [App\Http\Controllers\Admin\NotificationController::class, 'markAllAsRead'])->name('mark-all-read');
        Route::delete('/{notification}', [App\Http\Controllers\Admin\NotificationController::class, 'destroy'])->name('destroy');
        Route::post('/delete-all-read', [App\Http\Controllers\Admin\NotificationController::class, 'deleteAllRead'])->name('delete-all-read');
    });

    // ========================================================================
    // PICKUP REQUESTS
    // ========================================================================
    Route::prefix('pickups')->name('pickups.')->group(function () {
        // Confirm Dashboard
        Route::get('/confirm', [PickupRequestController::class, 'confirmView'])->name('confirm');
        Route::post('/{id}/confirm-quick', [PickupRequestController::class, 'confirmQuick'])->name('confirm-quick');
        Route::post('/confirm-all', [PickupRequestController::class, 'confirmAll'])->name('confirm-all');

        // List & View
        Route::get('/', [PickupRequestController::class, 'index'])->name('index');
        Route::get('/{id}', [PickupRequestController::class, 'show'])->name('show');

        // Actions
        Route::put('/{id}/accept', [PickupRequestController::class, 'accept'])->name('accept');
        Route::put('/{id}/en-route', [PickupRequestController::class, 'markEnRoute'])->name('en-route');
        Route::put('/{id}/picked-up', [PickupRequestController::class, 'markPickedUp'])->name('picked-up');
        Route::put('/{id}/cancel', [PickupRequestController::class, 'cancel'])->name('cancel');

        // Staff Assignment
        Route::put('/{id}/assign-staff', [PickupRequestController::class, 'assignStaff'])->name('assign-staff');

        // Bulk Actions
        Route::post('/bulk-accept', [PickupRequestController::class, 'bulkAccept'])->name('bulk-accept');

        // Statistics
        Route::get('/stats/data', [PickupRequestController::class, 'stats'])->name('stats');
    });

    // ========================================================================
    // REPORTS
    // ========================================================================
    Route::prefix('reports')->name('reports.')->group(function () {
        Route::get('/', [ReportController::class, 'index'])->name('index');
        Route::get('/revenue', [ReportController::class, 'revenue'])->name('revenue');
        Route::get('/orders', [ReportController::class, 'orders'])->name('orders');
        Route::get('/customers', [ReportController::class, 'customers'])->name('customers');
        Route::get('/branches', [ReportController::class, 'branches'])->name('branches');
        Route::post('/export', [ReportController::class, 'export'])->name('export');
    });

    // ========================================================================
    // RECEIPTS
    // ========================================================================
    Route::get('/receipts', function () {
        return view('admin.receipts.index');
    })->name('receipts.index');
    Route::get('/receipts/{order}', [ReceiptController::class, 'show'])->name('receipts.show');

    // ========================================================================
    // SETTINGS (ADDED DIRECT ROUTE FOR NAVIGATION)
    // ========================================================================
    Route::get('/settings', [SettingsController::class, 'index'])->name('settings'); // ADDED: Direct route for navigation

    Route::prefix('settings')->name('settings.')->group(function () {
        Route::post('/', [SettingsController::class, 'update'])->name('update');
        Route::post('/cleanup', [SettingsController::class, 'cleanup'])->name('cleanup');
        Route::post('/backup-cleanup', [SettingsController::class, 'cleanupBackups'])->name('backup-cleanup');
        Route::post('/fcm', [SettingsController::class, 'updateFCM'])->name('fcm');
        Route::post('/email', [SettingsController::class, 'updateEmail'])->name('email');
        Route::post('/sms', [SettingsController::class, 'updateSMS'])->name('sms');
        Route::post('/backup', [SettingsController::class, 'backup'])->name('backup');
        Route::get('/download-backup/{filename}', [SettingsController::class, 'downloadBackup'])->name('download-backup');
        Route::post('/notifications', [SettingsController::class, 'updateNotifications'])->name('notifications');
    });

    // ========================================================================
    // PROFILE
    // ========================================================================
    Route::get('/profile', [SettingsController::class, 'profile'])->name('profile');
    Route::post('/profile', [SettingsController::class, 'updateProfile'])->name('profile.update');
    Route::post('/profile/password', [SettingsController::class, 'updatePassword'])->name('profile.password');
});

// ============================================================================
// STAFF AUTHENTICATION ROUTES (Guest Only)
// ============================================================================

Route::middleware('guest')->prefix('staff')->name('staff.')->group(function () {
    Route::get('/login', [StaffLoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [StaffLoginController::class, 'login']);
});

// ============================================================================
// STAFF AUTHENTICATED ROUTES
// ============================================================================

Route::middleware(['auth:sanctum', 'staff'])->prefix('staff')->name('staff.')->group(function () {

    // ========================================================================
    // DASHBOARD
    // ========================================================================
    Route::get('/dashboard', [StaffDashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard/export', [StaffDashboardController::class, 'export'])->name('dashboard.export');

    // ========================================================================
    // AUTHENTICATION
    // ========================================================================
    Route::post('/logout', [StaffLoginController::class, 'logout'])->name('logout');

    // ========================================================================
    // ORDER MANAGEMENT
    // ========================================================================
    Route::resource('orders', StaffOrderController::class)->except(['destroy']);
    Route::post('/orders/{order}/status', [StaffOrderController::class, 'updateStatus'])->name('orders.update-status');
    Route::get('/orders/{order}/receipt', [StaffOrderController::class, 'receipt'])->name('orders.receipt');
    Route::get('/orders/{order}/record-payment', [StaffOrderController::class, 'recordPayment'])->name('orders.record-payment');

    // ========================================================================
    // CUSTOMER MANAGEMENT
    // ========================================================================
    Route::resource('customers', StaffCustomerController::class);

    // ========================================================================
    // STAFF PICKUP & DELIVERY MANAGEMENT
    // ========================================================================
    Route::prefix('pickups')->name('pickups.')->group(function () {
        // Static routes
        Route::get('/', [App\Http\Controllers\Staff\PickupRequestController::class, 'index'])->name('index');
        Route::get('/create', [App\Http\Controllers\Staff\PickupRequestController::class, 'create'])->name('create');
        Route::get('/customers/list', [App\Http\Controllers\Staff\PickupRequestController::class, 'customers'])->name('customers');
        Route::get('/confirm/pending', [App\Http\Controllers\Staff\PickupRequestController::class, 'confirm'])->name('confirm');
        Route::get('/stats/data', [App\Http\Controllers\Staff\PickupRequestController::class, 'stats'])->name('stats');

        // Store new pickup
        Route::post('/', [App\Http\Controllers\Staff\PickupRequestController::class, 'store'])->name('store');

        // Dynamic routes
        Route::get('/{pickup}', [App\Http\Controllers\Staff\PickupRequestController::class, 'show'])->name('show');

        // Status updates
        Route::post('/{pickup}/status', [App\Http\Controllers\Staff\PickupRequestController::class, 'updateStatus'])->name('update-status');
        Route::post('/{pickup}/accept', [App\Http\Controllers\Staff\PickupRequestController::class, 'accept'])->name('accept');
        Route::post('/{pickup}/en-route', [App\Http\Controllers\Staff\PickupRequestController::class, 'markEnRoute'])->name('en-route');
        Route::post('/{pickup}/picked-up', [App\Http\Controllers\Staff\PickupRequestController::class, 'markPickedUp'])->name('picked-up');
        Route::post('/{pickup}/cancel', [App\Http\Controllers\Staff\PickupRequestController::class, 'cancel'])->name('cancel');

        // GPS Location tracking
        Route::post('/{pickup}/update-location', [App\Http\Controllers\Staff\PickupRequestController::class, 'updateLocation'])->name('update-location');

        // Routing & Navigation for Staff (similar to admin logistics)
        Route::get('/{pickup}/route', [App\Http\Controllers\Staff\PickupRequestController::class, 'getRoute'])->name('route');
        Route::post('/{pickup}/start-navigation', [App\Http\Controllers\Staff\PickupRequestController::class, 'startNavigation'])->name('start-navigation');
    });

    // ========================================================================
    // UNCLAIMED LAUNDRY MANAGEMENT
    // ========================================================================
    Route::prefix('unclaimed')->name('unclaimed.')->group(function () {
        Route::get('/', [App\Http\Controllers\Staff\UnclaimedController::class, 'index'])->name('index');
        Route::get('/stats', [App\Http\Controllers\Staff\UnclaimedController::class, 'stats'])->name('stats');
        Route::get('/export', [App\Http\Controllers\Staff\UnclaimedController::class, 'export'])->name('export');
        Route::get('/{order}', [App\Http\Controllers\Staff\UnclaimedController::class, 'show'])->name('show');
        Route::post('/{order}/send-reminder', [App\Http\Controllers\Staff\UnclaimedController::class, 'sendReminder'])->name('send-reminder');
        Route::post('/{order}/mark-claimed', [App\Http\Controllers\Staff\UnclaimedController::class, 'markClaimed'])->name('mark-claimed');
        Route::post('/{order}/log-call', [App\Http\Controllers\Staff\UnclaimedController::class, 'logCallAttempt'])->name('log-call');
        Route::post('/bulk-reminders', [App\Http\Controllers\Staff\UnclaimedController::class, 'sendBulkReminders'])->name('bulk-reminders');
    });

    // ========================================================================
    // SERVICES (View Only)
    // ========================================================================
    Route::prefix('services')->name('services.')->group(function () {
        Route::get('/', [ServiceController::class, 'staffIndex'])->name('index');
        Route::get('/{service}', [ServiceController::class, 'staffShow'])->name('show');
    });

    // ========================================================================
    // BRANCHES (View Only)
    // ========================================================================
    Route::prefix('branches')->name('branches.')->group(function () {
        Route::get('/', [BranchController::class, 'staffIndex'])->name('index');
        Route::get('/{branch}', [BranchController::class, 'staffShow'])->name('show');
    });

    // ========================================================================
    // ANALYTICS (Staff View)
    // ========================================================================
    Route::prefix('analytics')->name('analytics.')->group(function () {
        Route::get('/', [AnalyticsController::class, 'staffIndex'])->name('index');
        Route::get('/export', [AnalyticsController::class, 'staffExport'])->name('export');
    });

    // ========================================================================
    // REPORTS (Staff View)
    // ========================================================================
    Route::prefix('reports')->name('reports.')->group(function () {
        Route::get('/', [ReportController::class, 'staffIndex'])->name('index');
        Route::get('/daily', [ReportController::class, 'staffDaily'])->name('daily');
        Route::get('/weekly', [ReportController::class, 'staffWeekly'])->name('weekly');
        Route::get('/monthly', [ReportController::class, 'staffMonthly'])->name('monthly');
        Route::post('/export', [ReportController::class, 'staffExport'])->name('export');
    });

    // ========================================================================
    // STAFF MANAGEMENT (View Only)
    // ========================================================================
    Route::prefix('users')->name('users.')->group(function () {
        Route::get('/', [StaffController::class, 'staffIndex'])->name('index');
        Route::get('/{user}', [StaffController::class, 'staffShow'])->name('show');
    });

    // ========================================================================
    // SETTINGS
    // ========================================================================
    Route::prefix('settings')->name('settings.')->group(function () {
        Route::get('/', [SettingsController::class, 'staffSettings'])->name('index');
        Route::post('/', [SettingsController::class, 'staffUpdateSettings'])->name('update');
        Route::get('/account', [SettingsController::class, 'staffAccount'])->name('account');
        Route::post('/account', [SettingsController::class, 'staffUpdateAccount'])->name('account.update');
    });

    // ========================================================================
    // PROFILE MANAGEMENT
    // ========================================================================
    Route::get('/profile', [SettingsController::class, 'staffProfile'])->name('profile');
    Route::post('/profile', [SettingsController::class, 'staffUpdateProfile'])->name('profile.update');
    Route::post('/profile/password', [SettingsController::class, 'staffUpdatePassword'])->name('profile.password');

    // ========================================================================
    // NOTIFICATIONS (Staff)
    // ========================================================================
    Route::prefix('notifications')->name('notifications.')->group(function () {
        Route::get('/', [App\Http\Controllers\Staff\NotificationController::class, 'index'])->name('index');
        Route::get('/recent', [App\Http\Controllers\Staff\NotificationController::class, 'getRecent'])->name('recent');
        Route::get('/unread-count', [App\Http\Controllers\Staff\NotificationController::class, 'getUnreadCount'])->name('unread-count');
        Route::post('/{id}/read', [App\Http\Controllers\Staff\NotificationController::class, 'markAsRead'])->name('mark-read');
        Route::post('/mark-all-read', [App\Http\Controllers\Staff\NotificationController::class, 'markAllAsRead'])->name('mark-all-read');
        Route::delete('/{id}', [App\Http\Controllers\Staff\NotificationController::class, 'destroy'])->name('destroy');
        Route::delete('/clear/read', [App\Http\Controllers\Staff\NotificationController::class, 'deleteAllRead'])->name('delete-read');
    });

    // ========================================================================
    // SEARCH (Global)
    // ========================================================================
    Route::get('/search', function () {
        $query = request('q');
        return view('staff.search.results', compact('query'));
    })->name('search');
});
