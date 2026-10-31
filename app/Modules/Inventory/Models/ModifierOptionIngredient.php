<?php
namespace App\Modules\Inventory\Models;
use Illuminate\Database\Eloquent\Model;
use App\Modules\Menu\Models\ModifierOption;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Modules\Branches\Models\Branch;

class ModifierOptionIngredient extends Model
{
    protected $fillable = ['modifier_option_id', 'ingredient_id', 'quantity_per_option'];
    protected function casts(): array { return ['quantity_per_option' => 'decimal:3']; }
    public function option(): BelongsTo { return $this->belongsTo(ModifierOption::class, 'modifier_option_id'); } public function ingredient(): BelongsTo { return $this->belongsTo(Ingredient::class); }
}
