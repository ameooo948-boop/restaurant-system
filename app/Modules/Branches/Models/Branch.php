<?php

namespace App\Modules\Branches\Models;

use App\Modules\Restaurants\Models\Restaurant;
use App\Modules\Inventory\Models\BranchIngredientStock;
use App\Modules\Inventory\Models\PurchaseOrder;
use App\Modules\Inventory\Models\InventoryCount;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Branch extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'restaurant_id',
        'code',
        'name',
        'slug',
        'phone',
        'email',
        'address_line',
        'city',
        'area',
        'latitude',
        'longitude',
        'is_primary',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'is_primary' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(Restaurant::class);
    }

    public function businessHours(): HasMany
    {
        return $this->hasMany(BranchBusinessHour::class);
    }

    public function specialHours(): HasMany
    {
        return $this->hasMany(BranchSpecialHour::class);
    }

    public function settings(): HasMany
    {
        return $this->hasMany(BranchSetting::class);
    }


    public function ingredientStocks(): HasMany { return $this->hasMany(BranchIngredientStock::class); }
    public function purchaseOrders(): HasMany { return $this->hasMany(PurchaseOrder::class); }
    public function inventoryCounts(): HasMany { return $this->hasMany(InventoryCount::class); }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopePrimary(Builder $query): Builder
    {
        return $query->where('is_primary', true);
    }
}
