<?php
namespace App\Modules\Inventory\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Modules\Restaurants\Models\Restaurant;
use App\Modules\Branches\Models\Branch;
use Illuminate\Database\Eloquent\SoftDeletes;

class Ingredient extends Model
{
    use SoftDeletes;
    protected $fillable = ['restaurant_id', 'base_unit_id', 'name', 'sku', 'description', 'reorder_level', 'is_active'];
    protected function casts(): array { return ['reorder_level' => 'decimal:3', 'is_active' => 'boolean']; }
    public function restaurant(): BelongsTo { return $this->belongsTo(Restaurant::class); } public function baseUnit(): BelongsTo { return $this->belongsTo(MeasurementUnit::class, 'base_unit_id'); } public function conversions(): HasMany { return $this->hasMany(IngredientUnitConversion::class); } public function recipeItems(): HasMany { return $this->hasMany(RecipeItem::class); } public function stocks(): HasMany { return $this->hasMany(BranchIngredientStock::class); } public function movements(): HasMany { return $this->hasMany(StockMovement::class); }
}
