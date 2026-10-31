<?php

namespace App\Modules\Menu\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use App\Modules\Inventory\Models\Recipe;

class ProductVariant extends Model
{
    protected $fillable = [
        'product_id', 'name', 'translations', 'sku', 'base_price', 'sort_order', 'is_default', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'translations' => 'array',
            'base_price' => 'decimal:2',
            'sort_order' => 'integer',
            'is_default' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function recipe(): HasOne { return $this->hasOne(Recipe::class, 'product_variant_id'); }

    public function branchSettings(): HasMany
    {
        return $this->hasMany(BranchProductSetting::class);
    }
}
