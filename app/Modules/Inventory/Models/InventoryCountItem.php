<?php
namespace App\Modules\Inventory\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Modules\Branches\Models\Branch;

class InventoryCountItem extends Model
{
    protected $fillable = ['inventory_count_id', 'ingredient_id', 'expected_qty', 'counted_qty', 'variance_qty', 'note'];
    protected function casts(): array { return ['expected_qty' => 'decimal:3', 'counted_qty' => 'decimal:3', 'variance_qty' => 'decimal:3']; }
    public function count(): BelongsTo { return $this->belongsTo(InventoryCount::class, 'inventory_count_id'); } public function ingredient(): BelongsTo { return $this->belongsTo(Ingredient::class); }
}
