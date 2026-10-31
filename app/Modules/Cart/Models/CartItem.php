<?php

namespace App\Modules\Cart\Models;

use App\Modules\Menu\Models\ProductVariant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CartItem extends Model
{
    protected $fillable = ['cart_id', 'product_variant_id', 'quantity', 'notes'];

    protected function casts(): array
    {
        return ['quantity' => 'integer'];
    }

    public function cart(): BelongsTo
    {
        return $this->belongsTo(Cart::class);
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    public function modifiers(): HasMany
    {
        return $this->hasMany(CartItemModifier::class);
    }
}
