<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Order extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'tracking_number',
        'customer_id',
        'branch_id',
        'service_id',
        'created_by',
        'staff_id',
        'weight',
        'number_of_loads',
        'price_per_kg',
        'subtotal',
        'addons_total',
        'discount_amount',
        'total_amount',
        'promotion_id',
        'promotion_override_total',
        'promotion_price_per_load',
        'pickup_request_id',
        'pickup_fee',
        'delivery_fee',
        'payment_status',
        'payment_method',
        'status',
        'received_at',
        'processing_at',
        'ready_at',
        'paid_at',
        'completed_at',
        'cancelled_at',
        'notes',
        'cancellation_reason',
        'last_reminder_at',
        'reminder_count',
        'is_unclaimed',
        'unclaimed_at',
        'storage_fee',
    ];

    protected $casts = [
        'weight' => 'decimal:2',
        'number_of_loads' => 'integer',
        'price_per_kg' => 'decimal:2',
        'subtotal' => 'decimal:2',
        'addons_total' => 'decimal:2',
        'pickup_fee' => 'decimal:2',
        'delivery_fee' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'storage_fee' => 'decimal:2',
        'received_at' => 'datetime',
        'processing_at' => 'datetime',
        'ready_at' => 'datetime',
        'paid_at' => 'datetime',
        'completed_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'last_reminder_at' => 'datetime',
        'unclaimed_at' => 'datetime',
        'is_unclaimed' => 'boolean',
        'reminder_count' => 'integer',
    ];

    // ========================================================================
    // RELATIONSHIPS
    // ========================================================================

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function staff(): BelongsTo
    {
        return $this->belongsTo(User::class, 'staff_id');
    }

    public function promotion(): BelongsTo
    {
        return $this->belongsTo(Promotion::class);
    }

    public function statusHistories(): HasMany
    {
        return $this->hasMany(OrderStatusHistory::class);
    }

    public function payment(): HasOne
    {
        return $this->hasOne(Payment::class);
    }

    public function rating(): HasOne
    {
        return $this->hasOne(CustomerRating::class);
    }

    public function unclaimedLaundry(): HasOne
    {
        return $this->hasOne(UnclaimedLaundry::class);
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class);
    }

    public function pickupRequest(): BelongsTo
    {
        return $this->belongsTo(PickupRequest::class, 'pickup_request_id');
    }

    /**
     * Add-ons attached to the order (using separate AddOn model)
     */
    public function addons(): BelongsToMany
    {
        return $this->belongsToMany(AddOn::class, 'order_addon')
            ->withPivot('price_at_purchase', 'quantity')
            ->withTimestamps();
    }

    /**
     * Calculate add-ons total from pivot table
     */
    public function getCalculatedAddonsTotalAttribute(): float
    {
        return $this->addons->sum(function ($addon) {
            return (float) $addon->pivot->price_at_purchase * (int) $addon->pivot->quantity;
        });
    }

    public function promotionUsage(): HasOne
    {
        return $this->hasOne(PromotionUsage::class);
    }

    // ========================================================================
    // SCOPES
    // ========================================================================

    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    public function scopeByBranch($query, int $branchId)
    {
        return $query->where('branch_id', $branchId);
    }

    public function scopeByCustomer($query, int $customerId)
    {
        return $query->where('customer_id', $customerId);
    }

    public function scopeByStaff($query, int $staffId)
    {
        return $query->where('staff_id', $staffId);
    }

    public function scopeUnassigned($query)
    {
        return $query->whereNull('staff_id');
    }

    public function scopeActive($query)
    {
        return $query->whereIn('status', ['received', 'ready', 'paid']);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeSearch($query, string $search)
    {
        return $query->where('tracking_number', 'like', "%{$search}%")
            ->orWhereHas('customer', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
    }

    // ========================================================================
    // ACCESSORS
    // ========================================================================

    public function getStatusLabelAttribute(): string
    {
        return ucfirst($this->status);
    }

    public function getFormattedWeightAttribute(): string
    {
        return number_format($this->weight, 2) . ' kg';
    }

    public function getFormattedSubtotalAttribute(): string
    {
        return '₱' . number_format($this->subtotal, 2);
    }

    public function getFormattedDiscountAttribute(): string
    {
        return '₱' . number_format($this->discount_amount, 2);
    }

    public function getFormattedTotalAttribute(): string
    {
        return '₱' . number_format($this->total_amount, 2);
    }

    public function getFormattedAddonsTotalAttribute(): string
    {
        return '₱' . number_format($this->addons_total, 2);
    }

    public function getDaysUnclaimedAttribute(): int
    {
        if (!$this->ready_at || $this->status !== 'ready') {
            return 0;
        }
        return (int) now()->diffInDays($this->ready_at);
    }

    public function getIsOverdueAttribute(): bool
    {
        return $this->status === 'ready' && $this->days_unclaimed >= 3;
    }

    public function getCalculatedStorageFeeAttribute(): float
    {
        if ($this->days_unclaimed <= 7) {
            return 0;
        }
        $extraDays = $this->days_unclaimed - 7;
        return $extraDays * config('unclaimed.storage_fee_per_day', 10);
    }

    public function getFormattedStorageFeeAttribute(): string
    {
        return '₱' . number_format($this->calculated_storage_fee, 2);
    }

    public function getUnclaimedStatusAttribute(): string
    {
        $days = $this->days_unclaimed;

        if ($days >= 14)
            return 'critical';
        if ($days >= 7)
            return 'urgent';
        if ($days >= 3)
            return 'warning';
        if ($days >= 1)
            return 'pending';
        return 'normal';
    }

    public function getUnclaimedColorAttribute(): string
    {
        $colors = [
            'critical' => 'danger',
            'urgent' => 'warning',
            'warning' => 'warning',
            'pending' => 'info',
            'normal' => 'success',
        ];
        return $colors[$this->unclaimed_status] ?? 'secondary';
    }

    public function getPricingDisplayAttribute()
    {
        if (!$this->service) {
            return 'Custom Order';
        }

        if ($this->service->pricing_type === 'per_load') {
            if ($this->service->service_type === 'special_item') {
                return '₱' . number_format($this->service->price_per_load, 2) . '/piece × ' . $this->number_of_loads . ' pieces';
            }

            $loads = $this->number_of_loads ?? 1;
            return '₱' . number_format($this->service->price_per_load, 2) . '/load × ' . $loads . ' loads';
        }

        return '₱' . number_format($this->service->price_per_kg, 2) . '/kg × ' . number_format($this->weight, 1) . 'kg';
    }

    // ========================================================================
    // METHODS
    // ========================================================================

    /**
     * Update order status with history tracking
     */
    public function updateStatus(string $newStatus, ?User $changedBy = null, ?string $notes = null): void
    {
        $this->status = $newStatus;
        $this->{$newStatus . '_at'} = now();
        $this->save();

        $this->statusHistories()->create([
            'status' => $newStatus,
            'changed_by' => $changedBy?->id,
            'notes' => $notes,
        ]);
    }

    /**
     * Assign order to staff member
     */
    public function assignToStaff(?int $staffId): void
    {
        $this->update(['staff_id' => $staffId]);
    }

    /**
     * Sync add-ons to order with proper pricing
     */
    public function syncAddons(array $addonIds): void
    {
        $addonData = [];
        $addonsTotal = 0;

        foreach ($addonIds as $addonId) {
            $addon = AddOn::findOrFail($addonId);
            $addonsTotal += $addon->price;

            $addonData[$addonId] = [
                'price_at_purchase' => $addon->price,
                'quantity' => 1
            ];
        }

        $this->addons()->sync($addonData);

        // Update order totals
        $this->update([
            'addons_total' => $addonsTotal,
            'total_amount' => ($this->subtotal - $this->discount_amount + $this->pickup_fee + $this->delivery_fee + $addonsTotal)
        ]);
    }

    /**
     * Get all add-ons as a formatted string
     */
    public function getAddonsListAttribute(): string
    {
        if ($this->addons->isEmpty()) {
            return 'None';
        }

        return $this->addons->map(function ($addon) {
            return $addon->name . ' (₱' . number_format($addon->pivot->price_at_purchase, 2) . ')';
        })->implode(', ');
    }

    public function isAssigned(): bool
    {
        return !is_null($this->staff_id);
    }

    public function isPaid(): bool
    {
        return in_array($this->status, ['paid', 'completed']);
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }

    public function isUnclaimed(): bool
    {
        return $this->status === 'ready' && !$this->paid_at;
    }

    public function canBeRated(): bool
    {
        return $this->status === 'completed' && !$this->rating;
    }

    public function isPerLoadService(): bool
    {
        return $this->service && $this->service->pricing_type === 'per_load';
    }

    public function isPerKgService(): bool
    {
        return $this->service && $this->service->pricing_type === 'per_kg';
    }

    public function calculateSubtotal()
    {
        if (!$this->service) {
            return 0;
        }

        if ($this->service->pricing_type === 'per_load') {
            $loads = $this->number_of_loads ?? 1;
            return $this->service->price_per_load * $loads;
        }

        return $this->service->price_per_kg * ($this->weight ?? 0);
    }

    public function getTotalWithStorageFee(): float
    {
        return $this->total_amount + $this->calculated_storage_fee;
    }

    public function getTimeline(): array
    {
        return [
            'received' => $this->received_at,
            'processing' => $this->processing_at,
            'ready' => $this->ready_at,
            'paid' => $this->paid_at,
            'completed' => $this->completed_at,
        ];
    }
}
