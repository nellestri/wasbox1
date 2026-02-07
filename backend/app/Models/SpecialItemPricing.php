<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SpecialItemPricing extends Model
{
    protected $fillable = [
        'promotion_id',
        'item_type',
        'item_size',
        'price',
        'description',
        'is_active',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function promotion(): BelongsTo
    {
        return $this->belongsTo(Promotion::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('item_type', $type);
    }

    public function getFormattedPriceAttribute(): string
    {
        return '₱' . number_format($this->price, 2);
    }

    public function getItemLabelAttribute(): string
    {
        $type = ucfirst($this->item_type);
        $size = $this->item_size ? " ({$this->item_size})" : '';
        return $type . $size;
    }
}

