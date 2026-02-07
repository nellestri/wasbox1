<?php

namespace App\Observers;

use App\Models\Order;
use App\Models\Notification;
use App\Models\AdminNotification;
use App\Models\StaffNotification;
use App\Models\User;
use Carbon\Carbon;

class OrderObserver
{
    /**
     * Handle the Order "created" event.
     */
    public function created(Order $order): void
    {
        $order->loadMissing('customer', 'branch', 'service');

        // 🔔 NOTIFY ADMIN: New order received
        AdminNotification::create([
            'type' => 'new_order',
            'title' => 'New Order Received',
            'message' => "Order #{$order->tracking_number} from {$order->customer->name} - ₱" . number_format($order->total_amount, 2),
            'icon' => 'cart-plus',
            'color' => 'success',
            'link' => route('admin.orders.show', $order->id),
            'data' => [
                'order_id' => $order->id,
                'tracking_number' => $order->tracking_number,
                'customer_name' => $order->customer->name,
                'total_amount' => $order->total_amount,
                'service' => $order->service?->name,
            ],
            'branch_id' => $order->branch_id,
            'user_id' => $order->created_by,
        ]);

        // 🔔 NOTIFY CUSTOMER: Order received
        Notification::create([
            'customer_id' => $order->customer_id,
            'type' => 'order_received',
            'title' => 'Order Received! 📦',
            'body' => "Your laundry order #{$order->tracking_number} has been received. We'll notify you when it's ready!",
            'order_id' => $order->id,
            'is_read' => false,
        ]);

        // 🔔 NOTIFY STAFF IN BRANCH: New order received
        if ($order->branch_id) {
            $staffUsers = User::where('branch_id', $order->branch_id)
                ->where('role', 'staff')
                ->where('is_active', true)
                ->get();

            foreach ($staffUsers as $staff) {
                StaffNotification::create([
                    'user_id' => $staff->id,
                    'type' => 'new_order',
                    'title' => 'New Order Received',
                    'message' => "Order #{$order->tracking_number} from {$order->customer->name} - ₱" . number_format($order->total_amount, 2),
                    'icon' => 'cart-plus',
                    'color' => 'info',
                    'link' => route('staff.orders.show', $order->id),
                    'data' => [
                        'order_id' => $order->id,
                        'tracking_number' => $order->tracking_number,
                        'customer_name' => $order->customer->name,
                        'total_amount' => $order->total_amount,
                        'service' => $order->service?->name,
                    ],
                    'branch_id' => $order->branch_id,
                ]);
            }
        }
    }

