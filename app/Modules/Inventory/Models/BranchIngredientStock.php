<?php
namespace App\Modules\Inventory\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Modules\Branches\Models\Branch;

class BranchIngredientStock extends Model
{
    protected $fillable = ['branch_id', 'ingredient_id', 'on_hand_qty', 'reserved_qty', 'reorder_level_override'];
    protected function casts(): array { return ['on_hand_qty' => 'decimal:3', 'reserved_qty' => 'decimal:3', 'reorder_level_override' => 'decimal:3']; }
    public function branch(): BelongsTo { return $this->belongsTo(Branch::class); } public function ingredient(): BelongsTo { return $this->belongsTo(Ingredient::class); } public function getAvailableQtyAttribute(): string { return number_format((float) $this->on_hand_qty - (float) $this->reserved_qty, 3, '.', ''); }
}
