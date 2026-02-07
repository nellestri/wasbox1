<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AdminNotification extends Model
{
    use HasFactory;

    protected $fillable = [
        'type',
        'title',
        'message',
        'icon',
        'color',
        'link',
        'data',
        'branch_id',
        'user_id',
        'read_at',
    ];

    protected $casts = [
        'data' => 'array',
        'read_at' => 'datetime',
    ];

    // ===========================
    // RELATIONSHIPS
    // ===========================

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // ===========================
    // SCOPES
    // ===========================

    public function scopeUnread($query)
    {
        return $query->whereNull('read_at');
    }

    public function scopeRead($query)
    {
        return $query->whereNotNull('read_at');
    }

    public function scopeOfType($query, $type)
    {
        return $query->where('type', $type);
    }

    public function scopeForBranch($query, $branchId)
    {
        return $query->where('branch_id', $branchId);
    }

    public function scopeRecent($query, $days = 7)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    // ===========================
    // ACCESSORS
    // ===========================

    public function getIsReadAttribute()
    {
        return $this->read_at !== null;
    }

    public function getTimeAgoAttribute()
    {
        return $this->created_at->diffForHumans();
    }

    public function getIconClassAttribute()
    {
        $icons = [
            'pickup_request' => 'bi-truck',
            'pickup_completed' => 'bi-check-circle',
            'pickup_cancelled' => 'bi-x-circle',
            'new_order' => 'bi-cart-plus',
            'payment' => 'bi-currency-dollar',
            'order_completed' => 'bi-check-all',
            'order_cancelled' => 'bi-x-circle',
            'unclaimed' => 'bi-exclamation-triangle',
            'new_customer' => 'bi-person-plus',
            'system' => 'bi-gear',
        ];

        return $icons[$this->type] ?? 'bi-bell';
    }

    // ===========================
    // METHODS
    // ===========================

    public function markAsRead()
    {
        $this->update(['read_at' => now()]);
    }

    public function markAsUnread()
    {
        $this->update(['read_at' => null]);
    }

    // ===========================
    // STATIC HELPERS - Create Notifications
    // ===========================

    /**
     * Notify admin of new pickup request
     */
    public static function notifyNewPickupRequest($pickupRequest)
    {
        $pickupRequest->loadMissing('customer');

        return self::create([
            'type' => 'pickup_request',
            'title' => 'New Pickup Request',
            'message' => "Customer {$pickupRequest->customer->name} requested pickup at {$pickupRequest->pickup_address}",
            'icon' => 'truck',
            'color' => 'info',
            'link' => route('admin.pickups.show', $pickupRequest->id),
            'data' => [
                'pickup_request_id' => $pickupRequest->id,
                'customer_id' => $pickupRequest->customer_id,
                'customer_name' => $pickupRequest->customer->name,
                'pickup_address' => $pickupRequest->pickup_address,
                'preferred_date' => $pickupRequest->preferred_date?->format('Y-m-d'),
                'preferred_time' => $pickupRequest->preferred_time,
            ],
            'branch_id' => $pickupRequest->branch_id,
        ]);
    }

    /**
     * Notify admin of new order
     */
    public static function notifyNewOrder($order)
    {
        $order->loadMissing('customer');

        return self::create([
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
            ],
            'branch_id' => $order->branch_id,
            'user_id' => $order->created_by,
        ]);
    }

    /**
     * Notify admin of payment received
     */
    public static function notifyPaymentReceived($order)
    {
        $order->loadMissing('customer');

        return self::create([
            'type' => 'payment',
            'title' => 'Payment Received',
            'message' => "₱" . number_format($order->total_amount, 2) . " received for order #{$order->tracking_number}",
            'icon' => 'currency-dollar',
            'color' => 'success',
            'link' => route('admin.orders.show', $order->id),
            'data' => [
                'order_id' => $order->id,
                'tracking_number' => $order->tracking_number,
                'amount' => $order->total_amount,
            ],
            'branch_id' => $order->branch_id,
        ]);
    }

    /**
     * Notify admin of order completion
     */
    public static function notifyOrderCompleted($order)
    {
        return self::create([
            'type' => 'order_completed',
            'title' => 'Order Completed',
            'message' => "Order #{$order->tracking_number} has been completed",
            'icon' => 'check-all',
            'color' => 'success',
            'link' => route('admin.orders.show', $order->id),
            'branch_id' => $order->branch_id,
        ]);
    }

    /**
     * Notify admin of order cancellation
     */
    public static function notifyOrderCancelled($order, $reason = null)
    {
        $order->loadMissing('customer');

        return self::create([
            'type' => 'order_cancelled',
            'title' => 'Order Cancelled',
            'message' => "Order #{$order->tracking_number} from {$order->customer->name} was cancelled",
            'icon' => 'x-circle',
            'color' => 'danger',
            'link' => route('admin.orders.show', $order->id),
            'data' => [
                'order_id' => $order->id,
                'tracking_number' => $order->tracking_number,
                'reason' => $reason,
            ],
            'branch_id' => $order->branch_id,
        ]);
    }

    /**
     * Notify admin of new customer registration
     */
    public static function notifyNewCustomer($customer)
    {
        return self::create([
            'type' => 'new_customer',
            'title' => 'New Customer Registered',
            'message' => "{$customer->name} registered via " . ($customer->registration_type ?? 'app'),
            'icon' => 'person-plus',
            'color' => 'primary',
            'link' => route('admin.customers.show', $customer->id),
            'data' => [
                'customer_id' => $customer->id,
                'customer_name' => $customer->name,
                'phone' => $customer->phone,
            ],
            'branch_id' => $customer->preferred_branch_id,
        ]);
    }

    /**
     * Notify admin of unclaimed laundry
     */
    public static function notifyUnclaimedLaundry($order, $daysUnclaimed)
    {
        $order->loadMissing('customer');

        return self::create([
            'type' => 'unclaimed',
            'title' => 'Unclaimed Laundry Alert',
            'message' => "Order #{$order->tracking_number} unclaimed for {$daysUnclaimed} days",
            'icon' => 'exclamation-triangle',
            'color' => 'warning',
            'link' => route('admin.orders.show', $order->id),
            'data' => [
                'order_id' => $order->id,
                'tracking_number' => $order->tracking_number,
                'days_unclaimed' => $daysUnclaimed,
                'customer_name' => $order->customer->name,
            ],
            'branch_id' => $order->branch_id,
        ]);
    }

    /**
     * Create a system notification
     */
    public static function notifySystem($title, $message, $link = null, $color = 'secondary')
    {
        return self::create([
            'type' => 'system',
            'title' => $title,
            'message' => $message,
            'icon' => 'gear',
            'color' => $color,
            'link' => $link,
        ]);
    }
}
