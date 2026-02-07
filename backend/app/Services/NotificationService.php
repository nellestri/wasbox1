<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\User;
use App\Models\Order;
use App\Models\PickupRequest;
use App\Models\Customer;

class NotificationService
{
    /**
     * Send notification to a specific user (staff/admin)
     */
    public static function sendToUser(
        int $userId,
        string $type,
        string $title,
        string $body,
        ?int $orderId = null,
        ?int $pickupRequestId = null,
        ?int $customerId = null,
        array $data = []
    ): Notification {
        return Notification::create([
            'user_id' => $userId,
            'customer_id' => $customerId,
            'type' => $type,
            'title' => $title,
            'body' => $body,
            'order_id' => $orderId,
            'pickup_request_id' => $pickupRequestId,
            'data' => $data,
            'is_read' => false,
        ]);
    }

    /**
     * Send notification to all staff in a branch
     */
    public static function sendToBranchStaff(
        int $branchId,
        string $type,
        string $title,
        string $body,
        ?int $orderId = null,
        ?int $pickupRequestId = null,
        ?int $customerId = null,
        array $data = []
    ): int {
        $staffUsers = User::where('branch_id', $branchId)
            ->where('role', 'staff')
            ->where('is_active', true)
            ->get();

        $count = 0;
        foreach ($staffUsers as $staff) {
            self::sendToUser(
                $staff->id,
                $type,
                $title,
                $body,
                $orderId,
                $pickupRequestId,
                $customerId,
                $data
            );
            $count++;
        }

        return $count;
    }

    /**
     * Send notification to all active staff
     */
    public static function sendToAllStaff(
        string $type,
        string $title,
        string $body,
        ?int $orderId = null,
        ?int $pickupRequestId = null,
        ?int $customerId = null,
        array $data = []
    ): int {
        $staffUsers = User::where('role', 'staff')
            ->where('is_active', true)
            ->get();

        $count = 0;
        foreach ($staffUsers as $staff) {
            self::sendToUser(
                $staff->id,
                $type,
                $title,
                $body,
                $orderId,
                $pickupRequestId,
                $customerId,
                $data
            );
            $count++;
        }

        return $count;
    }

    /**
     * Send notification to all admins
     */
    public static function sendToAllAdmins(
        string $type,
        string $title,
        string $body,
        ?int $orderId = null,
        ?int $pickupRequestId = null,
        ?int $customerId = null,
        array $data = []
    ): int {
        $admins = User::where('role', 'admin')
            ->where('is_active', true)
            ->get();

        $count = 0;
        foreach ($admins as $admin) {
            self::sendToUser(
                $admin->id,
                $type,
                $title,
                $body,
                $orderId,
                $pickupRequestId,
                $customerId,
                $data
            );
            $count++;
        }

        return $count;
    }

    /**
     * Send notification to customer
     */
    public static function sendToCustomer(
        int $customerId,
        string $type,
        string $title,
        string $body,
        ?int $orderId = null,
        ?int $pickupRequestId = null,
        array $data = []
    ): Notification {
        return Notification::create([
            'customer_id' => $customerId,
            'user_id' => null,
            'type' => $type,
            'title' => $title,
            'body' => $body,
            'order_id' => $orderId,
            'pickup_request_id' => $pickupRequestId,
            'data' => $data,
            'is_read' => false,
        ]);
    }

    // ========================================================================
    // PICKUP REQUEST NOTIFICATIONS
    // ========================================================================

    /**
     * Notify staff about new pickup request
     */
    public static function notifyNewPickupRequest(PickupRequest $pickup): int
    {
        $customer = $pickup->customer;
        $customerName = $customer ? $customer->name : 'A customer';

        $title = 'New Pickup Request';
        $body = "{$customerName} has requested a pickup at {$pickup->pickup_address}";

        // If pickup has a branch, notify only that branch's staff
        if ($pickup->branch_id) {
            return self::sendToBranchStaff(
                $pickup->branch_id,
                'pickup_request',
                $title,
                $body,
                null,
                $pickup->id,
                $pickup->customer_id,
                [
                    'pickup_id' => $pickup->id,
                    'customer_name' => $customerName,
                    'address' => $pickup->pickup_address,
                    'scheduled_at' => $pickup->scheduled_pickup_time?->format('M j, Y g:i A'),
                ]
            );
        }

        // Otherwise notify all staff
        return self::sendToAllStaff(
            'pickup_request',
            $title,
            $body,
            null,
            $pickup->id,
            $pickup->customer_id,
            [
                'pickup_id' => $pickup->id,
                'customer_name' => $customerName,
                'address' => $pickup->pickup_address,
                'scheduled_at' => $pickup->scheduled_pickup_time?->format('M j, Y g:i A'),
            ]
        );
    }

    /**
     * Notify customer that pickup was accepted
     */
    public static function notifyPickupAccepted(PickupRequest $pickup): Notification
    {
        return self::sendToCustomer(
            $pickup->customer_id,
            'pickup_accepted',
            'Pickup Request Accepted',
            'Your pickup request has been accepted. Our staff will arrive at the scheduled time.',
            null,
            $pickup->id,
            ['pickup_id' => $pickup->id]
        );
    }

    /**
     * Notify customer that staff is en route
     */
    public static function notifyPickupEnRoute(PickupRequest $pickup): Notification
    {
        return self::sendToCustomer(
            $pickup->customer_id,
            'pickup_en_route',
            'Staff En Route',
            'Our staff is on the way to pick up your laundry.',
            null,
            $pickup->id,
            ['pickup_id' => $pickup->id]
        );
    }

