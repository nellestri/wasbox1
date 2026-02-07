<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'order_id',
        'method',
        'amount',
        'receipt_number',
        'received_by',
        'notes',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'created_at' => 'datetime',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function receivedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by');
    }

    public function getFormattedAmountAttribute(): string
    {
        return '₱' . number_format($this->amount, 2);
    }
}