    /**
     * Handle the Order "updated" event.
     */
    public function updated(Order $order): void
    {
        // Only process status changes
        if (!$order->isDirty('status')) {
            return;
        }

        $order->loadMissing('customer', 'branch', 'staff');
        $newStatus = $order->status;
        $oldStatus = $order->getOriginal('status');

        switch ($newStatus) {
            // ========================================
            // WASHING IN PROGRESS
            // ========================================
            case 'washing':
                // 🔔 NOTIFY CUSTOMER: Washing started
                Notification::create([
                    'customer_id' => $order->customer_id,
                    'type' => 'washing_started',
                    'title' => 'Washing Started! 🧼',
                    'body' => "Your laundry (Order #{$order->tracking_number}) is now being washed.",
                    'order_id' => $order->id,
                    'is_read' => false,
                ]);

                // 🔔 NOTIFY STAFF ASSIGNED: Order washing started
                if ($order->staff_id) {
                    StaffNotification::create([
                        'user_id' => $order->staff_id,
                        'type' => 'washing_started',
                        'title' => 'Order Washing Started',
                        'message' => "Order #{$order->tracking_number} washing in progress",
                        'icon' => 'droplet',
                        'color' => 'info',
                        'link' => route('staff.orders.show', $order->id),
                        'data' => [
                            'order_id' => $order->id,
                            'tracking_number' => $order->tracking_number,
                            'customer_name' => $order->customer->name,
                        ],
                        'branch_id' => $order->branch_id,
                    ]);
                }
                break;

            // ========================================
            // ORDER READY - Start unclaimed tracking
            // ========================================
            case 'ready':
                // 🔔 NOTIFY CUSTOMER: Laundry is ready!
                Notification::create([
                    'customer_id' => $order->customer_id,
                    'type' => 'order_ready',
                    'title' => 'Laundry Ready for Pickup! 👕',
                    'body' => "Great news! Your laundry (Order #{$order->tracking_number}) is ready. Please pick it up at {$order->branch->name}.",
                    'order_id' => $order->id,
                    'is_read' => false,
                ]);

                // 🔔 NOTIFY ADMIN: Order ready
                AdminNotification::create([
                    'type' => 'order_ready',
                    'title' => 'Order Ready for Pickup',
                    'message' => "Order #{$order->tracking_number} is ready for customer pickup",
                    'icon' => 'bag-check',
                    'color' => 'info',
                    'link' => route('admin.orders.show', $order->id),
                    'branch_id' => $order->branch_id,
                ]);

                // 🔔 NOTIFY STAFF IN BRANCH: Order ready
                if ($order->branch_id) {
                    $staffUsers = User::where('branch_id', $order->branch_id)
                        ->where('role', 'staff')
                        ->where('is_active', true)
                        ->get();

                    foreach ($staffUsers as $staff) {
                        StaffNotification::create([
                            'user_id' => $staff->id,
                            'type' => 'order_ready',
                            'title' => 'Order Ready for Pickup',
                            'message' => "Order #{$order->tracking_number} is ready for customer pickup",
                            'icon' => 'bag-check',
                            'color' => 'info',
                            'link' => route('staff.orders.show', $order->id),
                            'data' => [
                                'order_id' => $order->id,
                                'tracking_number' => $order->tracking_number,
                                'customer_name' => $order->customer->name,
                            ],
                            'branch_id' => $order->branch_id,
                        ]);
                    }
                }
                break;

            // ========================================
            // PAYMENT RECEIVED
            // ========================================
            case 'paid':
                // 🔔 NOTIFY CUSTOMER: Payment confirmed
                Notification::create([
                    'customer_id' => $order->customer_id,
                    'type' => 'payment_received',
                    'title' => 'Payment Confirmed! 💰',
                    'body' => "Payment of ₱" . number_format($order->total_amount, 2) . " received for order #{$order->tracking_number}. Thank you!",
                    'order_id' => $order->id,
                    'is_read' => false,
                ]);

                // 🔔 NOTIFY ADMIN: Payment received
                AdminNotification::create([
                    'type' => 'payment',
                    'title' => 'Payment Received',
                    'message' => "₱" . number_format($order->total_amount, 2) . " received for order #{$order->tracking_number}",
                    'icon' => 'currency-dollar',
                    'color' => 'success',
                    'link' => route('admin.orders.show', $order->id),
                    'branch_id' => $order->branch_id,
                ]);

                // 🔔 NOTIFY STAFF ASSIGNED: Payment received
                if ($order->staff_id) {
                    StaffNotification::create([
                        'user_id' => $order->staff_id,
                        'type' => 'payment_received',
                        'title' => 'Payment Received',
                        'message' => "₱" . number_format($order->total_amount, 2) . " received for order #{$order->tracking_number}",
                        'icon' => 'currency-dollar',
                        'color' => 'success',
                        'link' => route('staff.orders.show', $order->id),
                        'data' => [
                            'order_id' => $order->id,
                            'tracking_number' => $order->tracking_number,
                            'amount' => $order->total_amount,
                        ],
                        'branch_id' => $order->branch_id,
                    ]);
                }
                break;

            // ========================================
            // ORDER COMPLETED
            // ========================================
            case 'completed':
                // 🔔 NOTIFY CUSTOMER: Order completed
                Notification::create([
                    'customer_id' => $order->customer_id,
                    'type' => 'order_completed',
                    'title' => 'Order Completed! ✅',
                    'body' => "Thank you for choosing WashBox! Your order #{$order->tracking_number} is complete. See you again!",
                    'order_id' => $order->id,
                    'is_read' => false,
                ]);

                // 🔔 NOTIFY ADMIN: Order completed
                AdminNotification::create([
                    'type' => 'order_completed',
                    'title' => 'Order Completed',
                    'message' => "Order #{$order->tracking_number} claimed by customer",
                    'icon' => 'check-circle',
                    'color' => 'success',
                    'link' => route('admin.orders.show', $order->id),
                    'branch_id' => $order->branch_id,
                ]);

                // 🔔 NOTIFY STAFF ASSIGNED: Order completed
                if ($order->staff_id) {
                    StaffNotification::create([
                        'user_id' => $order->staff_id,
                        'type' => 'order_completed',
                        'title' => 'Order Completed',
                        'message' => "Order #{$order->tracking_number} claimed by customer",
                        'icon' => 'check-circle',
                        'color' => 'success',
                        'link' => route('staff.orders.show', $order->id),
                        'data' => [
                            'order_id' => $order->id,
                            'tracking_number' => $order->tracking_number,
                            'customer_name' => $order->customer->name,
                        ],
                        'branch_id' => $order->branch_id,
                    ]);
                }
                break;

            // ========================================
            // ORDER CANCELLED
            // ========================================
            case 'cancelled':
                $reason = $order->cancellation_reason ?? 'No reason provided';

                // 🔔 NOTIFY CUSTOMER: Order cancelled
                Notification::create([
                    'customer_id' => $order->customer_id,
                    'type' => 'order_cancelled',
                    'title' => 'Order Cancelled ❌',
                    'body' => "Your order #{$order->tracking_number} has been cancelled. Reason: {$reason}",
                    'order_id' => $order->id,
                    'is_read' => false,
                ]);

                // 🔔 NOTIFY ADMIN: Order cancelled
                AdminNotification::create([
                    'type' => 'order_cancelled',
                    'title' => 'Order Cancelled',
                    'message' => "Order #{$order->tracking_number} cancelled. Reason: {$reason}",
                    'icon' => 'x-circle',
                    'color' => 'danger',
                    'link' => route('admin.orders.show', $order->id),
                    'branch_id' => $order->branch_id,
                ]);

                // 🔔 NOTIFY STAFF ASSIGNED: Order cancelled
                if ($order->staff_id) {
                    StaffNotification::create([
                        'user_id' => $order->staff_id,
                        'type' => 'order_cancelled',
                        'title' => 'Order Cancelled',
                        'message' => "Order #{$order->tracking_number} cancelled. Reason: {$reason}",
                        'icon' => 'x-circle',
                        'color' => 'danger',
                        'link' => route('staff.orders.show', $order->id),
                        'data' => [
                            'order_id' => $order->id,
                            'tracking_number' => $order->tracking_number,
                            'reason' => $reason,
                        ],
                        'branch_id' => $order->branch_id,
                    ]);
                }
                break;

            // ========================================
            // PICKUP ASSIGNED
            // ========================================
            case 'pickup_assigned':
                // 🔔 NOTIFY STAFF ASSIGNED: Pickup assigned
                if ($order->staff_id) {
                    StaffNotification::create([
                        'user_id' => $order->staff_id,
                        'type' => 'pickup_assigned',
                        'title' => 'Pickup Assigned',
                        'message' => "You have been assigned a pickup for Order #{$order->tracking_number}",
                        'icon' => 'truck',
                        'color' => 'primary',
                        'link' => route('staff.orders.show', $order->id),
                        'data' => [
                            'order_id' => $order->id,
                            'tracking_number' => $order->tracking_number,
                            'customer_name' => $order->customer->name,
                            'address' => $order->pickup_address,
                        ],
                        'branch_id' => $order->branch_id,
                    ]);
                }
                break;
        }

        // ========================================
        // UNCLAIMED ORDER ALERTS
        // ========================================
        $this->checkUnclaimedOrder($order);
    }