    /**
     * Notify customer that pickup is completed
     */
    public static function notifyPickupCompleted(PickupRequest $pickup, ?Order $order = null): Notification
    {
        $body = 'Your laundry has been picked up successfully.';
        if ($order) {
            $body .= " Order #{$order->tracking_number} has been created.";
        }

        return self::sendToCustomer(
            $pickup->customer_id,
            'pickup_completed',
            'Pickup Completed',
            $body,
            $order?->id,
            $pickup->id,
            [
                'pickup_id' => $pickup->id,
                'order_id' => $order?->id,
                'tracking_number' => $order?->tracking_number,
            ]
        );
    }

    // ========================================================================
    // ORDER NOTIFICATIONS
    // ========================================================================

    /**
     * Notify staff about new order
     */
    public static function notifyNewOrder(Order $order): int
    {
        $customer = $order->customer;
        $customerName = $customer ? $customer->name : 'Walk-in customer';

        $title = 'New Order Received';
        $body = "New order #{$order->tracking_number} from {$customerName}";

        if ($order->branch_id) {
            return self::sendToBranchStaff(
                $order->branch_id,
                'order_received',
                $title,
                $body,
                $order->id,
                null,
                $order->customer_id,
                [
                    'order_id' => $order->id,
                    'tracking_number' => $order->tracking_number,
                    'customer_name' => $customerName,
                ]
            );
        }

        return self::sendToAllStaff(
            'order_received',
            $title,
            $body,
            $order->id,
            null,
            $order->customer_id,
            [
                'order_id' => $order->id,
                'tracking_number' => $order->tracking_number,
                'customer_name' => $customerName,
            ]
        );
    }

    /**
     * Notify customer about order status change
     */
    public static function notifyOrderStatusChanged(Order $order, string $oldStatus, string $newStatus): ?Notification
    {
        if (!$order->customer_id) {
            return null; // Walk-in customer, no notification
        }

        $statusMessages = [
            'processing' => [
                'title' => 'Order Being Processed',
                'body' => "Your order #{$order->tracking_number} is now being processed.",
            ],
            'washing' => [
                'title' => 'Washing In Progress',
                'body' => "Your laundry is being washed. Order #{$order->tracking_number}.",
            ],
            'drying' => [
                'title' => 'Drying In Progress',
                'body' => "Your laundry is being dried. Order #{$order->tracking_number}.",
            ],
            'folding' => [
                'title' => 'Folding In Progress',
                'body' => "Your laundry is being folded. Order #{$order->tracking_number}.",
            ],
            'ready_for_pickup' => [
                'title' => 'Order Ready for Pickup',
                'body' => "Your order #{$order->tracking_number} is ready for pickup!",
            ],
            'ready_for_delivery' => [
                'title' => 'Order Ready for Delivery',
                'body' => "Your order #{$order->tracking_number} is ready and will be delivered soon.",
            ],
            'out_for_delivery' => [
                'title' => 'Out for Delivery',
                'body' => "Your order #{$order->tracking_number} is out for delivery!",
            ],
            'completed' => [
                'title' => 'Order Completed',
                'body' => "Your order #{$order->tracking_number} has been completed. Thank you!",
            ],
            'cancelled' => [
                'title' => 'Order Cancelled',
                'body' => "Your order #{$order->tracking_number} has been cancelled.",
            ],
        ];

        if (!isset($statusMessages[$newStatus])) {
            return null;
        }

        $message = $statusMessages[$newStatus];

        return self::sendToCustomer(
            $order->customer_id,
            'order_' . $newStatus,
            $message['title'],
            $message['body'],
            $order->id,
            null,
            [
                'order_id' => $order->id,
                'tracking_number' => $order->tracking_number,
                'old_status' => $oldStatus,
                'new_status' => $newStatus,
            ]
        );
    }

    /**
     * Notify customer about payment received
     */
    public static function notifyPaymentReceived(Order $order, float $amount): ?Notification
    {
        if (!$order->customer_id) {
            return null;
        }

        return self::sendToCustomer(
            $order->customer_id,
            'payment_received',
            'Payment Received',
            "Payment of ₱" . number_format($amount, 2) . " received for order #{$order->tracking_number}.",
            $order->id,
            null,
            [
                'order_id' => $order->id,
                'tracking_number' => $order->tracking_number,
                'amount' => $amount,
            ]
        );
    }

    // ========================================================================
    // UNCLAIMED ORDER NOTIFICATIONS
    // ========================================================================

    /**
     * Notify staff about unclaimed order
     */
    public static function notifyUnclaimedOrder(Order $order, int $daysUnclaimed): int
    {
        $customer = $order->customer;
        $customerName = $customer ? $customer->name : 'Unknown';

        $title = 'Unclaimed Order Reminder';
        $body = "Order #{$order->tracking_number} ({$customerName}) has been unclaimed for {$daysUnclaimed} days.";

        if ($order->branch_id) {
            return self::sendToBranchStaff(
                $order->branch_id,
                'unclaimed_reminder',
                $title,
                $body,
                $order->id,
                null,
                $order->customer_id,
                [
                    'order_id' => $order->id,
                    'tracking_number' => $order->tracking_number,
                    'days_unclaimed' => $daysUnclaimed,
                ]
            );
        }

        return self::sendToAllStaff(
            'unclaimed_reminder',
            $title,
            $body,
            $order->id,
            null,
            $order->customer_id,
            [
                'order_id' => $order->id,
                'tracking_number' => $order->tracking_number,
                'days_unclaimed' => $daysUnclaimed,
            ]
        );
    }
}
