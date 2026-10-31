<?php

namespace App\Modules\Orders\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Modules\Menu\Models\ProductVariant;

class OrderItem extends Model
{
    protected $fillable = [
        'order_id', 'product_id', 'product_variant_id', 'product_name_snapshot',
        'variant_name_snapshot', 'sku_snapshot', 'quantity', 'unit_price',
        'line_subtotal', 'notes', 'product_snapshot',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'unit_price' => 'decimal:2',
            'line_subtotal' => 'decimal:2',
            'product_snapshot' => 'array',
        ];
    }

    public function order(): BelongsTo { return $this->belongsTo(Order::class); }
    public function productVariant(): BelongsTo { return $this->belongsTo(ProductVariant::class, 'product_variant_id'); }
    public function modifiers(): HasMany { return $this->hasMany(OrderItemModifier::class); }
}
