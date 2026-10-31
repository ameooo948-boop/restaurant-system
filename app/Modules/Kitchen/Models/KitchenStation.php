<?php

namespace App\Modules\Kitchen\Models;

use App\Modules\Branches\Models\Branch;
use App\Modules\Menu\Models\Product;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class KitchenStation extends Model
{
    protected $fillable = [
        'branch_id', 'code', 'name', 'description', 'sort_order', 'is_default', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'is_default' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'kitchen_station_products')
            ->withPivot(['branch_id'])
            ->withTimestamps();
    }

    public function tickets(): HasMany
    {
        return $this->hasMany(KitchenTicket::class);
    }
}
