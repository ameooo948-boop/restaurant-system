<?php

namespace App\Modules\Delivery\Models;

use App\Modules\Branches\Models\Branch;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DeliveryZone extends Model
{
    protected $fillable = [
        'branch_id', 'name', 'city', 'area', 'delivery_fee',
        'minimum_order_amount', 'estimated_minutes', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'delivery_fee' => 'decimal:2',
            'minimum_order_amount' => 'decimal:2',
            'estimated_minutes' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function branch(): BelongsTo { return $this->belongsTo(Branch::class); }
    public function deliveries(): HasMany { return $this->hasMany(Delivery::class); }
}