    /**
     * Check for unclaimed orders and notify staff
     */
    private function checkUnclaimedOrder(Order $order): void
    {
        // Only check for orders that are ready but not claimed
        if ($order->status !== 'ready' || $order->claimed_at) {
            return;
        }

        $order->loadMissing('customer', 'branch');

        // Calculate days unclaimed
        $readyDate = $order->updated_at ?? $order->created_at;
        $daysUnclaimed = Carbon::now()->diffInDays($readyDate);

        // Send alerts based on days unclaimed
        if ($daysUnclaimed >= 1 && $daysUnclaimed < 3) {
            // 🔔 NOTIFY ALL STAFF IN BRANCH: Order unclaimed for 1 day
            $staffUsers = User::where('branch_id', $order->branch_id)
                ->where('role', 'staff')
                ->where('is_active', true)
                ->get();

            foreach ($staffUsers as $staff) {
                StaffNotification::create([
                    'user_id' => $staff->id,
                    'type' => 'unclaimed_order',
                    'title' => 'Order Unclaimed (1 Day)',
                    'message' => "Order #{$order->tracking_number} has been ready for 1 day",
                    'icon' => 'clock',
                    'color' => 'warning',
                    'link' => route('staff.orders.show', $order->id),
                    'data' => [
                        'order_id' => $order->id,
                        'tracking_number' => $order->tracking_number,
                        'customer_name' => $order->customer->name,
                        'days_unclaimed' => 1,
                    ],
                    'branch_id' => $order->branch_id,
                ]);
            }
        } elseif ($daysUnclaimed >= 3) {
            // 🔔 NOTIFY ALL STAFF IN BRANCH: Urgent unclaimed order
            $staffUsers = User::where('branch_id', $order->branch_id)
                ->where('role', 'staff')
                ->where('is_active', true)
                ->get();

            foreach ($staffUsers as $staff) {
                StaffNotification::create([
                    'user_id' => $staff->id,
                    'type' => 'urgent_unclaimed',
                    'title' => 'URGENT: Order Unclaimed (3+ Days)',
                    'message' => "Order #{$order->tracking_number} has been unclaimed for {$daysUnclaimed} days!",
                    'icon' => 'exclamation-triangle',
                    'color' => 'danger',
                    'link' => route('staff.orders.show', $order->id),
                    'data' => [
                        'order_id' => $order->id,
                        'tracking_number' => $order->tracking_number,
                        'customer_name' => $order->customer->name,
                        'days_unclaimed' => $daysUnclaimed,
                    ],
                    'branch_id' => $order->branch_id,
                ]);
            }

            // 🔔 ALSO NOTIFY ADMIN
            AdminNotification::create([
                'type' => 'unclaimed_urgent',
                'title' => 'URGENT: Unclaimed Order',
                'message' => "Order #{$order->tracking_number} unclaimed for {$daysUnclaimed} days",
                'icon' => 'exclamation-triangle',
                'color' => 'danger',
                'link' => route('admin.orders.show', $order->id),
                'data' => [
                    'order_id' => $order->id,
                    'tracking_number' => $order->tracking_number,
                    'customer_name' => $order->customer->name,
                    'days_unclaimed' => $daysUnclaimed,
                ],
                'branch_id' => $order->branch_id,
            ]);
        }
    }

