<?php

namespace App\Models;

use App\Services\FCMService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class Notification extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'type',
        'title',
        'body',
        'data',
        'order_id',
        'pickup_request_id',
        'fcm_message_id',
        'fcm_status',
        'fcm_error',
        'fcm_sent_at',
        'is_read',
        'read_at',
    ];

    protected $casts = [
        'data' => 'array',
        'is_read' => 'boolean',
        'read_at' => 'datetime',
        'fcm_sent_at' => 'datetime',
    ];

    // ===========================
    // RELATIONSHIPS
    // ===========================

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function pickupRequest()
    {
        return $this->belongsTo(PickupRequest::class);
    }

    // ===========================
    // SCOPES
    // ===========================

    public function scopeUnread($query)
    {
        return $query->where('is_read', false);
    }

    public function scopeRead($query)
    {
        return $query->where('is_read', true);
    }

    public function scopeForCustomer($query, $customerId)
    {
        return $query->where('customer_id', $customerId);
    }

    public function scopeOfType($query, $type)
    {
        return $query->where('type', $type);
    }

    public function scopeRecent($query)
    {
        return $query->orderBy('created_at', 'desc');
    }

    public function scopeFcmPending($query)
    {
        return $query->whereNull('fcm_status')->orWhere('fcm_status', 'pending');
    }

    public function scopeFcmSent($query)
    {
        return $query->where('fcm_status', 'sent');
    }

    public function scopeFcmFailed($query)
    {
        return $query->where('fcm_status', 'failed');
    }

    // ===========================
    // ACCESSORS
    // ===========================

    public function getIconAttribute()
    {
        $icons = [
            // Order notifications
            'order_received' => '📦',
            'order_ready' => '✅',
            'order_completed' => '🎉',
            'order_cancelled' => '❌',
            'payment_received' => '💰',

            // Pickup notifications
            'pickup_submitted' => '📬',
            'pickup_accepted' => '👍',
            'pickup_en_route' => '🚗',
            'pickup_completed' => '📦',
            'pickup_cancelled' => '❌',

            // Delivery notifications
            'delivery_scheduled' => '📅',
            'delivery_en_route' => '🚚',
            'delivery_completed' => '✅',
            'delivery_failed' => '❌',

            // Unclaimed notifications
            'unclaimed_reminder' => '⏰',
            'unclaimed_day1' => '📌',
            'unclaimed_day3' => '⏰',
            'unclaimed_day7' => '⚠️',
            'unclaimed_day14' => '🚨',

            // Other
            'promotion' => '🎁',
            'welcome' => '👋',
            'general' => '📢',
        ];

        return $icons[$this->type] ?? '🔔';
    }

    public function getColorAttribute()
    {
        $colors = [
            // Order
            'order_received' => 'info',
            'order_ready' => 'success',
            'order_completed' => 'success',
            'order_cancelled' => 'danger',
            'payment_received' => 'success',

            // Pickup
            'pickup_submitted' => 'info',
            'pickup_accepted' => 'success',
            'pickup_en_route' => 'primary',
            'pickup_completed' => 'success',
            'pickup_cancelled' => 'danger',

            // Delivery
            'delivery_scheduled' => 'info',
            'delivery_en_route' => 'primary',
            'delivery_completed' => 'success',
            'delivery_failed' => 'danger',

            // Unclaimed
            'unclaimed_reminder' => 'warning',
            'unclaimed_day1' => 'info',
            'unclaimed_day3' => 'warning',
            'unclaimed_day7' => 'warning',
            'unclaimed_day14' => 'danger',

            // Other
            'promotion' => 'primary',
            'welcome' => 'primary',
            'general' => 'secondary',
        ];

        return $colors[$this->type] ?? 'primary';
    }

    public function getTimeAgoAttribute()
    {
        return $this->created_at->diffForHumans();
    }

    // ===========================
    // HELPER METHODS
    // ===========================

    public function markAsRead()
    {
        $this->update([
            'is_read' => true,
            'read_at' => now(),
        ]);
    }

    public function markAsUnread()
    {
        $this->update([
            'is_read' => false,
            'read_at' => null,
        ]);
    }

    public function isRead()
    {
        return $this->is_read;
    }

    public function isSent()
    {
        return $this->fcm_status === 'sent';
    }

    public function isFailed()
    {
        return $this->fcm_status === 'failed';
    }

    // ===========================
    // FCM PUSH NOTIFICATION
    // ===========================

    /**
     * Send FCM push notification to customer's device
     */
    public function sendPushNotification(): bool
    {
        $customer = $this->customer;

        // Check if customer exists and has FCM token
        if (!$customer) {
            $this->updateFcmStatus('failed', 'Customer not found');
            return false;
        }

        if (empty($customer->fcm_token)) {
            $this->updateFcmStatus('skipped', 'No FCM token');
            return false;
        }

        if (isset($customer->notification_enabled) && !$customer->notification_enabled) {
            $this->updateFcmStatus('skipped', 'Notifications disabled by customer');
            return false;
        }

        try {
            $fcmService = app(FCMService::class);

            $result = $fcmService->sendToDevice(
                $customer->fcm_token,
                $this->title,
                $this->body,
                [
                    'notification_id' => (string) $this->id,
                    'type' => $this->type,
                    'order_id' => (string) ($this->order_id ?? ''),
                    'pickup_request_id' => (string) ($this->pickup_request_id ?? ''),
                    'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
                ]
            );

            if ($result) {
                $this->updateFcmStatus('sent');
                return true;
            } else {
                $this->updateFcmStatus('failed', 'FCM send returned false');
                return false;
            }
        } catch (\Exception $e) {
            Log::error('FCM Send Error: ' . $e->getMessage(), [
                'notification_id' => $this->id,
                'customer_id' => $customer->id,
            ]);
            $this->updateFcmStatus('failed', $e->getMessage());
            return false;
        }
    }

    /**
     * Update FCM status
     */
    protected function updateFcmStatus(string $status, ?string $error = null): void
    {
        $this->update([
            'fcm_status' => $status,
            'fcm_error' => $error,
            'fcm_sent_at' => $status === 'sent' ? now() : null,
        ]);
    }

    /**
     * Retry failed FCM notification
     */
    public function retryFcm(): bool
    {
        if ($this->fcm_status !== 'failed') {
            return false;
        }

        $this->update(['fcm_status' => 'pending', 'fcm_error' => null]);
        return $this->sendPushNotification();
    }

    // ===========================
    // STATIC FACTORY METHODS
    // ===========================

    /**
     * Create notification and optionally send FCM push
     */
    public static function createAndSend(array $data, bool $sendPush = true): self
    {
        $notification = self::create($data);

        if ($sendPush) {
            $notification->sendPushNotification();
        }

        return $notification;
    }

    // ===========================
    // ORDER NOTIFICATIONS
    // ===========================

    public static function createOrderReceived($order)
    {
        $order->loadMissing('customer', 'branch');

        return self::createAndSend([
            'customer_id' => $order->customer_id,
            'type' => 'order_received',
            'title' => 'Order Received! 📦',
            'body' => "Your order #{$order->tracking_number} has been received. We'll notify you when it's ready!",
            'data' => [
                'order_id' => $order->id,
                'tracking_number' => $order->tracking_number,
                'branch_name' => $order->branch->name ?? null,
            ],
            'order_id' => $order->id,
        ]);
    }

    public static function createOrderReady($order)
    {
        $order->loadMissing('customer', 'branch');

        return self::createAndSend([
            'customer_id' => $order->customer_id,
            'type' => 'order_ready',
            'title' => 'Laundry Ready for Pickup! 👕',
            'body' => "Great news! Your order #{$order->tracking_number} is ready at {$order->branch->name}.",
            'data' => [
                'order_id' => $order->id,
                'tracking_number' => $order->tracking_number,
                'branch_name' => $order->branch->name,
                'branch_address' => $order->branch->address ?? null,
            ],
            'order_id' => $order->id,
        ]);
    }

    public static function createPaymentReceived($order)
    {
        $order->loadMissing('customer');

        return self::createAndSend([
            'customer_id' => $order->customer_id,
            'type' => 'payment_received',
            'title' => 'Payment Confirmed! 💰',
            'body' => "Payment of ₱" . number_format($order->total_amount, 2) . " received for order #{$order->tracking_number}.",
            'data' => [
                'order_id' => $order->id,
                'tracking_number' => $order->tracking_number,
                'amount' => $order->total_amount,
            ],
            'order_id' => $order->id,
        ]);
    }

    public static function createOrderCompleted($order)
    {
        $order->loadMissing('customer');

        return self::createAndSend([
            'customer_id' => $order->customer_id,
            'type' => 'order_completed',
            'title' => 'Thank You! 🎉',
            'body' => "Your order #{$order->tracking_number} is complete. See you again at WashBox!",
            'data' => [
                'order_id' => $order->id,
                'tracking_number' => $order->tracking_number,
            ],
            'order_id' => $order->id,
        ]);
    }

    public static function createOrderCancelled($order, ?string $reason = null)
    {
        $order->loadMissing('customer');

        $body = "Your order #{$order->tracking_number} has been cancelled.";
        if ($reason) {
            $body .= " Reason: {$reason}";
        }

        return self::createAndSend([
            'customer_id' => $order->customer_id,
            'type' => 'order_cancelled',
            'title' => 'Order Cancelled ❌',
            'body' => $body,
            'data' => [
                'order_id' => $order->id,
                'tracking_number' => $order->tracking_number,
                'reason' => $reason,
            ],
            'order_id' => $order->id,
        ]);
    }

    // ===========================
    // PICKUP NOTIFICATIONS
    // ===========================

    public static function createPickupSubmitted($pickupRequest)
    {
        $pickupRequest->loadMissing('customer');

        return self::createAndSend([
            'customer_id' => $pickupRequest->customer_id,
            'type' => 'pickup_submitted',
            'title' => 'Pickup Request Submitted! 📬',
            'body' => "Your pickup for {$pickupRequest->preferred_date->format('M d, Y')} has been submitted. We'll confirm shortly!",
            'data' => [
                'pickup_request_id' => $pickupRequest->id,
                'preferred_date' => $pickupRequest->preferred_date->format('Y-m-d'),
                'preferred_time' => $pickupRequest->preferred_time,
            ],
            'pickup_request_id' => $pickupRequest->id,
        ]);
    }

    public static function createPickupAccepted($pickupRequest)
    {
        $pickupRequest->loadMissing('customer');

        return self::createAndSend([
            'customer_id' => $pickupRequest->customer_id,
            'type' => 'pickup_accepted',
            'title' => 'Pickup Confirmed! ✅',
            'body' => "Your pickup request for {$pickupRequest->preferred_date->format('M d, Y')} has been confirmed!",
            'data' => [
                'pickup_request_id' => $pickupRequest->id,
                'pickup_address' => $pickupRequest->pickup_address,
                'preferred_date' => $pickupRequest->preferred_date->format('Y-m-d'),
                'preferred_time' => $pickupRequest->preferred_time,
            ],
            'pickup_request_id' => $pickupRequest->id,
        ]);
    }

    public static function createPickupEnRoute($pickupRequest)
    {
        $pickupRequest->loadMissing('customer', 'assignedStaff');

        $staffName = $pickupRequest->assignedStaff?->name ?? 'Our rider';

        return self::createAndSend([
            'customer_id' => $pickupRequest->customer_id,
            'type' => 'pickup_en_route',
            'title' => 'Rider On The Way! 🚚',
            'body' => "{$staffName} is heading to your location. Please prepare your laundry!",
            'data' => [
                'pickup_request_id' => $pickupRequest->id,
                'pickup_address' => $pickupRequest->pickup_address,
                'staff_name' => $staffName,
            ],
            'pickup_request_id' => $pickupRequest->id,
        ]);
    }

    public static function createPickupCompleted($pickupRequest)
    {
        $pickupRequest->loadMissing('customer');

        return self::createAndSend([
            'customer_id' => $pickupRequest->customer_id,
            'type' => 'pickup_completed',
            'title' => 'Laundry Picked Up! 🧺',
            'body' => "Your laundry has been collected! We'll notify you when it's ready.",
            'data' => [
                'pickup_request_id' => $pickupRequest->id,
            ],
            'pickup_request_id' => $pickupRequest->id,
        ]);
    }

    public static function createPickupCancelled($pickupRequest, ?string $reason = null)
    {
        $pickupRequest->loadMissing('customer');

        $body = "Your pickup request has been cancelled.";
        if ($reason) {
            $body .= " Reason: {$reason}";
        }

        return self::createAndSend([
            'customer_id' => $pickupRequest->customer_id,
            'type' => 'pickup_cancelled',
            'title' => 'Pickup Cancelled ❌',
            'body' => $body,
            'data' => [
                'pickup_request_id' => $pickupRequest->id,
                'reason' => $reason,
            ],
            'pickup_request_id' => $pickupRequest->id,
        ]);
    }

    // ===========================
    // DELIVERY NOTIFICATIONS
    // ===========================

    public static function createDeliveryScheduled($order, $deliveryDate)
    {
        $order->loadMissing('customer');

        return self::createAndSend([
            'customer_id' => $order->customer_id,
            'type' => 'delivery_scheduled',
            'title' => 'Delivery Scheduled! 📅',
            'body' => "Your laundry (#{$order->tracking_number}) will be delivered on {$deliveryDate}.",
            'data' => [
                'order_id' => $order->id,
                'tracking_number' => $order->tracking_number,
                'delivery_date' => $deliveryDate,
            ],
            'order_id' => $order->id,
        ]);
    }

    public static function createDeliveryEnRoute($order)
    {
        $order->loadMissing('customer');

        return self::createAndSend([
            'customer_id' => $order->customer_id,
            'type' => 'delivery_en_route',
            'title' => 'Delivery On The Way! 🚚',
            'body' => "Your laundry (#{$order->tracking_number}) is out for delivery. Please be ready!",
            'data' => [
                'order_id' => $order->id,
                'tracking_number' => $order->tracking_number,
            ],
            'order_id' => $order->id,
        ]);
    }

    public static function createDeliveryCompleted($order)
    {
        $order->loadMissing('customer');

        return self::createAndSend([
            'customer_id' => $order->customer_id,
            'type' => 'delivery_completed',
            'title' => 'Delivered! ✅',
            'body' => "Your laundry (#{$order->tracking_number}) has been delivered. Thank you for choosing WashBox!",
            'data' => [
                'order_id' => $order->id,
                'tracking_number' => $order->tracking_number,
            ],
            'order_id' => $order->id,
        ]);
    }

    public static function createDeliveryFailed($order, ?string $reason = null)
    {
        $order->loadMissing('customer');

        $body = "Delivery attempt for order #{$order->tracking_number} was unsuccessful.";
        if ($reason) {
            $body .= " Reason: {$reason}";
        }
        $body .= " We will reschedule.";

        return self::createAndSend([
            'customer_id' => $order->customer_id,
            'type' => 'delivery_failed',
            'title' => 'Delivery Unsuccessful ❌',
            'body' => $body,
            'data' => [
                'order_id' => $order->id,
                'tracking_number' => $order->tracking_number,
                'reason' => $reason,
            ],
            'order_id' => $order->id,
        ]);
    }

    // ===========================
    // UNCLAIMED NOTIFICATIONS
    // ===========================

    public static function createUnclaimedReminder($order, int $days, string $urgency = 'normal')
    {
        $order->loadMissing('customer', 'branch');

        $messages = [
            'first' => [
                'title' => 'Friendly Reminder 🧺',
                'body' => "Hi! Your laundry (#{$order->tracking_number}) is ready at {$order->branch->name}. Please pick it up at your convenience.",
            ],
            'second' => [
                'title' => 'Your Laundry is Waiting 👕',
                'body' => "Your laundry has been ready for {$days} days. Please pick up order #{$order->tracking_number} at {$order->branch->name}.",
            ],
            'urgent' => [
                'title' => '⚠️ Urgent: Laundry Unclaimed',
                'body' => "URGENT: Order #{$order->tracking_number} has been unclaimed for {$days} days. Storage fees of ₱10/day may apply after 7 days.",
            ],
            'final' => [
                'title' => '🚨 Final Notice: Action Required',
                'body' => "FINAL NOTICE: Order #{$order->tracking_number} unclaimed for {$days} days. Per policy, items may be disposed after 30 days. Please contact us immediately.",
            ],
        ];

        $msg = $messages[$urgency] ?? $messages['first'];
        $type = "unclaimed_day{$days}";

        return self::createAndSend([
            'customer_id' => $order->customer_id,
            'type' => 'unclaimed_reminder',
            'title' => $msg['title'],
            'body' => $msg['body'],
            'data' => [
                'order_id' => $order->id,
                'tracking_number' => $order->tracking_number,
                'days_unclaimed' => $days,
                'urgency' => $urgency,
                'branch_name' => $order->branch->name,
                'branch_phone' => $order->branch->phone ?? null,
            ],
            'order_id' => $order->id,
        ]);
    }

    /**
     * Legacy method for backward compatibility
     */
    public static function createUnclaimedWarning($order, $days)
    {
        $urgency = match(true) {
            $days >= 14 => 'final',
            $days >= 7 => 'urgent',
            $days >= 3 => 'second',
            default => 'first',
        };

        return self::createUnclaimedReminder($order, $days, $urgency);
    }

    // ===========================
    // PROMOTION NOTIFICATIONS
    // ===========================

    public static function createPromotion($customer, $promotion)
    {
        return self::createAndSend([
            'customer_id' => $customer->id,
            'type' => 'promotion',
            'title' => '🎁 Special Offer for You!',
            'body' => "{$promotion->name}: {$promotion->description}. Use code: {$promotion->code}",
            'data' => [
                'promotion_id' => $promotion->id,
                'promotion_code' => $promotion->code,
                'discount_type' => $promotion->discount_type,
                'discount_value' => $promotion->discount_value,
                'valid_until' => $promotion->valid_until?->format('Y-m-d'),
            ],
        ]);
    }

    public static function createPromotionExpiring($customer, $promotion, int $daysLeft)
    {
        return self::createAndSend([
            'customer_id' => $customer->id,
            'type' => 'promotion',
            'title' => '⏰ Promotion Expiring Soon!',
            'body' => "Don't miss out! '{$promotion->name}' expires in {$daysLeft} day(s). Use code: {$promotion->code}",
            'data' => [
                'promotion_id' => $promotion->id,
                'promotion_code' => $promotion->code,
                'days_left' => $daysLeft,
            ],
        ]);
    }

    // ===========================
    // WELCOME & GENERAL
    // ===========================

    public static function createWelcome($customer)
    {
        return self::createAndSend([
            'customer_id' => $customer->id,
            'type' => 'welcome',
            'title' => 'Welcome to WashBox! 👋',
            'body' => "Hi {$customer->name}! Thanks for joining WashBox. We're here to make your laundry life easier!",
            'data' => [
                'customer_name' => $customer->name,
            ],
        ]);
    }

    public static function createGeneral($customerId, string $title, string $body, array $data = [])
    {
        return self::createAndSend([
            'customer_id' => $customerId,
            'type' => 'general',
            'title' => $title,
            'body' => $body,
            'data' => $data,
        ]);
    }

    // ===========================
    // BULK NOTIFICATIONS
    // ===========================

    /**
     * Send notification to all customers
     */
    public static function broadcastToAll(string $title, string $body, array $data = [])
    {
        $customers = Customer::whereNotNull('fcm_token')
            ->where('notification_enabled', true)
            ->get();

        $count = 0;
        foreach ($customers as $customer) {
            self::createGeneral($customer->id, $title, $body, $data);
            $count++;
        }

        return $count;
    }

    /**
     * Send notification to customers of a specific branch
     */
    public static function broadcastToBranch(int $branchId, string $title, string $body, array $data = [])
    {
        $customers = Customer::where('preferred_branch_id', $branchId)
            ->whereNotNull('fcm_token')
            ->where('notification_enabled', true)
            ->get();

        $count = 0;
        foreach ($customers as $customer) {
            self::createGeneral($customer->id, $title, $body, array_merge($data, ['branch_id' => $branchId]));
            $count++;
        }

        return $count;
    }
}
