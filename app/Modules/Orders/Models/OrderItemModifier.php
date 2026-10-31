<?php

namespace App\Modules\Orders\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItemModifier extends Model
{
    protected $fillable = [
        'order_item_id', 'modifier_option_id', 'group_name_snapshot', 'option_name_snapshot',
        'quantity', 'unit_price_delta', 'line_total',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'unit_price_delta' => 'decimal:2',
            'line_total' => 'decimal:2',
        ];
    }

    public function orderItem(): BelongsTo { return $this->belongsTo(OrderItem::class); }
}
