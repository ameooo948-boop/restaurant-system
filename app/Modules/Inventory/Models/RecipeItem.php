<?php
namespace App\Modules\Inventory\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Modules\Branches\Models\Branch;

class RecipeItem extends Model
{
    protected $fillable = ['recipe_id', 'ingredient_id', 'quantity', 'waste_percent'];
    protected function casts(): array { return ['quantity' => 'decimal:3', 'waste_percent' => 'decimal:2']; }
    public function recipe(): BelongsTo { return $this->belongsTo(Recipe::class); } public function ingredient(): BelongsTo { return $this->belongsTo(Ingredient::class); }
}
