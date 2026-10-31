<?php
namespace App\Modules\Inventory\Models;
use Illuminate\Database\Eloquent\Model;
use App\Modules\Menu\Models\ProductVariant;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Recipe extends Model
{
    protected $fillable = ['product_variant_id', 'yield_quantity', 'instructions', 'is_active'];
    protected function casts(): array { return ['yield_quantity' => 'decimal:3', 'is_active' => 'boolean']; }
    public function variant(): BelongsTo { return $this->belongsTo(ProductVariant::class, 'product_variant_id'); } public function items(): HasMany { return $this->hasMany(RecipeItem::class); }
}
