<?php

namespace App\Modules\Menu\Models;

use App\Modules\Restaurants\Models\Restaurant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ModifierGroup extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'restaurant_id', 'name', 'translations', 'description', 'selection_type', 'min_selections',
        'max_selections', 'sort_order', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'translations' => 'array',
            'min_selections' => 'integer',
            'max_selections' => 'integer',
            'sort_order' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(Restaurant::class);
    }

    public function options(): HasMany
    {
        return $this->hasMany(ModifierOption::class)->orderBy('sort_order');
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'product_modifier_groups')
            ->withPivot('sort_order')
            ->withTimestamps();
    }
}