    /**
     * Handle the Order "assigned" event (when staff is assigned)
     */
    public function assigned(Order $order, $staffId): void
    {
        $order->loadMissing('customer', 'branch');

        // 🔔 NOTIFY STAFF: Order assigned to them
        StaffNotification::create([
            'user_id' => $staffId,
            'type' => 'order_assigned',
            'title' => 'Order Assigned to You',
            'message' => "Order #{$order->tracking_number} has been assigned to you",
            'icon' => 'person-badge',
            'color' => 'primary',
            'link' => route('staff.orders.show', $order->id),
            'data' => [
                'order_id' => $order->id,
                'tracking_number' => $order->tracking_number,
                'customer_name' => $order->customer->name,
            ],
            'branch_id' => $order->branch_id,
        ]);

        // 🔔 NOTIFY CUSTOMER: Staff assigned
        Notification::create([
            'customer_id' => $order->customer_id,
            'type' => 'staff_assigned',
            'title' => 'Staff Assigned! 👨‍💼',
            'body' => "A staff member has been assigned to handle your order #{$order->tracking_number}",
            'order_id' => $order->id,
            'is_read' => false,
        ]);
    }

    /**
     * Handle the Order "staff changed" event
     */
    public function staffChanged(Order $order): void
    {
        $order->loadMissing('customer', 'branch');

        // 🔔 NOTIFY NEW STAFF: Order assigned
        if ($order->staff_id) {
            StaffNotification::create([
                'user_id' => $order->staff_id,
                'type' => 'order_assigned',
                'title' => 'Order Assigned to You',
                'message' => "Order #{$order->tracking_number} has been assigned to you",
                'icon' => 'person-badge',
                'color' => 'primary',
                'link' => route('staff.orders.show', $order->id),
                'data' => [
                    'order_id' => $order->id,
                    'tracking_number' => $order->tracking_number,
                    'customer_name' => $order->customer->name,
                ],
                'branch_id' => $order->branch_id,
            ]);
        }
    }
}
