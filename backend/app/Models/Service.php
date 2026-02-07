<?php

namespace App\Models;

use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Service extends Model
{

   use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'slug', // <--- ADD THIS
        'description',
        'price_per_kg',
        'price_per_load',
        'pricing_type',
        'min_weight',
        'max_weight',
        'turnaround_time',
        'service_type',
        'icon_path',
        'is_active',
    ];

    /**
     * The "booted" method of the model.
     * This ensures the slug is created even if the Seeder forgets it.
     */
    protected static function booted()
    {
        static::creating(function ($service) {
            if (empty($service->slug)) {
                $service->slug = Str::slug($service->name);
            }
        });
    }

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'price_per_kg' => 'decimal:2',
        'price_per_load' => 'decimal:2',
        'min_weight' => 'decimal:2',
        'max_weight' => 'decimal:2',
        'turnaround_time' => 'integer',
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get all orders using this service.
     */
    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    /**
     * Get branches that offer this service.
     */
    public function branches()
    {
        return $this->belongsToMany(Branch::class, 'branch_services')
            ->withPivot('is_available')
            ->withTimestamps();
    }

    /**
     * Scope a query to only include active services.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to only include inactive services.
     */
    public function scopeInactive($query)
    {
        return $query->where('is_active', false);
    }

    /**
     * Scope a query by service type.
     */
    public function scopeByType($query, $type)
    {
        return $query->where('service_type', $type);
    }

    /**
     * Get the icon URL attribute.
     */
    public function getIconUrlAttribute()
    {
        if (!$this->icon_path) {
            return null;
        }

        // If it's already a full URL, return it
        if (filter_var($this->icon_path, FILTER_VALIDATE_URL)) {
            return $this->icon_path;
        }

        // Otherwise, return storage URL
        return asset('storage/' . $this->icon_path);
    }

    /**
     * Get the formatted price attribute.
     */
    public function getFormattedPriceAttribute()
    {
        return '₱' . number_format($this->price_per_kg, 2) . '/kg';
    }

    /**
     * Get the status label.
     */
    public function getStatusLabelAttribute()
    {
        return $this->is_active ? 'Active' : 'Inactive';
    }

    /**
     * Get the status color.
     */
    public function getStatusColorAttribute()
    {
        return $this->is_active ? 'success' : 'secondary';
    }

    /**
     * Get formatted service type.
     */
    public function getServiceTypeDisplayAttribute()
    {
        if (!$this->service_type) {
            return 'General Service';
        }

        return ucfirst(str_replace('_', ' ', $this->service_type));
    }

    /**
     * Calculate total revenue from this service.
     */
    public function getTotalRevenueAttribute()
    {
        return $this->orders()->where('status', 'completed')->sum('total_amount');
    }

    /**
     * Get count of completed orders.
     */
    public function getCompletedOrdersCountAttribute()
    {
        return $this->orders()->where('status', 'completed')->count();
    }

    /**
     * Get count of pending orders.
     */
    public function getPendingOrdersCountAttribute()
    {
        return $this->orders()->whereIn('status', ['pending', 'processing', 'ready'])->count();
    }

    /**
     * Get average order value.
     */
    public function getAvgOrderValueAttribute()
    {
        return $this->orders()->where('status', 'completed')->avg('total_amount') ?? 0;
    }

    /**
     * Get total weight processed.
     */
    public function getTotalWeightAttribute()
    {
        return $this->orders()->sum('weight') ?? 0;
    }

    /**
     * Check if service has any orders.
     */
    public function hasOrders()
    {
        return $this->orders()->exists();
    }

    /**
     * Check if weight is within service limits.
     */
    public function isWeightValid($weight)
    {
        if ($this->min_weight && $weight < $this->min_weight) {
            return false;
        }

        if ($this->max_weight && $weight > $this->max_weight) {
            return false;
        }

        return true;
    }

    /**
     * Calculate price for given weight.
     */
    public function calculatePrice($weight)
    {
        if ($this->pricing_type === 'per_load') {
            return (float) $this->price_per_load;
        }

        return $weight * $this->price_per_kg;
    }

    /**
     * Check if this service is priced per load
     */
    public function isPerLoad(): bool
    {
        return $this->pricing_type === 'per_load';
    }

    /**
     * Get popular services (most orders).
     */
    public static function popular($limit = 5)
    {
        return static::withCount('orders')
            ->orderBy('orders_count', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get top revenue services.
     */
    public static function topRevenue($limit = 5)
    {
        return static::select('services.*')
            ->selectRaw('(SELECT SUM(total_amount) FROM orders WHERE orders.service_id = services.id AND orders.status = "completed") as revenue')
            ->orderBy('revenue', 'desc')
            ->limit($limit)
            ->get();
    }

}
