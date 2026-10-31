<?php

namespace App\Modules\Menu\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ModifierOption extends Model
{
    protected $fillable = [
        'modifier_group_id', 'name', 'translations', 'description', 'price_delta', 'sort_order', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'translations' => 'array',
            'price_delta' => 'decimal:2',
            'sort_order' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(ModifierGroup::class, 'modifier_group_id');
    }

    public function ingredientUsages(): HasMany { return $this->hasMany(\App\Modules\Inventory\Models\ModifierOptionIngredient::class); }

    public function branchPrices(): HasMany
    {
        return $this->hasMany(BranchModifierPrice::class);
    }
}
