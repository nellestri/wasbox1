<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\BranchController;
use App\Http\Controllers\Api\PickupController;
use App\Http\Controllers\Api\ServiceController;
use App\Http\Controllers\Api\CustomerController;
use App\Http\Controllers\Api\PromotionController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\AdminNotificationController;
use App\Http\Controllers\Api\StaffNotificationController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

Route::prefix('v1')->group(function () {

    // ========================================
    // PUBLIC ROUTES (No Authentication Required)
    // ========================================

    // Authentication
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
    Route::post('/reset-password', [AuthController::class, 'resetPassword']);

    // Branches (Public)
    Route::prefix('branches')->group(function () {
        Route::get('/', [BranchController::class, 'index']);
        Route::get('/nearest', [BranchController::class, 'nearest']); // Must be before {id}
        Route::get('/{id}', [BranchController::class, 'show']);
        Route::get('/{branch}/operating-hours', [BranchController::class, 'operatingHours']);
    });

    // Services (Public)
    Route::get('/services', [ServiceController::class, 'index']);

    // Promotions (Public)
    Route::prefix('promotions')->group(function () {
        Route::get('/', [PromotionController::class, 'index']);
        Route::get('/featured', [PromotionController::class, 'featured']); // Must be before {id}
        Route::get('/validate-code', [PromotionController::class, 'validateCode']); // New: validate promo code and preview discount
        Route::get('/applicable', [PromotionController::class, 'applicable']); // New: list applicable promos with preview
        Route::get('/{id}', [PromotionController::class, 'show']);
    });

    Route::get('/health', function () {
        return response()->json([
            'success' => true,
            'message' => 'WashBox API is running',
            'version' => '1.0.0',
            'timestamp' => now()->toIso8601String(),
        ]);
    });

    // ========================================
    // PROTECTED ROUTES (Require Authentication)
    // ========================================

    Route::middleware('auth:sanctum')->group(function () {

        // Authentication & Profile
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/user', [AuthController::class, 'user']);
        Route::get('/profile', [CustomerController::class, 'getProfile']);
        Route::put('/profile', [CustomerController::class, 'updateProfile']);
        Route::put('/password', [AuthController::class, 'changePassword']);

        // Customer Statistics & Info
        Route::prefix('customer')->group(function () {
            Route::get('/stats', [CustomerController::class, 'getStats']);
            Route::get('/active-orders', [CustomerController::class, 'getActiveOrders']);
            Route::get('/latest-pickup', [CustomerController::class, 'getLatestPickup']);
        });

        // Orders
        Route::prefix('orders')->group(function () {
            Route::get('/', [OrderController::class, 'index']);
            Route::post('/', [OrderController::class, 'store']);
            Route::get('/{id}', [OrderController::class, 'show']);
            Route::put('/{id}/cancel', [OrderController::class, 'cancel']); // Add this
        });

        // Pickup Requests (Customer)
        Route::prefix('pickups')->group(function () {
            Route::get('/', [PickupController::class, 'index']);          // Get all customer's pickup requests
            Route::post('/', [PickupController::class, 'store']);         // Create new pickup request
            Route::get('/{id}', [PickupController::class, 'show']);       // Get single pickup details
            Route::put('/{id}/cancel', [PickupController::class, 'cancel']); // Cancel pickup request
        });

        // ========================================
        // NOTIFICATIONS - COMPLETE ENDPOINTS
        // ========================================
        Route::prefix('notifications')->group(function () {
            // Get all notifications (with optional unread_only filter)
            Route::get('/', [NotificationController::class, 'index']);

            // Get unread count - THIS IS THE MISSING ENDPOINT!
            Route::get('/unread-count', [NotificationController::class, 'unreadCount']);

            // Mark all as read
            Route::post('/mark-all-read', [NotificationController::class, 'markAllAsRead']);

            // Mark single notification as read
            Route::post('/{id}/read', [NotificationController::class, 'markAsRead']);

            // Delete single notification
            Route::delete('/{id}', [NotificationController::class, 'destroy']);

            // Clear all read notifications
            Route::delete('/clear-read', [NotificationController::class, 'clearRead']);
        });
    });

    // ========================================
    // STAFF/OWNER ROUTES (Admin Actions)
    // ========================================

    Route::middleware(['auth:sanctum'])->prefix('staff')->group(function () {

        // Pickup Management (Staff/Owner actions)
        Route::prefix('pickups')->group(function () {
            Route::put('/{id}/accept', [PickupController::class, 'accept']);           // Accept pickup request
            Route::put('/{id}/en-route', [PickupController::class, 'markEnRoute']);    // Mark rider en route
            Route::put('/{id}/picked-up', [PickupController::class, 'markPickedUp']); // Mark as picked up
            Route::put('/{id}/link-order', [PickupController::class, 'linkOrder']);    // Link to order after pickup
        });

        // TODO: Add more staff routes here as needed
        // - Order management
        // - Customer management
        // - Reports & analytics
    });
});

Route::middleware('auth:staff')->group(function () {
    Route::get('/staff/notifications', [StaffNotificationController::class, 'index']);
    Route::get('/staff/notifications/unread-count', [StaffNotificationController::class, 'unreadCount']);
    Route::post('/staff/notifications/{id}/read', [StaffNotificationController::class, 'markAsRead']);
    Route::post('/staff/notifications/read-all', [StaffNotificationController::class, 'markAllAsRead']);
    Route::delete('/staff/notifications/{id}', [StaffNotificationController::class, 'delete']);
    Route::delete('/staff/notifications/clear-read', [StaffNotificationController::class, 'clearRead']);
});

Route::middleware('auth:admin')->group(function () {
    Route::get('/admin/notifications', [AdminNotificationController::class, 'index']);
    Route::get('/admin/notifications/unread-count', [AdminNotificationController::class, 'unreadCount']);
    Route::post('/admin/notifications/{id}/read', [AdminNotificationController::class, 'markAsRead']);
    Route::post('/admin/notifications/read-all', [AdminNotificationController::class, 'markAllAsRead']);
    Route::delete('/admin/notifications/{id}', [AdminNotificationController::class, 'delete']);
    Route::delete('/admin/notifications/clear-read', [AdminNotificationController::class, 'clearRead']);
});
