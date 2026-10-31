<?php

namespace App\Modules\Payments\Models;

use App\Modules\Orders\Models\Payment;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentGatewayEvent extends Model
{
    protected $fillable = [
        'provider', 'event_key', 'event_id', 'event_type', 'payment_id',
        'provider_order_id', 'payload_sha256', 'signature_verified', 'processed_at',
    ];

    protected function casts(): array
    {
        return [
            'signature_verified' => 'boolean',
            'processed_at' => 'datetime',
        ];
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }
}
