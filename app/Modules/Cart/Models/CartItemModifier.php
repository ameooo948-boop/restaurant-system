<?php

namespace App\Modules\Cart\Models;

use App\Modules\Menu\Models\ModifierOption;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CartItemModifier extends Model
{
    protected $fillable = ['cart_item_id', 'modifier_option_id', 'quantity'];

    protected function casts(): array
    {
        return ['quantity' => 'integer'];
    }

    public function cartItem(): BelongsTo
    {
        return $this->belongsTo(CartItem::class);
    }

    public function option(): BelongsTo
    {
        return $this->belongsTo(ModifierOption::class, 'modifier_option_id');
    }
}
