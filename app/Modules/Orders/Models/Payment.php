<?php

namespace App\Modules\Orders\Models;

use App\Modules\Payments\Enums\PaymentMethod;
use App\Modules\Payments\Enums\PaymentStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    protected $fillable = [
        'order_id', 'method', 'provider', 'provider_reference', 'status', 'amount',
        'currency', 'idempotency_key', 'metadata', 'paid_at',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'metadata' => 'array',
            'paid_at' => 'datetime',
        ];
    }

    public function order(): BelongsTo { return $this->belongsTo(Order::class); }
}
