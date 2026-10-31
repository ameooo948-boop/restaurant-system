<?php
namespace App\Modules\Inventory\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Modules\Branches\Models\Branch;

class IngredientUnitConversion extends Model
{
    protected $fillable = ['ingredient_id', 'unit_id', 'to_base_factor'];
    protected function casts(): array { return ['to_base_factor' => 'decimal:6']; }
    public function ingredient(): BelongsTo { return $this->belongsTo(Ingredient::class); } public function unit(): BelongsTo { return $this->belongsTo(MeasurementUnit::class, 'unit_id'); }
}
